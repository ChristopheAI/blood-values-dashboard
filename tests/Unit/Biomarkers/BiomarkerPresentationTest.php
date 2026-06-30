<?php

namespace Tests\Unit\Biomarkers;

use App\Domain\Biomarkers\BiomarkerPresentation;
use App\Domain\BloodTests\LongitudinalChange;
use App\Models\BiomarkerResult;
use Tests\TestCase;

class BiomarkerPresentationTest extends TestCase
{
    public function test_status_label_maps_known_statuses(): void
    {
        $presentation = new BiomarkerPresentation;

        $this->assertSame('In orde', $presentation->statusLabel('normal'));
        $this->assertSame('Aandacht', $presentation->statusLabel('high'));
        $this->assertSame('Aandacht', $presentation->statusLabel('low'));
        $this->assertSame('Controle nodig', $presentation->statusLabel('unknown'));
    }

    public function test_trend_payload_returns_first_measurement_without_previous_result(): void
    {
        $presentation = new BiomarkerPresentation;
        $change = new LongitudinalChange(
            biomarker: 'CRP',
            result: new BiomarkerResult,
            previousResult: null,
            previousValue: 'not measured',
            currentValue: '4',
            previousUnit: '',
            currentUnit: 'mg/L',
            status: 'high',
            delta: '+4',
            changeLabel: '+4 mg/L',
            comparable: false,
            reason: 'missing_previous',
            direction: 'unknown',
        );

        $this->assertSame(
            ['kind' => 'new', 'label' => 'Eerste meting'],
            $presentation->trendPayload($change),
        );
    }

    public function test_trend_payload_returns_not_comparable_reason_in_detailed_mode(): void
    {
        $presentation = new BiomarkerPresentation;
        $change = new LongitudinalChange(
            biomarker: 'CRP',
            result: new BiomarkerResult(['unit' => 'g/L']),
            previousResult: new BiomarkerResult(['unit' => 'mg/L']),
            previousValue: '1',
            currentValue: '2',
            previousUnit: 'mg/L',
            currentUnit: 'g/L',
            status: 'not comparable',
            delta: 'not comparable',
            changeLabel: null,
            comparable: false,
            reason: 'unit_mismatch',
            direction: 'unknown',
        );

        $this->assertSame(
            ['kind' => 'not_comparable', 'label' => 'Eenheid gewijzigd'],
            $presentation->trendPayload($change),
        );
    }

    public function test_compact_trend_label_collapses_not_comparable_to_dash(): void
    {
        $presentation = new BiomarkerPresentation;
        $change = new LongitudinalChange(
            biomarker: 'CRP',
            result: new BiomarkerResult,
            previousResult: new BiomarkerResult,
            previousValue: '1',
            currentValue: '2',
            previousUnit: 'mg/L',
            currentUnit: 'g/L',
            status: 'not comparable',
            delta: 'not comparable',
            changeLabel: null,
            comparable: false,
            reason: 'unit_mismatch',
            direction: 'unknown',
        );

        $this->assertSame('—', $presentation->compactTrendLabel($change));
    }
}
