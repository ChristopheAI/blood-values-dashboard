<?php

use App\Domain\Intake\ExtractBiomarkerDrafts;
use App\Domain\Intake\ExtractedBiomarkerCandidate;
use App\Domain\Intake\ExtractPdfLayoutText;
use App\Domain\Intake\RunBloodTestExtraction;
use App\Domain\Privacy\BuildDataExport;
use App\Models\Biomarker;
use App\Models\BiomarkerResult;
use App\Models\BloodTest;
use App\Models\BloodTestDocument;
use App\Models\ExtractionRun;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

it('extracts expected candidate values from a fixture pdf deterministically', function () {
    $candidates = app(ExtractBiomarkerDrafts::class)(
        base_path('tests/Fixtures/assisted-extraction-lab.pdf'),
    );

    expect($candidates)->toHaveCount(3);
    expect($candidates[0]->extractedName)->toBe('Ferritin')
        ->and($candidates[0]->value)->toBe('42')
        ->and($candidates[0]->unit)->toBe('ug/L')
        ->and($candidates[0]->referenceMin)->toBe('30')
        ->and($candidates[0]->referenceMax)->toBe('150')
        ->and($candidates[0]->referenceUnit)->toBe('ug/L');
    expect($candidates[1]->extractedName)->toBe('CRP')
        ->and($candidates[1]->value)->toBe('1.2')
        ->and($candidates[1]->unit)->toBe('mg/L');
    expect($candidates[2]->extractedName)->toBe('Vitamin D')
        ->and($candidates[2]->value)->toBe('61')
        ->and($candidates[2]->unit)->toBe('nmol/L');
});

it('uses inline extraction before tabular fallback when both are present', function () {
    $path = syntheticInlineAndTabularPdfPath();

    try {
        $candidates = app(ExtractBiomarkerDrafts::class)($path);
    } finally {
        @unlink($path);
    }

    expect($candidates)->toHaveCount(1)
        ->and($candidates[0]->extractedName)->toBe('Marker Inline')
        ->and($candidates[0]->value)->toBe('42')
        ->and($candidates[0]->unit)->toBe('mg/L')
        ->and($candidates[0]->confidence)->toBe(0.95);
});

it('extracts inline candidates from glyph-spaced text-layer pdfs', function () {
    $path = syntheticGlyphSpacedInlinePdfPath();

    try {
        $candidates = app(ExtractBiomarkerDrafts::class)($path);
    } finally {
        @unlink($path);
    }

    expect($candidates)->toHaveCount(1)
        ->and($candidates[0]->extractedName)->toBe('Marker Alpha')
        ->and($candidates[0]->value)->toBe('12.4')
        ->and($candidates[0]->unit)->toBe('mg/L')
        ->and($candidates[0]->referenceMin)->toBe('10')
        ->and($candidates[0]->referenceMax)->toBe('20')
        ->and($candidates[0]->referenceUnit)->toBe('mg/L')
        ->and($candidates[0]->confidence)->toBe(0.95);
});

it('extracts inline candidates when glyph spacing loses numeric field boundaries', function () {
    $path = syntheticCompactInlinePdfPath();

    try {
        $candidates = app(ExtractBiomarkerDrafts::class)($path);
    } finally {
        @unlink($path);
    }

    expect($candidates)->toHaveCount(1)
        ->and($candidates[0]->extractedName)->toBe('MarkerAlpha')
        ->and($candidates[0]->value)->toBe('12.4')
        ->and($candidates[0]->unit)->toBe('mg/L')
        ->and($candidates[0]->referenceMin)->toBe('10')
        ->and($candidates[0]->referenceMax)->toBe('20')
        ->and($candidates[0]->referenceUnit)->toBe('mg/L')
        ->and($candidates[0]->confidence)->toBe(0.8);
});

it('extracts compact inline candidates when the unit precedes numeric fields', function () {
    $path = syntheticCompactUnitFirstInlinePdfPath();

    try {
        $candidates = app(ExtractBiomarkerDrafts::class)($path);
    } finally {
        @unlink($path);
    }

    expect($candidates)->toHaveCount(1)
        ->and($candidates[0]->extractedName)->toBe('MarkerAlpha')
        ->and($candidates[0]->value)->toBe('12.4')
        ->and($candidates[0]->unit)->toBe('mg/L')
        ->and($candidates[0]->referenceMin)->toBe('10.0')
        ->and($candidates[0]->referenceMax)->toBe('20.0')
        ->and($candidates[0]->referenceUnit)->toBe('mg/L')
        ->and($candidates[0]->confidence)->toBe(0.8);
});

it('extracts compact unit-first candidates with symbolic slash units', function () {
    $path = syntheticSymbolicUnitFirstInlinePdfPath();

    try {
        $candidates = app(ExtractBiomarkerDrafts::class)($path);
    } finally {
        @unlink($path);
    }

    expect($candidates)->toHaveCount(2)
        ->and($candidates[0]->extractedName)->toBe('MarkerAlpha')
        ->and($candidates[0]->value)->toBe('12.4')
        ->and($candidates[0]->unit)->toBe('K°U/L')
        ->and($candidates[0]->referenceMin)->toBe('10.0')
        ->and($candidates[0]->referenceMax)->toBe('20.0')
        ->and($candidates[0]->referenceUnit)->toBe('K°U/L')
        ->and($candidates[1]->extractedName)->toBe('Marker Beta')
        ->and($candidates[1]->value)->toBe('8.4')
        ->and($candidates[1]->unit)->toBe('3°U/L')
        ->and($candidates[1]->referenceMin)->toBe('6.0')
        ->and($candidates[1]->referenceMax)->toBe('10.0')
        ->and($candidates[1]->referenceUnit)->toBe('3°U/L');
});

it('skips ambiguous compact unit-first candidates with comma separated numeric groups', function () {
    $path = syntheticCommaRangeUnitFirstInlinePdfPath();

    try {
        $candidates = app(ExtractBiomarkerDrafts::class)($path);
    } finally {
        @unlink($path);
    }

    expect($candidates)->toHaveCount(0);
});

it('uses CMA layout text fallback when compact extraction is ambiguous', function () {
    $layoutText = assistedCmaLayoutText([
        assistedCmaLayoutRow('Marker Alpha°', '10,1', 'umol/L', '5,0 - 15,0'),
        assistedCmaLayoutRow('Marker Beta C°', '0,81', 'mg/L', '0,68 - 1,22'),
    ]);

    app()->instance(ExtractPdfLayoutText::class, new class($layoutText) extends ExtractPdfLayoutText
    {
        public function __construct(private readonly string $layoutText) {}

        public function __invoke(string $pdfPath): ?string
        {
            return $this->layoutText;
        }
    });

    $path = syntheticCommaRangeUnitFirstInlinePdfPath();

    try {
        $candidates = app(ExtractBiomarkerDrafts::class)($path);
    } finally {
        @unlink($path);
    }

    expect($candidates)->toHaveCount(2);

    expect($candidates[0]->extractedName)->toBe('Marker Alpha')
        ->and($candidates[0]->value)->toBe('10.1')
        ->and($candidates[0]->unit)->toBe('umol/L')
        ->and($candidates[0]->referenceMin)->toBe('5.0')
        ->and($candidates[0]->referenceMax)->toBe('15.0')
        ->and($candidates[0]->confidence)->toBe(0.85);

    expect($candidates[1]->extractedName)->toBe('Marker Beta C')
        ->and($candidates[1]->value)->toBe('0.81')
        ->and($candidates[1]->unit)->toBe('mg/L')
        ->and($candidates[1]->referenceMin)->toBe('0.68')
        ->and($candidates[1]->referenceMax)->toBe('1.22')
        ->and($candidates[1]->confidence)->toBe(0.85);
});

it('auto-confirms exact catalog matches from CMA layout ranges and one-sided references', function () {
    Storage::fake('local');

    $layoutText = assistedCmaLayoutText([
        assistedCmaLayoutRow('Marker Alpha°', '10,1', 'umol/L', '5,0 - 15,0'),
        assistedCmaLayoutRow('Marker Gamma', '110', 'mL/min/1,73m2', '>=90'),
    ]);

    app()->instance(ExtractPdfLayoutText::class, new class($layoutText) extends ExtractPdfLayoutText
    {
        public function __construct(private readonly string $layoutText) {}

        public function __invoke(string $pdfPath): ?string
        {
            return $this->layoutText;
        }
    });

    $user = User::factory()->create();
    $markerAlpha = Biomarker::factory()->for($user)->create(['name' => 'Marker Alpha']);
    $markerGamma = Biomarker::factory()->for($user)->create(['name' => 'Marker Gamma']);
    $bloodTest = BloodTest::factory()->for($user)->create(['status' => 'uploaded']);
    $pdfPath = syntheticCommaRangeUnitFirstInlinePdfPath();
    $storagePath = 'blood-test-documents/synthetic-cma-auto-confirm.pdf';

    try {
        Storage::disk('local')->put($storagePath, file_get_contents($pdfPath));
    } finally {
        @unlink($pdfPath);
    }

    $document = BloodTestDocument::factory()->for($bloodTest)->create([
        'storage_disk' => 'local',
        'storage_path' => $storagePath,
        'mime_type' => 'application/pdf',
        'file_size' => Storage::disk('local')->size($storagePath),
    ]);

    $run = app(RunBloodTestExtraction::class)($document);

    $alphaResult = BiomarkerResult::query()
        ->where('blood_test_id', $bloodTest->id)
        ->where('biomarker_id', $markerAlpha->id)
        ->firstOrFail();
    $gammaResult = BiomarkerResult::query()
        ->where('blood_test_id', $bloodTest->id)
        ->where('biomarker_id', $markerGamma->id)
        ->firstOrFail();

    expect($run->status)->toBe('done')
        ->and($run->candidate_count)->toBe(2)
        ->and($alphaResult->confirmed_at)->not->toBeNull()
        ->and($alphaResult->status)->toBe('normal')
        ->and((float) $alphaResult->extraction_confidence)->toBe(0.85)
        ->and($gammaResult->confirmed_at)->not->toBeNull()
        ->and($gammaResult->status)->toBe('normal')
        ->and((float) $gammaResult->extraction_confidence)->toBe(0.85)
        ->and($bloodTest->refresh()->status)->toBe('confirmed');
});

