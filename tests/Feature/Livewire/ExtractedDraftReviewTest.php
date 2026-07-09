<?php

use App\Enums\ContextNoteCategory;
use App\Livewire\BloodTests\ReviewBloodTest;
use App\Models\Biomarker;
use App\Models\BiomarkerResult;
use App\Models\BloodTest;
use App\Models\BloodTestDocument;
use App\Models\ContextNote;
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
        ->assertSee('Geëxtraheerd - bevestig eerst')
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

it('confirms a null-biomarker extracted draft by creating the biomarker through the review form', function () {
    $user = User::factory()->create();
    $bloodTest = BloodTest::factory()->for($user)->create(['status' => 'reviewing']);
    $draft = BiomarkerResult::factory()->for($bloodTest)->create([
        'biomarker_id' => null,
        'value' => 42,
        'unit' => 'ug/L',
        'reference_min' => 30,
        'reference_max' => 150,
        'reference_unit' => 'ug/L',
        'status' => 'unknown',
        'entry_source' => 'extracted',
        'confirmed_at' => null,
        'extracted_name' => 'Magnesium',
        'extraction_confidence' => 0.84,
        'source_snippet' => 'Magnesium 42 ug/L ref 30-150 ug/L',
    ]);

    Livewire::actingAs($user)
        ->test(ReviewBloodTest::class, ['bloodTest' => $bloodTest])
        ->call('useDraft', $draft->id)
        ->assertSet('draftResultId', $draft->id)
        ->assertSet('resultForm.biomarker_id', null)
        ->assertSet('resultForm.name', 'Magnesium')
        ->call('confirmResult')
        ->assertHasNoErrors();

    $draft->refresh();
    $biomarker = Biomarker::query()
        ->where('user_id', $user->id)
        ->where('name', 'Magnesium')
        ->first();

    expect($biomarker)->not->toBeNull()
        ->and($draft->biomarker_id)->toBe($biomarker->id)
        ->and($draft->entry_source)->toBe('pdf_reviewed')
        ->and($draft->confirmed_at)->not->toBeNull()
        ->and($draft->status)->toBe('normal')
        ->and($draft->extracted_name)->toBe('Magnesium')
        ->and($bloodTest->refresh()->status)->toBe('confirmed');
});

it('normalizes unicode whitespace from extracted drafts before confirming through the review form', function () {
    $user = User::factory()->create();
    $biomarker = Biomarker::factory()->for($user)->create(['name' => 'Ferritin']);
    $bloodTest = BloodTest::factory()->for($user)->create(['status' => 'reviewing']);
    $draft = BiomarkerResult::factory()->for($bloodTest)->for($biomarker)->create([
        'value' => 42,
        'unit' => "\u{00A0}ug/L\u{00A0}",
        'reference_min' => 30,
        'reference_max' => 150,
        'reference_unit' => "\u{00A0}ug/L\u{00A0}",
        'status' => 'unknown',
        'entry_source' => 'extracted',
        'confirmed_at' => null,
        'extracted_name' => 'Ferritin',
        'extraction_confidence' => 0.84,
        'source_snippet' => 'Ferritin 42 ug/L ref 30-150 ug/L',
    ]);

    Livewire::actingAs($user)
        ->test(ReviewBloodTest::class, ['bloodTest' => $bloodTest])
        ->call('useDraft', $draft->id)
        ->call('confirmResult')
        ->assertHasNoErrors();

    $draft->refresh();

    expect($draft->unit)->toBe('ug/L')
        ->and($draft->reference_unit)->toBe('ug/L')
        ->and($draft->confirmed_at)->not->toBeNull()
        ->and($draft->status)->toBe('normal')
        ->and($bloodTest->refresh()->status)->toBe('confirmed');
});

