<?php

declare(strict_types=1);

namespace App\Domain\Dashboard;

use App\Domain\Biomarkers\DetectionLimitValue;
use App\Domain\Biomarkers\DetermineBiomarkerStatus;
use App\Domain\BloodTests\BuildLongitudinalChanges;
use App\Domain\BloodTests\LongitudinalChange;
use App\Enums\BiomarkerStatus;
use App\Models\BiomarkerResult;
use App\Models\User;
use App\Support\Format;
use App\Support\RangeBarScale;
use Illuminate\Support\Collection;

final class BuildBloodResultsOverview
{
    /** @var array<int, LongitudinalChange> */
    private array $changesByResultId = [];

    public function __construct(
        private readonly DetermineBiomarkerStatus $determineStatus,
        private readonly BuildLongitudinalChanges $buildLongitudinalChanges,
    ) {}

    /**
     * The full overview payload for the results page: rows grouped into the
     * page's reading sections plus the summary counts. Grouping and status
     * logic live here so the Blade view only renders.
     *
     * All confirmed results are loaded once: the deduped rows, the
     * previous-measurement history, and the measurement total are all derived
     * from that single load rather than re-querying per concern. The
     * 'measurements' count feeds the summary line that must match the
     * dashboard's 'Bevestigd' tile, or the tile appears to overcount on the
     * page it links to.
     *
     * @return array{attention: Collection<int, mixed>, normal: Collection<int, mixed>, unknown: Collection<int, mixed>, counts: array{biomarkers: int, measurements: int, low: int, high: int, normal: int, unknown: int}}
     */
    public function overview(User $user): array
    {
        $confirmed = $this->confirmedResults($user);
        $rows = $this->rowsFrom($confirmed);

        // One grouping pass feeds both the sections and the counts, so the
        // summary tiles cannot drift from the rows rendered under them.
        $byStatus = $rows->groupBy('status');
        $countFor = fn (BiomarkerStatus $status): int => $byStatus->get($status->value, collect())->count();

        return [
            // Filtered from $rows (not concatenated groups) to keep low and
            // high rows interleaved in one alphabetical sequence.
            'attention' => $rows
                ->filter(fn (array $row): bool => in_array($row['status'], [BiomarkerStatus::Low->value, BiomarkerStatus::High->value], true))
                ->values(),
            'normal' => $byStatus->get(BiomarkerStatus::Normal->value, collect())->values(),
            'unknown' => $byStatus->get(BiomarkerStatus::Unknown->value, collect())->values(),
            'counts' => [
                'biomarkers' => $rows->count(),
                'measurements' => $confirmed->count(),
                'low' => $countFor(BiomarkerStatus::Low),
                'high' => $countFor(BiomarkerStatus::High),
                'normal' => $countFor(BiomarkerStatus::Normal),
                'unknown' => $countFor(BiomarkerStatus::Unknown),
            ],
        ];
    }

    /**
     * The flat deduped rows — one per biomarker, holding its most recent
     * confirmed measurement — kept public for direct assertions in tests.
     * The page contract is overview() above; grouping or presentation changes
     * belong there or in rowsFrom()/row(), never here.
     *
     * PHPStan's invariant Collection template cannot carry the array shape here,
     * so the value type stays mixed, matching BuildLatestUploadSummary.
     *
     * @return Collection<int, mixed>
     */
    public function __invoke(User $user): Collection
    {
        return $this->rowsFrom($this->confirmedResults($user));
    }

    /**
     * @return Collection<int, BiomarkerResult>
     */
    private function confirmedResults(User $user): Collection
    {
        return BiomarkerResult::query()
            ->confirmedForUser($user->id)
            ->with(['biomarker', 'bloodTest'])
            ->get();
    }

