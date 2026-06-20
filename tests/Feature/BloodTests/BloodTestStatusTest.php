<?php

use App\Models\Biomarker;
use App\Models\BiomarkerResult;
use App\Models\BloodTest;
use App\Models\User;

it('does not mark a blood test confirmed from a foreign biomarker result', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $bloodTest = BloodTest::factory()->for($user)->create(['status' => 'reviewing']);
    $foreignBiomarker = Biomarker::factory()->for($otherUser)->create(['name' => 'Foreign private marker']);

    BiomarkerResult::factory()->for($bloodTest)->for($foreignBiomarker)->create([
        'entry_source' => 'extracted',
        'confirmed_at' => now(),
    ]);

    $bloodTest->recalculateStatusFromResults();

    expect($bloodTest->refresh()->status)->toBe('reviewing')
        ->and($bloodTest->confirmedResults()->count())->toBe(0);
});

it('does not let a foreign extracted draft block confirmed status for owned results', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $bloodTest = BloodTest::factory()->for($user)->create(['status' => 'reviewing']);
    $ownedBiomarker = Biomarker::factory()->for($user)->create(['name' => 'Ferritin']);
    $foreignBiomarker = Biomarker::factory()->for($otherUser)->create(['name' => 'Foreign private marker']);

    BiomarkerResult::factory()->for($bloodTest)->for($ownedBiomarker)->create([
        'entry_source' => 'extracted',
        'confirmed_at' => now(),
    ]);
    BiomarkerResult::factory()->for($bloodTest)->for($foreignBiomarker)->create([
        'entry_source' => 'extracted',
        'confirmed_at' => null,
    ]);

    $bloodTest->recalculateStatusFromResults();

    expect($bloodTest->refresh()->status)->toBe('confirmed');
});