it('auto-imports and auto-confirms trusted CMA layout candidates when the catalog is empty', function () {
    Storage::fake('local');

    $layoutText = assistedCmaLayoutText([
        assistedCmaLayoutRow('Marker Alpha°', '10,1', 'umol/L', '5,0 - 15,0'),
        assistedCmaLayoutRow('Marker Gamma', '110', 'mL/min/1,73m2', '>=90'),
    ]);

    app()->instance(ExtractPdfLayoutText::class, new class($layoutText) extends ExtractPdfLayoutText
    {
        public function __construct(private readonly string $layoutText) {}

        public function __invoke(string $pdfPath): ?string
        {
            return $this->layoutText;
        }
    });

    $user = User::factory()->create();
    $bloodTest = BloodTest::factory()->for($user)->create(['status' => 'uploaded']);
    $pdfPath = syntheticCommaRangeUnitFirstInlinePdfPath();
    $storagePath = 'blood-test-documents/synthetic-cma-empty-catalog.pdf';

    try {
        Storage::disk('local')->put($storagePath, file_get_contents($pdfPath));
    } finally {
        @unlink($pdfPath);
    }

    $document = BloodTestDocument::factory()->for($bloodTest)->create([
        'storage_disk' => 'local',
        'storage_path' => $storagePath,
        'mime_type' => 'application/pdf',
        'file_size' => Storage::disk('local')->size($storagePath),
    ]);

    $run = app(RunBloodTestExtraction::class)($document);

    $biomarkers = Biomarker::query()
        ->where('user_id', $user->id)
        ->orderBy('name')
        ->get();
    $results = BiomarkerResult::query()
        ->where('blood_test_id', $bloodTest->id)
        ->orderBy('extracted_name')
        ->get();

    expect($run->status)->toBe('done')
        ->and($run->candidate_count)->toBe(2)
        ->and($biomarkers)->toHaveCount(2)
        ->and($biomarkers->pluck('name')->all())->toBe(['Marker Alpha', 'Marker Gamma'])
        ->and($biomarkers->pluck('default_unit')->all())->toBe(['umol/L', 'mL/min/1,73m2'])
        ->and($results)->toHaveCount(2)
        ->and($results->pluck('biomarker_id')->filter()->count())->toBe(2)
        ->and($results->pluck('confirmed_at')->filter()->count())->toBe(2)
        ->and($results->pluck('status')->all())->toBe(['normal', 'normal'])
        ->and($results->pluck('extraction_confidence')->map(fn (string $confidence): float => (float) $confidence)->all())
        ->toBe([0.85, 0.85])
        ->and(BiomarkerResult::query()->where('blood_test_id', $bloodTest->id)->whereNull('confirmed_at')->count())->toBe(0)
        ->and($bloodTest->refresh()->status)->toBe('confirmed');
});

it('keeps duplicate trusted CMA layout names in review when the catalog is empty', function () {
    Storage::fake('local');

    $layoutText = assistedCmaLayoutText([
        assistedCmaLayoutRow('Repeated Marker°', '10,1', 'umol/L', '5,0 - 15,0'),
        assistedCmaLayoutRow('Repeated Marker°', '11,2', 'umol/L', '5,0 - 15,0'),
    ]);

    app()->instance(ExtractPdfLayoutText::class, new class($layoutText) extends ExtractPdfLayoutText
    {
        public function __construct(private readonly string $layoutText) {}

        public function __invoke(string $pdfPath): ?string
        {
            return $this->layoutText;
        }
    });

    $user = User::factory()->create();
    $bloodTest = BloodTest::factory()->for($user)->create(['status' => 'uploaded']);
    $pdfPath = syntheticCommaRangeUnitFirstInlinePdfPath();
    $storagePath = 'blood-test-documents/synthetic-cma-duplicate-empty-catalog.pdf';

    try {
        Storage::disk('local')->put($storagePath, file_get_contents($pdfPath));
    } finally {
        @unlink($pdfPath);
    }

    $document = BloodTestDocument::factory()->for($bloodTest)->create([
        'storage_disk' => 'local',
        'storage_path' => $storagePath,
        'mime_type' => 'application/pdf',
        'file_size' => Storage::disk('local')->size($storagePath),
    ]);

    $run = app(RunBloodTestExtraction::class)($document);

    $drafts = BiomarkerResult::query()
        ->where('blood_test_id', $bloodTest->id)
        ->orderBy('id')
        ->get();

    expect($run->status)->toBe('done')
        ->and($run->candidate_count)->toBe(2)
        ->and(Biomarker::query()->where('user_id', $user->id)->count())->toBe(0)
        ->and($drafts)->toHaveCount(2)
        ->and($drafts->pluck('biomarker_id')->all())->toBe([null, null])
        ->and($drafts->pluck('confirmed_at')->all())->toBe([null, null])
        ->and($drafts->pluck('extraction_confidence')->map(fn (string $confidence): float => (float) $confidence)->all())
        ->toBe([0.84, 0.84])
        ->and($bloodTest->refresh()->status)->toBe('reviewing');
});

it('auto-imports trusted CMA duplicate names when units disambiguate them', function () {
    Storage::fake('local');

    $layoutText = assistedCmaLayoutText([
        assistedCmaLayoutRow('Repeated Marker°', '35', '%', '20 - 40'),
        assistedCmaLayoutRow('Repeated Marker°', '2,1', 'K/uL', '1,0 - 3,0'),
    ]);

    app()->instance(ExtractPdfLayoutText::class, new class($layoutText) extends ExtractPdfLayoutText
    {
        public function __construct(private readonly string $layoutText) {}

        public function __invoke(string $pdfPath): ?string
        {
            return $this->layoutText;
        }
    });

    $user = User::factory()->create();
    $bloodTest = BloodTest::factory()->for($user)->create(['status' => 'uploaded']);
    $pdfPath = syntheticCommaRangeUnitFirstInlinePdfPath();
    $storagePath = 'blood-test-documents/synthetic-cma-duplicate-unit-disambiguated.pdf';

    try {
        Storage::disk('local')->put($storagePath, file_get_contents($pdfPath));
    } finally {
        @unlink($pdfPath);
    }

    $document = BloodTestDocument::factory()->for($bloodTest)->create([
        'storage_disk' => 'local',
        'storage_path' => $storagePath,
        'mime_type' => 'application/pdf',
        'file_size' => Storage::disk('local')->size($storagePath),
    ]);

    $run = app(RunBloodTestExtraction::class)($document);

    $biomarkers = Biomarker::query()
        ->where('user_id', $user->id)
        ->orderBy('name')
        ->get();
    $results = BiomarkerResult::query()
        ->where('blood_test_id', $bloodTest->id)
        ->orderBy('extracted_name')
        ->get();

    expect($run->status)->toBe('done')
        ->and($run->candidate_count)->toBe(2)
        ->and($biomarkers)->toHaveCount(2)
        ->and($biomarkers->pluck('name')->all())->toBe(['Repeated Marker (%)', 'Repeated Marker (K/uL)'])
        ->and($biomarkers->pluck('default_unit')->all())->toBe(['%', 'K/uL'])
        ->and($results)->toHaveCount(2)
        ->and($results->pluck('biomarker_id')->filter()->count())->toBe(2)
        ->and($results->pluck('confirmed_at')->filter()->count())->toBe(2)
        ->and($results->pluck('extraction_confidence')->map(fn (string $confidence): float => (float) $confidence)->all())
        ->toBe([0.85, 0.85])
        ->and(BiomarkerResult::query()->where('blood_test_id', $bloodTest->id)->whereNull('confirmed_at')->count())->toBe(0)
        ->and($bloodTest->refresh()->status)->toBe('confirmed');
});

it('auto-imports trusted CMA hemoglobin separately from hemoglobin A1c catalog entries', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    Biomarker::factory()->for($user)->create([
        'name' => 'Hemoglobine A1c (IFCC)*',
        'default_unit' => 'mmol/mol',
        'reference_unit' => 'mmol/mol',
    ]);
    Biomarker::factory()->for($user)->create([
        'name' => 'Hemoglobine A1c (NGSP)*',
        'default_unit' => '%',
        'reference_unit' => '%',
    ]);
    $bloodTest = bloodTestWithStoredDocument($user);

    runExtractionWithCandidates($bloodTest->documents()->firstOrFail(), [
        new ExtractedBiomarkerCandidate(
            extractedName: 'Hemoglobine',
            value: '14.2',
            unit: 'g/dL',
            referenceMin: '13.0',
            referenceMax: '17.0',
            referenceUnit: 'g/dL',
            confidence: 0.85,
            sourceSnippet: 'synthetic CMA hemoglobin row',
            source: ExtractedBiomarkerCandidate::SOURCE_CMA_TABULAR,
        ),
    ]);

    $biomarker = Biomarker::query()
        ->where('user_id', $user->id)
        ->where('name', 'Hemoglobine')
        ->firstOrFail();
    $result = BiomarkerResult::query()
        ->where('blood_test_id', $bloodTest->id)
        ->firstOrFail();

    expect(Biomarker::query()->where('user_id', $user->id)->count())->toBe(3)
        ->and($result->biomarker_id)->toBe($biomarker->id)
        ->and($result->confirmed_at)->not->toBeNull()
        ->and($result->status)->toBe('normal')
        ->and((float) $result->extraction_confidence)->toBe(0.85)
        ->and($bloodTest->refresh()->status)->toBe('confirmed');
});

it('auto-imports trusted CMA standard CRP separately from high-sensitivity CRP catalog entries', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $highSensitivityCrp = Biomarker::factory()->for($user)->create([
        'name' => 'CRP hooggevoelig*',
        'default_unit' => 'mg/L',
        'reference_unit' => 'mg/L',
    ]);
    $bloodTest = bloodTestWithStoredDocument($user);

    runExtractionWithCandidates($bloodTest->documents()->firstOrFail(), [
        new ExtractedBiomarkerCandidate(
            extractedName: 'CRP',
            value: '4.2',
            unit: 'mg/L',
            referenceMin: null,
            referenceMax: null,
            referenceUnit: 'mg/L',
            confidence: 0.85,
            sourceSnippet: 'synthetic CMA CRP row',
            source: ExtractedBiomarkerCandidate::SOURCE_CMA_TABULAR,
        ),
    ]);

    $standardCrp = Biomarker::query()
        ->where('user_id', $user->id)
        ->where('name', 'CRP')
        ->firstOrFail();
    $result = BiomarkerResult::query()
        ->where('blood_test_id', $bloodTest->id)
        ->firstOrFail();

    expect(Biomarker::query()->where('user_id', $user->id)->count())->toBe(2)
        ->and($standardCrp->is($highSensitivityCrp))->toBeFalse()
        ->and($result->biomarker_id)->toBe($standardCrp->id)
        ->and($result->confirmed_at)->toBeNull()
        ->and($result->status)->toBe('unknown')
        ->and((float) $result->extraction_confidence)->toBe(0.84)
        ->and($bloodTest->refresh()->status)->toBe('reviewing');
});