it('normalizes wrapped units from extracted drafts before confirming through the review form', function () {
    $user = User::factory()->create();
    $biomarker = Biomarker::factory()->for($user)->create(['name' => 'Ferritin']);
    $bloodTest = BloodTest::factory()->for($user)->create(['status' => 'reviewing']);
    $draft = BiomarkerResult::factory()->for($bloodTest)->for($biomarker)->create([
        'value' => 42,
        'unit' => '(ug/L)',
        'reference_min' => 30,
        'reference_max' => 150,
        'reference_unit' => '[ug/L]',
        'status' => 'unknown',
        'entry_source' => 'extracted',
        'confirmed_at' => null,
        'extracted_name' => 'Ferritin',
        'extraction_confidence' => 0.84,
        'source_snippet' => 'Ferritin 42 ug/L ref 30-150 ug/L',
    ]);

    Livewire::actingAs($user)
        ->test(ReviewBloodTest::class, ['bloodTest' => $bloodTest])
        ->call('useDraft', $draft->id)
        ->call('confirmResult')
        ->assertHasNoErrors();

    $draft->refresh();

    expect($draft->unit)->toBe('ug/L')
        ->and($draft->reference_unit)->toBe('ug/L')
        ->and($draft->confirmed_at)->not->toBeNull()
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
    BloodTestDocument::factory()->for($bloodTest)->create();
    BiomarkerResult::factory()->for($bloodTest)->for($biomarker)->create([
        'entry_source' => 'extracted',
        'confirmed_at' => null,
        'extracted_name' => 'Ferritin',
    ]);

    Livewire::actingAs($user)
        ->test(ReviewBloodTest::class, ['bloodTest' => $bloodTest])
        ->assertSee('Geëxtraheerde waarden reviewen')
        ->assertSee('Gelezen uit je PDF')
        ->assertDontSee('Waarden toevoegen');
});

it('does not tell the owner to read from a deleted PDF when drafts remain', function () {
    $user = User::factory()->create();
    $biomarker = Biomarker::factory()->for($user)->create(['name' => 'Ferritin']);
    $bloodTest = BloodTest::factory()->for($user)->create(['status' => 'reviewing']);
    BiomarkerResult::factory()->for($bloodTest)->for($biomarker)->create([
        'entry_source' => 'extracted',
        'confirmed_at' => null,
        'extracted_name' => 'Ferritin',
        'source_snippet' => null,
    ]);

    Livewire::actingAs($user)
        ->test(ReviewBloodTest::class, ['bloodTest' => $bloodTest])
        ->assertSee('Geen bronbestand gekoppeld.')
        ->assertSee('Review de geextraheerde rijen; de bron-PDF is niet meer gekoppeld. Niets telt mee totdat je een rij bevestigt.')
        ->assertDontSee('Gelezen uit je PDF');
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
        ->assertSee('Bevestigde waarden')
        ->assertSee('Geëxtraheerde waarden reviewen')
        ->assertSee('Sommige waarden tellen al mee voor status en trends.')
        ->assertDontSee('niets telt mee totdat je elke waarde bevestigt');
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
        ->assertSee('data-test="extracted-draft-row" data-state="draft" data-confidence="low"', false)
        ->assertSee('Lage betrouwbaarheid');
});

it('separates extracted draft value and reference fields in the review strip', function () {
    $user = User::factory()->create();
    $biomarker = Biomarker::factory()->for($user)->create(['name' => 'Apolipoprotein B']);
    $bloodTest = BloodTest::factory()->for($user)->create(['status' => 'reviewing']);

    BiomarkerResult::factory()->for($bloodTest)->for($biomarker)->create([
        'value' => 162,
        'unit' => 'mg/dL',
        'reference_min' => null,
        'reference_max' => 100,
        'reference_unit' => 'mg/dL',
        'entry_source' => 'extracted',
        'confirmed_at' => null,
        'extracted_name' => 'Apolipoprotein B',
        'extraction_confidence' => 0.82,
    ]);

    Livewire::actingAs($user)
        ->test(ReviewBloodTest::class, ['bloodTest' => $bloodTest])
        ->assertSee('data-test="draft-value"', false)
        ->assertSee('data-test="draft-reference"', false)
        ->assertSee('data-test="draft-review-state"', false)
        ->assertSee('Waarde')
        ->assertSee('162 mg/dL')
        ->assertSee('Referentie')
        ->assertSee('<= 100 mg/dL')
        ->assertSee('Reviewstatus')
        ->assertSee('Bevestiging nodig')
        ->assertDontSee('162 mg/dL · <= 100 mg/dL');
});

