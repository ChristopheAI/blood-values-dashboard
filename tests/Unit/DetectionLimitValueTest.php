<?php

use App\Domain\Biomarkers\DetectionLimitValue;

it('detects safe below-detection limits against one-sided maximum references', function () {
    $belowLimit = DetectionLimitValue::parse('<10');

    expect($belowLimit)->not->toBeNull()
        ->and($belowLimit->boundForStatus())->toBe('lt')
        ->and($belowLimit->isSafeForReference(null, '13'))->toBeTrue()
        ->and($belowLimit->isSafeForReference(null, '9'))->toBeFalse();
});

it('detects safe above-detection limits against one-sided minimum references', function () {
    $aboveLimit = DetectionLimitValue::parse('>40');

    expect($aboveLimit)->not->toBeNull()
        ->and($aboveLimit->boundForStatus())->toBe('gt')
        ->and($aboveLimit->isSafeForReference('40', null))->toBeTrue()
        ->and($aboveLimit->isSafeForReference('50', null))->toBeFalse();
});