it('keeps non-curated same-unit trusted CMA prefix siblings in review', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    Biomarker::factory()->for($user)->create([
        'name' => 'Marker Alpha Extra',
        'default_unit' => 'mg/L',
        'reference_unit' => 'mg/L',
    ]);
    $bloodTest = bloodTestWithStoredDocument($user);

    runExtractionWithCandidates($bloodTest->documents()->firstOrFail(), [
        new ExtractedBiomarkerCandidate(
            extractedName: 'Marker Alpha',
            value: '12.4',
            unit: 'mg/L',
            referenceMin: '10',
            referenceMax: '20',
            referenceUnit: 'mg/L',
            confidence: 0.85,
            sourceSnippet: 'synthetic non-curated prefix row',
            source: ExtractedBiomarkerCandidate::SOURCE_CMA_TABULAR,
        ),
    ]);

    $result = BiomarkerResult::query()
        ->where('blood_test_id', $bloodTest->id)
        ->firstOrFail();

    expect(Biomarker::query()->where('user_id', $user->id)->count())->toBe(1)
        ->and($result->biomarker_id)->toBeNull()
        ->and($result->confirmed_at)->toBeNull()
        ->and($result->status)->toBe('unknown')
        ->and((float) $result->extraction_confidence)->toBe(0.84)
        ->and($bloodTest->refresh()->status)->toBe('reviewing');
});

it('creates extracted draft rows and an extraction run after pdf upload', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $ferritin = Biomarker::factory()->for($user)->create(['name' => 'Ferritin']);
    $file = new UploadedFile(
        base_path('tests/Fixtures/assisted-extraction-lab.pdf'),
        'assisted-extraction-lab.pdf',
        'application/pdf',
        null,
        true,
    );

    $this->actingAs($user)
        ->post(route('blood-tests.store'), [
            'document' => $file,
            'test_date' => '2026-06-18',
            'lab_name' => 'Fixture Lab',
            'title' => 'Assisted extraction fixture',
        ])
        ->assertRedirect();

    $bloodTest = BloodTest::query()->firstOrFail();
    $run = ExtractionRun::query()->firstOrFail();

    expect($bloodTest->status)->toBe('reviewing')
        ->and($run->blood_test_id)->toBe($bloodTest->id)
        ->and($run->engine)->toBe('smalot/pdfparser')
        ->and($run->status)->toBe('done')
        ->and($run->candidate_count)->toBe(3);

    $drafts = BiomarkerResult::query()
        ->where('blood_test_id', $bloodTest->id)
        ->where('entry_source', 'extracted')
        ->orderBy('id')
        ->get();

    expect($drafts)->toHaveCount(3);

    $ferritinDraft = $drafts->firstWhere('extracted_name', 'Ferritin');

    expect($ferritinDraft)->not->toBeNull()
        ->and($ferritinDraft->biomarker_id)->toBe($ferritin->id)
        ->and((float) $ferritinDraft->value)->toBe(42.0)
        ->and($ferritinDraft->unit)->toBe('ug/L')
        ->and((float) $ferritinDraft->reference_min)->toBe(30.0)
        ->and((float) $ferritinDraft->reference_max)->toBe(150.0)
        ->and($ferritinDraft->reference_unit)->toBe('ug/L')
        ->and($ferritinDraft->confirmed_at)->not->toBeNull()
        ->and($ferritinDraft->status)->toBe('normal')
        ->and($ferritinDraft->extraction_confidence)->not->toBeNull()
        ->and($ferritinDraft->source_snippet)->toContain('Ferritin 42 ug/L');

    expect($drafts->whereNull('biomarker_id')->pluck('extracted_name')->all())
        ->toContain('CRP', 'Vitamin D');
});

it('creates extracted draft rows from tabular positioned pdf uploads', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $markerAlpha = Biomarker::factory()->for($user)->create(['name' => 'Marker Alpha']);
    $path = syntheticTabularPdfPath();
    $file = new UploadedFile(
        $path,
        'tabular-extraction.pdf',
        'application/pdf',
        null,
        true,
    );

    try {
        $this->actingAs($user)
            ->post(route('blood-tests.store'), [
                'document' => $file,
                'test_date' => '2026-06-19',
                'lab_name' => 'Synthetic Lab',
                'title' => 'Tabular extraction fixture',
            ])
            ->assertRedirect();
    } finally {
        @unlink($path);
    }

    $bloodTest = BloodTest::query()->firstOrFail();
    $run = ExtractionRun::query()->firstOrFail();

    expect($bloodTest->status)->toBe('reviewing')
        ->and($run->engine)->toBe('smalot/pdfparser')
        ->and($run->status)->toBe('done')
        ->and($run->candidate_count)->toBe(2);

    $drafts = BiomarkerResult::query()
        ->where('blood_test_id', $bloodTest->id)
        ->where('entry_source', 'extracted')
        ->orderBy('id')
        ->get();

    expect($drafts)->toHaveCount(2);

    $alphaDraft = $drafts->firstWhere('extracted_name', 'Marker Alpha');
    $betaDraft = $drafts->firstWhere('extracted_name', 'Marker Beta');

    expect($alphaDraft)->not->toBeNull()
        ->and($alphaDraft->biomarker_id)->toBe($markerAlpha->id)
        ->and((float) $alphaDraft->value)->toBe(12.4)
        ->and($alphaDraft->unit)->toBe('mg/L')
        ->and((float) $alphaDraft->reference_min)->toBe(10.0)
        ->and((float) $alphaDraft->reference_max)->toBe(20.0)
        ->and($alphaDraft->reference_unit)->toBe('mg/L')
        ->and($alphaDraft->status)->toBe('normal')
        ->and($alphaDraft->confirmed_at)->not->toBeNull()
        ->and((float) $alphaDraft->extraction_confidence)->toBe(0.85);

    expect($betaDraft)->not->toBeNull()
        ->and($betaDraft->biomarker_id)->toBeNull()
        ->and((float) $betaDraft->value)->toBe(5.0)
        ->and($betaDraft->unit)->toBe('U/mL')
        ->and($betaDraft->reference_min)->toBeNull()
        ->and((float) $betaDraft->reference_max)->toBe(8.0)
        ->and($betaDraft->reference_unit)->toBe('U/mL')
        ->and($betaDraft->confirmed_at)->toBeNull()
        ->and((float) $betaDraft->extraction_confidence)->toBe(0.75);

    expect($drafts->pluck('extracted_name')->all())->not->toContain('Marker Gamma');
});

it('auto-confirms clean inferred-value tabular rows when the biomarker is already in the catalog', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $markerAlpha = Biomarker::factory()->for($user)->create(['name' => 'Marker Alpha']);
    $path = syntheticInferredValueTabularPdfPath();
    $file = new UploadedFile(
        $path,
        'inferred-value-tabular.pdf',
        'application/pdf',
        null,
        true,
    );

    try {
        $this->actingAs($user)
            ->post(route('blood-tests.store'), [
                'document' => $file,
                'test_date' => '2026-06-19',
                'lab_name' => 'Synthetic Lab',
                'title' => 'Inferred value tabular fixture',
            ])
            ->assertRedirect();
    } finally {
        @unlink($path);
    }

    $bloodTest = BloodTest::query()->firstOrFail();
    $result = BiomarkerResult::query()
        ->where('blood_test_id', $bloodTest->id)
        ->where('biomarker_id', $markerAlpha->id)
        ->firstOrFail();

    expect($bloodTest->status)->toBe('confirmed')
        ->and($result->confirmed_at)->not->toBeNull()
        ->and($result->entry_source)->toBe('extracted')
        ->and((float) $result->extraction_confidence)->toBe(0.85)
        ->and(BiomarkerResult::query()->whereNull('confirmed_at')->count())->toBe(0);
});

it('auto-imports clean CMA-style inferred-value tabular rows when the catalog is empty', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $path = syntheticInferredValueTabularPdfPath();
    $file = new UploadedFile(
        $path,
        'cma-inferred-value-tabular.pdf',
        'application/pdf',
        null,
        true,
    );

    try {
        $this->actingAs($user)
            ->post(route('blood-tests.store'), [
                'document' => $file,
                'test_date' => '2026-06-19',
                'lab_name' => 'Synthetic CMA Lab',
                'title' => 'CMA inferred value fixture',
            ])
            ->assertRedirect();
    } finally {
        @unlink($path);
    }

    $bloodTest = BloodTest::query()->firstOrFail();
    $biomarker = Biomarker::query()
        ->where('user_id', $user->id)
        ->where('name', 'Marker Alpha')
        ->firstOrFail();
    $result = BiomarkerResult::query()
        ->where('blood_test_id', $bloodTest->id)
        ->where('biomarker_id', $biomarker->id)
        ->firstOrFail();

    expect($biomarker->default_unit)->toBe('mg/L')
        ->and($result->confirmed_at)->not->toBeNull()
        ->and($result->entry_source)->toBe('extracted')
        ->and($result->status)->toBe('normal')
        ->and((float) $result->extraction_confidence)->toBe(0.85)
        ->and(BiomarkerResult::query()->whereNull('confirmed_at')->count())->toBe(0)
        ->and($bloodTest->refresh()->status)->toBe('confirmed');
});

it('routes trusted CMA values without references to review instead of auto-confirming an unclassifiable value', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $bloodTest = bloodTestWithStoredDocument($user);

    runExtractionWithCandidates($bloodTest->documents()->firstOrFail(), [
        new ExtractedBiomarkerCandidate(
            extractedName: 'Marker Without Reference',
            value: '12.4',
            unit: 'mg/L',
            referenceMin: null,
            referenceMax: null,
            referenceUnit: 'mg/L',
            confidence: 0.85,
            sourceSnippet: 'synthetic CMA row without reference',
            source: ExtractedBiomarkerCandidate::SOURCE_CMA_TABULAR,
        ),
    ]);

    $biomarker = Biomarker::query()
        ->where('user_id', $user->id)
        ->where('name', 'Marker Without Reference')
        ->firstOrFail();
    $result = BiomarkerResult::query()
        ->where('blood_test_id', $bloodTest->id)
        ->where('biomarker_id', $biomarker->id)
        ->firstOrFail();

    expect($biomarker->default_unit)->toBe('mg/L')
        ->and($biomarker->reference_min)->toBeNull()
        ->and($biomarker->reference_max)->toBeNull()
        ->and($result->confirmed_at)->toBeNull()
        ->and($result->status)->toBe('unknown')
        ->and((float) $result->extraction_confidence)->toBe(0.84)
        ->and(BiomarkerResult::query()->whereNull('confirmed_at')->count())->toBe(1)
        ->and($bloodTest->refresh()->status)->toBe('reviewing');
});

