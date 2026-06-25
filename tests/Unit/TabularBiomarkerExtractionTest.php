<?php

use App\Domain\Intake\ExtractedBiomarkerCandidate;
use App\Domain\Intake\ExtractTabularBiomarkerCandidates;
use App\Domain\Intake\PositionedTextFragment;

it('extracts candidates from positioned tabular fragments', function () {
    $extract = new ExtractTabularBiomarkerCandidates;

    $candidates = $extract([
        new PositionedTextFragment('Analysis', 40, 700),
        new PositionedTextFragment('Value', 210, 700),
        new PositionedTextFragment('Unit', 300, 700),
        new PositionedTextFragment('Reference', 390, 700),
        new PositionedTextFragment('Marker Alpha', 40, 680),
        new PositionedTextFragment('12,4', 210, 680),
        new PositionedTextFragment('mg/L', 300, 680),
        new PositionedTextFragment('10 - 20', 390, 680),
        new PositionedTextFragment('Marker Beta', 40, 660),
        new PositionedTextFragment('<5', 210, 660),
        new PositionedTextFragment('U/mL', 300, 660),
        new PositionedTextFragment('< 8', 390, 660),
        new PositionedTextFragment('Marker Gamma', 40, 640),
        new PositionedTextFragment('not detected', 210, 640),
        new PositionedTextFragment('U/mL', 300, 640),
        new PositionedTextFragment('< 1', 390, 640),
    ]);

    expect($candidates)->toHaveCount(2);

    expect($candidates[0]->extractedName)->toBe('Marker Alpha')
        ->and($candidates[0]->value)->toBe('12.4')
        ->and($candidates[0]->unit)->toBe('mg/L')
        ->and($candidates[0]->referenceMin)->toBe('10')
        ->and($candidates[0]->referenceMax)->toBe('20')
        ->and($candidates[0]->referenceUnit)->toBe('mg/L')
        ->and($candidates[0]->confidence)->toBe(0.85)
        ->and($candidates[0]->source)->toBe(ExtractedBiomarkerCandidate::SOURCE_TABULAR);

    expect($candidates[1]->extractedName)->toBe('Marker Beta')
        ->and($candidates[1]->value)->toBe('<5')
        ->and($candidates[1]->unit)->toBe('U/mL')
        ->and($candidates[1]->referenceMin)->toBeNull()
        ->and($candidates[1]->referenceMax)->toBe('8')
        ->and($candidates[1]->referenceUnit)->toBe('U/mL')
        ->and($candidates[1]->confidence)->toBe(0.85);
});

it('preserves below-detection value prefixes from tabular value cells', function () {
    $extract = new ExtractTabularBiomarkerCandidates;

    $candidates = $extract([
        new PositionedTextFragment('Analyse', 40, 700),
        new PositionedTextFragment('Eenheid', 300, 700),
        new PositionedTextFragment('Referentie', 390, 700),
        new PositionedTextFragment('RA*', 40, 680),
        new PositionedTextFragment('<10', 210, 680),
        new PositionedTextFragment('kIU/L', 300, 680),
        new PositionedTextFragment('≤13', 390, 680),
        new PositionedTextFragment('CCP antilichamen*', 40, 660),
        new PositionedTextFragment('<1,1', 210, 660),
        new PositionedTextFragment('U/mL', 300, 660),
        new PositionedTextFragment('≤6,9', 390, 660),
    ]);

    expect($candidates)->toHaveCount(2);

    expect($candidates[0]->extractedName)->toBe('RA*')
        ->and($candidates[0]->value)->toBe('<10')
        ->and($candidates[0]->unit)->toBe('kIU/L')
        ->and($candidates[0]->referenceMin)->toBeNull()
        ->and($candidates[0]->referenceMax)->toBe('13')
        ->and($candidates[0]->referenceUnit)->toBe('kIU/L')
        ->and($candidates[0]->confidence)->toBe(0.85)
        ->and($candidates[0]->source)->toBe(ExtractedBiomarkerCandidate::SOURCE_CMA_TABULAR);

    expect($candidates[1]->extractedName)->toBe('CCP antilichamen*')
        ->and($candidates[1]->value)->toBe('<1.1')
        ->and($candidates[1]->unit)->toBe('U/mL')
        ->and($candidates[1]->referenceMin)->toBeNull()
        ->and($candidates[1]->referenceMax)->toBe('6.9')
        ->and($candidates[1]->referenceUnit)->toBe('U/mL')
        ->and($candidates[1]->confidence)->toBe(0.85);
});

