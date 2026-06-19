<?php

use App\Livewire\BloodTests\ReviewBloodTest;
use App\Models\Biomarker;
use App\Models\BiomarkerResult;
use App\Models\BloodTest;
use App\Models\ExtractionRun;
use App\Models\User;
use Livewire\Livewire;

it('shows extracted drafts and lets the owner confirm a draft through the review form', function () {
    $user = User::factory()->create();
    $biomarker = Biomarker::factory()->for($user)->create(['name' => 'Ferritin']);
    $bloodTest = BloodTest::factory()->for($user)->create(['status' => 'reviewing']);
    $draft = BiomarkerResult::factory()->for($bloodTest)->for($biomarker)->create([
        'value' => 42,
        'unit' => 'ug/L',
        'reference_min' => 30,
        'reference_max' => 150,
        'reference_unit' => 'ug/L',
        'status' => 'unknown',
        'entry_source' => 'extracted',
        'confirmed_at' => null,
        'extracted_name' => 'Ferritin',
        'extraction_confidence' => 0.95,
        'source_snippet' => 'Ferritin 42 ug/L ref 30-150 ug/L',
    ]);

    Livewire::actingAs($user)
        ->test(ReviewBloodTest::class, ['bloodTest' => $bloodTest])
        ->assertSee('Extracted - please confirm')
        ->assertSee('Ferritin')
        ->call('useDraft', $draft->id)
        ->assertSet('draftResultId', $draft->id)
        ->assertSet('resultForm.biomarker_id', $biomarker->id)
        ->assertSet('resultForm.value', '42')
        ->call('confirmResult')
        ->assertHasNoErrors();

    $draft->refresh();

    expect($draft->confirmed_at)->not->toBeNull()
        ->and($draft->entry_source)->toBe('pdf_reviewed')
        ->and($draft->status)->toBe('normal')
        ->and($bloodTest->refresh()->status)->toBe('confirmed');
});

it('keeps the blood test in review while confirming one draft if other extracted drafts remain', function () {
    $user = User::factory()->create();
    $ferritin = Biomarker::factory()->for($user)->create(['name' => 'Ferritin']);
    $vitaminD = Biomarker::factory()->for($user)->create(['name' => 'Vitamin D']);
    $bloodTest = BloodTest::factory()->for($user)->create(['status' => 'reviewing']);
    $draft = BiomarkerResult::factory()->for($bloodTest)->for($ferritin)->create([
        'value' => 42,
        'unit' => 'ug/L',
        'reference_min' => 30,
        'reference_max' => 150,
        'reference_unit' => 'ug/L',
        'status' => 'unknown',
        'entry_source' => 'extracted',
        'confirmed_at' => null,
        'extracted_name' => 'Ferritin',
    ]);
    BiomarkerResult::factory()->for($bloodTest)->for($vitaminD)->create([
        'entry_source' => 'extracted',
        'confirmed_at' => null,
        'extracted_name' => 'Vitamin D',
    ]);

    Livewire::actingAs($user)
        ->test(ReviewBloodTest::class, ['bloodTest' => $bloodTest])
        ->call('useDraft', $draft->id)
        ->call('confirmResult')
        ->assertHasNoErrors();

    expect($draft->refresh()->confirmed_at)->not->toBeNull()
        ->and($bloodTest->refresh()->status)->toBe('reviewing');
});

it('recalculates the blood test status after deleting drafts and confirmed values', function () {
    $user = User::factory()->create();
    $ferritin = Biomarker::factory()->for($user)->create(['name' => 'Ferritin']);
    $vitaminD = Biomarker::factory()->for($user)->create(['name' => 'Vitamin D']);
    $bloodTest = BloodTest::factory()->for($user)->create(['status' => 'reviewing']);
    $confirmed = BiomarkerResult::factory()->for($bloodTest)->for($ferritin)->create([
        'value' => 42,
        'unit' => 'ug/L',
        'reference_min' => 30,
        'reference_max' => 150,
        'reference_unit' => 'ug/L',
        'status' => 'normal',
        'entry_source' => 'extracted',
        'confirmed_at' => now(),
        'extracted_name' => 'Ferritin',
    ]);
    $draft = BiomarkerResult::factory()->for($bloodTest)->for($vitaminD)->create([
        'entry_source' => 'extracted',
        'confirmed_at' => null,
        'extracted_name' => 'Vitamin D',
    ]);

    $component = Livewire::actingAs($user)
        ->test(ReviewBloodTest::class, ['bloodTest' => $bloodTest])
        ->call('deleteDraft', $draft->id)
        ->assertHasNoErrors();

    expect($bloodTest->refresh()->status)->toBe('confirmed');

    $component
        ->call('deleteConfirmedResult', $confirmed->id)
        ->assertHasNoErrors();

    expect($bloodTest->refresh()->status)->toBe('reviewing');
});

