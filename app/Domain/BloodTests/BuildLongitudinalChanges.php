<?php

namespace App\Domain\BloodTests;

use App\Models\BiomarkerResult;
use App\Models\BloodTest;
use App\Models\User;
use App\Domain\Biomarkers\DetectionLimitValue;
use App\Domain\Biomarkers\QualitativeLabValue;
use App\Support\Format;
use Illuminate\Support\Collection;

class BuildLongitudinalChanges
{
    /**
     * @param  Collection<int, BloodTest>  $bloodTests
     * @return Collection<int, LongitudinalChange>
     */
    public function across(User $user, Collection $bloodTests): Collection
    {
        $bloodTestIds = $this->ownedBloodTestIds($user, $bloodTests);

        if ($bloodTestIds === []) {
            return collect();
        }

        $changes = [];

        foreach ($this->confirmedResults($user, $bloodTestIds)->groupBy('biomarker_id') as $results) {
            $previous = null;

            foreach ($results as $result) {
                if ($previous instanceof BiomarkerResult) {
                    $changes[] = $this->row($previous, $result);
                }

                $previous = $result;
            }
        }

        return collect($changes)->values();
    }

    /**
     * @return Collection<int, LongitudinalChange>
     */
    public function between(User $user, BloodTest $previousBloodTest, BloodTest $currentBloodTest): Collection
    {
        if ((int) $previousBloodTest->user_id !== $user->id || (int) $currentBloodTest->user_id !== $user->id) {
            return collect();
        }

        $results = $this->confirmedResults($user, [
            (int) $previousBloodTest->id,
            (int) $currentBloodTest->id,
        ]);

        $previousResults = $results
            ->where('blood_test_id', $previousBloodTest->id)
            ->keyBy('biomarker_id');
        $currentResults = $results
            ->where('blood_test_id', $currentBloodTest->id)
            ->keyBy('biomarker_id');

        return $previousResults
            ->keys()
            ->merge($currentResults->keys())
            ->unique()
            ->map(function (int|string $biomarkerId) use ($previousResults, $currentResults): LongitudinalChange {
                $previous = $previousResults->get($biomarkerId);
                $current = $currentResults->get($biomarkerId);

                return $this->row(
                    $previous instanceof BiomarkerResult ? $previous : null,
                    $current instanceof BiomarkerResult ? $current : null,
                );
            })
            ->sortBy('biomarker')
            ->values();
    }

    /**
     * @param  Collection<int, BloodTest>  $bloodTests
     * @return list<int>
     */
    private function ownedBloodTestIds(User $user, Collection $bloodTests): array
    {
        $ids = $bloodTests
            ->filter(fn (BloodTest $bloodTest): bool => (int) $bloodTest->user_id === $user->id)
            ->unique(fn (BloodTest $bloodTest): int => (int) $bloodTest->id)
            ->sortBy(function (BloodTest $bloodTest): string {
                $date = $bloodTest->test_date?->toDateString() ?? '9999-12-31';

                return $date.'-'.str_pad((string) $bloodTest->id, 12, '0', STR_PAD_LEFT);
            })
            ->map(fn (BloodTest $bloodTest): int => (int) $bloodTest->id)
            ->values()
            ->all();

        return array_values($ids);
    }

    /**
     * @param  list<int>  $bloodTestIds
     * @return Collection<int, BiomarkerResult>
     */
    private function confirmedResults(User $user, array $bloodTestIds): Collection
    {
        if ($bloodTestIds === []) {
            return collect();
        }

        $bloodTestPositionById = array_flip($bloodTestIds);

        return BiomarkerResult::query()
            ->confirmedForUser($user->id)
            ->whereIn('biomarker_results.blood_test_id', array_values(array_unique($bloodTestIds)))
            ->with(['biomarker', 'bloodTest'])
            ->orderBy('biomarker_results.id')
            ->get()
            ->sortBy(function (BiomarkerResult $result) use ($bloodTestPositionById): string {
                $bloodTestPosition = $bloodTestPositionById[(int) $result->blood_test_id] ?? PHP_INT_MAX;

                return str_pad((string) $bloodTestPosition, 12, '0', STR_PAD_LEFT)
                    .'-'.str_pad((string) $result->id, 12, '0', STR_PAD_LEFT);
            })
            ->values();
    }