it('hides the review strip when extraction has no drafts left', function () {
    $user = User::factory()->create();
    $bloodTest = BloodTest::factory()->for($user)->create(['status' => 'confirmed']);
    ExtractionRun::factory()->for($bloodTest)->create([
        'engine' => 'smalot/pdfparser',
        'status' => 'done',
        'candidate_count' => 2,
    ]);

    Livewire::actingAs($user)
        ->test(ReviewBloodTest::class, ['bloodTest' => $bloodTest])
        ->assertDontSee('data-test="review-strip"', false)
        ->assertDontSee('Geen geextraheerde drafts gevonden.')
        ->assertDontSee('No below-threshold rows need review.');
});

it('collapses manual entry when extracted values are already confirmed', function () {
    $user = User::factory()->create();
    $biomarker = Biomarker::factory()->for($user)->create(['name' => 'Ferritin']);
    $bloodTest = BloodTest::factory()->for($user)->create(['status' => 'confirmed']);
    BloodTestDocument::factory()->for($bloodTest)->create();
    ExtractionRun::factory()->for($bloodTest)->create([
        'engine' => 'smalot/pdfparser',
        'status' => 'done',
        'candidate_count' => 1,
    ]);
    BiomarkerResult::factory()->for($bloodTest)->for($biomarker)->create([
        'value' => 42,
        'unit' => 'ug/L',
        'status' => 'normal',
        'entry_source' => 'extracted',
        'confirmed_at' => now(),
        'extracted_name' => 'Ferritin',
    ]);

    Livewire::actingAs($user)
        ->test(ReviewBloodTest::class, ['bloodTest' => $bloodTest])
        ->assertSee('Bevestigde waarden')
        ->assertDontSee('data-test="confirm-biomarker-form"', false)
        ->assertSee('data-test="show-manual-entry-button"', false)
        ->assertDontSee('Waarden toevoegen')
        ->call('showManualEntry')
        ->assertSee('data-test="confirm-biomarker-form"', false)
        ->assertSee('Waarden toevoegen');
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
        ->assertSee('Waarden toevoegen')
        ->assertSee('Geen bronbestand gekoppeld.')
        ->assertSee('We konden geen waarden automatisch uit deze PDF lezen. Voeg waarden manueel toe wanneer je klaar bent.')
        ->assertDontSee('Voeg ze toe naast het document hieronder.')
        ->assertDontSee('No below-threshold rows need review.')
        ->assertDontSee('Confirm a biomarker value');
});

it('shows a manual-entry fallback when extraction failed', function () {
    $user = User::factory()->create();
    $bloodTest = BloodTest::factory()->for($user)->create(['status' => 'reviewing']);
    BloodTestDocument::factory()->for($bloodTest)->create();
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
        ->assertSee('Extractie mislukt. Manuele invoer blijft beschikbaar.')
        ->assertDontSee('No below-threshold rows need review.')
        ->assertSee('Waarden toevoegen')
        ->assertSee('Voeg waarden uit het bronbestand toe wanneer je klaar bent.');
});

it('does not point manual entry copy to a missing source document', function () {
    $user = User::factory()->create();
    $bloodTest = BloodTest::factory()->for($user)->create(['status' => 'reviewing']);

    Livewire::actingAs($user)
        ->test(ReviewBloodTest::class, ['bloodTest' => $bloodTest])
        ->assertSee('Geen bronbestand gekoppeld.')
        ->assertSee('Voeg waarden manueel toe wanneer je klaar bent.')
        ->assertDontSee('Voeg waarden uit het bronbestand toe wanneer je klaar bent.');
});

