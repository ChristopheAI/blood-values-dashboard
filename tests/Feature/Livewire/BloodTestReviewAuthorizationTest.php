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

it('tampered livewire action cannot delete another users extracted draft', function () {
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
        ->call('deleteDraft', $ownersDraft->id)
        ->assertForbidden();

    expect(BiomarkerResult::query()->whereKey($ownersDraft->id)->exists())->toBeTrue();
});

it('tampered livewire action cannot edit another users confirmed result', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $biomarker = Biomarker::factory()->for($owner)->create(['name' => 'Ferritin']);
    $ownersBloodTest = BloodTest::factory()->for($owner)->create(['status' => 'confirmed']);
    $otherUsersBloodTest = BloodTest::factory()->for($otherUser)->create(['status' => 'reviewing']);
    $ownersResult = BiomarkerResult::factory()->for($ownersBloodTest)->for($biomarker)->create([
        'value' => 42,
        'unit' => 'ug/L',
        'status' => 'normal',
        'confirmed_at' => now(),
    ]);

    Livewire::actingAs($otherUser)
        ->test(ReviewBloodTest::class, ['bloodTest' => $otherUsersBloodTest])
        ->call('editConfirmedResult', $ownersResult->id)
        ->assertForbidden();
});

it('tampered livewire action cannot delete another users confirmed result', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $biomarker = Biomarker::factory()->for($owner)->create(['name' => 'Ferritin']);
    $ownersBloodTest = BloodTest::factory()->for($owner)->create(['status' => 'confirmed']);
    $otherUsersBloodTest = BloodTest::factory()->for($otherUser)->create(['status' => 'reviewing']);
    $ownersResult = BiomarkerResult::factory()->for($ownersBloodTest)->for($biomarker)->create([
        'value' => 42,
        'unit' => 'ug/L',
        'status' => 'normal',
        'confirmed_at' => now(),
    ]);

    Livewire::actingAs($otherUser)
        ->test(ReviewBloodTest::class, ['bloodTest' => $otherUsersBloodTest])
        ->call('deleteConfirmedResult', $ownersResult->id)
        ->assertForbidden();

    expect(BiomarkerResult::query()->whereKey($ownersResult->id)->exists())->toBeTrue();
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

it('rejects manually adding a value with another users biomarker id', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $foreignBiomarker = Biomarker::factory()->for($otherUser)->create(['name' => 'Foreign private marker']);
    $bloodTest = BloodTest::factory()->for($owner)->create();

    Livewire::actingAs($owner)
        ->test(ReviewBloodTest::class, ['bloodTest' => $bloodTest])
        ->set('resultForm.biomarker_id', $foreignBiomarker->id)
        ->set('resultForm.value', '42')
        ->set('resultForm.unit', 'ug/L')
        ->call('confirmResult')
        ->assertForbidden();

    expect(BiomarkerResult::query()->where('blood_test_id', $bloodTest->id)->exists())->toBeFalse();
});

it('rejects confirming an extracted draft with another users biomarker id', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $foreignBiomarker = Biomarker::factory()->for($otherUser)->create(['name' => 'Foreign private marker']);
    $ownedBiomarker = Biomarker::factory()->for($owner)->create(['name' => 'Ferritin']);
    $bloodTest = BloodTest::factory()->for($owner)->create(['status' => 'reviewing']);
    $draft = BiomarkerResult::factory()->for($bloodTest)->for($ownedBiomarker)->create([
        'entry_source' => 'extracted',
        'confirmed_at' => null,
        'extracted_name' => 'Ferritin',
        'value' => 42,
        'unit' => 'ug/L',
    ]);

    Livewire::actingAs($owner)
        ->test(ReviewBloodTest::class, ['bloodTest' => $bloodTest])
        ->call('useDraft', $draft->id)
        ->set('resultForm.biomarker_id', $foreignBiomarker->id)
        ->call('confirmResult')
        ->assertForbidden();

    expect($draft->refresh()->confirmed_at)->toBeNull()
        ->and($draft->biomarker_id)->toBe($ownedBiomarker->id);
});

it('rejects editing a confirmed result with another users biomarker id', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $foreignBiomarker = Biomarker::factory()->for($otherUser)->create(['name' => 'Foreign private marker']);
    $ownedBiomarker = Biomarker::factory()->for($owner)->create(['name' => 'Ferritin']);
    $bloodTest = BloodTest::factory()->for($owner)->create(['status' => 'confirmed']);
    $result = BiomarkerResult::factory()->for($bloodTest)->for($ownedBiomarker)->create([
        'value' => 42,
        'unit' => 'ug/L',
        'status' => 'normal',
        'confirmed_at' => now(),
    ]);

    Livewire::actingAs($owner)
        ->test(ReviewBloodTest::class, ['bloodTest' => $bloodTest])
        ->call('editConfirmedResult', $result->id)
        ->set('resultForm.biomarker_id', $foreignBiomarker->id)
        ->call('confirmResult')
        ->assertForbidden();

    expect($result->refresh()->biomarker_id)->toBe($ownedBiomarker->id)
        ->and((float) $result->value)->toBe(42.0);
});