it('frames the review form as extracted value review when drafts exist', function () {
    $user = User::factory()->create();
    $biomarker = Biomarker::factory()->for($user)->create(['name' => 'Ferritin']);
    $bloodTest = BloodTest::factory()->for($user)->create(['status' => 'reviewing']);
    BiomarkerResult::factory()->for($bloodTest)->for($biomarker)->create([
        'entry_source' => 'extracted',
        'confirmed_at' => null,
        'extracted_name' => 'Ferritin',
    ]);

    Livewire::actingAs($user)
        ->test(ReviewBloodTest::class, ['bloodTest' => $bloodTest])
        ->assertSee('Review extracted values')
        ->assertSee('Read from your PDF')
        ->assertDontSee('Add your values');
});

it('does not tell the owner nothing counts when auto-confirmed values are already active', function () {
    $user = User::factory()->create();
    $ferritin = Biomarker::factory()->for($user)->create(['name' => 'Ferritin']);
    $vitaminD = Biomarker::factory()->for($user)->create(['name' => 'Vitamin D']);
    $bloodTest = BloodTest::factory()->for($user)->create(['status' => 'reviewing']);
    BiomarkerResult::factory()->for($bloodTest)->for($ferritin)->create([
        'entry_source' => 'extracted',
        'confirmed_at' => now(),
        'extracted_name' => 'Ferritin',
    ]);
    BiomarkerResult::factory()->for($bloodTest)->for($vitaminD)->create([
        'entry_source' => 'extracted',
        'confirmed_at' => null,
        'extracted_name' => 'Vitamin D',
    ]);

    Livewire::actingAs($user)
        ->test(ReviewBloodTest::class, ['bloodTest' => $bloodTest])
        ->assertSee('Confirmed values')
        ->assertSee('Review extracted values')
        ->assertSee('Some values are already active for status and trends.')
        ->assertDontSee('nothing counts until you confirm each one');
});

it('marks below auto-confirm threshold drafts as low confidence in the review strip', function () {
    $user = User::factory()->create();
    $biomarker = Biomarker::factory()->for($user)->create(['name' => 'Ferritin']);
    $bloodTest = BloodTest::factory()->for($user)->create(['status' => 'reviewing']);
    BiomarkerResult::factory()->for($bloodTest)->for($biomarker)->create([
        'entry_source' => 'extracted',
        'confirmed_at' => null,
        'extracted_name' => 'Ferritin',
        'extraction_confidence' => 0.84,
    ]);

    Livewire::actingAs($user)
        ->test(ReviewBloodTest::class, ['bloodTest' => $bloodTest])
        ->assertSee('Low confidence');
});

it('shows one-sided draft reference ranges in the review strip', function () {
    $user = User::factory()->create();
    $maxOnly = Biomarker::factory()->for($user)->create(['name' => 'Marker Max']);
    $minOnly = Biomarker::factory()->for($user)->create(['name' => 'Marker Min']);
    $bloodTest = BloodTest::factory()->for($user)->create(['status' => 'reviewing']);

    BiomarkerResult::factory()->for($bloodTest)->for($maxOnly)->create([
        'value' => 5,
        'unit' => 'U/mL',
        'reference_min' => null,
        'reference_max' => 8,
        'reference_unit' => 'U/mL',
        'entry_source' => 'extracted',
        'confirmed_at' => null,
        'extracted_name' => 'Marker Max',
    ]);
    BiomarkerResult::factory()->for($bloodTest)->for($minOnly)->create([
        'value' => 35,
        'unit' => 'ug/L',
        'reference_min' => 30,
        'reference_max' => null,
        'reference_unit' => 'ug/L',
        'entry_source' => 'extracted',
        'confirmed_at' => null,
        'extracted_name' => 'Marker Min',
    ]);

    Livewire::actingAs($user)
        ->test(ReviewBloodTest::class, ['bloodTest' => $bloodTest])
        ->assertSee('<= 8 U/mL')
        ->assertSee('>= 30 ug/L');
});

