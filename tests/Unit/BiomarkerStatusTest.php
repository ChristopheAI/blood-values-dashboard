<?php

use App\Domain\Biomarkers\DetectionLimitValue;
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
        'one-sided minimum at threshold returns normal' => [10.0, 'g/L', 10.0, null, 'g/L', 'normal'],
        'one-sided minimum above threshold returns normal' => [10.1, 'g/L', 10.0, null, 'g/L', 'normal'],
        'one-sided maximum above threshold returns high' => [20.1, 'g/L', null, 20.0, 'g/L', 'high'],
        'one-sided maximum at threshold returns normal' => [20.0, 'g/L', null, 20.0, 'g/L', 'normal'],
        'one-sided maximum below threshold returns normal' => [19.9, 'g/L', null, 20.0, 'g/L', 'normal'],
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

it('classifies safe detection limits against one-sided references', function () {
    $calculator = new DetermineBiomarkerStatus;

    $cases = [
        'below detection limit within maximum returns normal' => ['<10', 'kIU/L', null, 13.0, 'kIU/L', 'normal'],
        'below detection limit above maximum returns unknown' => ['<14', 'kIU/L', null, 13.0, 'kIU/L', 'unknown'],
        'above detection limit within minimum returns normal' => ['>40', 'mg/dL', 40.0, null, 'mg/dL', 'normal'],
        'above detection limit below minimum returns unknown' => ['>35', 'mg/dL', 40.0, null, 'mg/dL', 'unknown'],
    ];

    foreach ($cases as $label => [$rawValue, $valueUnit, $minimum, $maximum, $rangeUnit, $expectedStatus]) {
        $detectionLimit = DetectionLimitValue::parse($rawValue);

        expect($detectionLimit)->not->toBeNull($label);

        $status = $calculator->forDetectionLimit(
            detectionLimit: $detectionLimit,
            valueUnit: $valueUnit,
            referenceMinimum: $minimum,
            referenceMaximum: $maximum,
            referenceUnit: $rangeUnit,
        );

        expect($status->value)->toBe($expectedStatus, $label);
    }
});
