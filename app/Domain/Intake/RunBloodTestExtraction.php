<?php

namespace App\Domain\Intake;

use App\Domain\Biomarkers\DetermineBiomarkerStatus;
use App\Enums\BiomarkerStatus;
use App\Models\Biomarker;
use App\Models\BiomarkerResult;
use App\Models\BloodTestDocument;
use App\Models\ExtractionRun;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class RunBloodTestExtraction
{
    private const ENGINE = 'smalot/pdfparser';

    private const AUTO_CONFIRM_CONFIDENCE_THRESHOLD = 0.85;

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
            $candidateCount = $this->storeDrafts($document, $candidates);

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

        $hasDrafts = $bloodTest->results()
            ->where('entry_source', 'extracted')
            ->whereNull('confirmed_at')
            ->exists();

        $bloodTest->update([
            'status' => $candidateCount > 0 && ! $hasDrafts ? 'confirmed' : 'reviewing',
        ]);

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

            $autoConfirm = $this->shouldAutoConfirm($candidate, $biomarker);
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
        $name = Str::lower($candidate->extractedName);

        return Biomarker::query()
            ->where('user_id', $document->bloodTest->user_id)
            ->get()
            ->filter(function (Biomarker $biomarker) use ($name): bool {
                return $this->isCatalogPrefix($name, $biomarker->name)
                    || ($biomarker->short_name !== null && $this->isCatalogPrefix($name, $biomarker->short_name));
            })
            ->sortByDesc(fn (Biomarker $biomarker): int => max(
                mb_strlen($biomarker->name),
                $biomarker->short_name === null ? 0 : mb_strlen($biomarker->short_name),
            ))
            ->first();
    }

    private function isCatalogPrefix(string $extractedName, string $catalogName): bool
    {
        $catalogName = Str::lower(trim($catalogName));

        return $extractedName === $catalogName || str_starts_with($extractedName, $catalogName.' ');
    }

    private function shouldAutoConfirm(ExtractedBiomarkerCandidate $candidate, ?Biomarker $biomarker): bool
    {
        return $biomarker instanceof Biomarker
            && $candidate->confidence >= self::AUTO_CONFIRM_CONFIDENCE_THRESHOLD
            && is_numeric($candidate->value)
            && trim($candidate->unit) !== ''
            && ($candidate->referenceMin !== null || $candidate->referenceMax !== null);
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
