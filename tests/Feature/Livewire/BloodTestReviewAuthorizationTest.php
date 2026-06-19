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

it('rejects confirmed review state without a biomarker relation', function () {
    $owner = User::factory()->create();
    $bloodTest = BloodTest::factory()->for($owner)->create(['status' => 'confirmed']);

    BiomarkerResult::factory()->for($bloodTest)->create([
        'biomarker_id' => null,
        'entry_source' => 'pdf_reviewed',
        'confirmed_at' => now(),
        'extracted_name' => 'Sanitized extracted marker',
    ]);

    Livewire::actingAs($owner)
        ->test(ReviewBloodTest::class, ['bloodTest' => $bloodTest])
        ->assertForbidden();
});

it('rejects a tampered zero biomarker id instead of treating it as a new biomarker', function () {
    $user = User::factory()->create();
    $bloodTest = BloodTest::factory()->for($user)->create();

    Livewire::actingAs($user)
        ->test(ReviewBloodTest::class, ['bloodTest' => $bloodTest])
        ->set('resultForm.biomarker_id', 0)
        ->set('resultForm.name', 'Ferritin')
        ->set('resultForm.value', '42')
        ->set('resultForm.unit', 'ug/L')
        ->call('confirmResult')
        ->assertHasErrors(['resultForm.biomarker_id' => 'min']);

    expect(Biomarker::query()->where('user_id', $user->id)->count())->toBe(0)
        ->and(BiomarkerResult::query()->where('blood_test_id', $bloodTest->id)->count())->toBe(0);
});

it('trims padded review form names and units before storing confirmed values', function () {
    $user = User::factory()->create();
    $bloodTest = BloodTest::factory()->for($user)->create();

    Livewire::actingAs($user)
        ->test(ReviewBloodTest::class, ['bloodTest' => $bloodTest])
        ->set('resultForm.name', '  Ferritin  ')
        ->set('resultForm.value', '42')
        ->set('resultForm.unit', '  ug/L  ')
        ->set('resultForm.reference_min', '30')
        ->set('resultForm.reference_max', '150')
        ->set('resultForm.reference_unit', '  ug/L  ')
        ->call('confirmResult')
        ->assertHasNoErrors();

    $biomarker = Biomarker::query()->where('user_id', $user->id)->firstOrFail();
    $result = BiomarkerResult::query()->where('blood_test_id', $bloodTest->id)->firstOrFail();

    expect($biomarker->name)->toBe('Ferritin')
        ->and($biomarker->default_unit)->toBe('ug/L')
        ->and($biomarker->reference_unit)->toBe('ug/L')
        ->and($result->unit)->toBe('ug/L')
        ->and($result->reference_unit)->toBe('ug/L');
});
