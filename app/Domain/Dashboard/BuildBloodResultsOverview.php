<?php

declare(strict_types=1);

namespace App\Domain\Dashboard;

use App\Domain\Biomarkers\DetectionLimitValue;
use App\Domain\Biomarkers\DetermineBiomarkerStatus;
use App\Enums\BiomarkerStatus;
use App\Models\BiomarkerResult;
use App\Models\User;
use App\Support\Format;
use Illuminate\Support\Collection;

final class BuildBloodResultsOverview
{
    public function __construct(private readonly DetermineBiomarkerStatus $determineStatus) {}

    /**
     * One row per biomarker, holding that biomarker's most recent confirmed
     * measurement. Showing every historical value here (grouped only by status)
     * let a stale result sit next to the current one without a date, so a user
     * could read an old normal value as "resolved" — see the row() shape below.
     *
     * PHPStan's invariant Collection template cannot carry the array shape here,
     * so the value type stays mixed, matching BuildLatestUploadSummary.
     *
     * @return Collection<int, mixed>
     */
    public function __invoke(User $user): Collection
    {
        return BiomarkerResult::query()
            ->confirmedForUser($user->id)
            ->with(['biomarker', 'bloodTest'])
            ->get()
            ->groupBy('biomarker_id')
            ->map(fn (Collection $results): BiomarkerResult => $this->mostRecent($results))
            ->map(fn (BiomarkerResult $result): array => $this->row($result))
            ->sortBy('label', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();
    }

    /**
     * Pick the current measurement for a biomarker: latest by sample date, then
     * by confirmation time, then by id, so ties (same-day tests, second-precision
     * timestamps) resolve deterministically.
     *
     * @param  Collection<int, BiomarkerResult>  $results
     */
    private function mostRecent(Collection $results): BiomarkerResult
    {
        return $results->sort(function (BiomarkerResult $a, BiomarkerResult $b): int {
            return [
                $b->bloodTest?->test_date?->getTimestamp() ?? 0,
                $b->confirmed_at?->getTimestamp() ?? 0,
                $b->id,
            ] <=> [
                $a->bloodTest?->test_date?->getTimestamp() ?? 0,
                $a->confirmed_at?->getTimestamp() ?? 0,
                $a->id,
            ];
        })->first();
    }

    /**
     * @return array{label: string, value: string, valueLabel: string, unit: string|null, status: string, ref_min: float|null, ref_max: float|null, reference: string, date: string|null, is_detection_limit: bool}
     */
    private function row(BiomarkerResult $result): array
    {
        $min = $result->reference_min !== null ? (float) $result->reference_min : null;
        $max = $result->reference_max !== null ? (float) $result->reference_max : null;

        $isDetectionLimit = DetectionLimitValue::fromStoredResult($result) instanceof DetectionLimitValue;
        $status = BiomarkerStatus::tryFrom((string) $result->status) ?? BiomarkerStatus::Unknown;

        // Only re-derive an unknown status for a plain numeric value. A
        // detection-limit value ('<40') keeps the status computed at confirm
        // time via forDetectionLimit — the plain numeric path here would treat
        // the bound as an exact measurement and guess the wrong group. A
        // qualitative value ('Negatief') is not numeric and must never be
        // float-cast into a comparison.
        if (
            $status === BiomarkerStatus::Unknown
            && ! $isDetectionLimit
            && is_numeric($result->value)
            && ($min !== null || $max !== null)
        ) {
            $status = ($this->determineStatus)(
                (float) $result->value,
                $result->unit,
                $min,
                $max,
                $result->reference_unit,
            );
        }

        return [
            'label' => $result->biomarker->name,
            'value' => (string) $result->value,
            'valueLabel' => Format::biomarkerValue($result->value, $result->source_snippet),
            'unit' => $result->unit,
            'status' => $status->value,
            'ref_min' => $min,
            'ref_max' => $max,
            'reference' => $this->reference($min, $max, $result->unit),
            'date' => $result->bloodTest?->test_date !== null
                ? Format::dutchDate($result->bloodTest->test_date)
                : null,
            'is_detection_limit' => $isDetectionLimit,
        ];
    }

    private function reference(?float $min, ?float $max, ?string $unit): string
    {
        $suffix = $unit ? ' '.$unit : '';

        return match (true) {
            $min !== null && $max !== null => $min.' – '.$max.$suffix,
            $max !== null => '≤ '.$max.$suffix,
            $min !== null => '≥ '.$min.$suffix,
            default => '—',
        };
    }
}