    private function row(?BiomarkerResult $previous, ?BiomarkerResult $current): LongitudinalChange
    {
        $biomarker = 'Unknown biomarker';

        if ($current instanceof BiomarkerResult) {
            $biomarker = $current->biomarker->name;
        } elseif ($previous instanceof BiomarkerResult) {
            $biomarker = $previous->biomarker->name;
        }
        $previousUnit = $previous instanceof BiomarkerResult ? (string) $previous->unit : '';
        $currentUnit = $current instanceof BiomarkerResult ? (string) $current->unit : '';
        $previousValue = $previous instanceof BiomarkerResult
            ? $this->formatValue($previous)
            : 'not measured';
        $currentValue = $current instanceof BiomarkerResult
            ? $this->formatValue($current)
            : 'not measured';

        if (! $previous instanceof BiomarkerResult || ! $current instanceof BiomarkerResult) {
            return new LongitudinalChange(
                biomarker: $biomarker,
                result: $current,
                previousResult: $previous,
                previousValue: $previousValue,
                currentValue: $currentValue,
                previousUnit: $previousUnit,
                currentUnit: $currentUnit,
                status: 'not measured',
                delta: 'not measured',
                changeLabel: null,
                comparable: false,
                reason: $previous instanceof BiomarkerResult ? 'missing_current' : 'missing_previous',
                direction: 'unknown',
            );
        }

        $reason = $this->nonComparableReason($previous, $current);

        if ($reason !== null) {
            return new LongitudinalChange(
                biomarker: $biomarker,
                result: $current,
                previousResult: $previous,
                previousValue: $previousValue,
                currentValue: $currentValue,
                previousUnit: $previousUnit,
                currentUnit: $currentUnit,
                status: 'not comparable',
                delta: 'not comparable',
                changeLabel: null,
                comparable: false,
                reason: $reason,
                direction: 'unknown',
            );
        }

        $qualitativeComparison = $this->qualitativeComparison($previous, $current);

        if ($qualitativeComparison === 'mixed') {
            return $this->notComparableChange(
                biomarker: $biomarker,
                previous: $previous,
                current: $current,
                previousValue: $previousValue,
                currentValue: $currentValue,
                previousUnit: $previousUnit,
                currentUnit: $currentUnit,
                reason: 'qualitative_mismatch',
            );
        }

        if ($qualitativeComparison === 'same') {
            return $this->unchangedChange(
                biomarker: $biomarker,
                previous: $previous,
                current: $current,
                previousValue: $previousValue,
                currentValue: $currentValue,
                previousUnit: $previousUnit,
                currentUnit: $currentUnit,
            );
        }

        if ($qualitativeComparison === 'different') {
            return $this->notComparableChange(
                biomarker: $biomarker,
                previous: $previous,
                current: $current,
                previousValue: $previousValue,
                currentValue: $currentValue,
                previousUnit: $previousUnit,
                currentUnit: $currentUnit,
                reason: 'qualitative_change',
            );
        }

        $detectionComparison = $this->detectionLimitComparison($previous, $current);

        if ($detectionComparison === 'mixed') {
            return $this->notComparableChange(
                biomarker: $biomarker,
                previous: $previous,
                current: $current,
                previousValue: $previousValue,
                currentValue: $currentValue,
                previousUnit: $previousUnit,
                currentUnit: $currentUnit,
                reason: 'detection_limit_mismatch',
            );
        }

        if ($detectionComparison === 'same') {
            return $this->unchangedChange(
                biomarker: $biomarker,
                previous: $previous,
                current: $current,
                previousValue: $previousValue,
                currentValue: $currentValue,
                previousUnit: $previousUnit,
                currentUnit: $currentUnit,
            );
        }

        if ($detectionComparison === 'different') {
            return $this->notComparableChange(
                biomarker: $biomarker,
                previous: $previous,
                current: $current,
                previousValue: $previousValue,
                currentValue: $currentValue,
                previousUnit: $previousUnit,
                currentUnit: $currentUnit,
                reason: 'detection_limit_bounds',
            );
        }

        $difference = (float) $current->value - (float) $previous->value;
        $delta = ($difference > 0 ? '+' : '').Format::number($difference);

        return new LongitudinalChange(
            biomarker: $biomarker,
            result: $current,
            previousResult: $previous,
            previousValue: $previousValue,
            currentValue: $currentValue,
            previousUnit: $previousUnit,
            currentUnit: $currentUnit,
            status: $current->status,
            delta: $delta,
            changeLabel: $delta.' '.$currentUnit,
            comparable: true,
            reason: null,
            direction: $this->direction($difference),
        );
    }

