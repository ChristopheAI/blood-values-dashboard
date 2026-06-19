<?php

namespace App\Domain\Intake;

use App\Domain\Biomarkers\DetermineBiomarkerStatus;
use App\Enums\BiomarkerStatus;
use App\Models\Biomarker;
use App\Models\BiomarkerResult;
use App\Models\BloodTestDocument;
use App\Models\ExtractionRun;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class RunBloodTestExtraction
{
    private const ENGINE = 'smalot/pdfparser';

    public const AUTO_CONFIRM_CONFIDENCE_THRESHOLD = 0.85;

    public function __construct(private readonly ExtractBiomarkerDrafts $extractBiomarkerDrafts) {}

    public function __invoke(BloodTestDocument $document): ExtractionRun
    {
        $bloodTest = $document->bloodTest;
        $run = $bloodTest->extractionRuns()->create([
            'engine' => self::ENGINE,
            'status' => 'pending',
            'candidate_count' => 0,
        ]);
        $candidateCount = 0;

        try {
            $path = Storage::disk($document->storage_disk)->path($document->storage_path);
            $candidates = ($this->extractBiomarkerDrafts)($path);
            $candidateCount = count($candidates);
            DB::transaction(fn (): int => $this->storeDrafts($document, $candidates));

            $run->update([
                'status' => 'done',
                'candidate_count' => $candidateCount,
            ]);
        } catch (Throwable) {
            $run->update([
                'status' => 'failed',
                'candidate_count' => 0,
            ]);
        }

        $bloodTest->recalculateStatusFromResults();

        return $run->refresh();
    }

    /**
     * @param  list<ExtractedBiomarkerCandidate>  $candidates
     */
    private function storeDrafts(BloodTestDocument $document, array $candidates): int
    {
        $bloodTest = $document->bloodTest;
        $stored = 0;

        foreach ($candidates as $candidate) {
            $biomarker = $this->matchingBiomarker($document, $candidate);

            if ($biomarker instanceof Biomarker && $this->hasConfirmedValue($bloodTest->id, $biomarker->id)) {
                continue;
            }

            $autoConfirm = $this->shouldAutoConfirm($document, $candidate, $biomarker);
            $confirmedAt = $autoConfirm ? now() : null;
            $extractedName = $biomarker instanceof Biomarker ? $biomarker->name : $candidate->extractedName;

            $attributes = [
                'blood_test_id' => $bloodTest->id,
                'entry_source' => 'extracted',
            ];

            if ($biomarker instanceof Biomarker) {
                $attributes['biomarker_id'] = $biomarker->id;
            } else {
                $attributes['biomarker_id'] = null;
                $attributes['extracted_name'] = $extractedName;
                $attributes['confirmed_at'] = null;
            }

            BiomarkerResult::query()->updateOrCreate(
                $attributes,
                [
                    'biomarker_id' => $biomarker?->id,
                    'extracted_name' => $extractedName,
                    'value' => $candidate->value,
                    'unit' => $candidate->unit,
                    'reference_min' => $candidate->referenceMin,
                    'reference_max' => $candidate->referenceMax,
                    'reference_unit' => $candidate->referenceUnit,
                    'status' => $autoConfirm ? $this->status($candidate)->value : 'unknown',
                    'entry_source' => 'extracted',
                    'confirmed_at' => $confirmedAt,
                    'extraction_confidence' => $candidate->confidence,
                    'source_snippet' => Str::limit($candidate->sourceSnippet, 500, ''),
                ],
            );

            $stored++;
        }

        return $stored;
    }

    private function matchingBiomarker(BloodTestDocument $document, ExtractedBiomarkerCandidate $candidate): ?Biomarker
    {
        $name = $this->normalizedName($candidate->extractedName);
        $biomarkers = Biomarker::query()
            ->where('user_id', $document->bloodTest->user_id)
            ->get();

        $literalMatches = $biomarkers
            ->filter(function (Biomarker $biomarker) use ($name): bool {
                return $this->normalizedName($biomarker->name) === $name
                    || ($biomarker->short_name !== null && $this->normalizedName($biomarker->short_name) === $name);
            });

        if ($literalMatches->count() > 1) {
            return null;
        }

        $strongestMatches = [];
        $strongestLength = 0;

        foreach ($biomarkers as $biomarker) {
            $prefixLength = $this->catalogPrefixLength($name, $biomarker);

            if ($prefixLength === 0) {
                continue;
            }

            if ($prefixLength > $strongestLength) {
                $strongestMatches = [$biomarker];
                $strongestLength = $prefixLength;

                continue;
            }

            if ($prefixLength === $strongestLength) {
                $strongestMatches[] = $biomarker;
            }
        }

        return count($strongestMatches) === 1 ? $strongestMatches[0] : null;
    }

    private function catalogPrefixLength(string $extractedName, Biomarker $biomarker): int
    {
        $length = $this->prefixLength($extractedName, $biomarker->name);

        if ($biomarker->short_name !== null) {
            $length = max($length, $this->prefixLength($extractedName, $biomarker->short_name));
        }

        return $length;
    }

    private function prefixLength(string $extractedName, string $catalogName): int
    {
        $catalogName = $this->normalizedName($catalogName);

        if (! $this->isCatalogPrefix($extractedName, $catalogName)) {
            return 0;
        }

        return mb_strlen($catalogName);
    }

    private function isCatalogPrefix(string $extractedName, string $catalogName): bool
    {
        $catalogName = $this->normalizedName($catalogName);

        return $extractedName === $catalogName || str_starts_with($extractedName, $catalogName.' ');
    }

    private function shouldAutoConfirm(BloodTestDocument $document, ExtractedBiomarkerCandidate $candidate, ?Biomarker $biomarker): bool
    {
        return $biomarker instanceof Biomarker
            && $this->hasUnambiguousLiteralCatalogMatch($document, $candidate, $biomarker)
            && $candidate->confidence >= self::AUTO_CONFIRM_CONFIDENCE_THRESHOLD
            && is_numeric($candidate->value)
            && trim($candidate->unit) !== ''
            && ($candidate->referenceMin !== null || $candidate->referenceMax !== null);
    }

    private function hasUnambiguousLiteralCatalogMatch(
        BloodTestDocument $document,
        ExtractedBiomarkerCandidate $candidate,
        Biomarker $matchedBiomarker,
    ): bool {
        $name = $this->normalizedName($candidate->extractedName);
        $literalMatches = Biomarker::query()
            ->where('user_id', $document->bloodTest->user_id)
            ->get()
            ->filter(function (Biomarker $biomarker) use ($name): bool {
                return $this->normalizedName($biomarker->name) === $name
                    || ($biomarker->short_name !== null && $this->normalizedName($biomarker->short_name) === $name);
            });

        return $literalMatches->count() === 1
            && $literalMatches->first()?->is($matchedBiomarker);
    }

    private function normalizedName(string $name): string
    {
        return Str::lower(trim($name));
    }

    private function status(ExtractedBiomarkerCandidate $candidate): BiomarkerStatus
    {
        return (new DetermineBiomarkerStatus)(
            value: (float) $candidate->value,
            valueUnit: $candidate->unit,
            referenceMinimum: $candidate->referenceMin === null ? null : (float) $candidate->referenceMin,
            referenceMaximum: $candidate->referenceMax === null ? null : (float) $candidate->referenceMax,
            referenceUnit: $candidate->referenceUnit ?: $candidate->unit,
        );
    }

    private function hasConfirmedValue(int $bloodTestId, int $biomarkerId): bool
    {
        return BiomarkerResult::query()
            ->where('blood_test_id', $bloodTestId)
            ->where('biomarker_id', $biomarkerId)
            ->whereNotNull('confirmed_at')
            ->exists();
    }
}
