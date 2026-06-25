<?php

use App\Support\Format;

it('preserves detection prefixes when rendering biomarker values', function () {
    expect(Format::biomarkerValue('10', 'RA* <10 kIU/L ≤13 <'))->toBe('<10')
        ->and(Format::biomarkerValue('20', 'Marker Delta >20 U/L ≥10'))->toBe('>20')
        ->and(Format::biomarkerValue('1.1', 'CCP antilichamen* <1,1 U/mL ≤6,9 <'))->toBe('<1.1')
        ->and(Format::biomarkerValue('12.4', 'Marker Alpha 12,4 mg/L 10 - 20'))->toBe('12.4');
});

it('does not preserve reference-bound prefixes as biomarker value prefixes', function () {
    expect(Format::biomarkerValue('8', 'Marker Beta 8 U/mL < 8'))->toBe('8')
        ->and(Format::biomarkerValue('5', 'Marker Gamma 5 U/L > 5'))->toBe('5');
});

it('renders qualitative biomarker values without numeric coercion', function () {
    expect(Format::biomarkerValue('Negatief', 'T. pallidum AL* Negatief Negatief <'))->toBe('Negatief')
        ->and(Format::biomarkerValue('Niet gedetecteerd', 'C. trachomatis DNA (PCR) Niet gedetecteerd <'))->toBe('Niet gedetecteerd');
});
