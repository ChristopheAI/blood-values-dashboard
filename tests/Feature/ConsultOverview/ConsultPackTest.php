<?php

use App\Models\Biomarker;
use App\Models\BiomarkerResult;
use App\Models\BloodTest;
use App\Models\BloodTestDocument;
use App\Models\User;

it('builds a print ready consult pack from selected owned confirmed values and source documents', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $ferritin = Biomarker::factory()->for($user)->create(['name' => 'Ferritin']);
    $hemoglobin = Biomarker::factory()->for($user)->create(['name' => 'Hemoglobin']);
    $draft = Biomarker::factory()->for($user)->create(['name' => 'Draft marker']);
    $otherMarker = Biomarker::factory()->for($otherUser)->create(['name' => 'Other marker']);

    $april = BloodTest::factory()->for($user)->create(['test_date' => '2026-04-01', 'title' => 'April test']);
    $june = BloodTest::factory()->for($user)->create(['test_date' => '2026-06-01', 'title' => 'June test']);
    $otherBloodTest = BloodTest::factory()->for($otherUser)->create(['test_date' => '2026-06-01']);

    BloodTestDocument::factory()->for($april)->create(['original_filename' => 'april-lab.pdf']);
    BloodTestDocument::factory()->for($june)->create(['original_filename' => 'june-lab.pdf']);
    BloodTestDocument::factory()->for($otherBloodTest)->create(['original_filename' => 'other-private-lab.pdf']);

    BiomarkerResult::factory()->for($april)->for($ferritin)->create([
        'value' => 42,
        'unit' => 'ug/L',
        'status' => 'normal',
        'confirmed_at' => now(),
    ]);
    BiomarkerResult::factory()->for($june)->for($ferritin)->create([
        'value' => 55,
        'unit' => 'ug/L',
        'status' => 'high',
        'confirmed_at' => now(),
    ]);
    BiomarkerResult::factory()->for($june)->for($hemoglobin)->create([
        'value' => 14,
        'unit' => 'g/dL',
        'status' => 'normal',
        'confirmed_at' => now(),
    ]);
    BiomarkerResult::factory()->for($june)->for($draft)->create([
        'value' => 999,
        'unit' => 'mg/L',
        'status' => 'high',
        'confirmed_at' => null,
    ]);
    BiomarkerResult::factory()->for($otherBloodTest)->for($otherMarker)->create([
        'value' => 123,
        'unit' => 'mg/L',
        'status' => 'high',
        'confirmed_at' => now(),
    ]);

    $response = $this->actingAs($user)
        ->post(route('consult-overview.index'), [
            'blood_test_ids' => [$april->id, $june->id],
            'include_attention' => '1',
            'include_normal' => '1',
            'include_trends' => '1',
            'include_source_documents' => '1',
        ])
        ->assertOk()
        ->assertSee('data-test="consult-pack"', false)
        ->assertSee('data-test="print-consult-pack-button"', false)
        ->assertSee('data-test="export-consult-csv-form"', false)
        ->assertSee('data-test="consult-attention-values"', false)
        ->assertSee('data-test="consult-normal-values"', false)
        ->assertSee('data-test="consult-trend-changes"', false)
        ->assertSee('data-test="consult-source-documents"', false)
        ->assertSee('Ferritin')
        ->assertSee('55 ug/L')
        ->assertSee('+13 ug/L')
        ->assertSee('Hemoglobin')
        ->assertSee('14 g/dL')
        ->assertSee('april-lab.pdf')
        ->assertSee('june-lab.pdf')
        ->assertDontSee('Draft marker')
        ->assertDontSee('999')
        ->assertDontSee('Other marker')
        ->assertDontSee('123')
        ->assertDontSee('other-private-lab.pdf')
        ->assertDontSee('diagnosis')
        ->assertDontSee('treatment advice');

    $content = $response->getContent();

    expect(strpos($content, 'data-test="consult-attention-values"'))
        ->toBeLessThan(strpos($content, 'data-test="consult-normal-values"'))
        ->and(strpos($content, 'data-test="consult-normal-values"'))
        ->toBeLessThan(strpos($content, 'data-test="consult-trend-changes"'));
});