it('shows auto-confirmed extracted values as auto-filled and lets the owner edit or delete them', function () {
    $user = User::factory()->create();
    $biomarker = Biomarker::factory()->for($user)->create(['name' => 'Ferritin']);
    $bloodTest = BloodTest::factory()->for($user)->create(['status' => 'confirmed']);
    BloodTestDocument::factory()->for($bloodTest)->create();
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
        ->assertSee('Bevestigde waarden')
        ->assertSee('automatisch ingevuld uit PDF')
        ->assertDontSee('bron verwijderd')
        ->assertDontSee('Geëxtraheerd - bevestig eerst')
        ->call('editConfirmedResult', $result->id)
        ->assertSet('resultForm.biomarker_id', $biomarker->id)
        ->assertSet('resultForm.value', '42')
        ->call('deleteConfirmedResult', $result->id)
        ->assertHasNoErrors();

    expect(BiomarkerResult::query()->whereKey($result->id)->exists())->toBeFalse();
});

it('keeps auto-filled PDF trace when the owner edits an auto-confirmed value', function () {
    $user = User::factory()->create();
    $biomarker = Biomarker::factory()->for($user)->create(['name' => 'Ferritin']);
    $bloodTest = BloodTest::factory()->for($user)->create(['status' => 'confirmed']);
    BloodTestDocument::factory()->for($bloodTest)->create();
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
        ->call('editConfirmedResult', $result->id)
        ->set('resultForm.value', '43')
        ->call('confirmResult')
        ->assertHasNoErrors()
        ->assertSee('automatisch ingevuld uit PDF');

    $result->refresh();

    expect((float) $result->value)->toBe(43.0)
        ->and($result->entry_source)->toBe('extracted')
        ->and($result->extracted_name)->toBe('Ferritin')
        ->and($result->source_snippet)->toBe('Ferritin 42 ug/L ref 30-150 ug/L')
        ->and($result->confirmed_at)->not->toBeNull()
        ->and($result->status)->toBe('normal');
});

it('marks auto-filled values when the source PDF is gone', function () {
    $user = User::factory()->create();
    $biomarker = Biomarker::factory()->for($user)->create(['name' => 'Ferritin']);
    $bloodTest = BloodTest::factory()->for($user)->create(['status' => 'confirmed']);
    BiomarkerResult::factory()->for($bloodTest)->for($biomarker)->create([
        'value' => 42,
        'unit' => 'ug/L',
        'status' => 'normal',
        'entry_source' => 'extracted',
        'confirmed_at' => now(),
        'extracted_name' => 'Ferritin',
        'source_snippet' => null,
    ]);

    Livewire::actingAs($user)
        ->test(ReviewBloodTest::class, ['bloodTest' => $bloodTest])
        ->assertSee('Geen bronbestand gekoppeld.')
        ->assertSee('automatisch ingevuld uit PDF (bron verwijderd)');
});

it('shows a compact trend summary for confirmed values on the result screen', function () {
    $user = User::factory()->create();
    $biomarker = Biomarker::factory()->for($user)->create(['name' => 'Ferritin']);
    $previousBloodTest = BloodTest::factory()->for($user)->create([
        'test_date' => '2026-05-01',
        'status' => 'confirmed',
    ]);
    $bloodTest = BloodTest::factory()->for($user)->create([
        'test_date' => null,
        'status' => 'confirmed',
    ]);

    BiomarkerResult::factory()->for($previousBloodTest)->for($biomarker)->create([
        'value' => 40,
        'unit' => 'ug/L',
        'status' => 'normal',
        'confirmed_at' => now(),
    ]);
    BiomarkerResult::factory()->for($bloodTest)->for($biomarker)->create([
        'value' => 42,
        'unit' => 'ug/L',
        'status' => 'normal',
        'entry_source' => 'extracted',
        'confirmed_at' => now(),
    ]);

    Livewire::actingAs($user)
        ->test(ReviewBloodTest::class, ['bloodTest' => $bloodTest])
        ->assertSee('data-test="confirmed-value-trend" data-state="compared"', false)
        ->assertSee('+2 ug/L')
        ->assertSee('vorige 40 ug/L');
});