it('keeps a high-confidence catalog-matched value without a reference range as a review draft', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    Biomarker::factory()->for($user)->create(['name' => 'Marker Alpha', 'default_unit' => 'mg/L']);
    $bloodTest = bloodTestWithStoredDocument($user);

    runExtractionWithCandidates($bloodTest->documents()->firstOrFail(), [
        new ExtractedBiomarkerCandidate(
            extractedName: 'Marker Alpha',
            value: '12.4',
            unit: 'mg/L',
            referenceMin: null,
            referenceMax: null,
            referenceUnit: 'mg/L',
            confidence: 0.95,
            sourceSnippet: 'synthetic catalog match without reference',
            source: ExtractedBiomarkerCandidate::SOURCE_CMA_TABULAR,
        ),
    ]);

    $result = BiomarkerResult::query()->where('blood_test_id', $bloodTest->id)->firstOrFail();

    // No reference range means the status is "unknown"; an unclassifiable value
    // must not be auto-confirmed even at high confidence, so it stays a draft.
    expect($result->confirmed_at)->toBeNull()
        ->and($result->status)->toBe('unknown')
        ->and((float) $result->extraction_confidence)->toBe(0.84)
        ->and($bloodTest->refresh()->status)->toBe('reviewing');
});

it('does not fold a different analyte into a biomarker auto-imported earlier in the same run', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $bloodTest = bloodTestWithStoredDocument($user);

    // "Glucose" is auto-imported (unit but no reference range, so created yet not
    // auto-confirmed). "Glucose nuchter" is a genuinely different analyte whose name
    // has the just-created biomarker as a catalog prefix: it must not be folded into
    // "Glucose" (overwriting its value and losing its own identity).
    runExtractionWithCandidates($bloodTest->documents()->firstOrFail(), [
        new ExtractedBiomarkerCandidate(
            extractedName: 'Glucose',
            value: '5.1',
            unit: 'mmol/L',
            referenceMin: null,
            referenceMax: null,
            referenceUnit: 'mmol/L',
            confidence: 0.85,
            sourceSnippet: 'synthetic CMA glucose row',
            source: ExtractedBiomarkerCandidate::SOURCE_CMA_TABULAR,
        ),
        new ExtractedBiomarkerCandidate(
            extractedName: 'Glucose nuchter',
            value: '4.8',
            unit: 'mmol/L',
            referenceMin: null,
            referenceMax: null,
            referenceUnit: 'mmol/L',
            confidence: 0.85,
            sourceSnippet: 'synthetic CMA fasting glucose row',
            source: ExtractedBiomarkerCandidate::SOURCE_CMA_TABULAR,
        ),
    ]);

    $glucose = Biomarker::query()
        ->where('user_id', $user->id)
        ->where('name', 'Glucose')
        ->firstOrFail();
    $glucoseResult = BiomarkerResult::query()
        ->where('blood_test_id', $bloodTest->id)
        ->where('biomarker_id', $glucose->id)
        ->firstOrFail();
    $unanchoredDraft = BiomarkerResult::query()
        ->where('blood_test_id', $bloodTest->id)
        ->whereNull('biomarker_id')
        ->firstOrFail();

    expect(BiomarkerResult::query()->where('blood_test_id', $bloodTest->id)->count())->toBe(2)
        ->and(Biomarker::query()->where('user_id', $user->id)->where('name', 'Glucose nuchter')->exists())->toBeFalse()
        ->and((float) $glucoseResult->value)->toBe(5.1)
        ->and($unanchoredDraft->extracted_name)->toBe('Glucose nuchter')
        ->and((float) $unanchoredDraft->value)->toBe(4.8)
        ->and($unanchoredDraft->confirmed_at)->toBeNull();
});

it('routes an ambiguous dotted-thousands value to review instead of auto-confirming a possible misparse', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    Biomarker::factory()->for($user)->create(['name' => 'Marker Alpha', 'default_unit' => 'mg/L']);
    $bloodTest = bloodTestWithStoredDocument($user);

    // "1.234" is ambiguous: a decimal (1.234) or a European thousands group (1234).
    // The literal catalog match, unit and full reference range would otherwise
    // auto-confirm it, so the ambiguous numeric format alone must keep it in review.
    runExtractionWithCandidates($bloodTest->documents()->firstOrFail(), [
        new ExtractedBiomarkerCandidate(
            extractedName: 'Marker Alpha',
            value: '1.234',
            unit: 'mg/L',
            referenceMin: '1',
            referenceMax: '2000',
            referenceUnit: 'mg/L',
            confidence: 0.95,
            sourceSnippet: 'synthetic ambiguous thousands row',
            source: ExtractedBiomarkerCandidate::SOURCE_CMA_TABULAR,
        ),
    ]);

    $result = BiomarkerResult::query()->where('blood_test_id', $bloodTest->id)->firstOrFail();

    expect($result->confirmed_at)->toBeNull()
        ->and($result->status)->toBe('unknown')
        ->and((float) $result->extraction_confidence)->toBe(0.84)
        ->and($bloodTest->refresh()->status)->toBe('reviewing');
});

it('drops trusted CMA candidates without a unit or reference instead of creating review friction', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $bloodTest = bloodTestWithStoredDocument($user);

    runExtractionWithCandidates($bloodTest->documents()->firstOrFail(), [
        new ExtractedBiomarkerCandidate(
            extractedName: 'Marker Confirmed',
            value: '12.4',
            unit: 'mg/L',
            referenceMin: '10',
            referenceMax: '20',
            referenceUnit: 'mg/L',
            confidence: 0.85,
            sourceSnippet: 'synthetic trusted CMA row',
            source: ExtractedBiomarkerCandidate::SOURCE_CMA_TABULAR,
        ),
        new ExtractedBiomarkerCandidate(
            extractedName: 'Marker Missing Unit',
            value: '7.1',
            unit: '',
            referenceMin: null,
            referenceMax: null,
            referenceUnit: null,
            confidence: 0.60,
            sourceSnippet: 'synthetic non-actionable CMA row',
            source: ExtractedBiomarkerCandidate::SOURCE_CMA_TABULAR,
        ),
    ]);

    $results = BiomarkerResult::query()
        ->where('blood_test_id', $bloodTest->id)
        ->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->confirmed_at)->not->toBeNull()
        ->and(Biomarker::query()->where('user_id', $user->id)->count())->toBe(1)
        ->and(BiomarkerResult::query()->whereNull('confirmed_at')->count())->toBe(0)
        ->and($bloodTest->refresh()->status)->toBe('confirmed');
});

it('anchors a noisy extracted name to the owners catalog without creating a biomarker', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $marker = Biomarker::factory()->for($user)->create(['name' => 'Marker Alpha']);
    $bloodTest = bloodTestWithStoredDocument($user);

    runExtractionWithCandidates($bloodTest->documents()->firstOrFail(), [
        new ExtractedBiomarkerCandidate(
            extractedName: 'Marker Alpha sentence tail',
            value: '12.4',
            unit: 'mg/L',
            referenceMin: '10',
            referenceMax: '20',
            referenceUnit: 'mg/L',
            confidence: 0.6,
            sourceSnippet: 'synthetic anchored row',
        ),
    ]);

    $result = BiomarkerResult::query()->where('blood_test_id', $bloodTest->id)->firstOrFail();

    expect(Biomarker::query()->where('user_id', $user->id)->count())->toBe(1)
        ->and($result->biomarker_id)->toBe($marker->id)
        ->and($result->extracted_name)->toBe('Marker Alpha')
        ->and($result->confirmed_at)->toBeNull();
});

it('normalizes extracted name whitespace before catalog matching', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $marker = Biomarker::factory()->for($user)->create(['name' => 'Marker Alpha']);
    $bloodTest = bloodTestWithStoredDocument($user);

    runExtractionWithCandidates($bloodTest->documents()->firstOrFail(), [
        new ExtractedBiomarkerCandidate(
            extractedName: " \tMarker   Alpha\n",
            value: '12.4',
            unit: 'mg/L',
            referenceMin: '10',
            referenceMax: '20',
            referenceUnit: 'mg/L',
            confidence: 0.95,
            sourceSnippet: 'synthetic whitespace-normalized name row',
        ),
    ]);

    $result = BiomarkerResult::query()->where('blood_test_id', $bloodTest->id)->firstOrFail();

    expect($result->biomarker_id)->toBe($marker->id)
        ->and($result->extracted_name)->toBe('Marker Alpha')
        ->and($result->confirmed_at)->not->toBeNull()
        ->and($result->status)->toBe('normal')
        ->and($bloodTest->refresh()->status)->toBe('confirmed');
});

it('normalizes non-breaking extracted name whitespace before catalog matching', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $marker = Biomarker::factory()->for($user)->create(['name' => 'Marker Alpha']);
    $bloodTest = bloodTestWithStoredDocument($user);

    runExtractionWithCandidates($bloodTest->documents()->firstOrFail(), [
        new ExtractedBiomarkerCandidate(
            extractedName: "\u{00A0}Marker\u{00A0}Alpha\u{00A0}",
            value: '12.4',
            unit: 'mg/L',
            referenceMin: '10',
            referenceMax: '20',
            referenceUnit: 'mg/L',
            confidence: 0.95,
            sourceSnippet: 'synthetic non-breaking whitespace name row',
        ),
    ]);

    $result = BiomarkerResult::query()->where('blood_test_id', $bloodTest->id)->firstOrFail();

    expect($result->biomarker_id)->toBe($marker->id)
        ->and($result->extracted_name)->toBe('Marker Alpha')
        ->and($result->confirmed_at)->not->toBeNull()
        ->and($result->status)->toBe('normal')
        ->and($bloodTest->refresh()->status)->toBe('confirmed');
});

it('anchors catalog prefixes followed by punctuation as drafts', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $marker = Biomarker::factory()->for($user)->create(['name' => 'Marker Alpha']);
    $bloodTest = bloodTestWithStoredDocument($user);

    runExtractionWithCandidates($bloodTest->documents()->firstOrFail(), [
        new ExtractedBiomarkerCandidate(
            extractedName: 'Marker Alpha: sentence tail',
            value: '12.4',
            unit: 'mg/L',
            referenceMin: '10',
            referenceMax: '20',
            referenceUnit: 'mg/L',
            confidence: 0.95,
            sourceSnippet: 'synthetic punctuation-prefixed row',
        ),
    ]);

    $result = BiomarkerResult::query()->where('blood_test_id', $bloodTest->id)->firstOrFail();

    expect(Biomarker::query()->where('user_id', $user->id)->count())->toBe(1)
        ->and($result->biomarker_id)->toBe($marker->id)
        ->and($result->extracted_name)->toBe('Marker Alpha')
        ->and($result->confirmed_at)->toBeNull()
        ->and($result->status)->toBe('unknown');
});