it('does not show a trend change when comparable units are missing', function () {
    $user = User::factory()->create();
    $ferritin = Biomarker::factory()->for($user)->create(['name' => 'Ferritin']);

    $april = BloodTest::factory()->for($user)->create(['test_date' => '2026-04-01']);
    $june = BloodTest::factory()->for($user)->create(['test_date' => '2026-06-01']);

    BiomarkerResult::factory()->for($april)->for($ferritin)->create([
        'value' => 42,
        'unit' => '',
        'status' => 'normal',
        'confirmed_at' => now(),
    ]);
    BiomarkerResult::factory()->for($june)->for($ferritin)->create([
        'value' => 55,
        'unit' => '',
        'status' => 'high',
        'confirmed_at' => now(),
    ]);

    $this->actingAs($user)
        ->post(route('consult-overview.index'), [
            'blood_test_ids' => [$april->id, $june->id],
            'include_trends' => '1',
        ])
        ->assertOk()
        ->assertSee('No comparable confirmed changes in this selection.')
        ->assertDontSee('+13');
});

it('exports consult pack normal values changes and source documents as csv', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $ferritin = Biomarker::factory()->for($user)->create(['name' => 'Ferritin']);
    $hemoglobin = Biomarker::factory()->for($user)->create(['name' => 'Hemoglobin']);
    $draft = Biomarker::factory()->for($user)->create(['name' => 'Draft marker']);
    $otherMarker = Biomarker::factory()->for($otherUser)->create(['name' => 'Other marker']);

    $april = BloodTest::factory()->for($user)->create(['test_date' => '2026-04-01', 'title' => 'April test']);
    $june = BloodTest::factory()->for($user)->create(['test_date' => '2026-06-01', 'title' => 'June test']);
    $otherBloodTest = BloodTest::factory()->for($otherUser)->create(['test_date' => '2026-06-01']);

    BloodTestDocument::factory()->for($june)->create(['original_filename' => 'june-lab.pdf']);
    BloodTestDocument::factory()->for($otherBloodTest)->create(['original_filename' => 'other-private-lab.pdf']);

    BiomarkerResult::factory()->for($april)->for($ferritin)->create([
        'value' => 42,
        'unit' => 'ug/L',
        'status' => 'normal',
        'confirmed_at' => now(),
    ]);
    BiomarkerResult::factory()->for($june)->for($ferritin)->create([
        'value' => 55,
        'unit' => 'ug/L',
        'status' => 'high',
        'confirmed_at' => now(),
    ]);
    BiomarkerResult::factory()->for($june)->for($hemoglobin)->create([
        'value' => 14,
        'unit' => 'g/dL',
        'status' => 'normal',
        'confirmed_at' => now(),
    ]);
    BiomarkerResult::factory()->for($june)->for($draft)->create([
        'value' => 999,
        'unit' => 'mg/L',
        'status' => 'high',
        'confirmed_at' => null,
    ]);
    BiomarkerResult::factory()->for($otherBloodTest)->for($otherMarker)->create([
        'value' => 123,
        'unit' => 'mg/L',
        'status' => 'high',
        'confirmed_at' => now(),
    ]);

    $this->actingAs($user)
        ->post(route('consult-overview.csv'), [
            'blood_test_ids' => [$april->id, $june->id],
            'include_normal' => '1',
            'include_trends' => '1',
            'include_source_documents' => '1',
        ])
        ->assertOk()
        ->assertSee('normal,2026-04-01,Ferritin,42,ug/L,normal,', false)
        ->assertSee('normal,2026-06-01,Hemoglobin,14,g/dL,normal,', false)
        ->assertSee('change,2026-06-01,Ferritin,55,ug/L,high,"previous 42 ug/L; change +13 ug/L"', false)
        ->assertSee('source_document,2026-06-01,june-lab.pdf,,,,"June test"', false)
        ->assertDontSee('Draft marker')
        ->assertDontSee('999')
        ->assertDontSee('Other marker')
        ->assertDontSee('123')
        ->assertDontSee('other-private-lab.pdf');
});