it('shows source documents for the selected owned blood test without storage paths', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $biomarker = Biomarker::factory()->for($user)->create(['name' => 'Ferritin']);
    $bloodTest = BloodTest::factory()->for($user)->create(['status' => 'confirmed']);
    $otherBloodTest = BloodTest::factory()->for($otherUser)->create(['status' => 'confirmed']);

    BloodTestDocument::factory()->for($bloodTest)->create([
        'original_filename' => 'selected-lab-result.pdf',
        'storage_path' => 'blood-test-documents/private-selected-storage-name.pdf',
    ]);
    BloodTestDocument::factory()->for($otherBloodTest)->create([
        'original_filename' => 'foreign-lab-result.pdf',
        'storage_path' => 'blood-test-documents/foreign-storage-name.pdf',
    ]);
    BiomarkerResult::factory()->for($bloodTest)->for($biomarker)->create([
        'value' => 42,
        'unit' => 'ug/L',
        'status' => 'normal',
        'entry_source' => 'extracted',
        'confirmed_at' => now(),
    ]);

    Livewire::actingAs($user)
        ->test(ReviewBloodTest::class, ['bloodTest' => $bloodTest])
        ->assertSee('data-test="source-document-row"', false)
        ->assertSee('selected-lab-result.pdf')
        ->assertSee(route('blood-test-documents.download', $bloodTest->documents()->first()), false)
        ->assertDontSee('private-selected-storage-name.pdf')
        ->assertDontSee('foreign-lab-result.pdf')
        ->assertDontSee('foreign-storage-name.pdf');
});

it('shows context notes for the selected blood test only', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $biomarker = Biomarker::factory()->for($user)->create(['name' => 'Ferritin']);
    $bloodTest = BloodTest::factory()->for($user)->create(['status' => 'confirmed']);
    $otherOwnedBloodTest = BloodTest::factory()->for($user)->create(['status' => 'confirmed']);

    ContextNote::factory()->for($user)->for($bloodTest)->create([
        'note_date' => '2026-06-02',
        'category' => ContextNoteCategory::Other,
        'body' => 'Selected blood-test context note',
    ]);
    ContextNote::factory()->for($user)->for($otherOwnedBloodTest)->create([
        'body' => 'Other owned blood-test note',
    ]);
    ContextNote::factory()->for($otherUser)->create([
        'blood_test_id' => $bloodTest->id,
        'body' => 'Foreign corrupted context note',
    ]);
    BiomarkerResult::factory()->for($bloodTest)->for($biomarker)->create([
        'value' => 42,
        'unit' => 'ug/L',
        'status' => 'normal',
        'confirmed_at' => now(),
    ]);

    Livewire::actingAs($user)
        ->test(ReviewBloodTest::class, ['bloodTest' => $bloodTest])
        ->assertSee('data-test="blood-test-context-note-row"', false)
        ->assertSee('Selected blood-test context note')
        ->assertSee('2 juni 2026')
        ->assertSee('Other')
        ->assertDontSee('Other owned blood-test note')
        ->assertDontSee('Foreign corrupted context note');
});

it('shows confirmed only comparable changes for the selected blood test', function () {
    $user = User::factory()->create();
    $ferritin = Biomarker::factory()->for($user)->create(['name' => 'Ferritin']);
    $tsh = Biomarker::factory()->for($user)->create(['name' => 'TSH']);
    $previousBloodTest = BloodTest::factory()->for($user)->create([
        'test_date' => '2026-05-01',
        'status' => 'confirmed',
    ]);
    $currentBloodTest = BloodTest::factory()->for($user)->create([
        'test_date' => null,
        'status' => 'reviewing',
    ]);

    BiomarkerResult::factory()->for($previousBloodTest)->for($ferritin)->create([
        'value' => 40,
        'unit' => 'ug/L',
        'status' => 'normal',
        'confirmed_at' => now()->subMonth(),
    ]);
    BiomarkerResult::factory()->for($currentBloodTest)->for($ferritin)->create([
        'value' => 42,
        'unit' => 'ug/L',
        'status' => 'normal',
        'entry_source' => 'extracted',
        'confirmed_at' => now(),
    ]);
    BiomarkerResult::factory()->for($previousBloodTest)->for($tsh)->create([
        'value' => 9.9,
        'unit' => 'mIU/L',
        'status' => 'unknown',
        'entry_source' => 'extracted',
        'confirmed_at' => null,
        'extracted_name' => 'Draft TSH',
    ]);

    Livewire::actingAs($user)
        ->test(ReviewBloodTest::class, ['bloodTest' => $currentBloodTest])
        ->assertSee('data-test="confirmed-value-trend" data-state="compared"', false)
        ->assertSee('+2 ug/L')
        ->assertSee('vorige 40 ug/L')
        ->assertDontSee('9.9 mIU/L')
        ->assertDontSee('Draft TSH');
});