it('frames the review form as manual entry when extraction found no drafts', function () {
    $user = User::factory()->create();
    $bloodTest = BloodTest::factory()->for($user)->create(['status' => 'reviewing']);
    ExtractionRun::factory()->for($bloodTest)->create([
        'engine' => 'smalot/pdfparser',
        'status' => 'done',
        'candidate_count' => 0,
    ]);

    Livewire::actingAs($user)
        ->test(ReviewBloodTest::class, ['bloodTest' => $bloodTest])
        ->assertSee('Add your values')
        ->assertSee("We couldn't read values from this PDF automatically")
        ->assertDontSee('No below-threshold rows need review.')
        ->assertDontSee('Confirm a biomarker value');
});

it('shows a manual-entry fallback when extraction failed', function () {
    $user = User::factory()->create();
    $bloodTest = BloodTest::factory()->for($user)->create(['status' => 'reviewing']);
    ExtractionRun::factory()->for($bloodTest)->create([
        'engine' => 'smalot/pdfparser',
        'status' => 'failed',
        'candidate_count' => 0,
    ]);

    Livewire::actingAs($user)
        ->test(ReviewBloodTest::class, ['bloodTest' => $bloodTest])
        ->assertSee('data-test="intake-progress-stage-extract" data-state="failed"', false)
        ->assertSee('data-test="intake-progress-stage-values" data-state="pending"', false)
        ->assertSee('data-test="intake-progress-stage-status" data-state="pending"', false)
        ->assertSee('data-test="intake-progress-stage-trend" data-state="pending"', false)
        ->assertSee('Extraction failed. Manual entry is still available.')
        ->assertDontSee('No below-threshold rows need review.')
        ->assertSee('Add your values')
        ->assertSee('Add values from the source document when you are ready.');
});

it('shows auto-confirmed extracted values as auto-filled and lets the owner edit or delete them', function () {
    $user = User::factory()->create();
    $biomarker = Biomarker::factory()->for($user)->create(['name' => 'Ferritin']);
    $bloodTest = BloodTest::factory()->for($user)->create(['status' => 'confirmed']);
    $result = BiomarkerResult::factory()->for($bloodTest)->for($biomarker)->create([
        'value' => 42,
        'unit' => 'ug/L',
        'reference_min' => 30,
        'reference_max' => 150,
        'reference_unit' => 'ug/L',
        'status' => 'normal',
        'entry_source' => 'extracted',
        'confirmed_at' => now(),
        'extracted_name' => 'Ferritin',
        'extraction_confidence' => 0.95,
        'source_snippet' => 'Ferritin 42 ug/L ref 30-150 ug/L',
    ]);

    Livewire::actingAs($user)
        ->test(ReviewBloodTest::class, ['bloodTest' => $bloodTest])
        ->assertSee('Confirmed values')
        ->assertSee('auto-filled from PDF')
        ->assertDontSee('Extracted - please confirm')
        ->call('editConfirmedResult', $result->id)
        ->assertSet('resultForm.biomarker_id', $biomarker->id)
        ->assertSet('resultForm.value', '42')
        ->call('deleteConfirmedResult', $result->id)
        ->assertHasNoErrors();

    expect(BiomarkerResult::query()->whereKey($result->id)->exists())->toBeFalse();
});

it('rejects confirming a draft as a biomarker already present on the same blood test', function () {
    $user = User::factory()->create();
    $ferritin = Biomarker::factory()->for($user)->create(['name' => 'Ferritin']);
    $vitaminD = Biomarker::factory()->for($user)->create(['name' => 'Vitamin D']);
    $bloodTest = BloodTest::factory()->for($user)->create(['status' => 'reviewing']);
    $confirmed = BiomarkerResult::factory()->for($bloodTest)->for($ferritin)->create([
        'value' => 42,
        'unit' => 'ug/L',
        'status' => 'normal',
        'entry_source' => 'pdf_reviewed',
        'confirmed_at' => now(),
    ]);
    $draft = BiomarkerResult::factory()->for($bloodTest)->for($vitaminD)->create([
        'value' => 61,
        'unit' => 'nmol/L',
        'entry_source' => 'extracted',
        'confirmed_at' => null,
        'extracted_name' => 'Vitamin D',
    ]);

    $component = Livewire::actingAs($user)
        ->test(ReviewBloodTest::class, ['bloodTest' => $bloodTest])
        ->call('useDraft', $draft->id)
        ->set('resultForm.biomarker_id', $ferritin->id);

    $exception = null;

    try {
        $component->call('confirmResult');
    } catch (Throwable $caught) {
        $exception = $caught;
    }

    expect($exception)->toBeNull();

    $component->assertHasErrors(['resultForm.biomarker_id']);

    expect($draft->refresh()->confirmed_at)->toBeNull()
        ->and($draft->biomarker_id)->toBe($vitaminD->id)
        ->and((float) $confirmed->refresh()->value)->toBe(42.0)
        ->and(BiomarkerResult::query()
            ->where('blood_test_id', $bloodTest->id)
            ->where('biomarker_id', $ferritin->id)
            ->count())->toBe(1);
});

