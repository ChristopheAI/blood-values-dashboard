<?php

use App\Support\Format;

it('preserves below-detection prefixes when rendering biomarker values', function () {
    expect(Format::biomarkerValue('10', 'RA* <10 kIU/L ≤13 <'))->toBe('<10')
        ->and(Format::biomarkerValue('1.1', 'CCP antilichamen* <1,1 U/mL ≤6,9 <'))->toBe('<1.1')
        ->and(Format::biomarkerValue('12.4', 'Marker Alpha 12,4 mg/L 10 - 20'))->toBe('12.4');
});
