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
        ->assertSee('24 ng/mL')
        ->assertSee('60 nmol/L')
        ->assertDontSee('24 nmol/L')
        ->assertSee('not comparable')
        ->assertSee('CRP')
        ->assertSee('not measured')
        ->assertDontSee('not measured mg/L')
        ->assertDontSee('Unconfirmed');
});

it('shows a compare entry point on the blood tests index', function () {
    $user = User::factory()->create();
    $older = BloodTest::factory()->for($user)->create([
        'title' => 'April test',
        'test_date' => '2026-04-01',
    ]);
    $latest = BloodTest::factory()->for($user)->create([
        'title' => 'Juni test',
        'test_date' => '2026-06-01',
    ]);

    $this->actingAs($user)
        ->get(route('blood-tests.index'))
        ->assertOk()
        ->assertSee('data-test="compare-blood-tests-form"', false)
        ->assertSee('name="first"', false)
        ->assertSee('name="second"', false)
        ->assertSee('April test')
        ->assertSee('Juni test')
        ->assertSee('value="'.$older->id.'" selected', false)
        ->assertSee('value="'.$latest->id.'" selected', false);
});

it('redirects a bare compare route back to the blood test list with a visible message', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('blood-tests.compare'))
        ->assertRedirect(route('blood-tests.index'))
        ->assertSessionHas('compare_error', 'Kies twee bloedtesten om te vergelijken.');

    $this->actingAs($user)
        ->withSession(['compare_error' => 'Kies twee bloedtesten om te vergelijken.'])
        ->get(route('blood-tests.index'))
        ->assertOk()
        ->assertSee('data-test="compare-selection-message"', false)
        ->assertSee('Kies twee bloedtesten om te vergelijken.');
});

it('redirects same-test comparisons back with a visible message', function () {
    $user = User::factory()->create();
    $bloodTest = BloodTest::factory()->for($user)->create();

    $this->actingAs($user)
        ->get(route('blood-tests.compare', ['first' => $bloodTest, 'second' => $bloodTest]))
        ->assertRedirect(route('blood-tests.index'))
        ->assertSessionHas('compare_error', 'Kies twee verschillende bloedtesten om te vergelijken.');
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

it('does not compare confirmed results linked to another users biomarker', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $first = BloodTest::factory()->for($user)->create(['test_date' => '2026-05-01']);
    $second = BloodTest::factory()->for($user)->create(['test_date' => '2026-06-01']);
    $foreignMarker = Biomarker::factory()->for($otherUser)->create(['name' => 'Foreign private marker']);

    BiomarkerResult::factory()->for($second)->for($foreignMarker)->create([
        'value' => 123,
        'unit' => 'mg/L',
        'confirmed_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('blood-tests.compare', ['first' => $first, 'second' => $second]))
        ->assertOk()
        ->assertDontSee('Foreign private marker')
        ->assertDontSee('123');
});