it('keeps ambiguous below-detection limits at reduced confidence', function () {
    $extract = new ExtractTabularBiomarkerCandidates;

    $candidates = $extract([
        new PositionedTextFragment('Analyse', 40, 700),
        new PositionedTextFragment('Eenheid', 300, 700),
        new PositionedTextFragment('Referentie', 390, 700),
        new PositionedTextFragment('Marker Alpha', 40, 680),
        new PositionedTextFragment('<14', 210, 680),
        new PositionedTextFragment('kIU/L', 300, 680),
        new PositionedTextFragment('≤13', 390, 680),
    ]);

    expect($candidates)->toHaveCount(1)
        ->and($candidates[0]->value)->toBe('<14')
        ->and($candidates[0]->confidence)->toBe(0.75);
});

it('returns no tabular candidates when no header row is recognized', function () {
    $extract = new ExtractTabularBiomarkerCandidates;

    $candidates = $extract([
        new PositionedTextFragment('Marker Alpha', 40, 680),
        new PositionedTextFragment('12,4', 210, 680),
        new PositionedTextFragment('mg/L', 300, 680),
        new PositionedTextFragment('10 - 20', 390, 680),
    ]);

    expect($candidates)->toBe([]);
});

it('recognizes resultaat as a tabular value header', function () {
    $extract = new ExtractTabularBiomarkerCandidates;

    $candidates = $extract([
        new PositionedTextFragment('Analyse', 40, 700),
        new PositionedTextFragment('Resultaat', 210, 700),
        new PositionedTextFragment('Eenheid', 300, 700),
        new PositionedTextFragment('Referentie', 390, 700),
        new PositionedTextFragment('Marker Alpha', 40, 680),
        new PositionedTextFragment('12,4', 210, 680),
        new PositionedTextFragment('mg/L', 300, 680),
        new PositionedTextFragment('10 - 20', 390, 680),
    ]);

    expect($candidates)->toHaveCount(1)
        ->and($candidates[0]->extractedName)->toBe('Marker Alpha')
        ->and($candidates[0]->value)->toBe('12.4');
});

it('keeps an explicit reference unit from the reference column', function () {
    $extract = new ExtractTabularBiomarkerCandidates;

    $candidates = $extract([
        new PositionedTextFragment('Analysis', 40, 700),
        new PositionedTextFragment('Value', 210, 700),
        new PositionedTextFragment('Unit', 300, 700),
        new PositionedTextFragment('Reference', 390, 700),
        new PositionedTextFragment('Marker Alpha', 40, 680),
        new PositionedTextFragment('12,4', 210, 680),
        new PositionedTextFragment('mg/L', 300, 680),
        new PositionedTextFragment('10 - 20 g/L', 390, 680),
    ]);

    expect($candidates)->toHaveCount(1)
        ->and($candidates[0]->referenceMin)->toBe('10')
        ->and($candidates[0]->referenceMax)->toBe('20')
        ->and($candidates[0]->referenceUnit)->toBe('g/L');
});