it('rejects manually adding a biomarker that already has a value on the same blood test', function () {
    $user = User::factory()->create();
    $ferritin = Biomarker::factory()->for($user)->create(['name' => 'Ferritin']);
    $bloodTest = BloodTest::factory()->for($user)->create(['status' => 'confirmed']);
    $confirmed = BiomarkerResult::factory()->for($bloodTest)->for($ferritin)->create([
        'value' => 42,
        'unit' => 'ug/L',
        'status' => 'normal',
        'entry_source' => 'pdf_reviewed',
        'confirmed_at' => now(),
    ]);

    $component = Livewire::actingAs($user)
        ->test(ReviewBloodTest::class, ['bloodTest' => $bloodTest])
        ->set('resultForm.biomarker_id', $ferritin->id)
        ->set('resultForm.value', '50')
        ->set('resultForm.unit', 'ug/L');

    $exception = null;

    try {
        $component->call('confirmResult');
    } catch (Throwable $caught) {
        $exception = $caught;
    }

    expect($exception)->toBeNull();

    $component->assertHasErrors(['resultForm.biomarker_id']);

    expect((float) $confirmed->refresh()->value)->toBe(42.0)
        ->and(BiomarkerResult::query()
            ->where('blood_test_id', $bloodTest->id)
            ->where('biomarker_id', $ferritin->id)
            ->count())->toBe(1);
});

it('rejects editing a confirmed result to duplicate another biomarker on the same blood test', function () {
    $user = User::factory()->create();
    $ferritin = Biomarker::factory()->for($user)->create(['name' => 'Ferritin']);
    $vitaminD = Biomarker::factory()->for($user)->create(['name' => 'Vitamin D']);
    $bloodTest = BloodTest::factory()->for($user)->create(['status' => 'confirmed']);
    $ferritinResult = BiomarkerResult::factory()->for($bloodTest)->for($ferritin)->create([
        'value' => 42,
        'unit' => 'ug/L',
        'status' => 'normal',
        'entry_source' => 'pdf_reviewed',
        'confirmed_at' => now(),
    ]);
    $vitaminDResult = BiomarkerResult::factory()->for($bloodTest)->for($vitaminD)->create([
        'value' => 61,
        'unit' => 'nmol/L',
        'status' => 'normal',
        'entry_source' => 'pdf_reviewed',
        'confirmed_at' => now(),
    ]);

    $component = Livewire::actingAs($user)
        ->test(ReviewBloodTest::class, ['bloodTest' => $bloodTest])
        ->call('editConfirmedResult', $vitaminDResult->id)
        ->set('resultForm.biomarker_id', $ferritin->id);

    $exception = null;

    try {
        $component->call('confirmResult');
    } catch (Throwable $caught) {
        $exception = $caught;
    }

    expect($exception)->toBeNull();

    $component->assertHasErrors(['resultForm.biomarker_id']);

    expect($vitaminDResult->refresh()->biomarker_id)->toBe($vitaminD->id)
        ->and((float) $vitaminDResult->value)->toBe(61.0)
        ->and((float) $ferritinResult->refresh()->value)->toBe(42.0)
        ->and(BiomarkerResult::query()
            ->where('blood_test_id', $bloodTest->id)
            ->where('biomarker_id', $ferritin->id)
            ->count())->toBe(1);
});

it('blocks using another users extracted draft from a tampered livewire action', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $biomarker = Biomarker::factory()->for($owner)->create(['name' => 'Ferritin']);
    $ownersBloodTest = BloodTest::factory()->for($owner)->create(['status' => 'reviewing']);
    $otherUsersBloodTest = BloodTest::factory()->for($otherUser)->create(['status' => 'reviewing']);
    $ownersDraft = BiomarkerResult::factory()->for($ownersBloodTest)->for($biomarker)->create([
        'entry_source' => 'extracted',
        'confirmed_at' => null,
        'extracted_name' => 'Ferritin',
    ]);

    Livewire::actingAs($otherUser)
        ->test(ReviewBloodTest::class, ['bloodTest' => $otherUsersBloodTest])
        ->call('useDraft', $ownersDraft->id)
        ->assertForbidden();
});
