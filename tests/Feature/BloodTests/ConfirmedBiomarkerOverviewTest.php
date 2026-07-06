<?php

use App\Domain\Dashboard\BuildBloodResultsOverview;
use App\Models\Biomarker;
use App\Models\BiomarkerResult;
use App\Models\BloodTest;
use App\Models\User;

function confirmedOverviewResult(User $user, BloodTest $bloodTest, string $name, array $attributes): BiomarkerResult
{
    $biomarker = Biomarker::factory()->for($user)->create(['name' => $name]);

    return BiomarkerResult::factory()
        ->for($bloodTest)
        ->for($biomarker)
        ->create($attributes);
}

it('requires authentication for the confirmed biomarker overview', function () {
    $this->get(route('blood-results.overview'))
        ->assertRedirect(route('login'));
});

it('shows only confirmed values and never extracted drafts', function () {
    $user = User::factory()->create();
    $bloodTest = BloodTest::factory()->for($user)->create();

    confirmedOverviewResult($user, $bloodTest, 'Ferritine', [
        'value' => 42,
        'unit' => 'µg/L',
        'reference_min' => 20,
        'reference_max' => 300,
        'status' => 'normal',
        'confirmed_at' => now(),
    ]);

    // 87654 in plaats van een rond getal: icon-SVG-paths bevatten cijferreeksen
    // als "9.999", waar een assertDontSee('999') op zou matchen.
    confirmedOverviewResult($user, $bloodTest, 'Draftmarker', [
        'value' => 87654,
        'unit' => 'mg/L',
        'entry_source' => 'extracted',
        'status' => 'unknown',
        'confirmed_at' => null,
    ]);

    $this->actingAs($user)
        ->get(route('blood-results.overview'))
        ->assertOk()
        ->assertSee('data-test="confirmed-biomarker-overview"', false)
        ->assertSee('Ferritine')
        ->assertSee('1 biomarker')
        ->assertSee('1 bevestigde waarde')
        ->assertDontSee('Draftmarker')
        ->assertDontSee('87654');
});

