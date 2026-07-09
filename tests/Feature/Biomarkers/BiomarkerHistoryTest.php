<?php

use App\Models\Biomarker;
use App\Models\BiomarkerResult;
use App\Models\BloodTest;
use App\Models\User;

it('biomarker history uses confirmed values only and orders by test date', function () {
    $user = User::factory()->create();
    $biomarker = Biomarker::factory()->for($user)->create([
        'name' => 'Ferritin',
        'default_unit' => 'ug/L',
        'reference_min' => 30,
        'reference_max' => 150,
        'reference_unit' => 'ug/L',
    ]);

    $newerBloodTest = BloodTest::factory()->for($user)->create(['test_date' => '2026-05-19']);
    $olderBloodTest = BloodTest::factory()->for($user)->create(['test_date' => '2025-05-19']);
    $unconfirmedBloodTest = BloodTest::factory()->for($user)->create(['test_date' => '2026-01-19']);

    BiomarkerResult::factory()->for($newerBloodTest)->for($biomarker)->create([
        'value' => 42,
        'unit' => 'ug/L',
        'confirmed_at' => now(),
    ]);
    BiomarkerResult::factory()->for($olderBloodTest)->for($biomarker)->create([
        'value' => 35,
        'unit' => 'ug/L',
        'confirmed_at' => now(),
    ]);
    BiomarkerResult::factory()->for($unconfirmedBloodTest)->for($biomarker)->create([
        'value' => 999,
        'unit' => 'ug/L',
        'confirmed_at' => null,
    ]);

    $this->actingAs($user)
        ->get(route('biomarkers.show', $biomarker))
        ->assertOk()
        ->assertSee('data-test="back-to-blood-results"', false)
        ->assertSee(route('blood-results.overview'), false)
        ->assertSee('data-test="biomarker-trend-context"', false)
        ->assertSee('30 – 150 ug/L')
        ->assertSee('Laag, normaal, hoog of geen status')
        ->assertSeeInOrder(['19 mei 2025', '35', '19 mei 2026', '42'])
        ->assertDontSeeText('999');
});
