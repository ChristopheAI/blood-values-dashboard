<?php

use App\Domain\BloodTests\BuildLongitudinalChanges;
use App\Models\Biomarker;
use App\Models\BiomarkerResult;
use App\Models\BloodTest;
use App\Models\User;

it('builds same-unit numeric changes across owned confirmed blood tests', function () {
    $user = User::factory()->create();
    $ferritin = Biomarker::factory()->for($user)->create(['name' => 'Ferritin']);
    $may = BloodTest::factory()->for($user)->create(['test_date' => '2026-05-01']);
    $june = BloodTest::factory()->for($user)->create(['test_date' => '2026-06-01']);

    $previousResult = BiomarkerResult::factory()->for($may)->for($ferritin)->create([
        'value' => 42,
        'unit' => 'ug/L',
        'status' => 'normal',
        'confirmed_at' => now(),
    ]);
    $currentResult = BiomarkerResult::factory()->for($june)->for($ferritin)->create([
        'value' => 55,
        'unit' => 'ug/L',
        'status' => 'high',
        'confirmed_at' => now(),
    ]);

    $changes = app(BuildLongitudinalChanges::class)->across($user, collect([$june, $may]));

    expect($changes)->toHaveCount(1);

    $change = $changes->first();

    expect($change->biomarker)->toBe('Ferritin')
        ->and($change->previousResult->is($previousResult))->toBeTrue()
        ->and($change->result->is($currentResult))->toBeTrue()
        ->and($change->previousValue)->toBe('42')
        ->and($change->currentValue)->toBe('55')
        ->and($change->previousUnit)->toBe('ug/L')
        ->and($change->currentUnit)->toBe('ug/L')
        ->and($change->delta)->toBe('+13')
        ->and($change->changeLabel)->toBe('+13 ug/L')
        ->and($change->comparable)->toBeTrue()
        ->and($change->reason)->toBeNull();
});

it('orders undated blood tests after dated blood tests when building across changes', function () {
    $user = User::factory()->create();
    $ferritin = Biomarker::factory()->for($user)->create(['name' => 'Ferritin']);
    $currentBloodTest = BloodTest::factory()->for($user)->create(['test_date' => null]);
    $previousBloodTest = BloodTest::factory()->for($user)->create(['test_date' => '2026-05-01']);

    $previousResult = BiomarkerResult::factory()->for($previousBloodTest)->for($ferritin)->create([
        'value' => 40,
        'unit' => 'ug/L',
        'confirmed_at' => now()->subMonth(),
    ]);
    $currentResult = BiomarkerResult::factory()->for($currentBloodTest)->for($ferritin)->create([
        'value' => 42,
        'unit' => 'ug/L',
        'confirmed_at' => now(),
    ]);

    $changes = app(BuildLongitudinalChanges::class)->across($user, collect([$currentBloodTest, $previousBloodTest]));

    expect($changes)->toHaveCount(1);

    $change = $changes->first();

    expect($change->previousResult->is($previousResult))->toBeTrue()
        ->and($change->result->is($currentResult))->toBeTrue()
        ->and($change->changeLabel)->toBe('+2 ug/L');
});

it('marks unsafe pairwise comparisons with explicit reasons', function () {
    $user = User::factory()->create();
    $vitaminD = Biomarker::factory()->for($user)->create(['name' => 'Vitamin D']);
    $crp = Biomarker::factory()->for($user)->create(['name' => 'CRP']);
    $tsh = Biomarker::factory()->for($user)->create(['name' => 'TSH']);
    $may = BloodTest::factory()->for($user)->create(['test_date' => '2026-05-01']);
    $june = BloodTest::factory()->for($user)->create(['test_date' => '2026-06-01']);

    BiomarkerResult::factory()->for($may)->for($vitaminD)->create([
        'value' => 24,
        'unit' => 'ng/mL',
        'confirmed_at' => now(),
    ]);
    BiomarkerResult::factory()->for($june)->for($vitaminD)->create([
        'value' => 60,
        'unit' => 'nmol/L',
        'confirmed_at' => now(),
    ]);
    BiomarkerResult::factory()->for($june)->for($crp)->create([
        'value' => 1.2,
        'unit' => 'mg/L',
        'confirmed_at' => now(),
    ]);
    BiomarkerResult::factory()->for($may)->for($tsh)->create([
        'value' => 2.1,
        'unit' => '',
        'confirmed_at' => now(),
    ]);
    BiomarkerResult::factory()->for($june)->for($tsh)->create([
        'value' => 2.4,
        'unit' => '',
        'confirmed_at' => now(),
    ]);

    $rows = app(BuildLongitudinalChanges::class)->between($user, $may, $june)->keyBy('biomarker');

    expect($rows['Vitamin D']->comparable)->toBeFalse()
        ->and($rows['Vitamin D']->reason)->toBe('unit_mismatch')
        ->and($rows['Vitamin D']->delta)->toBe('not comparable')
        ->and($rows['CRP']->comparable)->toBeFalse()
        ->and($rows['CRP']->reason)->toBe('missing_previous')
        ->and($rows['CRP']->delta)->toBe('not measured')
        ->and($rows['TSH']->comparable)->toBeFalse()
        ->and($rows['TSH']->reason)->toBe('missing_unit')
        ->and($rows['TSH']->delta)->toBe('not comparable');
});