it('never shows values owned by another user', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $ownBloodTest = BloodTest::factory()->for($user)->create();
    confirmedOverviewResult($user, $ownBloodTest, 'Eigen marker', [
        'value' => 12,
        'unit' => 'mg/L',
        'status' => 'normal',
        'confirmed_at' => now(),
    ]);

    $foreignBloodTest = BloodTest::factory()->for($otherUser)->create();
    confirmedOverviewResult($otherUser, $foreignBloodTest, 'Vreemde marker', [
        'value' => 9876.5,
        'unit' => 'mg/L',
        'status' => 'normal',
        'confirmed_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('blood-results.overview'))
        ->assertOk()
        ->assertSee('Eigen marker')
        ->assertDontSee('Vreemde marker')
        ->assertDontSee('9876.5');
});

it('groups rows by status with counts and keeps the display value as a string', function () {
    $user = User::factory()->create();
    $bloodTest = BloodTest::factory()->for($user)->create();

    confirmedOverviewResult($user, $bloodTest, 'Lage marker', [
        'value' => 10, 'unit' => 'mg/L', 'reference_min' => 20, 'reference_max' => 30,
        'status' => 'low', 'confirmed_at' => now()->subMinutes(3),
    ]);
    confirmedOverviewResult($user, $bloodTest, 'Hoge marker', [
        'value' => 50, 'unit' => 'mg/L', 'reference_min' => 20, 'reference_max' => 30,
        'status' => 'high', 'confirmed_at' => now()->subMinutes(2),
    ]);
    confirmedOverviewResult($user, $bloodTest, 'Normale marker', [
        'value' => 25, 'unit' => 'mg/L', 'reference_min' => 20, 'reference_max' => 30,
        'status' => 'normal', 'confirmed_at' => now()->subMinute(),
    ]);
    confirmedOverviewResult($user, $bloodTest, 'Onbekende marker', [
        'value' => 5, 'unit' => 'mg/L', 'status' => 'unknown', 'confirmed_at' => now(),
    ]);

    $rows = app(BuildBloodResultsOverview::class)($user);

    expect($rows)->toHaveCount(4)
        ->and($rows->pluck('status')->countBy()->all())->toEqualCanonicalizing(['unknown' => 1, 'normal' => 1, 'high' => 1, 'low' => 1])
        ->and($rows->firstWhere('label', 'Lage marker')['reference'])->toBe('20 – 30 mg/L')
        ->and($rows->firstWhere('label', 'Onbekende marker')['reference'])->toBe('—');

    foreach ($rows as $row) {
        expect($row['value'])->toBeString();
    }

    $this->actingAs($user)
        ->get(route('blood-results.overview'))
        ->assertOk()
        ->assertSeeInOrder(['laag', 'hoog', 'normaal', 'onbekend'])
        ->assertSee('data-test="confirmed-overview-attention"', false)
        ->assertSee('data-test="confirmed-overview-normal"', false)
        ->assertSee('data-test="confirmed-overview-unknown"', false);
});

it('positions the number line marker from the float value', function () {
    $user = User::factory()->create();
    $bloodTest = BloodTest::factory()->for($user)->create();

    confirmedOverviewResult($user, $bloodTest, 'Kreatinine', [
        'value' => 0.81,
        'unit' => 'mg/dL',
        'reference_min' => 0.5,
        'reference_max' => 1.0,
        'status' => 'normal',
        'confirmed_at' => now(),
    ]);

    // scale = [0.5 - 0.15*0.5, 1.0 + 0.15*0.5] = [0.425, 1.075]
    // position = (0.81 - 0.425) / 0.65 * 100 = 59.23%
    $this->actingAs($user)
        ->get(route('blood-results.overview'))
        ->assertOk()
        ->assertSee('data-test="confirmed-range-bar"', false)
        ->assertSee('left: 59.23%', false)
        ->assertSee('left: 11.54%; width: 76.92%', false)
        ->assertSee('0.81 mg/dL');
});

it('derives status through the fallback when the persisted status is unknown but references allow it', function () {
    $user = User::factory()->create();
    $bloodTest = BloodTest::factory()->for($user)->create();

    confirmedOverviewResult($user, $bloodTest, 'Fallback marker', [
        'value' => 5,
        'unit' => 'mg/L',
        'reference_min' => 1,
        'reference_max' => 3,
        'reference_unit' => 'mg/L',
        'status' => 'unknown',
        'confirmed_at' => now(),
    ]);

    $rows = app(BuildBloodResultsOverview::class)($user);

    expect($rows->firstWhere('label', 'Fallback marker')['status'])->toBe('high');

    $this->actingAs($user)
        ->get(route('blood-results.overview'))
        ->assertOk()
        ->assertSee('data-test="confirmed-overview-attention"', false)
        ->assertSee('hoog');
});

it('keeps the persisted status when it is conclusive and stays unknown on mismatched reference units', function () {
    $user = User::factory()->create();
    $bloodTest = BloodTest::factory()->for($user)->create();

    confirmedOverviewResult($user, $bloodTest, 'Vertrouwde kolom', [
        'value' => 25, 'unit' => 'mg/L', 'reference_min' => 20, 'reference_max' => 30,
        'status' => 'high', 'confirmed_at' => now(),
    ]);
    confirmedOverviewResult($user, $bloodTest, 'Eenheid mismatch', [
        'value' => 5, 'unit' => 'mg/L', 'reference_min' => 1, 'reference_max' => 3,
        'reference_unit' => 'µmol/L', 'status' => 'unknown', 'confirmed_at' => now(),
    ]);

    $rows = app(BuildBloodResultsOverview::class)($user);

    expect($rows->firstWhere('label', 'Vertrouwde kolom')['status'])->toBe('high')
        ->and($rows->firstWhere('label', 'Eenheid mismatch')['status'])->toBe('unknown');
});

it('does not duplicate the latest-upload digest on the overview page', function () {
    $user = User::factory()->create();
    $bloodTest = BloodTest::factory()->for($user)->create();

    confirmedOverviewResult($user, $bloodTest, 'Ferritine', [
        'value' => 42, 'unit' => 'µg/L', 'status' => 'normal', 'confirmed_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('blood-results.overview'))
        ->assertOk()
        ->assertDontSee('data-test="blood-results-overview"', false)
        ->assertDontSee('Je bloedresultaten');
});

it('shows one row per biomarker using the most recent measurement, not every historical value', function () {
    $user = User::factory()->create();
    $biomarker = Biomarker::factory()->for($user)->create(['name' => 'CRP']);

    $older = BloodTest::factory()->for($user)->create(['test_date' => '2026-04-15']);
    BiomarkerResult::factory()->for($older)->for($biomarker)->create([
        'value' => '1.2', 'unit' => 'mg/L', 'reference_min' => 0, 'reference_max' => 5,
        'status' => 'normal', 'confirmed_at' => now()->subMonth(),
    ]);

    $current = BloodTest::factory()->for($user)->create(['test_date' => '2026-06-15']);
    BiomarkerResult::factory()->for($current)->for($biomarker)->create([
        'value' => '7.8', 'unit' => 'mg/L', 'reference_min' => 0, 'reference_max' => 5,
        'status' => 'high', 'confirmed_at' => now(),
    ]);

    $rows = app(BuildBloodResultsOverview::class)($user);

    expect($rows)->toHaveCount(1)
        ->and($rows->first()['value'])->toBe('7.8')
        ->and($rows->first()['status'])->toBe('high')
        ->and($rows->first()['date'])->toBe('15 juni 2026');

    $this->actingAs($user)
        ->get(route('blood-results.overview'))
        ->assertOk()
        ->assertSee('1 biomarker')
        ->assertSee('2 bevestigde waarden')
        ->assertSee('7.8')
        ->assertSee('15 juni 2026')
        ->assertDontSee('15 april 2026');
});

it('shows the same confirmed-measurement total as the dashboard tile it links from', function () {
    $user = User::factory()->create();
    $biomarker = Biomarker::factory()->for($user)->create(['name' => 'CRP']);

    foreach (['2026-04-15', '2026-06-15'] as $date) {
        $bloodTest = BloodTest::factory()->for($user)->create(['test_date' => $date]);
        BiomarkerResult::factory()->for($bloodTest)->for($biomarker)->create([
            'value' => '1.2', 'unit' => 'mg/L', 'status' => 'normal', 'confirmed_at' => now(),
        ]);
    }

    // Dashboard 'Bevestigd' tile counts every confirmed measurement (2)…
    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSeeInOrder(['data-test="dashboard-metric-confirmed"', '2', 'Bevestigd'], false);

    // …and the overview it links to surfaces that same total next to the
    // deduped biomarker count, so the numbers reconcile for the user.
    $this->actingAs($user)
        ->get(route('blood-results.overview'))
        ->assertOk()
        ->assertSee('1 biomarker')
        ->assertSee('gebaseerd op')
        ->assertSee('2 bevestigde waarden');
});

it('keeps a detection-limit value in the unknown group and shows its prefix instead of the bare bound', function () {
    $user = User::factory()->create();
    $bloodTest = BloodTest::factory()->for($user)->create(['test_date' => '2026-06-15']);

    confirmedOverviewResult($user, $bloodTest, 'CMV IgM', [
        'value' => '50', 'unit' => 'U/L', 'reference_max' => 30,
        'status' => 'unknown', 'confirmed_at' => now(),
        'source_snippet' => 'CMV IgM < 50 U/L',
    ]);

    $row = app(BuildBloodResultsOverview::class)($user)->firstWhere('label', 'CMV IgM');

    expect($row['status'])->toBe('unknown')
        ->and($row['is_detection_limit'])->toBeTrue()
        ->and($row['valueLabel'])->toBe('<50');

    $this->actingAs($user)
        ->get(route('blood-results.overview'))
        ->assertOk()
        ->assertSee('data-test="confirmed-overview-unknown"', false)
        ->assertSee('<50 U/L')
        ->assertDontSee('data-test="confirmed-overview-attention"', false);
});

it('never float-casts a qualitative value into a status', function () {
    $user = User::factory()->create();
    $bloodTest = BloodTest::factory()->for($user)->create();

    // References present on purpose: without the is_numeric guard, (float) 'Negatief'
    // would be 0.0 and get classified 'normal' against 0–1.
    confirmedOverviewResult($user, $bloodTest, 'HIV-antistoffen', [
        'value' => 'Negatief', 'unit' => 'kwalitatief',
        'reference_min' => 0, 'reference_max' => 1,
        'status' => 'unknown', 'confirmed_at' => now(),
    ]);

    $row = app(BuildBloodResultsOverview::class)($user)->firstWhere('label', 'HIV-antistoffen');

    expect($row['status'])->toBe('unknown')
        ->and($row['valueLabel'])->toBe('Negatief');
});

it('is reachable from the sidebar navigation and the dashboard confirmed tile', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('blood-results.overview'))
        ->assertOk()
        ->assertSee('Mijn bloedwaarden')
        ->assertSee(route('blood-results.overview'));

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('data-test="dashboard-metric-link-confirmed"', false)
        ->assertSee(route('blood-results.overview'));
});

it('shows an empty state without any values', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('blood-results.overview'))
        ->assertOk()
        ->assertSee('data-test="confirmed-overview-empty"', false)
        ->assertSee('Nog geen bevestigde waarden');
});
