<?php

use App\Models\Biomarker;
use App\Models\BiomarkerResult;
use App\Models\BloodTest;
use App\Models\User;

it('biomarker history uses confirmed values only and orders by test date', function () {
    $user = User::factory()->create();
    $biomarker = Biomarker::factory()->for($user)->create(['name' => 'Ferritin']);

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
        ->assertSeeInOrder(['2025-05-19', '35', '2026-05-19', '42'])
        ->assertDontSeeText('999');
});