it('keeps a compact explicit reference unit from the reference column', function () {
    $extract = new ExtractTabularBiomarkerCandidates;

    $candidates = $extract([
        new PositionedTextFragment('Analysis', 40, 700),
        new PositionedTextFragment('Value', 210, 700),
        new PositionedTextFragment('Unit', 300, 700),
        new PositionedTextFragment('Reference', 390, 700),
        new PositionedTextFragment('Marker Alpha', 40, 680),
        new PositionedTextFragment('12,4', 210, 680),
        new PositionedTextFragment('mg/L', 300, 680),
        new PositionedTextFragment('10 - 20g/L', 390, 680),
    ]);

    expect($candidates)->toHaveCount(1)
        ->and($candidates[0]->referenceMin)->toBe('10')
        ->and($candidates[0]->referenceMax)->toBe('20')
        ->and($candidates[0]->referenceUnit)->toBe('g/L');
});

it('keeps a compact explicit reference unit from one-sided reference cells', function () {
    $extract = new ExtractTabularBiomarkerCandidates;

    $candidates = $extract([
        new PositionedTextFragment('Analysis', 40, 700),
        new PositionedTextFragment('Value', 210, 700),
        new PositionedTextFragment('Unit', 300, 700),
        new PositionedTextFragment('Reference', 390, 700),
        new PositionedTextFragment('Marker Alpha', 40, 680),
        new PositionedTextFragment('5', 210, 680),
        new PositionedTextFragment('mg/L', 300, 680),
        new PositionedTextFragment('<8g/L', 390, 680),
    ]);

    expect($candidates)->toHaveCount(1)
        ->and($candidates[0]->referenceMin)->toBeNull()
        ->and($candidates[0]->referenceMax)->toBe('8')
        ->and($candidates[0]->referenceUnit)->toBe('g/L');
});

it('normalizes wrapped reference units from the reference column', function () {
    $extract = new ExtractTabularBiomarkerCandidates;

    $candidates = $extract([
        new PositionedTextFragment('Analysis', 40, 700),
        new PositionedTextFragment('Value', 210, 700),
        new PositionedTextFragment('Unit', 300, 700),
        new PositionedTextFragment('Reference', 390, 700),
        new PositionedTextFragment('Marker Alpha', 40, 680),
        new PositionedTextFragment('12,4', 210, 680),
        new PositionedTextFragment('mg/L', 300, 680),
        new PositionedTextFragment('10 - 20 (mg/L)', 390, 680),
    ]);

    expect($candidates)->toHaveCount(1)
        ->and($candidates[0]->referenceMin)->toBe('10')
        ->and($candidates[0]->referenceMax)->toBe('20')
        ->and($candidates[0]->referenceUnit)->toBe('mg/L');
});

it('infers the value column when a lab header omits the explicit value label', function () {
    $extract = new ExtractTabularBiomarkerCandidates;

    $candidates = $extract([
        new PositionedTextFragment('Analyse', 40, 700),
        new PositionedTextFragment('Eenheid', 387, 700),
        new PositionedTextFragment('Referentie', 465, 700),
        new PositionedTextFragment('Marker Alpha', 50, 680),
        new PositionedTextFragment('12,4', 190, 680),
        new PositionedTextFragment('mg/L', 387, 680),
        new PositionedTextFragment('10 - 20', 465, 680),
    ]);

    expect($candidates)->toHaveCount(1)
        ->and($candidates[0]->extractedName)->toBe('Marker Alpha')
        ->and($candidates[0]->value)->toBe('12.4')
        ->and($candidates[0]->unit)->toBe('mg/L')
        ->and($candidates[0]->confidence)->toBe(0.85)
        ->and($candidates[0]->source)->toBe(ExtractedBiomarkerCandidate::SOURCE_CMA_TABULAR);
});

it('keeps trusted CMA inferred value rows high confidence when the reference is missing', function () {
    $extract = new ExtractTabularBiomarkerCandidates;

    $candidates = $extract([
        new PositionedTextFragment('Analyse', 40, 700),
        new PositionedTextFragment('Eenheid', 387, 700),
        new PositionedTextFragment('Referentie', 465, 700),
        new PositionedTextFragment('Marker Alpha', 50, 680),
        new PositionedTextFragment('12,4', 190, 680),
        new PositionedTextFragment('mg/L', 387, 680),
        new PositionedTextFragment('pending', 465, 680),
    ]);

    expect($candidates)->toHaveCount(1)
        ->and($candidates[0]->referenceMin)->toBeNull()
        ->and($candidates[0]->referenceMax)->toBeNull()
        ->and($candidates[0]->confidence)->toBe(0.85)
        ->and($candidates[0]->source)->toBe(ExtractedBiomarkerCandidate::SOURCE_CMA_TABULAR);
});

