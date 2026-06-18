<?php

use App\Models\Biomarker;
use App\Models\BiomarkerResult;
use App\Models\BloodTest;
use App\Models\User;

it('compares two blood tests using confirmed values only', function () {
    $user = User::factory()->create();
    $ferritin = Biomarker::factory()->for($user)->create(['name' => 'Ferritin']);
    $vitaminD = Biomarker::factory()->for($user)->create(['name' => 'Vitamin D']);
    $crp = Biomarker::factory()->for($user)->create(['name' => 'CRP']);
    $unconfirmed = Biomarker::factory()->for($user)->create(['name' => 'Unconfirmed']);

    $first = BloodTest::factory()->for($user)->create(['test_date' => '2025-05-19']);
    $second = BloodTest::factory()->for($user)->create(['test_date' => '2026-05-19']);

    BiomarkerResult::factory()->for($first)->for($ferritin)->create([
        'value' => 35,
        'unit' => 'ug/L',
        'confirmed_at' => now(),
    ]);
    BiomarkerResult::factory()->for($second)->for($ferritin)->create([
        'value' => 42,
        'unit' => 'ug/L',
        'confirmed_at' => now(),
    ]);
    BiomarkerResult::factory()->for($first)->for($vitaminD)->create([
        'value' => 24,
        'unit' => 'ng/mL',
        'confirmed_at' => now(),
    ]);
    BiomarkerResult::factory()->for($second)->for($vitaminD)->create([
        'value' => 60,
        'unit' => 'nmol/L',
        'confirmed_at' => now(),
    ]);
    BiomarkerResult::factory()->for($second)->for($crp)->create([
        'value' => 1.2,
        'unit' => 'mg/L',
        'confirmed_at' => now(),
    ]);
    BiomarkerResult::factory()->for($second)->for($unconfirmed)->create([
        'value' => 999,
        'unit' => 'mg/L',
        'confirmed_at' => null,
    ]);

    $this->actingAs($user)
        ->get(route('blood-tests.compare', ['first' => $first, 'second' => $second]))
        ->assertOk()
        ->assertSee('Ferritin')
        ->assertSee('+7')
        ->assertSee('Vitamin D')
        ->assertSee('not comparable')
        ->assertSee('CRP')
        ->assertSee('not measured')
        ->assertDontSee('Unconfirmed');
});

it('user cannot compare another users blood tests', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $ownersBloodTest = BloodTest::factory()->for($owner)->create();
    $otherUsersBloodTest = BloodTest::factory()->for($otherUser)->create();

    $this->actingAs($otherUser)
        ->get(route('blood-tests.compare', ['first' => $ownersBloodTest, 'second' => $otherUsersBloodTest]))
        ->assertForbidden();
});