it('keeps the management layer available below the patient friendly overview', function () {
    $user = User::factory()->create();
    $ferritin = Biomarker::factory()->for($user)->create(['name' => 'Ferritin']);
    $vitaminD = Biomarker::factory()->for($user)->create(['name' => 'Vitamin D']);
    $bloodTest = BloodTest::factory()->for($user)->create(['status' => 'reviewing']);

    BiomarkerResult::factory()->for($bloodTest)->for($ferritin)->create([
        'value' => 42,
        'unit' => 'ug/L',
        'status' => 'normal',
        'entry_source' => 'extracted',
        'confirmed_at' => now(),
    ]);
    BiomarkerResult::factory()->for($bloodTest)->for($vitaminD)->create([
        'value' => 61,
        'unit' => 'nmol/L',
        'entry_source' => 'extracted',
        'confirmed_at' => null,
        'extracted_name' => 'Vitamin D',
    ]);

    Livewire::actingAs($user)
        ->test(ReviewBloodTest::class, ['bloodTest' => $bloodTest])
        ->assertSee('data-test="blood-results-overview"', false)
        ->assertSee('data-test="intake-progress"', false)
        ->assertSee('data-test="confirmed-value-row"', false)
        ->assertSee('data-test="review-strip"', false)
        ->assertSee('data-test="extracted-draft-row"', false)
        ->assertSee('data-test="confirm-biomarker-form"', false)
        ->assertSee('Bewerken')
        ->assertSee('Verwijderen')
        ->assertSee('Draft gebruiken')
        ->assertSee('Waarde bevestigen');
});

it('renders the patient friendly overview for an older owned blood test', function () {
    $user = User::factory()->create();
    $ferritin = Biomarker::factory()->for($user)->create(['name' => 'Ferritine']);
    $crp = Biomarker::factory()->for($user)->create(['name' => 'CRP']);
    $hemoglobin = Biomarker::factory()->for($user)->create(['name' => 'Hemoglobine']);
    $previous = BloodTest::factory()->for($user)->create([
        'title' => 'Vorige meting voor trend',
        'test_date' => '2026-03-01',
        'status' => 'confirmed',
        'created_at' => now()->subMonths(3),
    ]);
    $olderBloodTest = BloodTest::factory()->for($user)->create([
        'title' => 'Bloedafname april',
        'test_date' => '2026-04-08',
        'status' => 'confirmed',
        'created_at' => now()->subMonths(2),
    ]);
    $newerBloodTest = BloodTest::factory()->for($user)->create([
        'title' => 'Nieuwere bloedtest',
        'test_date' => '2026-06-01',
        'status' => 'confirmed',
        'created_at' => now(),
    ]);

    BiomarkerResult::factory()->for($previous)->for($ferritin)->create([
        'value' => 35,
        'unit' => 'ug/L',
        'confirmed_at' => now()->subMonths(3),
    ]);
    BiomarkerResult::factory()->for($olderBloodTest)->for($ferritin)->create([
        'value' => 42,
        'unit' => 'ug/L',
        'reference_min' => '30',
        'reference_max' => '150',
        'reference_unit' => 'ug/L',
        'status' => 'normal',
        'entry_source' => 'extracted',
        'confirmed_at' => now()->subMonths(2),
    ]);
    BiomarkerResult::factory()->for($olderBloodTest)->for($crp)->create([
        'value' => 4.2,
        'unit' => 'mg/L',
        'status' => 'unknown',
        'entry_source' => 'extracted',
        'confirmed_at' => now()->subMonths(2),
    ]);
    BiomarkerResult::factory()->for($olderBloodTest)->for($hemoglobin)->create([
        'value' => 18.1,
        'unit' => 'g/dL',
        'reference_min' => '13',
        'reference_max' => '17',
        'reference_unit' => 'g/dL',
        'status' => 'high',
        'entry_source' => 'extracted',
        'confirmed_at' => now()->subMonths(2),
    ]);
    BiomarkerResult::factory()->for($newerBloodTest)->for($ferritin)->create([
        'value' => 12,
        'unit' => 'ug/L',
        'status' => 'normal',
        'confirmed_at' => now(),
    ]);

    Livewire::actingAs($user)
        ->test(ReviewBloodTest::class, ['bloodTest' => $olderBloodTest])
        ->assertSee('data-test="blood-results-overview"', false)
        ->assertSee('Je bloedresultaten')
        ->assertSee('Afname 8 april 2026')
        ->assertSee('Bloedafname april')
        ->assertSee('1/3 waarde is normaal')
        ->assertSee('2 waarden vragen aandacht')
        ->assertSee('data-test="featured-attention-card"', false)
        ->assertSee('data-test="attention-next-step"', false)
        ->assertSee('Zet hem klaar op je consultlijst')
        ->assertSee('data-test="compact-normal-row"', false)
        ->assertSee('data-test="compact-review-row"', false)
        ->assertSee('+7 ug/L')
        ->assertDontSee('Nieuwere bloedtest');
});

