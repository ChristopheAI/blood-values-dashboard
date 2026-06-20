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

    private const DRAFT_CONFIDENCE_CAP = self::AUTO_CONFIRM_CONFIDENCE_THRESHOLD - 0.01;

    public function __construct(private readonly ExtractBiomarkerDrafts $extractBiomarkerDrafts) {}

    /**
     * @param  (callable(string, string): void)|null  $progress
     */
    public function __invoke(BloodTestDocument $document, ?callable $progress = null): ExtractionRun
    {
        $bloodTest = $document->bloodTest;
        $run = $bloodTest->extractionRuns()->create([
            'engine' => self::ENGINE,
            'status' => 'pending',
            'candidate_count' => 0,
        ]);
        $candidateCount = 0;
        $currentStage = 'extract';
        $extractionSucceeded = false;

        try {
            $this->reportProgress($progress, 'extract', 'active');
            $path = Storage::disk($document->storage_disk)->path($document->storage_path);
            $candidates = ($this->extractBiomarkerDrafts)($path);
            $candidateCount = count($candidates);
            $this->reportProgress($progress, 'extract', 'done');

            $currentStage = 'values';
            $this->reportProgress($progress, 'values', 'active');
            DB::transaction(fn (): int => $this->storeDrafts($document, $candidates));
            $this->reportProgress($progress, 'values', 'done');

            $run->update([
                'status' => 'done',
                'candidate_count' => $candidateCount,
            ]);

            $extractionSucceeded = true;
        } catch (Throwable) {
            $this->reportProgress($progress, $currentStage, 'failed');

            $run->update([
                'status' => 'failed',
                'candidate_count' => 0,
            ]);
        }

        if ($extractionSucceeded) {
            $this->reportProgress($progress, 'status', 'active');
            $bloodTest->recalculateStatusFromResults();
            $this->reportProgress($progress, 'status', 'done');

            $this->reportProgress($progress, 'trend', 'active');
            $this->reportProgress($progress, 'trend', 'done');
        } else {
            $bloodTest->recalculateStatusFromResults();
        }

        return $run->refresh();
    }

    /**
     * @param  (callable(string, string): void)|null  $progress
     */
    private function reportProgress(?callable $progress, string $stage, string $state): void
    {
        if ($progress === null) {
            return;
        }

        $progress($stage, $state);
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

            $confidence = $this->effectiveConfidence($document, $candidate, $biomarker);
            $autoConfirm = $this->shouldAutoConfirm($document, $candidate, $biomarker, $confidence);
            $confirmedAt = $autoConfirm ? now() : null;
            $extractedName = $biomarker instanceof Biomarker ? $biomarker->name : $candidate->extractedName;
            $sourceSnippet = $this->sourceSnippet($candidate);
            $value = $this->normalizedNumber($candidate->value);
            $referenceMin = $this->normalizedNullableNumber($candidate->referenceMin);
            $referenceMax = $this->normalizedNullableNumber($candidate->referenceMax);

            $attributes = [
                'blood_test_id' => $bloodTest->id,
                'entry_source' => 'extracted',
            ];

            if ($biomarker instanceof Biomarker) {
                $attributes['biomarker_id'] = $biomarker->id;
            } else {
                $attributes['biomarker_id'] = null;
                $attributes['extracted_name'] = $extractedName;
                $attributes['value'] = $value;
                $attributes['unit'] = $candidate->unit;
                $attributes['reference_min'] = $referenceMin;
                $attributes['reference_max'] = $referenceMax;
                $attributes['reference_unit'] = $candidate->referenceUnit;
                $attributes['confirmed_at'] = null;
                $attributes['source_snippet'] = $sourceSnippet;
            }

            $values = [
                'biomarker_id' => $biomarker?->id,
                'extracted_name' => $extractedName,
                'value' => $value,
                'unit' => $candidate->unit,
                'reference_min' => $referenceMin,
                'reference_max' => $referenceMax,
                'reference_unit' => $candidate->referenceUnit,
                'status' => $autoConfirm ? $this->status($candidate, $value, $referenceMin, $referenceMax)->value : 'unknown',
                'entry_source' => 'extracted',
                'confirmed_at' => $confirmedAt,
                'extraction_confidence' => $confidence,
                'source_snippet' => $sourceSnippet,
            ];

            if (! $biomarker instanceof Biomarker) {
                BiomarkerResult::query()->create(array_merge($attributes, $values));
                $stored++;

                continue;
            }

            BiomarkerResult::query()->updateOrCreate($attributes, $values);

            $stored++;
        }

        return $stored;
    }

    private function sourceSnippet(ExtractedBiomarkerCandidate $candidate): string
    {
        $snippet = preg_replace('/\s+/u', ' ', $candidate->sourceSnippet) ?? $candidate->sourceSnippet;

        return Str::limit(trim($snippet), 500, '');
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

        if ($extractedName === $catalogName) {
            return true;
        }

        return preg_match('/^'.preg_quote($catalogName, '/').'[^\p{L}\p{N}]/u', $extractedName) === 1;
    }

    private function effectiveConfidence(
        BloodTestDocument $document,
        ExtractedBiomarkerCandidate $candidate,
        ?Biomarker $biomarker,
    ): float {
        if (! $this->canAutoConfirm($document, $candidate, $biomarker)) {
            return min($candidate->confidence, self::DRAFT_CONFIDENCE_CAP);
        }

        return $candidate->confidence;
    }

    private function shouldAutoConfirm(
        BloodTestDocument $document,
        ExtractedBiomarkerCandidate $candidate,
        ?Biomarker $biomarker,
        float $confidence,
    ): bool {
        return $confidence >= self::AUTO_CONFIRM_CONFIDENCE_THRESHOLD
            && $this->canAutoConfirm($document, $candidate, $biomarker);
    }

    private function canAutoConfirm(BloodTestDocument $document, ExtractedBiomarkerCandidate $candidate, ?Biomarker $biomarker): bool
    {
        return $biomarker instanceof Biomarker
            && $this->hasUnambiguousLiteralCatalogMatch($document, $candidate, $biomarker)
            && is_numeric($this->normalizedNumber($candidate->value))
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

    private function status(
        ExtractedBiomarkerCandidate $candidate,
        string $value,
        ?string $referenceMin,
        ?string $referenceMax,
    ): BiomarkerStatus {
        return (new DetermineBiomarkerStatus)(
            value: (float) $value,
            valueUnit: $candidate->unit,
            referenceMinimum: $referenceMin === null ? null : (float) $referenceMin,
            referenceMaximum: $referenceMax === null ? null : (float) $referenceMax,
            referenceUnit: $candidate->referenceUnit ?: $candidate->unit,
        );
    }

    private function normalizedNumber(string $number): string
    {
        return str_replace(',', '.', trim($number));
    }

    private function normalizedNullableNumber(?string $number): ?string
    {
        if ($number === null) {
            return null;
        }

        return $this->normalizedNumber($number);
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