it('keeps high confidence prefix-only catalog matches as drafts', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $marker = Biomarker::factory()->for($user)->create(['name' => 'Marker Alpha']);
    $bloodTest = bloodTestWithStoredDocument($user);

    runExtractionWithCandidates($bloodTest->documents()->firstOrFail(), [
        new ExtractedBiomarkerCandidate(
            extractedName: 'Marker Alpha sentence tail',
            value: '12.4',
            unit: 'mg/L',
            referenceMin: '10',
            referenceMax: '20',
            referenceUnit: 'mg/L',
            confidence: 0.95,
            sourceSnippet: 'synthetic noisy high-confidence row',
        ),
    ]);

    $result = BiomarkerResult::query()->where('blood_test_id', $bloodTest->id)->firstOrFail();

    expect($result->biomarker_id)->toBe($marker->id)
        ->and($result->extracted_name)->toBe('Marker Alpha')
        ->and($result->confirmed_at)->toBeNull()
        ->and($result->status)->toBe('unknown')
        ->and($bloodTest->refresh()->status)->toBe('reviewing');
});

it('leaves ambiguous prefix-only catalog matches unanchored', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    Biomarker::factory()->for($user)->create(['name' => 'Marker Alpha']);
    Biomarker::factory()->for($user)->create(['name' => 'Marker Beta', 'short_name' => 'Marker Alpha']);
    $bloodTest = bloodTestWithStoredDocument($user);

    runExtractionWithCandidates($bloodTest->documents()->firstOrFail(), [
        new ExtractedBiomarkerCandidate(
            extractedName: 'Marker Alpha sentence tail',
            value: '12.4',
            unit: 'mg/L',
            referenceMin: '10',
            referenceMax: '20',
            referenceUnit: 'mg/L',
            confidence: 0.95,
            sourceSnippet: 'synthetic ambiguous prefix row',
        ),
    ]);

    $result = BiomarkerResult::query()->where('blood_test_id', $bloodTest->id)->firstOrFail();

    expect($result->confirmed_at)->toBeNull()
        ->and($result->biomarker_id)->toBeNull()
        ->and($result->extracted_name)->toBe('Marker Alpha sentence tail')
        ->and($result->status)->toBe('unknown')
        ->and($bloodTest->refresh()->status)->toBe('reviewing');
});

it('keeps ambiguous exact catalog alias matches as drafts', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    Biomarker::factory()->for($user)->create(['name' => 'C reactive protein', 'short_name' => 'CRP']);
    Biomarker::factory()->for($user)->create(['name' => 'Creatine reactive protein', 'short_name' => 'CRP']);
    $bloodTest = bloodTestWithStoredDocument($user);

    runExtractionWithCandidates($bloodTest->documents()->firstOrFail(), [
        new ExtractedBiomarkerCandidate(
            extractedName: 'CRP',
            value: '1.2',
            unit: 'mg/L',
            referenceMin: null,
            referenceMax: '5',
            referenceUnit: 'mg/L',
            confidence: 0.95,
            sourceSnippet: 'synthetic ambiguous alias row',
        ),
    ]);

    $result = BiomarkerResult::query()->where('blood_test_id', $bloodTest->id)->firstOrFail();

    expect($result->confirmed_at)->toBeNull()
        ->and($result->biomarker_id)->toBeNull()
        ->and($result->extracted_name)->toBe('CRP')
        ->and($result->status)->toBe('unknown')
        ->and($bloodTest->refresh()->status)->toBe('reviewing');
});

it('auto-confirms high confidence catalog matched candidates and leaves lower confidence rows as drafts', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $marker = Biomarker::factory()->for($user)->create(['name' => 'Marker Alpha']);
    $missingRangeMarker = Biomarker::factory()->for($user)->create(['name' => 'Marker Beta']);
    $missingUnitMarker = Biomarker::factory()->for($user)->create(['name' => 'Marker Gamma']);
    $bloodTest = bloodTestWithStoredDocument($user);

    runExtractionWithCandidates($bloodTest->documents()->firstOrFail(), [
        new ExtractedBiomarkerCandidate(
            extractedName: 'Marker Alpha',
            value: '12.4',
            unit: 'mg/L',
            referenceMin: '10',
            referenceMax: '20',
            referenceUnit: 'mg/L',
            confidence: 0.95,
            sourceSnippet: 'synthetic high row',
        ),
        new ExtractedBiomarkerCandidate(
            extractedName: 'Marker Beta',
            value: '7',
            unit: 'mg/L',
            referenceMin: null,
            referenceMax: null,
            referenceUnit: null,
            confidence: 0.95,
            sourceSnippet: 'synthetic missing range row',
        ),
        new ExtractedBiomarkerCandidate(
            extractedName: 'Marker Gamma',
            value: '9',
            unit: '',
            referenceMin: '3',
            referenceMax: '12',
            referenceUnit: null,
            confidence: 0.95,
            sourceSnippet: 'synthetic missing unit row',
        ),
        new ExtractedBiomarkerCandidate(
            extractedName: 'Unmatched Marker',
            value: '5',
            unit: 'U/mL',
            referenceMin: null,
            referenceMax: '8',
            referenceUnit: 'U/mL',
            confidence: 0.95,
            sourceSnippet: 'synthetic unmatched row',
        ),
    ]);

    $confirmed = BiomarkerResult::query()
        ->where('blood_test_id', $bloodTest->id)
        ->where('biomarker_id', $marker->id)
        ->firstOrFail();
    $draft = BiomarkerResult::query()
        ->where('blood_test_id', $bloodTest->id)
        ->whereNull('biomarker_id')
        ->firstOrFail();
    $missingRangeDraft = BiomarkerResult::query()
        ->where('blood_test_id', $bloodTest->id)
        ->where('biomarker_id', $missingRangeMarker->id)
        ->firstOrFail();
    $missingUnitDraft = BiomarkerResult::query()
        ->where('blood_test_id', $bloodTest->id)
        ->where('biomarker_id', $missingUnitMarker->id)
        ->firstOrFail();

    expect($confirmed->confirmed_at)->not->toBeNull()
        ->and($confirmed->entry_source)->toBe('extracted')
        ->and($confirmed->extracted_name)->toBe('Marker Alpha')
        ->and((float) $confirmed->extraction_confidence)->toBe(0.95)
        ->and($confirmed->status)->toBe('normal')
        ->and($missingRangeDraft->confirmed_at)->toBeNull()
        ->and((float) $missingRangeDraft->extraction_confidence)->toBeLessThan(RunBloodTestExtraction::AUTO_CONFIRM_CONFIDENCE_THRESHOLD)
        ->and($missingUnitDraft->confirmed_at)->toBeNull()
        ->and((float) $missingUnitDraft->extraction_confidence)->toBeLessThan(RunBloodTestExtraction::AUTO_CONFIRM_CONFIDENCE_THRESHOLD)
        ->and($missingUnitDraft->unit)->toBe('')
        ->and($missingUnitDraft->status)->toBe('unknown')
        ->and($draft->confirmed_at)->toBeNull()
        ->and((float) $draft->extraction_confidence)->toBeLessThan(RunBloodTestExtraction::AUTO_CONFIRM_CONFIDENCE_THRESHOLD)
        ->and($draft->extracted_name)->toBe('Unmatched Marker')
        ->and($bloodTest->refresh()->status)->toBe('reviewing');
});

it('keeps catalog lookups bounded while storing a batch of trusted candidates', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $bloodTest = bloodTestWithStoredDocument($user);
    $candidates = [];

    foreach (range(1, 12) as $index) {
        $name = sprintf('Marker %02d', $index);

        Biomarker::query()->create([
            'user_id' => $user->id,
            'name' => $name,
            'active' => true,
        ]);

        $candidates[] = new ExtractedBiomarkerCandidate(
            extractedName: $name,
            value: (string) (10 + $index),
            unit: 'mg/L',
            referenceMin: '5',
            referenceMax: '30',
            referenceUnit: 'mg/L',
            confidence: 0.95,
            sourceSnippet: 'synthetic batch row',
            source: ExtractedBiomarkerCandidate::SOURCE_CMA_TABULAR,
        );
    }

    DB::flushQueryLog();
    DB::enableQueryLog();

    try {
        runExtractionWithCandidates($bloodTest->documents()->firstOrFail(), $candidates);
    } finally {
        $queries = DB::getQueryLog();
        DB::disableQueryLog();
    }

    $catalogSelects = collect($queries)
        ->filter(fn (array $query): bool => str_contains($query['query'], 'from "biomarkers"')
            && str_contains($query['query'], '"user_id" ='))
        ->count();
    $confirmedValueSelects = collect($queries)
        ->filter(fn (array $query): bool => str_contains($query['query'], 'from "biomarker_results"')
            && str_contains($query['query'], '"confirmed_at" is not null'))
        ->count();

    expect($catalogSelects)->toBeLessThanOrEqual(4)
        ->and($confirmedValueSelects)->toBeLessThanOrEqual(2)
        ->and(BiomarkerResult::query()->where('blood_test_id', $bloodTest->id)->whereNotNull('confirmed_at')->count())->toBe(12)
        ->and($bloodTest->refresh()->status)->toBe('confirmed');
});

it('keeps candidates with unparseable reference bounds as drafts', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $marker = Biomarker::factory()->for($user)->create(['name' => 'Marker Alpha']);
    $bloodTest = bloodTestWithStoredDocument($user);

    runExtractionWithCandidates($bloodTest->documents()->firstOrFail(), [
        new ExtractedBiomarkerCandidate(
            extractedName: 'Marker Alpha',
            value: '12.4',
            unit: 'mg/L',
            referenceMin: 'not-a-number',
            referenceMax: '20',
            referenceUnit: 'mg/L',
            confidence: 0.95,
            sourceSnippet: 'synthetic malformed range row',
        ),
    ]);

    $result = BiomarkerResult::query()
        ->where('blood_test_id', $bloodTest->id)
        ->where('biomarker_id', $marker->id)
        ->firstOrFail();

    expect($result->confirmed_at)->toBeNull()
        ->and($result->status)->toBe('unknown')
        ->and((float) $result->extraction_confidence)->toBeLessThan(RunBloodTestExtraction::AUTO_CONFIRM_CONFIDENCE_THRESHOLD)
        ->and($result->reference_min)->toBeNull()
        ->and((float) $result->reference_max)->toBe(20.0)
        ->and($bloodTest->refresh()->status)->toBe('reviewing');
});

