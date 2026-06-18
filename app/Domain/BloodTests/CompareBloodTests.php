<?php

namespace App\Domain\BloodTests;

use App\Models\BiomarkerResult;
use App\Models\BloodTest;
use Illuminate\Support\Collection;

class CompareBloodTests
{
    /**
     * @return Collection<int, array{
     *     biomarker: string,
     *     previous_value: string,
     *     current_value: string,
     *     unit: string,
     *     status: string,
     *     delta: string
     * }>
     */
    public function __invoke(BloodTest $first, BloodTest $second): Collection
    {
        $firstResults = $this->confirmedResultsByBiomarker($first);
        $secondResults = $this->confirmedResultsByBiomarker($second);

        return collect(array_keys($firstResults))
            ->merge(array_keys($secondResults))
            ->unique()
            ->map(function (int|string $biomarkerId) use ($firstResults, $secondResults): array {
                $previous = $firstResults[(int) $biomarkerId] ?? null;
                $current = $secondResults[(int) $biomarkerId] ?? null;

                $status = 'not measured';
                $delta = 'not measured';

                if ($previous === null && $current === null) {
                    return [
                        'biomarker' => 'Unknown biomarker',
                        'previous_value' => 'not measured',
                        'current_value' => 'not measured',
                        'unit' => '',
                        'status' => $status,
                        'delta' => $delta,
                    ];
                }

                if ($previous === null) {
                    return [
                        'biomarker' => $current->biomarker->name,
                        'previous_value' => 'not measured',
                        'current_value' => $this->formatValue($current->value),
                        'unit' => $current->unit,
                        'status' => $status,
                        'delta' => $delta,
                    ];
                }

                if ($current === null) {
                    return [
                        'biomarker' => $previous->biomarker->name,
                        'previous_value' => $this->formatValue($previous->value),
                        'current_value' => 'not measured',
                        'unit' => $previous->unit,
                        'status' => $status,
                        'delta' => $delta,
                    ];
                }

                if ($previous->unit === $current->unit) {
                    $difference = (float) $current->value - (float) $previous->value;
                    $delta = ($difference > 0 ? '+' : '').rtrim(rtrim(number_format($difference, 4, '.', ''), '0'), '.');
                    $status = $current->status;
                } else {
                    $status = 'not comparable';
                    $delta = 'not comparable';
                }

                return [
                    'biomarker' => $current->biomarker->name,
                    'previous_value' => $this->formatValue($previous->value),
                    'current_value' => $this->formatValue($current->value),
                    'unit' => $current->unit,
                    'status' => $status,
                    'delta' => $delta,
                ];
            })
            ->sortBy('biomarker')
            ->values();
    }

    private function formatValue(mixed $value): string
    {
        return rtrim(rtrim(number_format((float) $value, 4, '.', ''), '0'), '.');
    }

    /**
     * @return array<int, BiomarkerResult>
     */
    private function confirmedResultsByBiomarker(BloodTest $bloodTest): array
    {
        $results = [];

        foreach ($bloodTest->confirmedResults()->with('biomarker')->get() as $result) {
            $results[$result->biomarker_id] = $result;
        }

        return $results;
    }
}
