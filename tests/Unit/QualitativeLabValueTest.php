<?php

use App\Domain\Biomarkers\QualitativeLabValue;
use App\Enums\BiomarkerStatus;

it('parses common dutch qualitative lab values', function () {
    expect(QualitativeLabValue::parse('Negatief')?->storedValue())->toBe('Negatief')
        ->and(QualitativeLabValue::parse('Niet gedetecteerd')?->token)->toBe('niet_gedetecteerd')
        ->and(QualitativeLabValue::parseReference('Negatief <')?->token)->toBe('negatief');
});

it('classifies matching qualitative references as normal', function () {
    $value = QualitativeLabValue::parse('Negatief');
    $reference = QualitativeLabValue::parse('Negatief');

    expect($value?->statusAgainstReference($reference))->toBe(BiomarkerStatus::Normal);
});

it('classifies pcr not-detected rows with a below marker as normal', function () {
    $value = QualitativeLabValue::parse('Niet gedetecteerd');

    expect($value?->statusAgainstReference(null, true))->toBe(BiomarkerStatus::Normal)
        ->and($value?->statusAgainstReference(null, false))->toBe(BiomarkerStatus::Unknown);
});