it('skips candidates with unparseable values without failing the extraction run', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    Biomarker::factory()->for($user)->create(['name' => 'Marker Alpha']);
    $bloodTest = bloodTestWithStoredDocument($user);

    $run = runExtractionWithCandidates($bloodTest->documents()->firstOrFail(), [
        new ExtractedBiomarkerCandidate(
            extractedName: 'Marker Alpha',
            value: 'not-a-number',
            unit: 'mg/L',
            referenceMin: '10',
            referenceMax: '20',
            referenceUnit: 'mg/L',
            confidence: 0.95,
            sourceSnippet: 'synthetic malformed value row',
        ),
    ]);

    expect($run->status)->toBe('done')
        ->and($run->candidate_count)->toBe(1)
        ->and(BiomarkerResult::query()->where('blood_test_id', $bloodTest->id)->count())->toBe(0)
        ->and($bloodTest->refresh()->status)->toBe('reviewing');
});

it('keeps candidates with mismatched reference units as drafts', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $marker = Biomarker::factory()->for($user)->create(['name' => 'Marker Alpha']);
    $bloodTest = bloodTestWithStoredDocument($user);

    runExtractionWithCandidates($bloodTest->documents()->firstOrFail(), [
        new ExtractedBiomarkerCandidate(
            extractedName: 'Marker Alpha',
            value: '12.4',
            unit: 'mg/L',
            referenceMin: '10',
            referenceMax: '20',
            referenceUnit: 'g/L',
            confidence: 0.95,
            sourceSnippet: 'synthetic mismatched reference unit row',
        ),
    ]);

    $result = BiomarkerResult::query()
        ->where('blood_test_id', $bloodTest->id)
        ->where('biomarker_id', $marker->id)
        ->firstOrFail();

    expect($result->confirmed_at)->toBeNull()
        ->and($result->status)->toBe('unknown')
        ->and($result->reference_unit)->toBe('g/L')
        ->and((float) $result->extraction_confidence)->toBeLessThan(RunBloodTestExtraction::AUTO_CONFIRM_CONFIDENCE_THRESHOLD)
        ->and($bloodTest->refresh()->status)->toBe('reviewing');
});

it('keeps candidates with reversed reference ranges as drafts', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $marker = Biomarker::factory()->for($user)->create(['name' => 'Marker Alpha']);
    $bloodTest = bloodTestWithStoredDocument($user);

    runExtractionWithCandidates($bloodTest->documents()->firstOrFail(), [
        new ExtractedBiomarkerCandidate(
            extractedName: 'Marker Alpha',
            value: '12.4',
            unit: 'mg/L',
            referenceMin: '20',
            referenceMax: '10',
            referenceUnit: 'mg/L',
            confidence: 0.95,
            sourceSnippet: 'synthetic reversed reference range row',
        ),
    ]);

    $result = BiomarkerResult::query()
        ->where('blood_test_id', $bloodTest->id)
        ->where('biomarker_id', $marker->id)
        ->firstOrFail();

    expect($result->confirmed_at)->toBeNull()
        ->and($result->status)->toBe('unknown')
        ->and((float) $result->reference_min)->toBe(20.0)
        ->and((float) $result->reference_max)->toBe(10.0)
        ->and((float) $result->extraction_confidence)->toBeLessThan(RunBloodTestExtraction::AUTO_CONFIRM_CONFIDENCE_THRESHOLD)
        ->and($bloodTest->refresh()->status)->toBe('reviewing');
});

it('preserves repeated unmatched extracted names as separate draft rows', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $bloodTest = bloodTestWithStoredDocument($user);

    $run = runExtractionWithCandidates($bloodTest->documents()->firstOrFail(), [
        new ExtractedBiomarkerCandidate(
            extractedName: 'Repeated Marker',
            value: '5',
            unit: 'U/mL',
            referenceMin: null,
            referenceMax: '8',
            referenceUnit: 'U/mL',
            confidence: 0.95,
            sourceSnippet: 'synthetic repeated row one',
        ),
        new ExtractedBiomarkerCandidate(
            extractedName: 'Repeated Marker',
            value: '7',
            unit: 'U/mL',
            referenceMin: null,
            referenceMax: '8',
            referenceUnit: 'U/mL',
            confidence: 0.95,
            sourceSnippet: 'synthetic repeated row two',
        ),
    ]);

    $drafts = BiomarkerResult::query()
        ->where('blood_test_id', $bloodTest->id)
        ->whereNull('biomarker_id')
        ->orderBy('id')
        ->get();

    expect($run->status)->toBe('done')
        ->and($run->candidate_count)->toBe(2)
        ->and($drafts)->toHaveCount(2)
        ->and($drafts->pluck('extracted_name')->all())->toBe(['Repeated Marker', 'Repeated Marker'])
        ->and($drafts->pluck('value')->map(fn (string $value): float => (float) $value)->all())->toBe([5.0, 7.0]);
});

it('stores extracted source snippets as compact single-line trace metadata', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $bloodTest = bloodTestWithStoredDocument($user);

    runExtractionWithCandidates($bloodTest->documents()->firstOrFail(), [
        new ExtractedBiomarkerCandidate(
            extractedName: 'Unmatched Marker',
            value: '5',
            unit: 'U/mL',
            referenceMin: null,
            referenceMax: '8',
            referenceUnit: 'U/mL',
            confidence: 0.75,
            sourceSnippet: "Unmatched Marker\t5 U/mL\nsynthetic continuation",
        ),
    ]);

    $draft = BiomarkerResult::query()
        ->where('blood_test_id', $bloodTest->id)
        ->firstOrFail();

    expect($draft->source_snippet)->toBe('Unmatched Marker 5 U/mL synthetic continuation');
});

it('does not collapse unmatched drafts when source snippets truncate to the same text', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $bloodTest = bloodTestWithStoredDocument($user);
    $sharedSnippetPrefix = str_repeat('same long source text ', 40);

    $run = runExtractionWithCandidates($bloodTest->documents()->firstOrFail(), [
        new ExtractedBiomarkerCandidate(
            extractedName: 'Repeated Marker',
            value: '5',
            unit: 'U/mL',
            referenceMin: null,
            referenceMax: '8',
            referenceUnit: 'U/mL',
            confidence: 0.95,
            sourceSnippet: $sharedSnippetPrefix.'row one',
        ),
        new ExtractedBiomarkerCandidate(
            extractedName: 'Repeated Marker',
            value: '7',
            unit: 'U/mL',
            referenceMin: null,
            referenceMax: '8',
            referenceUnit: 'U/mL',
            confidence: 0.95,
            sourceSnippet: $sharedSnippetPrefix.'row two',
        ),
    ]);

    $draftValues = BiomarkerResult::query()
        ->where('blood_test_id', $bloodTest->id)
        ->whereNull('biomarker_id')
        ->orderBy('id')
        ->pluck('value')
        ->map(fn (string $value): float => (float) $value)
        ->all();

    expect($run->status)->toBe('done')
        ->and($run->candidate_count)->toBe(2)
        ->and($draftValues)->toBe([5.0, 7.0]);
});

it('preserves identical repeated unmatched candidates as separate draft rows', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $bloodTest = bloodTestWithStoredDocument($user);

    $run = runExtractionWithCandidates($bloodTest->documents()->firstOrFail(), [
        new ExtractedBiomarkerCandidate(
            extractedName: 'Repeated Marker',
            value: '5',
            unit: 'U/mL',
            referenceMin: null,
            referenceMax: '8',
            referenceUnit: 'U/mL',
            confidence: 0.95,
            sourceSnippet: 'synthetic identical row',
        ),
        new ExtractedBiomarkerCandidate(
            extractedName: 'Repeated Marker',
            value: '5',
            unit: 'U/mL',
            referenceMin: null,
            referenceMax: '8',
            referenceUnit: 'U/mL',
            confidence: 0.95,
            sourceSnippet: 'synthetic identical row',
        ),
    ]);

    $drafts = BiomarkerResult::query()
        ->where('blood_test_id', $bloodTest->id)
        ->whereNull('biomarker_id')
        ->orderBy('id')
        ->get();

    expect($run->status)->toBe('done')
        ->and($run->candidate_count)->toBe(2)
        ->and($drafts)->toHaveCount(2);
});

it('keeps duplicate catalog-matched candidates as separate drafts instead of auto-confirming one', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    Biomarker::factory()->for($user)->create(['name' => 'Marker Alpha']);
    $bloodTest = bloodTestWithStoredDocument($user);

    $run = runExtractionWithCandidates($bloodTest->documents()->firstOrFail(), [
        new ExtractedBiomarkerCandidate(
            extractedName: 'Marker Alpha',
            value: '5',
            unit: 'U/mL',
            referenceMin: null,
            referenceMax: '8',
            referenceUnit: 'U/mL',
            confidence: 0.95,
            sourceSnippet: 'synthetic duplicate catalog row one',
        ),
        new ExtractedBiomarkerCandidate(
            extractedName: 'Marker Alpha',
            value: '7',
            unit: 'U/mL',
            referenceMin: null,
            referenceMax: '8',
            referenceUnit: 'U/mL',
            confidence: 0.95,
            sourceSnippet: 'synthetic duplicate catalog row two',
        ),
    ]);

    $drafts = BiomarkerResult::query()
        ->where('blood_test_id', $bloodTest->id)
        ->orderBy('id')
        ->get();

    expect($run->status)->toBe('done')
        ->and($run->candidate_count)->toBe(2)
        ->and($drafts)->toHaveCount(2)
        ->and($drafts->pluck('biomarker_id')->all())->toBe([null, null])
        ->and($drafts->pluck('confirmed_at')->all())->toBe([null, null])
        ->and($drafts->pluck('value')->map(fn (string $value): float => (float) $value)->all())->toBe([5.0, 7.0])
        ->and($drafts->pluck('extraction_confidence')->map(fn (string $confidence): float => (float) $confidence)->all())
        ->toBe([0.84, 0.84])
        ->and($bloodTest->refresh()->status)->toBe('reviewing');
});