it('marks the values stage done when confirmed values already exist', function () {
    $user = User::factory()->create();
    $biomarker = Biomarker::factory()->for($user)->create(['name' => 'Ferritin']);
    $bloodTest = BloodTest::factory()->for($user)->create(['status' => 'confirmed']);
    BiomarkerResult::factory()->for($bloodTest)->for($biomarker)->create([
        'value' => 42,
        'unit' => 'ug/L',
        'status' => 'normal',
        'entry_source' => 'pdf_reviewed',
        'confirmed_at' => now(),
    ]);

    Livewire::actingAs($user)
        ->test(ReviewBloodTest::class, ['bloodTest' => $bloodTest])
        ->assertSee('data-test="intake-progress-stage-extract" data-state="pending"', false)
        ->assertSee('data-test="intake-progress-stage-values" data-state="done"', false)
        ->assertSee('data-test="intake-progress-stage-status" data-state="done"', false)
        ->assertSee('data-test="intake-progress-stage-trend" data-state="done"', false);
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

it('confirms a below-detection draft using the prefixed value from the review form', function () {
    $user = User::factory()->create();
    $biomarker = Biomarker::factory()->for($user)->create(['name' => 'RA*']);
    $bloodTest = BloodTest::factory()->for($user)->create(['status' => 'reviewing']);
    $draft = BiomarkerResult::factory()->for($bloodTest)->for($biomarker)->create([
        'value' => 10,
        'unit' => 'kIU/L',
        'reference_min' => null,
        'reference_max' => 13,
        'reference_unit' => 'kIU/L',
        'status' => 'unknown',
        'entry_source' => 'extracted',
        'confirmed_at' => null,
        'extracted_name' => 'RA*',
        'extraction_confidence' => 0.75,
        'source_snippet' => 'RA* <10 kIU/L ≤13 <',
    ]);

    Livewire::actingAs($user)
        ->test(ReviewBloodTest::class, ['bloodTest' => $bloodTest])
        ->call('useDraft', $draft->id)
        ->assertSet('resultForm.value', '<10')
        ->call('confirmResult')
        ->assertHasNoErrors();

    $draft->refresh();

    expect((float) $draft->value)->toBe(10.0)
        ->and($draft->status)->toBe('normal')
        ->and($draft->confirmed_at)->not->toBeNull()
        ->and($draft->source_snippet)->toContain('<10');
});