it('does not extract CMA timestamp rows as biomarkers', function () {
    $extract = new ExtractTabularBiomarkerCandidates;

    $candidates = $extract([
        new PositionedTextFragment('Analyse', 40, 700),
        new PositionedTextFragment('Eenheid', 300, 700),
        new PositionedTextFragment('Referentie', 390, 700),
        new PositionedTextFragment('Marker Alpha', 40, 680),
        new PositionedTextFragment('12,4', 210, 680),
        new PositionedTextFragment('mg/L', 300, 680),
        new PositionedTextFragment('10 - 20', 390, 680),
        new PositionedTextFragment('29/04/2026 08:32', 40, 660),
        new PositionedTextFragment('1', 210, 660),
    ]);

    expect($candidates)->toHaveCount(1);
    expect($candidates[0]->extractedName)->toBe('Marker Alpha');
});

it('ignores trailing CMA comparison markers after range references', function () {
    $extract = new ExtractTabularBiomarkerCandidates;

    $candidates = $extract([
        new PositionedTextFragment('Analyse', 40, 700),
        new PositionedTextFragment('Eenheid', 300, 700),
        new PositionedTextFragment('Referentie', 390, 700),
        new PositionedTextFragment('Marker Alpha', 40, 680),
        new PositionedTextFragment('12,4', 210, 680),
        new PositionedTextFragment('mg/L', 300, 680),
        new PositionedTextFragment('10 - 20 <', 390, 680),
    ]);

    expect($candidates)->toHaveCount(1)
        ->and($candidates[0]->referenceMin)->toBe('10')
        ->and($candidates[0]->referenceMax)->toBe('20')
        ->and($candidates[0]->referenceUnit)->toBe('mg/L')
        ->and($candidates[0]->confidence)->toBe(0.85);
});

it('keeps missing-unit tabular rows as low confidence candidates', function () {
    $extract = new ExtractTabularBiomarkerCandidates;

    $candidates = $extract([
        new PositionedTextFragment('Analysis', 40, 700),
        new PositionedTextFragment('Value', 210, 700),
        new PositionedTextFragment('Unit', 300, 700),
        new PositionedTextFragment('Reference', 390, 700),
        new PositionedTextFragment('Marker Alpha', 40, 680),
        new PositionedTextFragment('12,4', 210, 680),
        new PositionedTextFragment('10 - 20', 390, 680),
    ]);

    expect($candidates)->toHaveCount(1)
        ->and($candidates[0]->extractedName)->toBe('Marker Alpha')
        ->and($candidates[0]->value)->toBe('12.4')
        ->and($candidates[0]->unit)->toBe('')
        ->and($candidates[0]->referenceMin)->toBe('10')
        ->and($candidates[0]->referenceMax)->toBe('20')
        ->and($candidates[0]->confidence)->toBeLessThan(0.85);
});

it('treats one-sided references with exact values as high confidence', function () {
    $extract = new ExtractTabularBiomarkerCandidates;

    $candidates = $extract([
        new PositionedTextFragment('Analysis', 40, 700),
        new PositionedTextFragment('Value', 210, 700),
        new PositionedTextFragment('Unit', 300, 700),
        new PositionedTextFragment('Reference', 390, 700),
        new PositionedTextFragment('Marker Alpha', 40, 680),
        new PositionedTextFragment('5', 210, 680),
        new PositionedTextFragment('U/mL', 300, 680),
        new PositionedTextFragment('< 8', 390, 680),
    ]);

    expect($candidates)->toHaveCount(1)
        ->and($candidates[0]->referenceMin)->toBeNull()
        ->and($candidates[0]->referenceMax)->toBe('8')
        ->and($candidates[0]->confidence)->toBe(0.85);
});

