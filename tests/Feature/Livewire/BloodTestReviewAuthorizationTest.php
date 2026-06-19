<?php

use App\Livewire\BloodTests\ReviewBloodTest;
use App\Models\Biomarker;
use App\Models\BiomarkerResult;
use App\Models\BloodTest;
use App\Models\User;
use Livewire\Livewire;

it('user cannot open review for another users blood test', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $bloodTest = BloodTest::factory()->for($owner)->create();

    $this->actingAs($otherUser)
        ->get(route('blood-tests.show', $bloodTest))
        ->assertForbidden();
});

it('user cannot confirm values for another users blood test', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $ownersBloodTest = BloodTest::factory()->for($owner)->create();
    $otherUsersBloodTest = BloodTest::factory()->for($otherUser)->create();

    Livewire::actingAs($otherUser)
        ->test(ReviewBloodTest::class, ['bloodTest' => $otherUsersBloodTest])
        ->set('resultForm.name', 'Ferritin')
        ->set('resultForm.value', '42')
        ->set('resultForm.unit', 'ug/L')
        ->call('confirmResult', $ownersBloodTest->id)
        ->assertForbidden();

    expect(BiomarkerResult::query()->where('blood_test_id', $ownersBloodTest->id)->exists())->toBeFalse();
});

it('tampered livewire action parameter cannot confirm another users blood test', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $ownersBloodTest = BloodTest::factory()->for($owner)->create();
    $otherUsersBloodTest = BloodTest::factory()->for($otherUser)->create();

    Livewire::actingAs($otherUser)
        ->test(ReviewBloodTest::class, ['bloodTest' => $otherUsersBloodTest])
        ->set('resultForm.name', 'Ferritin')
        ->set('resultForm.value', '42')
        ->set('resultForm.unit', 'ug/L')
        ->call('confirmResult', $ownersBloodTest->id)
        ->assertForbidden();

    expect(BiomarkerResult::query()->where('blood_test_id', $ownersBloodTest->id)->exists())->toBeFalse();
});

it('tampered livewire public property cannot switch owner context', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $ownersBloodTest = BloodTest::factory()->for($owner)->create();
    $otherUsersBloodTest = BloodTest::factory()->for($otherUser)->create();

    Livewire::actingAs($otherUser)
        ->test(ReviewBloodTest::class, ['bloodTest' => $otherUsersBloodTest])
        ->set('resultForm.name', 'Ferritin')
        ->set('resultForm.value', '42')
        ->set('resultForm.unit', 'ug/L')
        ->set('bloodTestId', $ownersBloodTest->id)
        ->assertForbidden();

    expect(BiomarkerResult::query()->where('blood_test_id', $ownersBloodTest->id)->exists())->toBeFalse();
});

it('rejects review state with a cross-owner biomarker relation', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $foreignBiomarker = Biomarker::factory()->for($otherUser)->create(['name' => 'Other private marker']);
    $bloodTest = BloodTest::factory()->for($owner)->create(['status' => 'reviewing']);

    BiomarkerResult::factory()->for($bloodTest)->for($foreignBiomarker)->create([
        'entry_source' => 'extracted',
        'confirmed_at' => null,
        'extracted_name' => 'Sanitized extracted marker',
    ]);

    Livewire::actingAs($owner)
        ->test(ReviewBloodTest::class, ['bloodTest' => $bloodTest])
        ->assertForbidden();
});