    private function nonComparableReason(BiomarkerResult $previous, BiomarkerResult $current): ?string
    {
        $previousUnit = trim((string) $previous->unit);
        $currentUnit = trim((string) $current->unit);

        if ($previousUnit === '' || $currentUnit === '') {
            return 'missing_unit';
        }

        if ($previousUnit !== $currentUnit) {
            return 'unit_mismatch';
        }

        if (! is_numeric($previous->value) || ! is_numeric($current->value)) {
            return 'non_numeric';
        }

        return null;
    }

    private function formatValue(BiomarkerResult $result): string
    {
        $qualitative = QualitativeLabValue::fromResult($result);

        if ($qualitative instanceof QualitativeLabValue) {
            return $qualitative->storedValue();
        }

        if (! is_numeric($result->value)) {
            return trim((string) $result->value);
        }

        return Format::biomarkerValue($result->value, $result->source_snippet);
    }

    private function qualitativeComparison(BiomarkerResult $previous, BiomarkerResult $current): ?string
    {
        $previousQualitative = QualitativeLabValue::fromResult($previous);
        $currentQualitative = QualitativeLabValue::fromResult($current);

        if ($previousQualitative === null && $currentQualitative === null) {
            return null;
        }

        if ($previousQualitative === null || $currentQualitative === null) {
            return 'mixed';
        }

        if ($previousQualitative->token === $currentQualitative->token) {
            return 'same';
        }

        return 'different';
    }

    private function detectionLimitComparison(BiomarkerResult $previous, BiomarkerResult $current): ?string
    {
        $previousLimit = DetectionLimitValue::fromStoredResult($previous);
        $currentLimit = DetectionLimitValue::fromStoredResult($current);

        if ($previousLimit === null && $currentLimit === null) {
            return null;
        }

        if ($previousLimit === null || $currentLimit === null) {
            return 'mixed';
        }

        if ($previousLimit->boundForStatus() === $currentLimit->boundForStatus()
            && $previousLimit->numericForStatus() === $currentLimit->numericForStatus()) {
            return 'same';
        }

        return 'different';
    }

    private function unchangedChange(
        string $biomarker,
        BiomarkerResult $previous,
        BiomarkerResult $current,
        string $previousValue,
        string $currentValue,
        string $previousUnit,
        string $currentUnit,
    ): LongitudinalChange {
        return new LongitudinalChange(
            biomarker: $biomarker,
            result: $current,
            previousResult: $previous,
            previousValue: $previousValue,
            currentValue: $currentValue,
            previousUnit: $previousUnit,
            currentUnit: $currentUnit,
            status: $current->status,
            delta: 'unchanged',
            changeLabel: null,
            comparable: true,
            reason: null,
            direction: 'unchanged',
        );
    }

    private function notComparableChange(
        string $biomarker,
        BiomarkerResult $previous,
        BiomarkerResult $current,
        string $previousValue,
        string $currentValue,
        string $previousUnit,
        string $currentUnit,
        string $reason,
    ): LongitudinalChange {
        return new LongitudinalChange(
            biomarker: $biomarker,
            result: $current,
            previousResult: $previous,
            previousValue: $previousValue,
            currentValue: $currentValue,
            previousUnit: $previousUnit,
            currentUnit: $currentUnit,
            status: 'not comparable',
            delta: 'not comparable',
            changeLabel: null,
            comparable: false,
            reason: $reason,
            direction: 'unknown',
        );
    }

    private function direction(float $difference): string
    {
        if ($difference > 0) {
            return 'higher';
        }

        if ($difference < 0) {
            return 'lower';
        }

        return 'unchanged';
    }
}
