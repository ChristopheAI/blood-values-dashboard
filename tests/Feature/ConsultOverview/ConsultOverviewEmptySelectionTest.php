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
