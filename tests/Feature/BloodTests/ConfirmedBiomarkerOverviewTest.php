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
        ->assertSeeInOrder(['laag', 'hoog', 'normaal', 'geen status'])
        ->assertSee('data-test="confirmed-summary-count-high"', false)
        ->assertSee('border-amber-300 bg-amber-50', false)
        ->assertSee('data-test="confirmed-summary-count-unknown"', false)
        ->assertSee('data-test="confirmed-overview-attention"', false)
        ->assertSee('data-test="confirmed-overview-normal"', false)
        ->assertSee('data-test="confirmed-overview-unknown"', false);
});

it('builds the grouped overview payload with counts the view renders verbatim', function () {
    $user = User::factory()->create();
    $bloodTest = BloodTest::factory()->for($user)->create(['test_date' => now()->subDay()]);

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

    // Een tweede, oudere meting van een bestaande biomarker: telt mee in
    // 'measurements' maar levert geen extra rij op — dedup per biomarker.
    $olderBloodTest = BloodTest::factory()->for($user)->create(['test_date' => now()->subMonths(2)]);
    BiomarkerResult::factory()
        ->for($olderBloodTest)
        ->for(Biomarker::query()->where('name', 'Hoge marker')->firstOrFail())
        ->create([
            'value' => 45, 'unit' => 'mg/L', 'reference_min' => 20, 'reference_max' => 30,
            'status' => 'high', 'confirmed_at' => now()->subMonths(2),
        ]);

    $overview = app(BuildBloodResultsOverview::class)->overview($user);

    expect($overview['counts'])->toBe([
        'biomarkers' => 4,
        'measurements' => 5,
        'low' => 1,
        'high' => 1,
        'normal' => 1,
        'unknown' => 1,
    ])
        ->and($overview['attention']->pluck('label')->all())->toBe(['Hoge marker', 'Lage marker'])
        ->and($overview['normal']->pluck('label')->all())->toBe(['Normale marker'])
        ->and($overview['unknown']->pluck('label')->all())->toBe(['Onbekende marker']);
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

    // The stale April value is not shown as a current row; it may only appear
    // inside the labeled 'Vorige meting' history referent.
    $response = $this->actingAs($user)
        ->get(route('blood-results.overview'))
        ->assertOk()
        ->assertSee('1 biomarker')
        ->assertSee('2 bevestigde waarden')
        ->assertSee('7.8')
        ->assertSee('15 juni 2026')
        ->assertSee('Vorige meting');

    expect(substr_count($response->getContent(), '15 april 2026'))->toBe(1);
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

it('states the magnitude beyond the reference as a factual sentence with arrow pill', function () {
    $user = User::factory()->create();
    $bloodTest = BloodTest::factory()->for($user)->create(['test_date' => '2026-06-15']);

    confirmedOverviewResult($user, $bloodTest, 'CRP', [
        'value' => '7.8', 'unit' => 'mg/L', 'reference_min' => 0, 'reference_max' => 5,
        'status' => 'high', 'confirmed_at' => now(),
    ]);

    $row = app(BuildBloodResultsOverview::class)($user)->firstWhere('label', 'CRP');

    expect($row['beyond'])->toBe(['direction' => 'above', 'label' => '2.8']);

    $this->actingAs($user)
        ->get(route('blood-results.overview'))
        ->assertOk()
        ->assertSee('↑ hoog')
        ->assertSee('Ligt 2.8 mg/L boven de opgegeven referentie.')
        ->assertSee('data-test="confirmed-beyond-sentence"', false);
});

it('explains why a detection-limit row has no status and never computes a beyond-magnitude for it', function () {
    $user = User::factory()->create();
    $bloodTest = BloodTest::factory()->for($user)->create();

    confirmedOverviewResult($user, $bloodTest, 'CMV IgM', [
        'value' => '50', 'value_comparator' => '<', 'unit' => 'U/L', 'reference_max' => 30,
        'status' => 'unknown', 'confirmed_at' => now(),
    ]);

    $row = app(BuildBloodResultsOverview::class)($user)->firstWhere('label', 'CMV IgM');

    expect($row['beyond'])->toBeNull();

    $this->actingAs($user)
        ->get(route('blood-results.overview'))
        ->assertOk()
        ->assertSee('geen status')
        ->assertSee('is een meetgrens van het lab, geen exacte meting');
});

it('captions the reference in its own unit when it differs from the value unit', function () {
    $user = User::factory()->create();
    $bloodTest = BloodTest::factory()->for($user)->create();

    confirmedOverviewResult($user, $bloodTest, 'Eenheid mismatch', [
        'value' => 5, 'unit' => 'mg/L', 'reference_min' => 1, 'reference_max' => 3,
        'reference_unit' => 'µmol/L', 'status' => 'unknown', 'confirmed_at' => now(),
    ]);

    $row = app(BuildBloodResultsOverview::class)($user)->firstWhere('label', 'Eenheid mismatch');

    expect($row['reference'])->toBe('1 – 3 µmol/L')
        ->and($row['reference_unit_mismatch'])->toBeTrue();

    $this->actingAs($user)
        ->get(route('blood-results.overview'))
        ->assertOk()
        ->assertSee('1 – 3 µmol/L')
        ->assertSee('andere eenheid dan de meting');
});

it('shows the reference-context reassurance line once when a value is out of range', function () {
    $user = User::factory()->create();
    $bloodTest = BloodTest::factory()->for($user)->create();

    confirmedOverviewResult($user, $bloodTest, 'CRP', [
        'value' => '7.8', 'unit' => 'mg/L', 'reference_min' => 0, 'reference_max' => 5,
        'status' => 'high', 'confirmed_at' => now(),
    ]);

    $response = $this->actingAs($user)->get(route('blood-results.overview'))->assertOk();

    expect(substr_count($response->getContent(), 'data-test="confirmed-reference-context"'))->toBe(1);

    $response
        ->assertSee('ook gezonde mensen er soms buiten vallen')
        ->assertSee('data-test="confirmed-reference-context"', false);
});

it('does not show the reassurance line when nothing is out of range', function () {
    $user = User::factory()->create();
    $bloodTest = BloodTest::factory()->for($user)->create();

    confirmedOverviewResult($user, $bloodTest, 'Ferritine', [
        'value' => '80', 'unit' => 'ug/L', 'reference_min' => 30, 'reference_max' => 150,
        'status' => 'normal', 'confirmed_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('blood-results.overview'))
        ->assertOk()
        ->assertDontSee('data-test="confirmed-reference-context"', false);
});

it('shows the previous measurement with its date on an out-of-range card', function () {
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

    $row = app(BuildBloodResultsOverview::class)($user)->firstWhere('label', 'CRP');

    expect($row['history']['previousLabel'])->toBe('1.2 mg/L')
        ->and($row['history']['previousDate'])->toBe('15 april 2026')
        ->and($row['history']['delta'])->toBe('+6.6 mg/L');

    $this->actingAs($user)
        ->get(route('blood-results.overview'))
        ->assertOk()
        ->assertSee('Vorige meting')
        ->assertSee('15 april 2026')
        ->assertSee('data-test="confirmed-history"', false);
});

it('omits the delta in history when units changed between measurements', function () {
    $user = User::factory()->create();
    $biomarker = Biomarker::factory()->for($user)->create(['name' => 'Glucose']);

    $older = BloodTest::factory()->for($user)->create(['test_date' => '2026-04-15']);
    BiomarkerResult::factory()->for($older)->for($biomarker)->create([
        'value' => '90', 'unit' => 'mg/dL', 'reference_min' => 70, 'reference_max' => 100,
        'status' => 'normal', 'confirmed_at' => now()->subMonth(),
    ]);

    $current = BloodTest::factory()->for($user)->create(['test_date' => '2026-06-15']);
    BiomarkerResult::factory()->for($current)->for($biomarker)->create([
        'value' => '7', 'unit' => 'mmol/L', 'reference_min' => 4, 'reference_max' => 6,
        'status' => 'high', 'confirmed_at' => now(),
    ]);

    $row = app(BuildBloodResultsOverview::class)($user)->firstWhere('label', 'Glucose');

    expect($row['history']['previousLabel'])->toBe('90 mg/dL')
        ->and($row['history']['previousDate'])->toBe('15 april 2026')
        ->and($row['history']['delta'])->toBeNull();
});

it('treats an undated blood test as the newest measurement, matching the longitudinal chronology', function () {
    $user = User::factory()->create();
    $biomarker = Biomarker::factory()->for($user)->create(['name' => 'CRP']);

    $dated = BloodTest::factory()->for($user)->create(['test_date' => '2026-06-01']);
    BiomarkerResult::factory()->for($dated)->for($biomarker)->create([
        'value' => '10', 'unit' => 'mg/L', 'reference_min' => 0, 'reference_max' => 5,
        'status' => 'high', 'confirmed_at' => now(),
    ]);

    // Datum kon niet worden vastgesteld; across() en het overzicht moeten deze
    // meting allebei als nieuwste behandelen, anders keert de delta om.
    $undated = BloodTest::factory()->for($user)->create(['test_date' => null]);
    BiomarkerResult::factory()->for($undated)->for($biomarker)->create([
        'value' => '20', 'unit' => 'mg/L', 'reference_min' => 0, 'reference_max' => 5,
        'status' => 'high', 'confirmed_at' => now()->subDay(),
    ]);

    $row = app(BuildBloodResultsOverview::class)($user)->firstWhere('label', 'CRP');

    expect($row['value'])->toBe('20')
        ->and($row['date'])->toBeNull()
        ->and($row['history']['previousLabel'])->toBe('10 mg/L')
        ->and($row['history']['previousDate'])->toBe('1 juni 2026')
        ->and($row['history']['delta'])->toBe('+10 mg/L');
});

it('explains a no-status row even when no specific cause applies', function () {
    $user = User::factory()->create();
    $bloodTest = BloodTest::factory()->for($user)->create();

    // Kwalitatieve waarde naast een numerieke referentie: geen meetgrens,
    // geen ontbrekende referentie, geen eenheidsverschil — toch geen status.
    confirmedOverviewResult($user, $bloodTest, 'CMV IgG', [
        'value' => 'Negatief', 'unit' => 'index', 'reference_min' => 0, 'reference_max' => 1,
        'status' => 'unknown', 'confirmed_at' => now(),
    ]);

    $row = app(BuildBloodResultsOverview::class)($user)->firstWhere('label', 'CMV IgG');

    expect($row['no_status_reason'])->toBe('not_classified');

    $this->actingAs($user)
        ->get(route('blood-results.overview'))
        ->assertOk()
        ->assertSee('De waarde en de referentie konden niet automatisch vergeleken worden');
});

it('shows an empty state without any values', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('blood-results.overview'))
        ->assertOk()
        ->assertSee('data-test="confirmed-overview-empty"', false)
        ->assertSee('Nog geen bevestigde waarden');
});