it('marks the blood test confirmed when every extracted candidate is auto-confirmed', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    Biomarker::factory()->for($user)->create(['name' => 'Marker Alpha']);
    $bloodTest = bloodTestWithStoredDocument($user);

    runExtractionWithCandidates($bloodTest->documents()->firstOrFail(), [
        new ExtractedBiomarkerCandidate(
            extractedName: 'Marker Alpha',
            value: '12.4',
            unit: 'mg/L',
            referenceMin: '10',
            referenceMax: '20',
            referenceUnit: 'mg/L',
            confidence: 0.95,
            sourceSnippet: 'synthetic high row',
        ),
    ]);

    expect($bloodTest->refresh()->status)->toBe('confirmed');
});

it('auto-confirms clean extracted decimal comma values after normalizing numbers', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    Biomarker::factory()->for($user)->create(['name' => 'Marker Alpha']);
    $bloodTest = bloodTestWithStoredDocument($user);

    runExtractionWithCandidates($bloodTest->documents()->firstOrFail(), [
        new ExtractedBiomarkerCandidate(
            extractedName: 'Marker Alpha',
            value: '12,4',
            unit: 'mg/L',
            referenceMin: '10,0',
            referenceMax: '20,0',
            referenceUnit: 'mg/L',
            confidence: 0.95,
            sourceSnippet: 'synthetic decimal comma row',
        ),
    ]);

    $result = BiomarkerResult::query()->where('blood_test_id', $bloodTest->id)->firstOrFail();

    expect($bloodTest->refresh()->status)->toBe('confirmed')
        ->and($result->confirmed_at)->not->toBeNull()
        ->and($result->status)->toBe('normal')
        ->and((float) $result->value)->toBe(12.4)
        ->and((float) $result->reference_min)->toBe(10.0)
        ->and((float) $result->reference_max)->toBe(20.0)
        ->and((float) $result->extraction_confidence)->toBe(0.95);
});

it('normalizes non-breaking numeric whitespace before auto-confirming clean rows', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    Biomarker::factory()->for($user)->create(['name' => 'Marker Alpha']);
    $bloodTest = bloodTestWithStoredDocument($user);

    runExtractionWithCandidates($bloodTest->documents()->firstOrFail(), [
        new ExtractedBiomarkerCandidate(
            extractedName: 'Marker Alpha',
            value: "\u{00A0}12.4\u{00A0}",
            unit: 'mg/L',
            referenceMin: "\u{00A0}10\u{00A0}",
            referenceMax: "\u{00A0}20\u{00A0}",
            referenceUnit: 'mg/L',
            confidence: 0.95,
            sourceSnippet: 'synthetic non-breaking numeric whitespace row',
        ),
    ]);

    $result = BiomarkerResult::query()->where('blood_test_id', $bloodTest->id)->firstOrFail();

    expect($bloodTest->refresh()->status)->toBe('confirmed')
        ->and($result->confirmed_at)->not->toBeNull()
        ->and($result->status)->toBe('normal')
        ->and((float) $result->value)->toBe(12.4)
        ->and((float) $result->reference_min)->toBe(10.0)
        ->and((float) $result->reference_max)->toBe(20.0)
        ->and((float) $result->extraction_confidence)->toBe(0.95);
});

it('normalizes extracted units before auto-confirming clean rows', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    Biomarker::factory()->for($user)->create(['name' => 'Marker Alpha']);
    $bloodTest = bloodTestWithStoredDocument($user);

    runExtractionWithCandidates($bloodTest->documents()->firstOrFail(), [
        new ExtractedBiomarkerCandidate(
            extractedName: 'Marker Alpha',
            value: '12.4',
            unit: ' mg/L ',
            referenceMin: '10',
            referenceMax: '20',
            referenceUnit: 'mg/L',
            confidence: 0.95,
            sourceSnippet: 'synthetic padded unit row',
        ),
    ]);

    $result = BiomarkerResult::query()->where('blood_test_id', $bloodTest->id)->firstOrFail();

    expect($bloodTest->refresh()->status)->toBe('confirmed')
        ->and($result->confirmed_at)->not->toBeNull()
        ->and($result->status)->toBe('normal')
        ->and($result->unit)->toBe('mg/L')
        ->and($result->reference_unit)->toBe('mg/L')
        ->and((float) $result->extraction_confidence)->toBe(0.95);
});

it('normalizes non-breaking extracted unit whitespace before auto-confirming clean rows', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    Biomarker::factory()->for($user)->create(['name' => 'Marker Alpha']);
    $bloodTest = bloodTestWithStoredDocument($user);

    runExtractionWithCandidates($bloodTest->documents()->firstOrFail(), [
        new ExtractedBiomarkerCandidate(
            extractedName: 'Marker Alpha',
            value: '12.4',
            unit: "\u{00A0}mg/L\u{00A0}",
            referenceMin: '10',
            referenceMax: '20',
            referenceUnit: 'mg/L',
            confidence: 0.95,
            sourceSnippet: 'synthetic non-breaking unit whitespace row',
        ),
    ]);

    $result = BiomarkerResult::query()->where('blood_test_id', $bloodTest->id)->firstOrFail();

    expect($bloodTest->refresh()->status)->toBe('confirmed')
        ->and($result->confirmed_at)->not->toBeNull()
        ->and($result->status)->toBe('normal')
        ->and($result->unit)->toBe('mg/L')
        ->and($result->reference_unit)->toBe('mg/L')
        ->and((float) $result->extraction_confidence)->toBe(0.95);
});

it('normalizes wrapped extracted units before auto-confirming clean rows', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    Biomarker::factory()->for($user)->create(['name' => 'Marker Alpha']);
    $bloodTest = bloodTestWithStoredDocument($user);

    runExtractionWithCandidates($bloodTest->documents()->firstOrFail(), [
        new ExtractedBiomarkerCandidate(
            extractedName: 'Marker Alpha',
            value: '12.4',
            unit: '(mg/L)',
            referenceMin: '10',
            referenceMax: '20',
            referenceUnit: '[mg/L]',
            confidence: 0.95,
            sourceSnippet: 'synthetic wrapped unit row',
        ),
    ]);

    $result = BiomarkerResult::query()->where('blood_test_id', $bloodTest->id)->firstOrFail();

    expect($bloodTest->refresh()->status)->toBe('confirmed')
        ->and($result->confirmed_at)->not->toBeNull()
        ->and($result->status)->toBe('normal')
        ->and($result->unit)->toBe('mg/L')
        ->and($result->reference_unit)->toBe('mg/L')
        ->and((float) $result->extraction_confidence)->toBe(0.95);
});

it('does not overwrite a previously confirmed value when extraction sees the same biomarker', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $ferritin = Biomarker::factory()->for($user)->create(['name' => 'Ferritin']);
    $bloodTest = bloodTestWithStoredDocument($user);
    $bloodTest->update(['status' => 'confirmed']);
    $document = $bloodTest->documents()->firstOrFail();

    $confirmed = BiomarkerResult::factory()->for($bloodTest)->for($ferritin)->create([
        'value' => 50,
        'unit' => 'ug/L',
        'status' => 'normal',
        'entry_source' => 'pdf_reviewed',
        'confirmed_at' => now(),
        'note' => 'User confirmed value.',
    ]);

    runExtractionWithCandidates($document, [
        new ExtractedBiomarkerCandidate(
            extractedName: 'Ferritin',
            value: '42',
            unit: 'ug/L',
            referenceMin: '30',
            referenceMax: '150',
            referenceUnit: 'ug/L',
            confidence: 0.95,
            sourceSnippet: 'synthetic rerun row',
        ),
    ]);

    $confirmed->refresh();

    expect((float) $confirmed->value)->toBe(50.0)
        ->and($confirmed->entry_source)->toBe('pdf_reviewed')
        ->and($confirmed->note)->toBe('User confirmed value.');

    expect(BiomarkerResult::query()
        ->where('blood_test_id', $bloodTest->id)
        ->where('biomarker_id', $ferritin->id)
        ->where('entry_source', 'extracted')
        ->exists())->toBeFalse()
        ->and($bloodTest->extractionRuns()->latest('id')->firstOrFail()->candidate_count)->toBe(1)
        ->and($bloodTest->refresh()->status)->toBe('confirmed');
});

it('matches extracted names only against the owning users biomarker catalog', function () {
    Storage::fake('local');

    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    Biomarker::factory()->for($otherUser)->create(['name' => 'Ferritin']);

    $bloodTest = BloodTest::factory()->for($owner)->create();
    $document = BloodTestDocument::factory()->for($bloodTest)->create([
        'storage_path' => 'blood-test-documents/owner-scope-fixture.pdf',
    ]);
    Storage::disk('local')->put(
        $document->storage_path,
        File::get(base_path('tests/Fixtures/assisted-extraction-lab.pdf')),
    );

    app(RunBloodTestExtraction::class)($document);

    $draft = BiomarkerResult::query()
        ->where('blood_test_id', $bloodTest->id)
        ->where('extracted_name', 'Ferritin')
        ->firstOrFail();

    expect($draft->biomarker_id)->toBeNull();
});

it('records failed extraction runs without storing parser output', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $bloodTest = bloodTestWithStoredDocument($user);
    $document = $bloodTest->documents()->firstOrFail();
    $extractor = new class extends ExtractBiomarkerDrafts
    {
        public function __construct() {}

        /**
         * @return list<ExtractedBiomarkerCandidate>
         */
        public function __invoke(string $pdfPath): array
        {
            throw new RuntimeException('Synthetic parser failure');
        }
    };

    $run = (new RunBloodTestExtraction($extractor))($document);

    expect($run->status)->toBe('failed')
        ->and($run->candidate_count)->toBe(0)
        ->and(BiomarkerResult::query()->count())->toBe(0)
        ->and($bloodTest->refresh()->status)->toBe('reviewing');
});

it('rolls back candidate rows when extraction persistence fails mid-run', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $bloodTest = bloodTestWithStoredDocument($user);
    $document = $bloodTest->documents()->firstOrFail();

    BiomarkerResult::created(function (BiomarkerResult $result): void {
        if ($result->extracted_name === 'Second Marker') {
            throw new RuntimeException('Synthetic storage failure');
        }
    });

    try {
        $run = runExtractionWithCandidates($document, [
            new ExtractedBiomarkerCandidate(
                extractedName: 'First Marker',
                value: '12.4',
                unit: 'mg/L',
                referenceMin: '10',
                referenceMax: '20',
                referenceUnit: 'mg/L',
                confidence: 0.95,
                sourceSnippet: 'synthetic first row',
            ),
            new ExtractedBiomarkerCandidate(
                extractedName: 'Second Marker',
                value: '7.1',
                unit: 'mg/L',
                referenceMin: '4',
                referenceMax: '9',
                referenceUnit: 'mg/L',
                confidence: 0.95,
                sourceSnippet: 'synthetic second row',
            ),
        ]);
    } finally {
        BiomarkerResult::flushEventListeners();
    }

    expect($run->status)->toBe('failed')
        ->and($run->candidate_count)->toBe(0)
        ->and(BiomarkerResult::query()->where('blood_test_id', $bloodTest->id)->count())->toBe(0)
        ->and($bloodTest->refresh()->status)->toBe('reviewing');
});