it('excludes drafts foreign blood tests and cross-owner biomarker links', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $ferritin = Biomarker::factory()->for($user)->create(['name' => 'Ferritin']);
    $draftMarker = Biomarker::factory()->for($user)->create(['name' => 'Draft marker']);
    $foreignMarker = Biomarker::factory()->for($otherUser)->create(['name' => 'Foreign marker']);
    $may = BloodTest::factory()->for($user)->create(['test_date' => '2026-05-01']);
    $june = BloodTest::factory()->for($user)->create(['test_date' => '2026-06-01']);
    $foreignBloodTest = BloodTest::factory()->for($otherUser)->create(['test_date' => '2026-06-01']);

    BiomarkerResult::factory()->for($may)->for($ferritin)->create([
        'value' => 42,
        'unit' => 'ug/L',
        'confirmed_at' => now(),
    ]);
    BiomarkerResult::factory()->for($june)->for($ferritin)->create([
        'value' => 55,
        'unit' => 'ug/L',
        'confirmed_at' => now(),
    ]);
    BiomarkerResult::factory()->for($june)->for($draftMarker)->create([
        'value' => 999,
        'unit' => 'mg/L',
        'confirmed_at' => null,
    ]);
    BiomarkerResult::factory()->for($june)->for($foreignMarker)->create([
        'value' => 123,
        'unit' => 'mg/L',
        'confirmed_at' => now(),
    ]);
    BiomarkerResult::factory()->for($foreignBloodTest)->for($foreignMarker)->create([
        'value' => 321,
        'unit' => 'mg/L',
        'confirmed_at' => now(),
    ]);

    $changes = app(BuildLongitudinalChanges::class)->across($user, collect([$may, $june, $foreignBloodTest]));

    expect($changes->pluck('biomarker')->all())->toBe(['Ferritin'])
        ->and($changes->first()->changeLabel)->toBe('+13 ug/L');
});

it('caps undated blood tests to upload order when building across changes', function () {
    $user = User::factory()->create();
    $ferritin = Biomarker::factory()->for($user)->create(['name' => 'Ferritin']);
    $previousBloodTest = BloodTest::factory()->for($user)->create(['test_date' => '2026-05-01']);
    $undatedBloodTest = BloodTest::factory()->for($user)->create(['test_date' => null]);
    $futureBloodTest = BloodTest::factory()->for($user)->create(['test_date' => '2026-07-01']);

    $previousResult = BiomarkerResult::factory()->for($previousBloodTest)->for($ferritin)->create([
        'value' => 40,
        'unit' => 'ug/L',
        'confirmed_at' => now()->subMonths(2),
    ]);
    BiomarkerResult::factory()->for($futureBloodTest)->for($ferritin)->create([
        'value' => 50,
        'unit' => 'ug/L',
        'confirmed_at' => now(),
    ]);
    $undatedResult = BiomarkerResult::factory()->for($undatedBloodTest)->for($ferritin)->create([
        'value' => 42,
        'unit' => 'ug/L',
        'confirmed_at' => now()->subMonth(),
    ]);

    $changes = app(BuildLongitudinalChanges::class)->across(
        $user,
        $user->bloodTestsUpToAndIncluding($undatedBloodTest),
    );

    expect($changes)->toHaveCount(1);

    $change = $changes->first();

    expect($change->previousResult->is($previousResult))->toBeTrue()
        ->and($change->result->is($undatedResult))->toBeTrue()
        ->and($change->changeLabel)->toBe('+2 ug/L');
});

it('returns no blood tests for a foreign blood test in bloodTestsUpToAndIncluding', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $foreignBloodTest = BloodTest::factory()->for($otherUser)->create(['test_date' => '2026-05-01']);

    expect($user->bloodTestsUpToAndIncluding($foreignBloodTest))->toBeEmpty();
});

it('preserves below-detection prefixes when formatting confirmed values', function () {
    $user = User::factory()->create();
    $ra = Biomarker::factory()->for($user)->create(['name' => 'RA*']);
    $bloodTest = BloodTest::factory()->for($user)->create(['test_date' => '2026-05-19']);

    BiomarkerResult::factory()->for($bloodTest)->for($ra)->create([
        'value' => 10,
        'unit' => 'kIU/L',
        'status' => 'normal',
        'confirmed_at' => '2026-05-20 09:00:00',
        'source_snippet' => 'RA* <10 kIU/L ≤13 <',
    ]);

    $summary = app(\App\Domain\Dashboard\BuildLatestUploadSummary::class)->forBloodTest($user, $bloodTest);

    expect($summary['normalRows'][0]['valueLabel'])->toBe('<10 kIU/L');
});

it('treats repeated below-detection limits as unchanged rather than a zero delta', function () {
    $user = User::factory()->create();
    $ra = Biomarker::factory()->for($user)->create(['name' => 'RA*']);
    $may = BloodTest::factory()->for($user)->create(['test_date' => '2026-04-22']);
    $june = BloodTest::factory()->for($user)->create(['test_date' => '2026-05-19']);

    BiomarkerResult::factory()->for($may)->for($ra)->create([
        'value' => 10,
        'unit' => 'kIU/L',
        'status' => 'normal',
        'confirmed_at' => '2026-04-23 09:00:00',
        'source_snippet' => 'RA* <10 kIU/L ≤13 <',
    ]);
    BiomarkerResult::factory()->for($june)->for($ra)->create([
        'value' => 10,
        'unit' => 'kIU/L',
        'status' => 'normal',
        'confirmed_at' => '2026-05-20 09:00:00',
        'source_snippet' => 'RA* <10 kIU/L ≤13 <',
    ]);

    $changes = app(BuildLongitudinalChanges::class)->across($user, collect([$may, $june]));

    expect($changes)->toHaveCount(1)
        ->and($changes->first()->previousValue)->toBe('<10')
        ->and($changes->first()->currentValue)->toBe('<10')
        ->and($changes->first()->delta)->toBe('unchanged')
        ->and($changes->first()->direction)->toBe('unchanged')
        ->and($changes->first()->comparable)->toBeTrue();
});
