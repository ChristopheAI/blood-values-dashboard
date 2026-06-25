<?php

use App\Domain\Intake\ExtractCmaLayoutBiomarkerCandidates;
use App\Domain\Intake\ExtractedBiomarkerCandidate;

it('extracts CMA layout rows by fixed columns', function () {
    $extract = new ExtractCmaLayoutBiomarkerCandidates;

    $candidates = $extract(cmaLayoutText([
        cmaLayoutRow('Marker Alpha°', '10,1', 'umol/L', '5,0 - 15,0'),
        cmaLayoutRow('Marker Beta C°', '0,81', 'mg/L', '0,68 - 1,22'),
        cmaLayoutRow('Marker Gamma', '110', 'mL/min/1,73m2', '>=90'),
        cmaLayoutRow('Marker Delta°', '+ 162', 'mg/dL', '<=100'),
        cmaLayoutContinuation('This explanatory text should not become a biomarker row.'),
    ]));

    expect($candidates)->toHaveCount(4);

    expect($candidates[0]->extractedName)->toBe('Marker Alpha')
        ->and($candidates[0]->value)->toBe('10.1')
        ->and($candidates[0]->unit)->toBe('umol/L')
        ->and($candidates[0]->referenceMin)->toBe('5.0')
        ->and($candidates[0]->referenceMax)->toBe('15.0')
        ->and($candidates[0]->referenceUnit)->toBe('umol/L')
        ->and($candidates[0]->confidence)->toBe(0.85)
        ->and($candidates[0]->source)->toBe(ExtractedBiomarkerCandidate::SOURCE_CMA_LAYOUT);

    expect($candidates[1]->extractedName)->toBe('Marker Beta C')
        ->and($candidates[1]->value)->toBe('0.81')
        ->and($candidates[1]->unit)->toBe('mg/L')
        ->and($candidates[1]->referenceMin)->toBe('0.68')
        ->and($candidates[1]->referenceMax)->toBe('1.22');

    expect($candidates[2]->extractedName)->toBe('Marker Gamma')
        ->and($candidates[2]->value)->toBe('110')
        ->and($candidates[2]->referenceMin)->toBe('90')
        ->and($candidates[2]->referenceMax)->toBeNull()
        ->and($candidates[2]->confidence)->toBe(0.85);

    expect($candidates[3]->extractedName)->toBe('Marker Delta')
        ->and($candidates[3]->value)->toBe('162')
        ->and($candidates[3]->referenceMin)->toBeNull()
        ->and($candidates[3]->referenceMax)->toBe('100')
        ->and($candidates[3]->confidence)->toBe(0.85);
});

it('returns no CMA layout rows without the CMA column header', function () {
    $extract = new ExtractCmaLayoutBiomarkerCandidates;

    $candidates = $extract(implode("\n", [
        cmaLayoutRow('Marker Alpha', '10,1', 'umol/L', '5,0 - 15,0'),
    ]));

    expect($candidates)->toBe([]);
});

it('extracts indented CMA sub-rows when columns sit left of the header positions', function () {
    $extract = new ExtractCmaLayoutBiomarkerCandidates;

    $candidates = $extract(cmaLayoutText([
        'Trombofilie',
        cmaLayoutIndentedRow('Homocysteïne°', '10,1', 'µmol/L', '5,0 - 15,0'),
        cmaLayoutRow('Cystatine C°', '0,81', 'mg/L', '0,68 - 1,22'),
        cmaLayoutRow('Apolipoproteïne B°', '+ 162', 'mg/dL', '<=100'),
        cmaLayoutContinuation('This explanatory text should not become a biomarker row.'),
    ]));

    expect($candidates)->toHaveCount(3);

    expect($candidates[0]->extractedName)->toBe('Homocysteïne')
        ->and($candidates[0]->value)->toBe('10.1')
        ->and($candidates[0]->unit)->toBe('µmol/L')
        ->and($candidates[0]->referenceMin)->toBe('5.0')
        ->and($candidates[0]->referenceMax)->toBe('15.0')
        ->and($candidates[0]->source)->toBe(ExtractedBiomarkerCandidate::SOURCE_CMA_LAYOUT);

    expect($candidates[1]->extractedName)->toBe('Cystatine C')
        ->and($candidates[2]->extractedName)->toBe('Apolipoproteïne B');
});

function cmaLayoutText(array $rows): string
{
    return implode("\n", array_merge([
        cmaLayoutHeader(),
        'STOLLING',
    ], $rows));
}

function cmaLayoutHeader(): string
{
    return str_pad('Analyse', 36).str_pad('822.434.825', 36).str_pad('Eenheid', 24).'Referentie';
}

function cmaLayoutRow(string $name, string $value, string $unit, string $reference): string
{
    return str_pad($name, 36).str_pad($value, 36).str_pad($unit, 24).$reference.'        <';
}

function cmaLayoutIndentedRow(string $name, string $value, string $unit, string $reference): string
{
    return str_repeat(' ', 6)
        .str_pad($name, 30)
        .str_pad($value, 20)
        .str_pad($unit, 20)
        .$reference
        .'        <';
}

function cmaLayoutContinuation(string $text): string
{
    return str_repeat(' ', 36).$text;
}
