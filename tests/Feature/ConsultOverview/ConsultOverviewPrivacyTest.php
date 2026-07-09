<?php

use App\Models\Biomarker;
use App\Models\BiomarkerResult;
use App\Models\BloodTest;
use App\Models\PinnedBiomarker;
use App\Models\User;

it('excludes orphaned null-biomarker confirmed values and pins from the consult overview and csv export', function () {
    $user = User::factory()->create();
    $biomarker = Biomarker::factory()->for($user)->create(['name' => 'Ferritin']);
    $bloodTest = BloodTest::factory()->for($user)->create(['test_date' => '2026-06-01']);

    // A confirmed value and a pin whose biomarker link was nulled by a privacy
    // deletion. They must never surface, and must never reach the unconditional
    // $result->biomarker->name / $pin->biomarker->name dereferences in the CSV export.
    BiomarkerResult::factory()->for($bloodTest)->create([
        'biomarker_id' => null,
        'value' => 999,
        'unit' => 'orphanunit',
        'status' => 'high',
        'entry_source' => 'pdf_reviewed',
        'confirmed_at' => now(),
        'extracted_name' => 'Orphaned marker',
    ]);
    PinnedBiomarker::factory()->for($user)->create([
        'biomarker_id' => null,
        'note' => 'Orphaned pin note',
    ]);

    // A genuine confirmed value so the overview is non-empty.
    BiomarkerResult::factory()->for($bloodTest)->for($biomarker)->create([
        'value' => 42,
        'unit' => 'ug/L',
        'status' => 'normal',
        'entry_source' => 'pdf_reviewed',
        'confirmed_at' => now(),
    ]);

    $filters = [
        'blood_test_ids' => [$bloodTest->id],
        'include_pinned' => '1',
        'include_attention' => '1',
        'include_normal' => '1',
        'include_trends' => '1',
    ];

    $this->actingAs($user)
        ->post(route('consult-overview.index'), $filters)
        ->assertOk()
        ->assertSee('Ferritin')
        ->assertDontSee('Orphaned marker')
        ->assertDontSee('Orphaned pin note')
        ->assertDontSee('orphanunit');

    $csv = $this->actingAs($user)->post(route('consult-overview.csv'), $filters);
    $csv->assertOk();

    expect($csv->getContent())->toContain('Ferritin')
        ->and($csv->getContent())->not->toContain('Orphaned')
        ->and($csv->getContent())->not->toContain('orphanunit');
});

it('does not carry consult questions in generated get urls', function () {
    $user = User::factory()->create();
    $secretQuestion = 'Could we discuss the training context privately?';

    $biomarker = Biomarker::factory()->for($user)->create(['name' => 'Ferritin']);
    $bloodTest = BloodTest::factory()->for($user)->create(['test_date' => '2026-06-01']);
    BiomarkerResult::factory()->for($bloodTest)->for($biomarker)->create([
        'value' => 42, 'unit' => 'ug/L', 'status' => 'normal', 'confirmed_at' => now(),
    ]);

    $this->actingAs($user)
        ->post(route('consult-overview.index'), [
            'from' => '2026-06-01',
            'to' => '2026-06-01',
            'include_context' => '1',
            'questions' => $secretQuestion,
        ])
        ->assertOk()
        ->assertSee($secretQuestion)
        ->assertSee('method="POST"', false)
        ->assertSee('action="'.route('consult-overview.index').'"', false)
        ->assertSee('action="'.route('consult-overview.csv').'"', false)
        ->assertDontSee('/consult-overview?questions=', false)
        ->assertDontSee('/consult-overview.csv?questions=', false)
        ->assertDontSee('questions='.rawurlencode($secretQuestion), false)
        ->assertDontSee('questions='.urlencode($secretQuestion), false);

    $this->actingAs($user)
        ->get(route('consult-overview.index', ['questions' => $secretQuestion]))
        ->assertOk()
        ->assertDontSee($secretQuestion);
});

it('does not carry consult questions into the csv export form', function () {
    $user = User::factory()->create();
    $secretQuestion = 'Could we discuss the training context privately?';

    $biomarker = Biomarker::factory()->for($user)->create(['name' => 'Ferritin']);
    $bloodTest = BloodTest::factory()->for($user)->create(['test_date' => '2026-06-01']);
    BiomarkerResult::factory()->for($bloodTest)->for($biomarker)->create([
        'value' => 42, 'unit' => 'ug/L', 'status' => 'normal', 'confirmed_at' => now(),
    ]);

    $this->actingAs($user)
        ->post(route('consult-overview.index'), [
            'include_context' => '1',
            'blood_test_ids' => [$bloodTest->id],
            'questions' => $secretQuestion,
        ])
        ->assertOk()
        ->assertSee($secretQuestion)
        ->assertSee('data-test="export-consult-csv-form"', false)
        ->assertDontSee('type="hidden" name="questions"', false)
        ->assertDontSee('value="'.$secretQuestion.'"', false);
});