    /**
     * @param  Collection<int, BiomarkerResult>  $confirmed
     * @return Collection<int, mixed>
     */
    private function rowsFrom(Collection $confirmed): Collection
    {
        $this->changesByResultId = [];

        return $confirmed
            ->groupBy('biomarker_id')
            ->map(function (Collection $results): array {
                // Newest first: current is [0], previous (if any) is [1]. The
                // unique (blood_test_id, biomarker_id) constraint guarantees
                // the previous entry comes from a different blood test.
                $ordered = $this->orderedByRecency($results);
                $current = $ordered->first();
                $previous = $ordered->get(1);

                if ($previous instanceof BiomarkerResult) {
                    $this->changesByResultId[(int) $current->id] = $this->buildLongitudinalChanges->change($previous, $current);
                }

                return $this->row($current);
            })
            ->sortBy('label', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();
    }

    /**
     * Order a biomarker's measurements newest-first: latest by sample date,
     * then by confirmation time, then by id, so ties (same-day tests,
     * second-precision timestamps) resolve deterministically. An undated
     * blood test counts as NEWEST, matching BuildLongitudinalChanges's
     * chronology — sorting it oldest would let the overview report the
     * opposite change direction from the consult and dashboard surfaces.
     *
     * @param  Collection<int, BiomarkerResult>  $results
     * @return Collection<int, BiomarkerResult>
     */
    private function orderedByRecency(Collection $results): Collection
    {
        return $results->sort(function (BiomarkerResult $a, BiomarkerResult $b): int {
            return [
                $b->bloodTest?->test_date?->getTimestamp() ?? PHP_INT_MAX,
                $b->confirmed_at?->getTimestamp() ?? 0,
                $b->id,
            ] <=> [
                $a->bloodTest?->test_date?->getTimestamp() ?? PHP_INT_MAX,
                $a->confirmed_at?->getTimestamp() ?? 0,
                $a->id,
            ];
        })->values();
    }

    /**
     * @return array{label: string, value: string, valueLabel: string, valueWithUnit: string, unit: string|null, status: string, statusLabel: string, reference: string, reference_unit_mismatch: bool, date: string|null, is_detection_limit: bool, beyond: array{direction: string, label: string}|null, no_status_reason: string|null, bar: array{position: string, normalStart: string, normalWidth: string, minLabel: string, maxLabel: string}|null, history: array{previousLabel: string, previousDate: string|null, delta: string|null}|null}
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

        $unitMismatch = $result->reference_unit !== null
            && $result->reference_unit !== ''
            && $result->reference_unit !== $result->unit;

        $valueLabel = Format::biomarkerValue($result->value, $result->source_snippet, $result->value_comparator);

        return [
            'label' => $result->biomarker->name,
            'value' => (string) $result->value,
            'valueLabel' => $valueLabel,
            'valueWithUnit' => $valueLabel.($result->unit ? ' '.$result->unit : ''),
            'unit' => $result->unit,
            'status' => $status->value,
            'statusLabel' => $status->dutchLabel(),
            // The range must be captioned in ITS OWN unit: on a mismatch the
            // value's unit would print a factually wrong reference.
            'reference' => $this->reference($min, $max, $result->reference_unit ?: $result->unit),
            'reference_unit_mismatch' => $unitMismatch,
            'date' => $result->bloodTest?->test_date !== null
                ? Format::dutchDate($result->bloodTest->test_date)
                : null,
            'is_detection_limit' => $isDetectionLimit,
            'beyond' => $this->beyond($result, $status, $min, $max, $isDetectionLimit, $unitMismatch),
            'no_status_reason' => $this->noStatusReason($status, $isDetectionLimit, $min, $max, $unitMismatch),
            'bar' => $this->bar($result, $min, $max, $isDetectionLimit, $unitMismatch),
            'history' => $this->history($result),
        ];
    }

    /**
     * The biomarker's previous measurement with its date — a fact, not a trend
     * line. Uses BuildLongitudinalChanges so unit changes, qualitative values,
     * and detection limits drop the delta rather than compute a false one.
     *
     * @return array{previousLabel: string, previousDate: string|null, delta: string|null}|null
     */
    private function history(BiomarkerResult $result): ?array
    {
        $change = $this->changesByResultId[(int) $result->id] ?? null;

        if (! $change instanceof LongitudinalChange || ! $change->previousResult instanceof BiomarkerResult) {
            return null;
        }

        return [
            'previousLabel' => Format::biomarkerValue(
                $change->previousResult->value,
                $change->previousResult->source_snippet,
                $change->previousResult->value_comparator,
            ).($change->previousResult->unit ? ' '.$change->previousResult->unit : ''),
            'previousDate' => $change->previousResult->bloodTest?->test_date !== null
                ? Format::dutchDate($change->previousResult->bloodTest->test_date)
                : null,
            'delta' => $change->comparable && $change->direction !== 'unchanged'
                ? (string) $change->changeLabel
                : null,
        ];
    }

    /**
     * How far an out-of-range value sits beyond the reference bound — the
     * magnitude a bare 'hoog'/'laag' flag hides. Guarded like the range bar:
     * only for plain numeric values judged against a same-unit reference.
     *
     * @return array{direction: string, label: string}|null
     */
    private function beyond(BiomarkerResult $result, BiomarkerStatus $status, ?float $min, ?float $max, bool $isDetectionLimit, bool $unitMismatch): ?array
    {
        if ($isDetectionLimit || $unitMismatch || ! is_numeric($result->value)) {
            return null;
        }

        $value = (float) $result->value;

        if ($status === BiomarkerStatus::High && $max !== null) {
            return ['direction' => 'above', 'label' => Format::number($value - $max)];
        }

        if ($status === BiomarkerStatus::Low && $min !== null) {
            return ['direction' => 'below', 'label' => Format::number($min - $value)];
        }

        return null;
    }

    /**
     * Why a row shows no status — the one fact that resolves the apparent
     * contradiction of e.g. '<50' next to 'Referentie: ≤ 30'. Returns a
     * reason code ('detection_limit', 'no_reference', 'unit_mismatch',
     * 'not_classified'); the view owns the sentence. A row that is unknown
     * without a specific cause (e.g. a qualitative value next to a numeric
     * range) still gets the 'not_classified' fallback — an unknown row with
     * an empty explanation would leave exactly the contradiction this field
     * exists to resolve.
     */
    private function noStatusReason(BiomarkerStatus $status, bool $isDetectionLimit, ?float $min, ?float $max, bool $unitMismatch): ?string
    {
        if ($isDetectionLimit) {
            return 'detection_limit';
        }

        if ($min === null && $max === null) {
            return 'no_reference';
        }

        if ($unitMismatch) {
            return 'unit_mismatch';
        }

        if ($status === BiomarkerStatus::Unknown) {
            return 'not_classified';
        }

        return null;
    }

    /**
     * Range-bar geometry, driven by the (float) cast of the value — never the
     * string. A detection limit ('<40') or qualitative value ('Negatief') gets
     * no marker: it would show a bound or non-numeric value as an exact
     * measurement. A reference in a different unit gets no band either: the
     * geometry would lie.
     *
     * @return array{position: string, normalStart: string, normalWidth: string, minLabel: string, maxLabel: string}|null
     */
    private function bar(BiomarkerResult $result, ?float $min, ?float $max, bool $isDetectionLimit, bool $unitMismatch): ?array
    {
        if ($isDetectionLimit || $unitMismatch || ! is_numeric($result->value)) {
            return null;
        }

        if ($min === null || $max === null || $max <= $min) {
            return null;
        }

        $value = (float) $result->value;
        $scale = RangeBarScale::twoSided($value, $min, $max);
        $percentages = RangeBarScale::percentages($value, $min, $max, $scale['scaleMin'], $scale['scaleMax']);

        return [
            'position' => number_format($percentages['position'], 2, '.', ''),
            'normalStart' => number_format($percentages['normalStart'], 2, '.', ''),
            'normalWidth' => number_format($percentages['normalWidth'], 2, '.', ''),
            'minLabel' => Format::number($min),
            'maxLabel' => Format::number($max),
        ];
    }

    private function reference(?float $min, ?float $max, ?string $unit): string
    {
        return Format::referenceRange($min, $max, $unit);
    }
}