it('ignores same-row fragments outside the recognized column bands', function () {
    $extract = new ExtractTabularBiomarkerCandidates;

    $candidates = $extract([
        new PositionedTextFragment('Analysis', 40, 700),
        new PositionedTextFragment('Value', 210, 700),
        new PositionedTextFragment('Unit', 300, 700),
        new PositionedTextFragment('Reference', 390, 700),
        new PositionedTextFragment('Marker Alpha', 40, 680),
        new PositionedTextFragment('12,4', 210, 680),
        new PositionedTextFragment('mg/L', 300, 680),
        new PositionedTextFragment('10 - 20', 390, 680),
        new PositionedTextFragment('Footer text outside table', 650, 680),
    ]);

    expect($candidates)->toHaveCount(1)
        ->and($candidates[0]->sourceSnippet)->toBe('Marker Alpha 12,4 mg/L 10 - 20');
});

it('caps tabular extraction candidates', function () {
    $extract = new ExtractTabularBiomarkerCandidates;

    $fragments = [
        new PositionedTextFragment('Analysis', 40, 700),
        new PositionedTextFragment('Value', 210, 700),
        new PositionedTextFragment('Unit', 300, 700),
        new PositionedTextFragment('Reference', 390, 700),
    ];

    for ($index = 0; $index < 85; $index++) {
        $y = 680 - ($index * 10);
        $fragments[] = new PositionedTextFragment('Marker '.$index, 40, $y);
        $fragments[] = new PositionedTextFragment((string) $index, 210, $y);
        $fragments[] = new PositionedTextFragment('mg/L', 300, $y);
        $fragments[] = new PositionedTextFragment('10 - 20', 390, $y);
    }

    expect($extract($fragments))->toHaveCount(80);
});

it('bounds and flags an over-captured biomarker name', function () {
    $extract = new ExtractTabularBiomarkerCandidates;

    $candidates = $extract([
        new PositionedTextFragment('Analysis', 180, 700),
        new PositionedTextFragment('Value', 300, 700),
        new PositionedTextFragment('Unit', 390, 700),
        new PositionedTextFragment('Reference', 480, 700),
        new PositionedTextFragment('Unrelated', 20, 680),
        new PositionedTextFragment('prose', 40, 680),
        new PositionedTextFragment('Marker', 180, 680),
        new PositionedTextFragment('Alpha', 195, 680),
        new PositionedTextFragment('lorem', 210, 680),
        new PositionedTextFragment('ipsum', 225, 680),
        new PositionedTextFragment('dolor', 238, 680),
        new PositionedTextFragment('12,4', 300, 680),
        new PositionedTextFragment('mg/L', 390, 680),
        new PositionedTextFragment('10 - 20', 480, 680),
    ]);

    expect($candidates)->toHaveCount(1);
    expect($candidates[0]->extractedName)->toBe('Marker Alpha lorem ipsum');
    expect(count(explode(' ', $candidates[0]->extractedName)))->toBeLessThanOrEqual(4);
    expect($candidates[0]->confidence)->toBeLessThanOrEqual(0.6);
});

it('drops prose noise after a large gap inside the name cell', function () {
    $extract = new ExtractTabularBiomarkerCandidates;

    $candidates = $extract([
        new PositionedTextFragment('Analysis', 40, 700),
        new PositionedTextFragment('Value', 210, 700),
        new PositionedTextFragment('Unit', 300, 700),
        new PositionedTextFragment('Reference', 390, 700),
        new PositionedTextFragment('Marker', 40, 680),
        new PositionedTextFragment('Alpha', 64, 680),
        new PositionedTextFragment('prose sentence tail', 122, 680),
        new PositionedTextFragment('12,4', 210, 680),
        new PositionedTextFragment('mg/L', 300, 680),
        new PositionedTextFragment('10 - 20', 390, 680),
    ]);

    expect($candidates)->toHaveCount(1);
    expect($candidates[0]->extractedName)->toBe('Marker Alpha');
    expect($candidates[0]->confidence)->toBe(0.85);
});