it('treats the create-new select empty value as no biomarker id', function () {
    $user = User::factory()->create();
    $bloodTest = BloodTest::factory()->for($user)->create();

    Livewire::actingAs($user)
        ->test(ReviewBloodTest::class, ['bloodTest' => $bloodTest])
        ->set('resultForm.biomarker_id', '')
        ->set('resultForm.name', 'Ferritin')
        ->set('resultForm.value', '42')
        ->set('resultForm.unit', 'ug/L')
        ->call('confirmResult')
        ->assertHasNoErrors();

    $biomarker = Biomarker::query()->where('user_id', $user->id)->firstOrFail();
    $result = BiomarkerResult::query()->where('blood_test_id', $bloodTest->id)->firstOrFail();

    expect($biomarker->name)->toBe('Ferritin')
        ->and($result->biomarker_id)->toBe($biomarker->id);
});

it('rejects whitespace-only manual biomarker names and units after trimming', function () {
    $user = User::factory()->create();
    $bloodTest = BloodTest::factory()->for($user)->create();

    Livewire::actingAs($user)
        ->test(ReviewBloodTest::class, ['bloodTest' => $bloodTest])
        ->set('resultForm.biomarker_id', '')
        ->set('resultForm.name', '   ')
        ->set('resultForm.value', '42')
        ->set('resultForm.unit', '   ')
        ->call('confirmResult')
        ->assertHasErrors([
            'resultForm.name' => 'required_without',
            'resultForm.unit' => 'required',
        ]);

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

it('accepts decimal comma values in manual review input', function () {
    $user = User::factory()->create();
    $bloodTest = BloodTest::factory()->for($user)->create();

    Livewire::actingAs($user)
        ->test(ReviewBloodTest::class, ['bloodTest' => $bloodTest])
        ->set('resultForm.name', 'Marker Alpha')
        ->set('resultForm.value', '12,4')
        ->set('resultForm.unit', 'mg/L')
        ->set('resultForm.reference_min', '10,0')
        ->set('resultForm.reference_max', '20,0')
        ->set('resultForm.reference_unit', 'mg/L')
        ->call('confirmResult')
        ->assertHasNoErrors();

    $biomarker = Biomarker::query()->where('user_id', $user->id)->firstOrFail();
    $result = BiomarkerResult::query()->where('blood_test_id', $bloodTest->id)->firstOrFail();

    expect((float) $biomarker->reference_min)->toBe(10.0)
        ->and((float) $biomarker->reference_max)->toBe(20.0)
        ->and((float) $result->value)->toBe(12.4)
        ->and((float) $result->reference_min)->toBe(10.0)
        ->and((float) $result->reference_max)->toBe(20.0)
        ->and($result->status)->toBe('normal');
});

it('reuses an owned biomarker when a manual review name only differs by case', function () {
    $user = User::factory()->create();
    $biomarker = Biomarker::factory()->for($user)->create(['name' => 'Ferritin']);
    $bloodTest = BloodTest::factory()->for($user)->create();

    Livewire::actingAs($user)
        ->test(ReviewBloodTest::class, ['bloodTest' => $bloodTest])
        ->set('resultForm.name', '  ferritin  ')
        ->set('resultForm.value', '42')
        ->set('resultForm.unit', 'ug/L')
        ->call('confirmResult')
        ->assertHasNoErrors();

    $result = BiomarkerResult::query()->where('blood_test_id', $bloodTest->id)->firstOrFail();

    expect(Biomarker::query()->where('user_id', $user->id)->count())->toBe(1)
        ->and($result->biomarker_id)->toBe($biomarker->id)
        ->and($result->biomarker->name)->toBe('Ferritin');
});

it('reuses an owned biomarker when a manual review name contains pdf whitespace', function () {
    $user = User::factory()->create();
    $biomarker = Biomarker::factory()->for($user)->create(['name' => 'Marker Alpha']);
    $bloodTest = BloodTest::factory()->for($user)->create();

    Livewire::actingAs($user)
        ->test(ReviewBloodTest::class, ['bloodTest' => $bloodTest])
        ->set('resultForm.name', "\u{00A0}marker\u{00A0}alpha\u{00A0}")
        ->set('resultForm.value', '12.4')
        ->set('resultForm.unit', 'mg/L')
        ->call('confirmResult')
        ->assertHasNoErrors();

    $result = BiomarkerResult::query()->where('blood_test_id', $bloodTest->id)->firstOrFail();

    expect(Biomarker::query()->where('user_id', $user->id)->count())->toBe(1)
        ->and($result->biomarker_id)->toBe($biomarker->id)
        ->and($result->biomarker->name)->toBe('Marker Alpha');
});

it('rejects name-only confirmation when normalized biomarker names are ambiguous', function () {
    $user = User::factory()->create();
    Biomarker::factory()->for($user)->create(['name' => 'Marker Alpha']);
    Biomarker::factory()->for($user)->create(['name' => 'Marker  Alpha']);
    $bloodTest = BloodTest::factory()->for($user)->create();

    $component = Livewire::actingAs($user)
        ->test(ReviewBloodTest::class, ['bloodTest' => $bloodTest])
        ->set('resultForm.name', 'marker alpha')
        ->set('resultForm.value', '12.4')
        ->set('resultForm.unit', 'mg/L');

    $exception = null;

    try {
        $component->call('confirmResult');
    } catch (Throwable $caught) {
        $exception = $caught;
    }

    expect($exception)->toBeNull();

    $component->assertHasErrors(['resultForm.name']);

    expect(BiomarkerResult::query()->where('blood_test_id', $bloodTest->id)->exists())->toBeFalse();
});
