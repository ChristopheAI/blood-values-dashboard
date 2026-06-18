<?php

use App\Domain\Biomarkers\DetermineBiomarkerStatus;

it('determines biomarker status from value unit and reference range', function () {
    $calculator = new DetermineBiomarkerStatus;

    $cases = [
        'value below range returns low' => [9.9, 'g/L', 10.0, 20.0, 'g/L', 'low'],
        'value inside range returns normal' => [15.0, 'g/L', 10.0, 20.0, 'g/L', 'normal'],
        'value above range returns high' => [20.1, 'g/L', 10.0, 20.0, 'g/L', 'high'],
        'missing range returns unknown' => [15.0, 'g/L', null, null, 'g/L', 'unknown'],
        'mismatched unit returns unknown' => [15.0, 'mg/L', 10.0, 20.0, 'g/L', 'unknown'],
        'reversed range returns unknown' => [15.0, 'g/L', 20.0, 10.0, 'g/L', 'unknown'],
        'one-sided minimum below threshold returns low' => [9.9, 'g/L', 10.0, null, 'g/L', 'low'],
        'one-sided maximum above threshold returns high' => [20.1, 'g/L', null, 20.0, 'g/L', 'high'],
    ];

    foreach ($cases as $label => [$value, $valueUnit, $minimum, $maximum, $rangeUnit, $expectedStatus]) {
        $status = $calculator(
            value: $value,
            valueUnit: $valueUnit,
            referenceMinimum: $minimum,
            referenceMaximum: $maximum,
            referenceUnit: $rangeUnit,
        );

        expect($status->value)->toBe($expectedStatus, $label);
    }
});