it('keeps extracted drafts out of confirmed-only workflows and export until confirmed', function () {
    $user = User::factory()->create();
    $biomarker = Biomarker::factory()->for($user)->create(['name' => 'Ferritin']);
    $first = BloodTest::factory()->for($user)->create(['test_date' => '2026-05-01']);
    $second = BloodTest::factory()->for($user)->create(['test_date' => '2026-06-01']);

    BiomarkerResult::factory()->for($first)->for($biomarker)->create([
        'value' => 35,
        'unit' => 'ug/L',
        'status' => 'normal',
        'confirmed_at' => now(),
    ]);
    BiomarkerResult::factory()->for($second)->for($biomarker)->create([
        'value' => 42,
        'unit' => 'ug/L',
        'status' => 'unknown',
        'entry_source' => 'extracted',
        'confirmed_at' => null,
        'extracted_name' => 'Ferritin',
        'extraction_confidence' => 0.95,
        'source_snippet' => 'Ferritin 42 ug/L ref 30-150 ug/L',
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('42 ug/L');

    $this->actingAs($user)
        ->get(route('biomarkers.show', $biomarker))
        ->assertOk()
        ->assertSee('35 ug/L')
        ->assertDontSee('42 ug/L');

    $this->actingAs($user)
        ->get(route('blood-tests.compare', ['first' => $first, 'second' => $second]))
        ->assertOk()
        ->assertSee('not measured')
        ->assertDontSee('+7');

    $this->actingAs($user)
        ->post(route('consult-overview.index'), [
            'blood_test_ids' => [$first->id, $second->id],
            'include_trends' => '1',
            'include_attention' => '1',
        ])
        ->assertOk()
        ->assertDontSee('42 ug/L');

    $export = app(BuildDataExport::class)($user);

    expect($export['biomarker_results'])->toHaveCount(1)
        ->and((float) $export['biomarker_results'][0]['value'])->toBe(35.0);
});

it('delete all removes extracted drafts and extraction runs', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $biomarker = Biomarker::factory()->for($user)->create(['name' => 'Ferritin']);
    $bloodTest = BloodTest::factory()->for($user)->create();
    BiomarkerResult::factory()->for($bloodTest)->for($biomarker)->create([
        'entry_source' => 'extracted',
        'confirmed_at' => null,
        'extracted_name' => 'Ferritin',
    ]);
    ExtractionRun::factory()->for($bloodTest)->create([
        'engine' => 'smalot/pdfparser',
        'status' => 'done',
        'candidate_count' => 1,
    ]);

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->delete(route('data.destroy'), ['confirmation' => 'DELETE ALL'])
        ->assertRedirect(route('data.edit'));

    expect(BiomarkerResult::query()->count())->toBe(0)
        ->and(ExtractionRun::query()->count())->toBe(0);
});

function syntheticTabularPdfPath(): string
{
    $stream = implode("\n", [
        'BT',
        '/F1 12 Tf',
        positionedPdfText('Analysis', 40, 180),
        positionedPdfText('Value', 210, 180),
        positionedPdfText('Unit', 300, 180),
        positionedPdfText('Reference', 390, 180),
        positionedPdfText('Marker Alpha', 40, 160),
        positionedPdfText('12,4', 210, 160),
        positionedPdfText('mg/L', 300, 160),
        positionedPdfText('10 - 20', 390, 160),
        positionedPdfText('Marker Beta', 40, 140),
        positionedPdfText('<5', 210, 140),
        positionedPdfText('U/mL', 300, 140),
        positionedPdfText('< 8', 390, 140),
        positionedPdfText('Marker Gamma', 40, 120),
        positionedPdfText('not detected', 210, 120),
        positionedPdfText('U/mL', 300, 120),
        positionedPdfText('< 1', 390, 120),
        'ET',
        '',
    ]);

    return syntheticPdfPath($stream);
}

function syntheticInlineAndTabularPdfPath(): string
{
    $stream = implode("\n", [
        'BT',
        '/F1 12 Tf',
        positionedPdfText('Marker Inline 42 mg/L ref 10-20 mg/L', 40, 200),
        positionedPdfText('Analysis', 40, 180),
        positionedPdfText('Value', 210, 180),
        positionedPdfText('Unit', 300, 180),
        positionedPdfText('Reference', 390, 180),
        positionedPdfText('Marker Alpha', 40, 160),
        positionedPdfText('12,4', 210, 160),
        positionedPdfText('mg/L', 300, 160),
        positionedPdfText('10 - 20', 390, 160),
        'ET',
        '',
    ]);

    return syntheticPdfPath($stream);
}

function syntheticGlyphSpacedInlinePdfPath(): string
{
    $stream = implode("\n", [
        'BT',
        '/F1 12 Tf',
        positionedPdfText('M a r k e r   A l p h a   1 2 , 4   m g / L   r e f   1 0 - 2 0   m g / L', 40, 200),
        'ET',
        '',
    ]);

    return syntheticPdfPath($stream);
}

function syntheticCompactInlinePdfPath(): string
{
    $stream = implode("\n", [
        'BT',
        '/F1 12 Tf',
        positionedPdfText('MarkerAlpha12,4mg/L10-20mg/L', 40, 200),
        'ET',
        '',
    ]);

    return syntheticPdfPath($stream);
}

function syntheticCompactUnitFirstInlinePdfPath(): string
{
    $stream = implode("\n", [
        'BT',
        '/F1 12 Tf',
        positionedPdfText('MarkerAlphamg/L12,4-10,0-20,0<', 40, 200),
        'ET',
        '',
    ]);

    return syntheticPdfPath($stream);
}

function syntheticSymbolicUnitFirstInlinePdfPath(): string
{
    $stream = implode("\n", [
        'BT',
        '/F1 12 Tf',
        positionedPdfText('MarkerAlphaK°U/L12,4-10,0-20,0<', 40, 200),
        positionedPdfText('Marker Beta 3°U/L 8,4-6,0-10,0<', 40, 180),
        'ET',
        '',
    ]);

    return syntheticPdfPath($stream);
}

function syntheticCommaRangeUnitFirstInlinePdfPath(): string
{
    $stream = implode("\n", [
        'BT',
        '/F1 12 Tf',
        positionedPdfText('MarkerAlphaK°U/L12,4-10,0,20,0<', 40, 200),
        positionedPdfText('Marker Beta 3°U/L 8,4-6,0,10,0<', 40, 180),
        'ET',
        '',
    ]);

    return syntheticPdfPath($stream);
}

function assistedCmaLayoutText(array $rows): string
{
    return implode("\n", array_merge([
        str_pad('Analyse', 36).str_pad('822.434.825', 36).str_pad('Eenheid', 24).'Referentie',
        'STOLLING',
    ], $rows));
}

function assistedCmaLayoutRow(string $name, string $value, string $unit, string $reference): string
{
    return str_pad($name, 36).str_pad($value, 36).str_pad($unit, 24).$reference.'        <';
}

function syntheticInferredValueTabularPdfPath(): string
{
    $stream = implode("\n", [
        'BT',
        '/F1 12 Tf',
        positionedPdfText('Analyse', 40, 180),
        positionedPdfText('Eenheid', 387, 180),
        positionedPdfText('Referentie', 465, 180),
        positionedPdfText('Marker Alpha', 50, 160),
        positionedPdfText('12,4', 190, 160),
        positionedPdfText('mg/L', 387, 160),
        positionedPdfText('10 - 20', 465, 160),
        'ET',
        '',
    ]);

    return syntheticPdfPath($stream);
}

function syntheticPdfPath(string $stream): string
{
    $objects = [
        '<< /Type /Catalog /Pages 2 0 R >>',
        '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
        '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 500 220] /Resources << /Font << /F1 5 0 R >> >> /Contents 4 0 R >>',
        '<< /Length '.strlen($stream)." >>\nstream\n".$stream.'endstream',
        '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
    ];

    $pdf = "%PDF-1.4\n";
    $offsets = [0];

    foreach ($objects as $index => $object) {
        $objectNumber = $index + 1;
        $offsets[$objectNumber] = strlen($pdf);
        $pdf .= $objectNumber." 0 obj\n".$object."\nendobj\n";
    }

    $xref = strlen($pdf);
    $pdf .= "xref\n0 ".(count($objects) + 1)."\n0000000000 65535 f \n";

    for ($objectNumber = 1; $objectNumber <= count($objects); $objectNumber++) {
        $pdf .= sprintf("%010d 00000 n \n", $offsets[$objectNumber]);
    }

    $pdf .= "trailer\n<< /Root 1 0 R /Size ".(count($objects) + 1)." >>\nstartxref\n".$xref."\n%%EOF\n";

    $path = tempnam(sys_get_temp_dir(), 'tabular-pdf-');
    file_put_contents($path, $pdf);

    return $path;
}

function positionedPdfText(string $text, int $x, int $y): string
{
    $escapedText = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);

    return "1 0 0 1 {$x} {$y} Tm ({$escapedText}) Tj";
}

function bloodTestWithStoredDocument(User $user): BloodTest
{
    $bloodTest = BloodTest::factory()->for($user)->create(['status' => 'uploaded']);

    $bloodTest->documents()->create([
        'original_filename' => 'synthetic.pdf',
        'storage_disk' => 'local',
        'storage_path' => 'blood-test-documents/synthetic.pdf',
        'mime_type' => 'application/pdf',
        'file_size' => 100,
    ]);

    Storage::disk('local')->put('blood-test-documents/synthetic.pdf', '%PDF-1.4 synthetic');

    return $bloodTest;
}

/**
 * @param  list<ExtractedBiomarkerCandidate>  $candidates
 */
function runExtractionWithCandidates(BloodTestDocument $document, array $candidates): ExtractionRun
{
    $extractor = new class($candidates) extends ExtractBiomarkerDrafts
    {
        /**
         * @param  list<ExtractedBiomarkerCandidate>  $candidates
         */
        public function __construct(private readonly array $candidates) {}

        /**
         * @return list<ExtractedBiomarkerCandidate>
         */
        public function __invoke(string $pdfPath): array
        {
            return $this->candidates;
        }
    };

    return (new RunBloodTestExtraction($extractor))($document);
}
