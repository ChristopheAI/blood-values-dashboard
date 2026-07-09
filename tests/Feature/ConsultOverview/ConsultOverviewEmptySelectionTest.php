<?php

use App\Models\Biomarker;
use App\Models\BiomarkerResult;
use App\Models\BloodTest;
use App\Models\BloodTestDocument;
use App\Models\User;

it('does not widen an empty consult selection to all owned blood tests', function () {
    $user = User::factory()->create();
    $ferritin = Biomarker::factory()->for($user)->create(['name' => 'Ferritin']);
    $bloodTest = BloodTest::factory()->for($user)->create([
        'test_date' => '2026-06-01',
        'title' => 'June private test',
    ]);

    BloodTestDocument::factory()->for($bloodTest)->create([
        'original_filename' => 'june-private-lab.pdf',
    ]);
    BiomarkerResult::factory()->for($bloodTest)->for($ferritin)->create([
        'value' => 18,
        'unit' => 'ug/L',
        'status' => 'low',
        'confirmed_at' => now(),
    ]);

    $this->actingAs($user)
        ->post(route('consult-overview.index'), [
            'include_attention' => '1',
            'include_source_documents' => '1',
        ])
        ->assertOk()
        ->assertSee('Geen bloedtesten geselecteerd voor dit overzicht.')
        ->assertSee('Geen passende bevestigde waarden.')
        ->assertSee('Geen bronbestanden gekoppeld aan deze selectie.')
        ->assertDontSee('Ferritin')
        ->assertDontSee('18 ug/L')
        ->assertDontSee('june-private-lab.pdf');
});

it('hides export and print actions while nothing is selected', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('consult-overview.index'))
        ->assertOk()
        ->assertSee('data-test="consult-actions-disabled"', false)
        ->assertSee('Selecteer eerst een bloedtest')
        ->assertDontSee('data-test="export-consult-csv-button"', false)
        ->assertDontSee('data-test="print-consult-pack-button"', false);
});

it('preselects the latest consult-ready blood test on a bare visit', function () {
    $user = User::factory()->create();
    $ferritin = Biomarker::factory()->for($user)->create(['name' => 'Ferritin']);

    $older = BloodTest::factory()->for($user)->create([
        'test_date' => '2026-03-01',
        'title' => 'Maarttest',
    ]);
    BiomarkerResult::factory()->for($older)->for($ferritin)->create([
        'value' => 30, 'unit' => 'ug/L', 'status' => 'normal', 'confirmed_at' => now()->subMonths(3),
    ]);

    $latest = BloodTest::factory()->for($user)->create([
        'test_date' => '2026-06-01',
        'title' => 'Junitest',
    ]);
    BiomarkerResult::factory()->for($latest)->for($ferritin)->create([
        'value' => 18, 'unit' => 'ug/L', 'status' => 'low', 'confirmed_at' => now(),
    ]);

    // Een test zonder bevestigde waarden mag nooit voorgeselecteerd worden.
    BloodTest::factory()->for($user)->create([
        'test_date' => '2026-07-01',
        'title' => 'Julitest zonder bevestiging',
    ]);

    $this->actingAs($user)
        ->get(route('consult-overview.index'))
        ->assertOk()
        ->assertSee('Junitest')
        ->assertSee('18 ug/L')
        ->assertSee('data-test="export-consult-csv-button"', false)
        ->assertDontSee('Geen bloedtesten geselecteerd voor dit overzicht.')
        // 'Maarttest' staat wel als keuze in het filterformulier, maar zijn
        // waarde mag niet in het pack zitten: alleen de laatste test is
        // voorgeselecteerd.
        ->assertDontSee('30 ug/L');
});

it('does not preselect anything once an explicit filter is in the request', function () {
    $user = User::factory()->create();
    $ferritin = Biomarker::factory()->for($user)->create(['name' => 'Ferritin']);
    $bloodTest = BloodTest::factory()->for($user)->create([
        'test_date' => '2026-06-01',
        'title' => 'June private test',
    ]);
    BiomarkerResult::factory()->for($bloodTest)->for($ferritin)->create([
        'value' => 18, 'unit' => 'ug/L', 'status' => 'low', 'confirmed_at' => now(),
    ]);

    // Een expliciete datumrange die niets dekt blijft leeg — de kale-GET
    // preselectie mag alleen zonder enige query-parameter gelden.
    $this->actingAs($user)
        ->get(route('consult-overview.index', ['from' => '2020-01-01', 'to' => '2020-12-31']))
        ->assertOk()
        ->assertSee('Geen bloedtesten geselecteerd voor dit overzicht.')
        ->assertDontSee('18 ug/L');
});
