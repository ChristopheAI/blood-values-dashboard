<?php

use App\Domain\Intake\ExtractBiomarkerDrafts;
use App\Domain\Intake\RunBloodTestExtraction;
use App\Models\Biomarker;
use App\Models\BiomarkerResult;
use App\Models\BloodTest;
use App\Models\BloodTestDocument;
use App\Models\ExtractionRun;
use App\Models\User;
use Illuminate\Http\UploadedFile;
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
        ->and($ferritinDraft->confirmed_at)->toBeNull()
        ->and($ferritinDraft->status)->toBe('unknown')
        ->and($ferritinDraft->extraction_confidence)->not->toBeNull()
        ->and($ferritinDraft->source_snippet)->toContain('Ferritin 42 ug/L');

    expect($drafts->whereNull('biomarker_id')->pluck('extracted_name')->all())
        ->toContain('CRP', 'Vitamin D');
});

it('does not overwrite a previously confirmed value when extraction sees the same biomarker', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $ferritin = Biomarker::factory()->for($user)->create(['name' => 'Ferritin']);
    $bloodTest = BloodTest::factory()->for($user)->create(['status' => 'confirmed']);
    $document = BloodTestDocument::factory()->for($bloodTest)->create([
        'storage_path' => 'blood-test-documents/fixture.pdf',
    ]);
    Storage::disk('local')->put(
        $document->storage_path,
        File::get(base_path('tests/Fixtures/assisted-extraction-lab.pdf')),
    );

    $confirmed = BiomarkerResult::factory()->for($bloodTest)->for($ferritin)->create([
        'value' => 50,
        'unit' => 'ug/L',
        'status' => 'normal',
        'entry_source' => 'pdf_reviewed',
        'confirmed_at' => now(),
        'note' => 'User confirmed value.',
    ]);

    app(RunBloodTestExtraction::class)($document);

    $confirmed->refresh();

    expect((float) $confirmed->value)->toBe(50.0)
        ->and($confirmed->entry_source)->toBe('pdf_reviewed')
        ->and($confirmed->note)->toBe('User confirmed value.');

    expect(BiomarkerResult::query()
        ->where('blood_test_id', $bloodTest->id)
        ->where('biomarker_id', $ferritin->id)
        ->where('entry_source', 'extracted')
        ->exists())->toBeFalse();
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

    $export = app(App\Domain\Privacy\BuildDataExport::class)($user);

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
