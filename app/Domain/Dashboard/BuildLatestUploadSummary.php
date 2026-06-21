<?php

namespace App\Domain\Dashboard;

use App\Models\BiomarkerResult;
use App\Models\BloodTest;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class BuildLatestUploadSummary
{
    /**
     * @return array{
     *     bloodTest: BloodTest,
     *     confirmedCount: int,
     *     confirmedLabel: string,
     *     normalCount: int,
     *     normalSummaryLabel: string,
     *     newCount: int,
     *     changedCount: int,
     *     attentionCount: int,
     *     attentionSummaryLabel: string,
     *     attentionHeading: string,
     *     normalHeading: string,
     *     collectedLabel: string,
     *     rows: Collection<int, mixed>,
     *     attentionRows: Collection<int, mixed>,
     *     featuredAttentionRows: Collection<int, mixed>,
     *     reviewRows: Collection<int, mixed>,
     *     normalRows: Collection<int, mixed>,
     *     rangeRows: Collection<int, mixed>
     * }|null
     */
    public function __invoke(User $user): ?array
    {
        $bloodTest = BloodTest::query()
            ->where('user_id', $user->id)
            ->whereHas('results', fn ($query) => $query
                ->whereNotNull('confirmed_at')
                ->whereHas('biomarker', fn ($query) => $query->where('user_id', $user->id)))
            ->latest()
            ->first();

        if (! $bloodTest) {
            return null;
        }

        $rows = BiomarkerResult::query()
            ->confirmedForUser($user->id)
            ->where('blood_test_id', $bloodTest->id)
            ->with('biomarker')
            ->orderByRaw("case status when 'high' then 0 when 'low' then 1 when 'unknown' then 2 else 3 end")
            ->orderBy('id')
            ->get()
            ->map(fn (BiomarkerResult $result) => $this->summarizeResult($user->id, $result));

        if ($rows->isEmpty()) {
            return null;
        }

        $attentionRows = $rows
            ->filter(fn (array $row) => $row['needsAttention'])
            ->values();
        $featuredAttentionRows = $attentionRows
            ->filter(fn (array $row) => in_array($row['status'], ['high', 'low'], true))
            ->values();
        $reviewRows = $attentionRows
            ->filter(fn (array $row) => $row['status'] === 'unknown')
            ->values();
        $normalRows = $rows
            ->filter(fn (array $row) => $row['status'] === 'normal')
            ->values();

        return [
            'bloodTest' => $bloodTest,
            'confirmedCount' => $rows->count(),
            'confirmedLabel' => $this->confirmedLabel($rows->count()),
            'normalCount' => $normalRows->count(),
            'normalSummaryLabel' => $this->normalSummaryLabel($normalRows->count(), $rows->count()),
            'newCount' => $rows->where('trendKind', 'new')->count(),
            'changedCount' => $rows->where('trendKind', 'changed')->count(),
            'attentionCount' => $attentionRows->count(),
            'attentionSummaryLabel' => $this->attentionSummaryLabel($attentionRows->count()),
            'attentionHeading' => $attentionRows->count() === 1
                ? 'Deze waarde vraagt aandacht'
                : 'Deze waarden vragen aandacht',
            'normalHeading' => $normalRows->count() === 1
                ? 'Deze waarde is in orde'
                : 'Deze waarden zijn in orde',
            'collectedLabel' => $this->collectedLabel($bloodTest),
            'rows' => $rows,
            'attentionRows' => $attentionRows,
            'featuredAttentionRows' => $featuredAttentionRows,
            'reviewRows' => $reviewRows,
            'normalRows' => $normalRows,
            'rangeRows' => $rows
                ->filter(fn (array $row) => $row['range']['available'])
                ->take(5)
                ->values(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function summarizeResult(int $userId, BiomarkerResult $result): array
    {
        $previous = $this->previousConfirmedResult($userId, $result);
        $trend = $this->buildTrend($result, $previous);
        $valueLabel = $this->formatNumber((float) $result->value).' '.$result->unit;

        return [
            'name' => $result->biomarker->name,
            'valueLabel' => $valueLabel,
            'status' => $result->status,
            'statusLabel' => $this->statusLabel($result->status),
            'statusSummary' => $this->statusSummary($result->status),
            'takeaway' => $this->takeaway($result->status),
            'needsAttention' => in_array($result->status, ['low', 'high', 'unknown'], true),
            'trendKind' => $trend['kind'],
            'trendLabel' => $trend['label'],
            'range' => $this->buildRange($result),
        ];
    }

    private function previousConfirmedResult(int $userId, BiomarkerResult $result): ?BiomarkerResult
    {
        return BiomarkerResult::query()
            ->confirmedForUser($userId)
            ->where('biomarker_results.biomarker_id', $result->biomarker_id)
            ->where('biomarker_results.blood_test_id', '!=', $result->blood_test_id)
            ->join('blood_tests as previous_blood_tests', 'previous_blood_tests.id', '=', 'biomarker_results.blood_test_id')
            ->select('biomarker_results.*')
            ->orderByDesc('previous_blood_tests.test_date')
            ->orderByDesc('biomarker_results.confirmed_at')
            ->orderByDesc('biomarker_results.id')
            ->first();
    }

    /**
     * @return array{kind: string, label: string}
     */
    private function buildTrend(BiomarkerResult $result, ?BiomarkerResult $previous): array
    {
        if (! $previous) {
            return ['kind' => 'new', 'label' => 'Eerste meting'];
        }

        if ($previous->unit !== $result->unit) {
            return ['kind' => 'not_comparable', 'label' => 'Eenheid gewijzigd'];
        }

        $delta = (float) $result->value - (float) $previous->value;

        if (abs($delta) < 0.00001) {
            return ['kind' => 'unchanged', 'label' => 'Geen verandering'];
        }

        $prefix = $delta > 0 ? '+' : '';

        return [
            'kind' => 'changed',
            'label' => $prefix.$this->formatNumber($delta).' '.$result->unit,
        ];
    }

    /**
     * @return array{available: bool, position: float|null, normalStart: float|null, normalWidth: float|null, label: string}
     */
    private function buildRange(BiomarkerResult $result): array
    {
        $value = (float) $result->value;
        $min = $this->toFloatOrNull($result->reference_min);
        $max = $this->toFloatOrNull($result->reference_max);

        if ($min === null && $max === null) {
            return [
                'available' => false,
                'position' => null,
                'normalStart' => null,
                'normalWidth' => null,
                'label' => 'Geen volledige referentie',
            ];
        }

        if ($result->reference_unit && $result->reference_unit !== $result->unit) {
            return [
                'available' => false,
                'position' => null,
                'normalStart' => null,
                'normalWidth' => null,
                'label' => 'Referentie-eenheid verschilt',
            ];
        }

        if ($min !== null && $max !== null) {
            if ($max <= $min) {
                return [
                    'available' => false,
                    'position' => null,
                    'normalStart' => null,
                    'normalWidth' => null,
                    'label' => 'Geen volledige referentie',
                ];
            }

            $span = $max - $min;
            $scaleMin = min($min, $value) - ($span * 0.15);
            $scaleMax = max($max, $value) + ($span * 0.15);

            if ($scaleMin >= 0 && $min >= 0 && $value >= 0) {
                $scaleMin = max(0, $scaleMin);
            }

            return $this->rangePayload(
                value: $value,
                normalStartValue: $min,
                normalEndValue: $max,
                scaleMin: $scaleMin,
                scaleMax: $scaleMax,
                label: 'Normaal: '.$this->formatNumber($min).' - '.$this->formatNumber($max).' '.$result->unit,
            );
        }

        if ($max === null) {
            $scaleMin = min($min * 0.5, $value * 0.85, $min - 1);
            $scaleMax = max($min * 1.5, $value * 1.15, $min + 1);

            return $this->rangePayload(
                value: $value,
                normalStartValue: $min,
                normalEndValue: $scaleMax,
                scaleMin: $scaleMin,
                scaleMax: $scaleMax,
                label: 'Normaal: vanaf '.$this->formatNumber($min).' '.$result->unit,
            );
        }

        $scaleMin = min(0, $value, $max);
        $scaleMax = max($max * 1.5, $value * 1.15, $max + 1);

        return $this->rangePayload(
            value: $value,
            normalStartValue: $scaleMin,
            normalEndValue: $max,
            scaleMin: $scaleMin,
            scaleMax: $scaleMax,
            label: 'Normaal: onder '.$this->formatNumber($max).' '.$result->unit,
        );
    }

    /**
     * @return array{available: bool, position: float, normalStart: float, normalWidth: float, label: string}
     */
    private function rangePayload(float $value, float $normalStartValue, float $normalEndValue, float $scaleMin, float $scaleMax, string $label): array
    {
        if ($scaleMax <= $scaleMin) {
            $scaleMax = $scaleMin + 1;
        }

        $position = (($value - $scaleMin) / ($scaleMax - $scaleMin)) * 100;
        $normalStart = (($normalStartValue - $scaleMin) / ($scaleMax - $scaleMin)) * 100;
        $normalEnd = (($normalEndValue - $scaleMin) / ($scaleMax - $scaleMin)) * 100;

        return [
            'available' => true,
            'position' => max(0, min(100, $position)),
            'normalStart' => max(0, min(100, $normalStart)),
            'normalWidth' => max(0, min(100, $normalEnd) - max(0, min(100, $normalStart))),
            'label' => $label,
        ];
    }

    private function toFloatOrNull(?string $value): ?float
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        return (float) str_replace(',', '.', $value);
    }

    private function formatNumber(float $value): string
    {
        return rtrim(rtrim(number_format($value, 4, '.', ''), '0'), '.');
    }

    private function confirmedLabel(int $count): string
    {
        return $count.' '.($count === 1 ? 'bevestigde waarde' : 'bevestigde waarden');
    }

    private function normalSummaryLabel(int $normalCount, int $confirmedCount): string
    {
        $valueWord = $normalCount === 1 ? 'waarde is' : 'waarden zijn';

        return $normalCount.'/'.$confirmedCount.' '.$valueWord.' normaal';
    }

    private function attentionSummaryLabel(int $attentionCount): string
    {
        if ($attentionCount === 0) {
            return 'Geen waarden vragen aandacht';
        }

        return $attentionCount.' '.($attentionCount === 1 ? 'waarde vraagt' : 'waarden vragen').' aandacht';
    }

    private function collectedLabel(BloodTest $bloodTest): string
    {
        if (! $bloodTest->test_date) {
            return 'Afname zonder datum';
        }

        return 'Afname '.$this->formatDutchDate($bloodTest->test_date);
    }

    private function formatDutchDate(CarbonInterface $date): string
    {
        $months = [
            1 => 'januari',
            2 => 'februari',
            3 => 'maart',
            4 => 'april',
            5 => 'mei',
            6 => 'juni',
            7 => 'juli',
            8 => 'augustus',
            9 => 'september',
            10 => 'oktober',
            11 => 'november',
            12 => 'december',
        ];

        return $date->day.' '.$months[$date->month].' '.$date->year;
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'normal' => 'In orde',
            'high', 'low' => 'Aandacht',
            default => 'Controle nodig',
        };
    }

    private function statusSummary(string $status): string
    {
        return match ($status) {
            'normal' => 'Binnen de opgegeven referentie.',
            'high' => 'Boven de opgegeven referentie.',
            'low' => 'Onder de opgegeven referentie.',
            default => 'Geen betrouwbare referentie om status te bepalen.',
        };
    }

    private function takeaway(string $status): string
    {
        return match ($status) {
            'normal' => 'Zit binnen de opgegeven referentie.',
            'high' => 'Ligt boven de opgegeven referentie.',
            'low' => 'Ligt onder de opgegeven referentie.',
            default => 'Controleer de referentie voor je deze waarde gebruikt.',
        };
    }
}
