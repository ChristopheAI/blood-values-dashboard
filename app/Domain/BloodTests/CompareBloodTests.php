<?php

namespace App\Domain\BloodTests;

use App\Models\BloodTest;
use Illuminate\Support\Collection;

class CompareBloodTests
{
    public function __construct(private readonly BuildLongitudinalChanges $buildLongitudinalChanges) {}

    /**
     * @return Collection<int, array{
     *     biomarker: string,
     *     previous_value: string,
     *     current_value: string,
     *     previous_unit: string,
     *     current_unit: string,
     *     status: string,
     *     delta: string
     * }>
     */
    public function __invoke(BloodTest $first, BloodTest $second): Collection
    {
        return $this->buildLongitudinalChanges
            ->between($first->user, $first, $second)
            ->map(function (LongitudinalChange $change): array {
                return [
                    'biomarker' => $change->biomarker,
                    'previous_value' => $change->previousValue,
                    'current_value' => $change->currentValue,
                    'previous_unit' => $change->previousUnit,
                    'current_unit' => $change->currentUnit,
                    'status' => $change->status,
                    'delta' => $change->delta,
                ];
            })
            ->values();
    }
}
