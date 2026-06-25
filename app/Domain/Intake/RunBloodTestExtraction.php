<?php

namespace App\Domain\Intake;

use App\Domain\Biomarkers\DetermineBiomarkerStatus;
use App\Enums\BiomarkerStatus;
use App\Models\Biomarker;
use App\Models\BiomarkerResult;
use App\Models\BloodTestDocument;
use App\Models\ExtractionRun;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class RunBloodTestExtraction
{
    private const ENGINE = 'smalot/pdfparser';

    public const AUTO_CONFIRM_CONFIDENCE_THRESHOLD = 0.85;

    private const DRAFT_CONFIDENCE_CAP = self::AUTO_CONFIRM_CONFIDENCE_THRESHOLD - 0.01;

    /**
     * @var array<int, Collection<int, Biomarker>>
     */
    private array $biomarkersByUserId = [];

    /**
     * @var array<int, array<int, true>>
     */
    private array $confirmedBiomarkerIdsByBloodTestId = [];

    /**
     * Biomarkers created during the current run via trusted auto-import. They must
     * not act as catalog *prefix* anchors for later, genuinely different candidates,
     * which would silently fold a distinct analyte into the wrong biomarker.
     *
     * @var array<int, true>
     */
    private array $biomarkerIdsCreatedThisRun = [];

    /**
     * Biomarkers already written as a result row during the current run. A second
     * candidate resolving to one of these is kept as an unanchored draft rather than
     * overwriting the first row via updateOrCreate (the same protection that
     * duplicateMatchedBiomarkerIds gives pre-existing catalog biomarkers).
     *
     * @var array<int, true>
     */
    private array $writtenBiomarkerIdsThisRun = [];

    public function __construct(private readonly ExtractBiomarkerDrafts $extractBiomarkerDrafts) {}

    /**
     * @param  (callable(string, string): void)|null  $progress
     */
    public function __invoke(BloodTestDocument $document, ?callable $progress = null): ExtractionRun
    {
        $this->biomarkersByUserId = [];
        $this->confirmedBiomarkerIdsByBloodTestId = [];
        $this->biomarkerIdsCreatedThisRun = [];
        $this->writtenBiomarkerIdsThisRun = [];

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
        $candidates = $this->disambiguateTrustedCmaDuplicateNamesByUnit($candidates);
        $bloodTest = $document->bloodTest;
        $duplicateMatchedBiomarkerIds = $this->duplicateMatchedBiomarkerIds($document, $candidates);
        $duplicateTrustedAutoImportNames = $this->duplicateTrustedAutoImportNames($candidates);
        $stored = 0;

        foreach ($candidates as $candidate) {
            $value = $this->normalizedNumber($candidate->value);

            if (! is_numeric($value)) {
                continue;
            }

            if ($this->shouldDiscardNonActionableTrustedCmaCandidate($candidate)) {
                continue;
            }

            $matchedBiomarker = $this->matchingBiomarker($document, $candidate);
            $biomarker = $matchedBiomarker;

            if ($matchedBiomarker instanceof Biomarker && $this->hasConfirmedValue($bloodTest->id, $matchedBiomarker->id)) {
                continue;
            }

            if ($matchedBiomarker instanceof Biomarker && isset($duplicateMatchedBiomarkerIds[$matchedBiomarker->id])) {
                $biomarker = null;
            }

            // A second candidate that resolves to a biomarker already written in this
            // same run is kept as an unanchored draft, so it never overwrites the first
            // candidate's row (a biomarker auto-imported earlier in the loop is invisible
            // to the pre-loop duplicate scan).
            if ($matchedBiomarker instanceof Biomarker && isset($this->writtenBiomarkerIdsThisRun[$matchedBiomarker->id])) {
                $biomarker = null;
            }

            if (! $matchedBiomarker instanceof Biomarker) {
                $biomarker = $this->trustedAutoImportBiomarker(
                    $document,
                    $candidate,
                    $duplicateTrustedAutoImportNames,
                );
            }

            $confidence = $this->effectiveConfidence($document, $candidate, $biomarker);
            $autoConfirm = $this->shouldAutoConfirm($document, $candidate, $biomarker, $confidence);
            $confirmedAt = $autoConfirm ? now() : null;
            $extractedName = $biomarker instanceof Biomarker ? $biomarker->name : $candidate->extractedName;
            $sourceSnippet = $this->sourceSnippet($candidate);
            $referenceMin = $this->normalizedNullableNumber($candidate->referenceMin);
            $referenceMax = $this->normalizedNullableNumber($candidate->referenceMax);
            $unit = $this->normalizedUnit($candidate->unit);
            $referenceUnit = $this->normalizedNullableUnit($candidate->referenceUnit);

            $attributes = [
                'blood_test_id' => $bloodTest->id,
                'blood_test_document_id' => $document->id,
                'entry_source' => 'extracted',
            ];

            if ($biomarker instanceof Biomarker) {
                $attributes['biomarker_id'] = $biomarker->id;
            } else {
                $attributes['biomarker_id'] = null;
                $attributes['extracted_name'] = $extractedName;
                $attributes['value'] = $value;
                $attributes['unit'] = $unit;
                $attributes['reference_min'] = $referenceMin;
                $attributes['reference_max'] = $referenceMax;
                $attributes['reference_unit'] = $referenceUnit;
                $attributes['confirmed_at'] = null;
                $attributes['source_snippet'] = $sourceSnippet;
            }

            $values = [
                'blood_test_document_id' => $document->id,
                'biomarker_id' => $biomarker?->id,
                'extracted_name' => $extractedName,
                'value' => $value,
                'unit' => $unit,
                'reference_min' => $referenceMin,
                'reference_max' => $referenceMax,
                'reference_unit' => $referenceUnit,
                'status' => $autoConfirm ? $this->status($unit, $referenceUnit, $value, $referenceMin, $referenceMax)->value : 'unknown',
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
            $this->writtenBiomarkerIdsThisRun[$biomarker->id] = true;

            if ($autoConfirm) {
                $this->rememberConfirmedBiomarkerValue($bloodTest->id, $biomarker->id);
            }

            $stored++;
        }

        return $stored;
    }

    /**
     * @param  list<ExtractedBiomarkerCandidate>  $candidates
     * @return list<ExtractedBiomarkerCandidate>
     */
    private function disambiguateTrustedCmaDuplicateNamesByUnit(array $candidates): array
    {
        $duplicateIndexesByName = [];

        foreach ($candidates as $index => $candidate) {
            if (! $this->isTrustedCmaSource($candidate)) {
                continue;
            }

            if (! is_numeric($this->normalizedNumber($candidate->value))) {
                continue;
            }

            $name = $this->normalizedName($candidate->extractedName);

            if ($name === '') {
                continue;
            }

            $duplicateIndexesByName[$name][] = $index;
        }

        foreach ($duplicateIndexesByName as $indexes) {
            if (count($indexes) < 2) {
                continue;
            }

            $units = [];

            foreach ($indexes as $index) {
                $unit = $this->normalizedUnit($candidates[$index]->unit);

                if ($unit === '' || isset($units[$unit])) {
                    continue 2;
                }

                $units[$unit] = true;
            }

            foreach ($indexes as $index) {
                $candidate = $candidates[$index];
                $unit = $this->normalizedUnit($candidate->unit);
                $name = $this->unitDisambiguatedName($candidate->extractedName, $unit);

                $candidates[$index] = new ExtractedBiomarkerCandidate(
                    extractedName: $name,
                    value: $candidate->value,
                    unit: $candidate->unit,
                    referenceMin: $candidate->referenceMin,
                    referenceMax: $candidate->referenceMax,
                    referenceUnit: $candidate->referenceUnit,
                    confidence: $candidate->confidence,
                    sourceSnippet: $candidate->sourceSnippet,
                    source: $candidate->source,
                );
            }
        }

        return array_values($candidates);
    }

    private function unitDisambiguatedName(string $name, string $unit): string
    {
        $name = $this->canonicalExtractedName($name);
        $suffix = ' ('.$unit.')';

        return str_ends_with($name, $suffix) ? $name : $name.$suffix;
    }

    /**
     * @param  list<ExtractedBiomarkerCandidate>  $candidates
     * @return array<int, true>
     */
    private function duplicateMatchedBiomarkerIds(BloodTestDocument $document, array $candidates): array
    {
        $matchedCounts = [];

        foreach ($candidates as $candidate) {
            if (! is_numeric($this->normalizedNumber($candidate->value))) {
                continue;
            }

            $biomarker = $this->matchingBiomarker($document, $candidate);

            if (! $biomarker instanceof Biomarker) {
                continue;
            }

            $matchedCounts[$biomarker->id] = ($matchedCounts[$biomarker->id] ?? 0) + 1;
        }

        $duplicates = [];

        foreach ($matchedCounts as $biomarkerId => $count) {
            if ($count > 1) {
                $duplicates[(int) $biomarkerId] = true;
            }
        }

        return $duplicates;
    }

    /**
     * @param  list<ExtractedBiomarkerCandidate>  $candidates
     * @return array<string, true>
     */
    private function duplicateTrustedAutoImportNames(array $candidates): array
    {
        $nameCounts = [];

        foreach ($candidates as $candidate) {
            if (! $this->isTrustedCmaSource($candidate)) {
                continue;
            }

            if (! is_numeric($this->normalizedNumber($candidate->value))) {
                continue;
            }

            $name = $this->normalizedName($candidate->extractedName);

            if ($name === '') {
                continue;
            }

            $nameCounts[$name] = ($nameCounts[$name] ?? 0) + 1;
        }

        $duplicates = [];

        foreach ($nameCounts as $name => $count) {
            if ($count > 1) {
                $duplicates[$name] = true;
            }
        }

        return $duplicates;
    }

    private function shouldDiscardNonActionableTrustedCmaCandidate(ExtractedBiomarkerCandidate $candidate): bool
    {
        return $this->isTrustedCmaSource($candidate)
            && $this->normalizedUnit($candidate->unit) === ''
            && $candidate->referenceMin === null
            && $candidate->referenceMax === null;
    }

    private function sourceSnippet(ExtractedBiomarkerCandidate $candidate): string
    {
        $snippet = preg_replace('/\s+/u', ' ', $candidate->sourceSnippet) ?? $candidate->sourceSnippet;

        return Str::limit(trim($snippet), 500, '');
    }

    private function matchingBiomarker(BloodTestDocument $document, ExtractedBiomarkerCandidate $candidate): ?Biomarker
    {
        $name = $this->normalizedName($candidate->extractedName);
        $biomarkers = $this->biomarkersFor($document);

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
            // A biomarker auto-imported earlier in this same run must not act as a
            // prefix anchor for a later, genuinely different candidate; that would
            // fold a distinct analyte into the wrong biomarker. A literal exact match
            // (handled above) against such a biomarker is still allowed.
            if (isset($this->biomarkerIdsCreatedThisRun[$biomarker->id])) {
                continue;
            }

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

    /**
     * @param  array<string, true>  $duplicateTrustedAutoImportNames
     */
    private function trustedAutoImportBiomarker(
        BloodTestDocument $document,
        ExtractedBiomarkerCandidate $candidate,
        array $duplicateTrustedAutoImportNames,
    ): ?Biomarker {
        $name = $this->canonicalExtractedName($candidate->extractedName);
        $normalizedName = $this->normalizedName($name);

        if (isset($duplicateTrustedAutoImportNames[$normalizedName])) {
            return null;
        }

        if (! $this->canCreateTrustedBiomarker($document, $candidate, $name)) {
            return null;
        }

        $unit = $this->normalizedUnit($candidate->unit);
        $referenceUnit = $this->normalizedNullableUnit($candidate->referenceUnit) ?? $unit;

        $biomarker = Biomarker::query()->create([
            'user_id' => $document->bloodTest->user_id,
            'name' => $name,
            'default_unit' => $unit,
            'reference_min' => $this->normalizedNullableNumber($candidate->referenceMin),
            'reference_max' => $this->normalizedNullableNumber($candidate->referenceMax),
            'reference_unit' => $referenceUnit,
            'active' => true,
        ]);

        $this->rememberBiomarker($biomarker);
        $this->biomarkerIdsCreatedThisRun[$biomarker->id] = true;

        return $biomarker;
    }

    private function canCreateTrustedBiomarker(
        BloodTestDocument $document,
        ExtractedBiomarkerCandidate $candidate,
        string $name,
    ): bool {
        return $this->isTrustedCmaSource($candidate)
            && $candidate->confidence >= self::AUTO_CONFIRM_CONFIDENCE_THRESHOLD
            && $name !== ''
            && $this->containsLetter($name)
            && is_numeric($this->normalizedNumber($candidate->value))
            && $this->normalizedUnit($candidate->unit) !== ''
            && $this->hasParseableReferenceEvidence($candidate)
            && $this->hasCompatibleReferenceUnit($candidate)
            && ! $this->hasPotentialCatalogMatch($document, $candidate);
    }

    private function isTrustedCmaSource(ExtractedBiomarkerCandidate $candidate): bool
    {
        return $candidate->source === ExtractedBiomarkerCandidate::SOURCE_CMA_LAYOUT
            || $candidate->source === ExtractedBiomarkerCandidate::SOURCE_CMA_TABULAR;
    }

    private function hasPotentialCatalogMatch(BloodTestDocument $document, ExtractedBiomarkerCandidate $candidate): bool
    {
        $name = $this->normalizedName($candidate->extractedName);

        return $this->biomarkersFor($document)
            ->contains(function (Biomarker $biomarker) use ($candidate, $name): bool {
                $catalogName = $this->normalizedName($biomarker->name);
                $shortName = $biomarker->short_name === null ? null : $this->normalizedName($biomarker->short_name);

                if ($catalogName === $name || $shortName === $name) {
                    return true;
                }

                if (
                    $this->isCatalogPrefix($name, $catalogName)
                    || ($shortName !== null && $this->isCatalogPrefix($name, $shortName))
                ) {
                    return true;
                }

                if (
                    $this->isCatalogPrefix($catalogName, $name)
                    || ($shortName !== null && $this->isCatalogPrefix($shortName, $name))
                ) {
                    return ! $this->canCreateTrustedPrefixSibling($candidate, $name, $biomarker);
                }

                return false;
            });
    }

    private function canCreateTrustedPrefixSibling(
        ExtractedBiomarkerCandidate $candidate,
        string $name,
        Biomarker $biomarker,
    ): bool {
        $unit = $this->normalizedUnit($candidate->unit);

        if ($unit === '') {
            return false;
        }

        $catalogUnits = $this->catalogUnits($biomarker);

        if ($catalogUnits === []) {
            return false;
        }

        if (! in_array($unit, $catalogUnits, true)) {
            return true;
        }

        return $this->isCuratedSameUnitPrefixSibling($name, $biomarker);
    }

    /**
     * @return list<string>
     */
    private function catalogUnits(Biomarker $biomarker): array
    {
        $units = [];

        foreach ([$biomarker->default_unit, $biomarker->reference_unit] as $unit) {
            if ($unit === null) {
                continue;
            }

            $unit = $this->normalizedUnit($unit);

            if ($unit !== '') {
                $units[$unit] = true;
            }
        }

        return array_keys($units);
    }

    private function isCuratedSameUnitPrefixSibling(string $name, Biomarker $biomarker): bool
    {
        if ($name !== 'crp') {
            return false;
        }

        foreach ([$biomarker->name, $biomarker->short_name] as $catalogName) {
            if ($catalogName === null) {
                continue;
            }

            $catalogName = $this->normalizedName($catalogName);

            if (preg_match('/^crp\s+(hooggevoelig|high[- ]sensitivity|sensitive|cardio|cardiac|ultra[- ]sensitive)/u', $catalogName) === 1) {
                return true;
            }
        }

        return false;
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
            && $this->normalizedUnit($candidate->unit) !== ''
            && $this->hasParseableReferenceEvidence($candidate)
            && $this->hasCompatibleReferenceUnit($candidate)
            && $this->hasUnambiguousNumericFormat($candidate)
            && $this->autoConfirmYieldsConclusiveStatus($candidate);
    }

    /**
     * A single dotted group of exactly three digits ("1.234") is ambiguous between a
     * decimal value (1.234) and a European thousands separator (1234), which the
     * parser would silently resolve to the fractional reading. Rather than lock in a
     * possibly wrong value, keep such rows as a review draft so a human decides.
     */
    private function hasUnambiguousNumericFormat(ExtractedBiomarkerCandidate $candidate): bool
    {
        foreach ([$candidate->value, $candidate->referenceMin, $candidate->referenceMax] as $raw) {
            if ($raw === null) {
                continue;
            }

            $trimmed = trim(preg_replace('/\s+/u', '', $raw) ?? $raw);

            if (preg_match('/^[+-]?\d{1,3}\.\d{3}$/', $trimmed) === 1) {
                return false;
            }
        }

        return true;
    }

    /**
     * Only auto-confirm values the system can actually classify. Without a usable
     * reference range the status resolves to "unknown"; locking such a value in
     * would bypass the human review the confidence gate exists to guarantee, so we
     * route it to manual review (kept as an unconfirmed draft) instead.
     */
    private function autoConfirmYieldsConclusiveStatus(ExtractedBiomarkerCandidate $candidate): bool
    {
        return $this->status(
            $this->normalizedUnit($candidate->unit),
            $this->normalizedNullableUnit($candidate->referenceUnit),
            $this->normalizedNumber($candidate->value),
            $this->normalizedNullableNumber($candidate->referenceMin),
            $this->normalizedNullableNumber($candidate->referenceMax),
        ) !== BiomarkerStatus::Unknown;
    }

    private function hasParseableReferenceEvidence(ExtractedBiomarkerCandidate $candidate): bool
    {
        if ($candidate->referenceMin === null && $candidate->referenceMax === null) {
            return $this->isTrustedCmaSource($candidate);
        }

        return $this->hasParseableReferenceBounds($candidate);
    }

    private function hasParseableReferenceBounds(ExtractedBiomarkerCandidate $candidate): bool
    {
        $referenceMin = $this->normalizedNullableNumber($candidate->referenceMin);
        $referenceMax = $this->normalizedNullableNumber($candidate->referenceMax);

        return ($candidate->referenceMin === null || $referenceMin !== null)
            && ($candidate->referenceMax === null || $referenceMax !== null)
            && ($referenceMin !== null || $referenceMax !== null)
            && ($referenceMin === null || $referenceMax === null || (float) $referenceMin <= (float) $referenceMax);
    }

    private function hasCompatibleReferenceUnit(ExtractedBiomarkerCandidate $candidate): bool
    {
        $referenceUnit = $this->normalizedNullableUnit($candidate->referenceUnit);

        return $referenceUnit === null || $referenceUnit === $this->normalizedUnit($candidate->unit);
    }

    private function hasUnambiguousLiteralCatalogMatch(
        BloodTestDocument $document,
        ExtractedBiomarkerCandidate $candidate,
        Biomarker $matchedBiomarker,
    ): bool {
        $name = $this->normalizedName($candidate->extractedName);
        $literalMatches = $this->biomarkersFor($document)
            ->filter(function (Biomarker $biomarker) use ($name): bool {
                return $this->normalizedName($biomarker->name) === $name
                    || ($biomarker->short_name !== null && $this->normalizedName($biomarker->short_name) === $name);
            });

        return $literalMatches->count() === 1
            && $literalMatches->first()?->is($matchedBiomarker);
    }

    /**
     * @return Collection<int, Biomarker>
     */
    private function biomarkersFor(BloodTestDocument $document): Collection
    {
        $userId = $document->bloodTest->user_id;

        return $this->biomarkersByUserId[$userId] ??= Biomarker::query()
            ->where('user_id', $userId)
            ->get();
    }

    private function rememberBiomarker(Biomarker $biomarker): void
    {
        if (! array_key_exists($biomarker->user_id, $this->biomarkersByUserId)) {
            return;
        }

        $this->biomarkersByUserId[$biomarker->user_id]->push($biomarker);
    }

    private function normalizedName(string $name): string
    {
        $name = preg_replace('/\s+/u', ' ', trim($name)) ?? $name;

        return Str::lower(trim($name));
    }

    private function canonicalExtractedName(string $name): string
    {
        $name = preg_replace('/\s+/u', ' ', trim($name)) ?? $name;

        return trim($name);
    }

    private function containsLetter(string $text): bool
    {
        return preg_match('/\p{L}/u', $text) === 1;
    }

    private function status(string $unit, ?string $referenceUnit, string $value, ?string $referenceMin, ?string $referenceMax): BiomarkerStatus
    {
        return (new DetermineBiomarkerStatus)(
            value: (float) $value,
            valueUnit: $unit,
            referenceMinimum: $referenceMin === null ? null : (float) $referenceMin,
            referenceMaximum: $referenceMax === null ? null : (float) $referenceMax,
            referenceUnit: $referenceUnit ?: $unit,
        );
    }

    private function normalizedNumber(string $number): string
    {
        $number = preg_replace('/\s+/u', ' ', $number) ?? $number;
        $number = str_replace(',', '.', trim($number));

        if (preg_match('/^(?:<=|>=|≤|≥|<|>)(?<num>-?\d+(?:\.\d+)?)/u', $number, $match)) {
            return $match['num'];
        }

        return $number;
    }

    private function normalizedNullableNumber(?string $number): ?string
    {
        if ($number === null) {
            return null;
        }

        $number = $this->normalizedNumber($number);

        return is_numeric($number) ? $number : null;
    }

    private function normalizedUnit(string $unit): string
    {
        $unit = preg_replace('/\s+/u', ' ', $unit) ?? $unit;

        return trim($unit, " \t\n\r\0\x0B()[]{}.,;:");
    }

    private function normalizedNullableUnit(?string $unit): ?string
    {
        if ($unit === null) {
            return null;
        }

        $unit = $this->normalizedUnit($unit);

        return $unit === '' ? null : $unit;
    }

    private function hasConfirmedValue(int $bloodTestId, int $biomarkerId): bool
    {
        if (! array_key_exists($bloodTestId, $this->confirmedBiomarkerIdsByBloodTestId)) {
            $biomarkerIds = BiomarkerResult::query()
                ->where('blood_test_id', $bloodTestId)
                ->whereNotNull('biomarker_id')
                ->whereNotNull('confirmed_at')
                ->pluck('biomarker_id')
                ->all();

            $this->confirmedBiomarkerIdsByBloodTestId[$bloodTestId] = array_fill_keys(
                array_map('intval', $biomarkerIds),
                true,
            );
        }

        return isset($this->confirmedBiomarkerIdsByBloodTestId[$bloodTestId][$biomarkerId]);
    }

    private function rememberConfirmedBiomarkerValue(int $bloodTestId, int $biomarkerId): void
    {
        if (! array_key_exists($bloodTestId, $this->confirmedBiomarkerIdsByBloodTestId)) {
            return;
        }

        $this->confirmedBiomarkerIdsByBloodTestId[$bloodTestId][$biomarkerId] = true;
    }
}
