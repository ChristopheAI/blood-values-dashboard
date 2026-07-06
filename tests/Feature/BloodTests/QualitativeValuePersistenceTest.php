<?php

use App\Livewire\BloodTests\ReviewBloodTest;
use App\Models\Biomarker;
use App\Models\BiomarkerResult;
use App\Models\BloodTest;
use App\Models\User;
use Livewire\Livewire;

// Guards against the class of bug where qualitative lab values ('Negatief',
// 'Niet gedetecteerd', ...) are written into the biomarker_results.value
// column. SQLite's type affinity silently accepts text in a numeric column;
// Postgres (the production target) rejects it. Run this suite against Postgres
// in CI to catch a regression before it reaches staging.

it('persists a qualitative confirmed value as text in the value column', function () {
    $user = User::factory()->create();
    $biomarker = Biomarker::factory()->for($user)->create(['name' => 'HIV-antistoffen']);
    $bloodTest = BloodTest::factory()->for($user)->create(['status' => 'reviewing']);

    Livewire::actingAs($user)
        ->test(ReviewBloodTest::class, ['bloodTest' => $bloodTest])
        ->set('resultForm.biomarker_id', $biomarker->id)
        ->set('resultForm.value', 'Negatief')
        ->set('resultForm.unit', 'kwalitatief')
        ->call('confirmResult')
        ->assertHasNoErrors();

    $result = BiomarkerResult::query()
        ->where('biomarker_id', $biomarker->id)
        ->whereNotNull('confirmed_at')
        ->firstOrFail();

    expect($result->value)->toBe('Negatief');
});

it('persists a qualitative extracted draft when confirmed through the review form', function () {
    $user = User::factory()->create();
    $biomarker = Biomarker::factory()->for($user)->create(['name' => 'SARS-CoV-2 PCR']);
    $bloodTest = BloodTest::factory()->for($user)->create(['status' => 'reviewing']);
    $draft = BiomarkerResult::factory()->for($bloodTest)->for($biomarker)->create([
        'value' => 'Niet gedetecteerd',
        'unit' => 'kwalitatief',
        'reference_min' => null,
        'reference_max' => null,
        'reference_unit' => null,
        'status' => 'unknown',
        'entry_source' => 'extracted',
        'confirmed_at' => null,
        'extracted_name' => 'SARS-CoV-2 PCR',
        'extraction_confidence' => 0.9,
        'source_snippet' => 'SARS-CoV-2 PCR: Niet gedetecteerd',
    ]);

    Livewire::actingAs($user)
        ->test(ReviewBloodTest::class, ['bloodTest' => $bloodTest])
        ->call('useDraft', $draft->id)
        ->call('confirmResult')
        ->assertHasNoErrors();

    expect($draft->refresh()->value)->toBe('Niet gedetecteerd')
        ->and($draft->confirmed_at)->not->toBeNull();
});
