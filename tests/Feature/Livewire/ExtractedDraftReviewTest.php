<?php

use App\Livewire\BloodTests\ReviewBloodTest;
use App\Models\Biomarker;
use App\Models\BiomarkerResult;
use App\Models\BloodTest;
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
