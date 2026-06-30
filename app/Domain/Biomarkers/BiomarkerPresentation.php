<?php

namespace App\Domain\Biomarkers;

use App\Domain\BloodTests\LongitudinalChange;
use App\Models\BiomarkerResult;

final class BiomarkerPresentation
{
    public function statusLabel(string $status): string
    {
        return match ($status) {
            'normal' => 'In orde',
            'high', 'low' => 'Aandacht',
            default => 'Controle nodig',
        };
    }

    /**
     * @return array{kind: string, label: string}
     */
    public function trendPayload(?LongitudinalChange $change): array
    {
        if (! $change instanceof LongitudinalChange || ! $change->previousResult instanceof BiomarkerResult) {
            return ['kind' => 'new', 'label' => 'Eerste meting'];
        }

        if (! $change->comparable) {
            return [
                'kind' => 'not_comparable',
                'label' => $this->notComparableTrendLabel($change->reason),
            ];
        }

        if ($change->direction === 'unchanged') {
            return ['kind' => 'unchanged', 'label' => 'Geen verandering'];
        }

        return [
            'kind' => 'changed',
            'label' => (string) $change->changeLabel,
        ];
    }

    public function compactTrendLabel(?LongitudinalChange $change): string
    {
        if (! $change instanceof LongitudinalChange) {
            return '—';
        }

        if (! $change->previousResult instanceof BiomarkerResult) {
            return 'Eerste meting';
        }

        if (! $change->comparable) {
            return '—';
        }

        if ($change->direction === 'unchanged') {
            return 'Geen verandering';
        }

        return (string) $change->changeLabel;
    }

    private function notComparableTrendLabel(?string $reason): string
    {
        return match ($reason) {
            'missing_unit' => 'Eenheid ontbreekt',
            'unit_mismatch' => 'Eenheid gewijzigd',
            'non_numeric' => 'Niet numeriek vergelijkbaar',
            default => 'Niet vergelijkbaar',
        };
    }
}