it('does not merge fragments from a different page that share a vertical position', function () {
    $extract = new ExtractTabularBiomarkerCandidates;

    $candidates = $extract([
        // Page 1: a real-style header (no explicit value label) and one clean row.
        new PositionedTextFragment('Analyse', 40, 700, 1),
        new PositionedTextFragment('Eenheid', 300, 700, 1),
        new PositionedTextFragment('Referentie', 390, 700, 1),
        new PositionedTextFragment('Marker Alpha', 40, 680, 1),
        new PositionedTextFragment('12,4', 210, 680, 1),
        new PositionedTextFragment('mg/L', 300, 680, 1),
        new PositionedTextFragment('10 - 20', 390, 680, 1),
        // Page 3 marketing prose sitting at the SAME y as the biomarker row.
        new PositionedTextFragment('Prose line on a later marketing page', 40, 680, 3),
        new PositionedTextFragment('continues across that page', 210, 680, 3),
    ]);

    expect($candidates)->toHaveCount(1);
    expect($candidates[0]->extractedName)->toBe('Marker Alpha');
    expect($candidates[0]->confidence)->toBe(0.85);
});

it('does not extract standalone non-table rows from later pages', function () {
    $extract = new ExtractTabularBiomarkerCandidates;

    $candidates = $extract([
        // Page 1: a real table with a recognized header and one clean row.
        new PositionedTextFragment('Analysis', 40, 700, 1),
        new PositionedTextFragment('Value', 210, 700, 1),
        new PositionedTextFragment('Unit', 300, 700, 1),
        new PositionedTextFragment('Reference', 390, 700, 1),
        new PositionedTextFragment('Marker Alpha', 40, 680, 1),
        new PositionedTextFragment('12,4', 210, 680, 1),
        new PositionedTextFragment('mg/L', 300, 680, 1),
        new PositionedTextFragment('10 - 20', 390, 680, 1),
        // Page 2: prose happens to align with the learned columns, but has no table context.
        new PositionedTextFragment('Marketing footer', 40, 680, 2),
        new PositionedTextFragment('12,4', 210, 680, 2),
        new PositionedTextFragment('mg/L', 300, 680, 2),
        new PositionedTextFragment('10 - 20', 390, 680, 2),
    ]);

    expect($candidates)->toHaveCount(1);
    expect($candidates[0]->extractedName)->toBe('Marker Alpha');
});

it('extracts continuation rows on a later page without a repeated header', function () {
    $extract = new ExtractTabularBiomarkerCandidates;

    $candidates = $extract([
        // Page 1: header + first data row.
        new PositionedTextFragment('Analysis', 40, 700, 1),
        new PositionedTextFragment('Value', 210, 700, 1),
        new PositionedTextFragment('Unit', 300, 700, 1),
        new PositionedTextFragment('Reference', 390, 700, 1),
        new PositionedTextFragment('Marker Alpha', 40, 90, 1),
        new PositionedTextFragment('12,4', 210, 90, 1),
        new PositionedTextFragment('mg/L', 300, 90, 1),
        new PositionedTextFragment('10 - 20', 390, 90, 1),
        // Page 2: continuation row near the top, no repeated header.
        new PositionedTextFragment('Marker Beta', 40, 680, 2),
        new PositionedTextFragment('7,1', 210, 680, 2),
        new PositionedTextFragment('mg/L', 300, 680, 2),
        new PositionedTextFragment('3 - 9', 390, 680, 2),
    ]);

    expect($candidates)->toHaveCount(2);
    expect($candidates[0]->extractedName)->toBe('Marker Alpha');
    expect($candidates[1]->extractedName)->toBe('Marker Beta');
});
