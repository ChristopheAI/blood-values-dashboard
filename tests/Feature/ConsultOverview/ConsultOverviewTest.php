<?php

use App\Enums\ContextNoteCategory;
use App\Models\Biomarker;
use App\Models\BiomarkerResult;
use App\Models\BloodTest;
use App\Models\ContextNote;
use App\Models\PinnedBiomarker;
use App\Models\User;

it('renders a consult overview with confirmed owner data pins context questions and disclaimer', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $ferritin = Biomarker::factory()->for($user)->create(['name' => 'Ferritin']);
    $unconfirmed = Biomarker::factory()->for($user)->create(['name' => 'Draft marker']);
    $otherMarker = Biomarker::factory()->for($otherUser)->create(['name' => 'Other marker']);

    $may = BloodTest::factory()->for($user)->create(['test_date' => '2026-05-01', 'title' => 'May test']);
    $june = BloodTest::factory()->for($user)->create(['test_date' => '2026-06-01', 'title' => 'June test']);
    $otherBloodTest = BloodTest::factory()->for($otherUser)->create(['test_date' => '2026-06-01']);

    BiomarkerResult::factory()->for($may)->for($ferritin)->create([
        'value' => 42,
        'unit' => 'ug/L',
        'status' => 'normal',
        'confirmed_at' => now(),
    ]);
    BiomarkerResult::factory()->for($june)->for($ferritin)->create([
        'value' => 18,
        'unit' => 'ug/L',
        'status' => 'low',
        'confirmed_at' => now(),
    ]);
    BiomarkerResult::factory()->for($june)->for($unconfirmed)->create([
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

    PinnedBiomarker::factory()->for($user)->for($ferritin)->create(['note' => 'Follow around consults']);
    ContextNote::factory()->for($user)->for($june)->create([
        'note_date' => '2026-06-01',
        'category' => ContextNoteCategory::Sleep->value,
        'body' => 'Slept poorly before the June test.',
    ]);

    $this->actingAs($user)
        ->post(route('consult-overview.index'), [
            'from' => '2026-05-01',
            'to' => '2026-06-30',
            'include_pinned' => '1',
            'include_attention' => '1',
            'include_trends' => '1',
            'include_context' => '1',
            'questions' => 'What should I ask about the change?',
        ])
        ->assertOk()
        ->assertSee('Persoonlijke trackinggegevens uit bevestigde waarden')
        ->assertSee('Bespreek dit overzicht met je arts')
        ->assertSee('Ferritin')
        ->assertSee('Follow around consults')
        ->assertSee('Slept poorly before the June test.')
        ->assertSee('What should I ask about the change?')
        ->assertSee('18 ug/L')
        ->assertDontSee('Draft marker')
        ->assertDontSee('999')
        ->assertDontSee('Other marker')
        ->assertDontSee('123');
});

it('includes only low high and unknown confirmed values in the attention section', function () {
    $user = User::factory()->create();
    $normal = Biomarker::factory()->for($user)->create(['name' => 'Normal marker']);
    $low = Biomarker::factory()->for($user)->create(['name' => 'Low marker']);
    $high = Biomarker::factory()->for($user)->create(['name' => 'High marker']);
    $unknown = Biomarker::factory()->for($user)->create(['name' => 'Unknown marker']);
    $bloodTest = BloodTest::factory()->for($user)->create(['test_date' => '2026-06-01']);

    foreach ([[$normal, 'normal'], [$low, 'low'], [$high, 'high'], [$unknown, 'unknown']] as [$biomarker, $status]) {
        BiomarkerResult::factory()->for($bloodTest)->for($biomarker)->create([
            'value' => 10,
            'unit' => 'mg/L',
            'status' => $status,
            'confirmed_at' => now(),
        ]);
    }

    $this->actingAs($user)
        ->post(route('consult-overview.index'), [
            'from' => '2026-06-01',
            'to' => '2026-06-01',
            'include_attention' => '1',
        ])
        ->assertOk()
        ->assertSee('Low marker')
        ->assertSee('High marker')
        ->assertSee('Unknown marker')
        ->assertDontSee('Normal marker');
});

it('rejects selected blood tests not owned by the authenticated user', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $ownedBloodTest = BloodTest::factory()->for($owner)->create();
    $otherBloodTest = BloodTest::factory()->for($otherUser)->create();

    $this->actingAs($owner)
        ->post(route('consult-overview.index'), [
            'blood_test_ids' => [$ownedBloodTest->id, $otherBloodTest->id],
            'include_attention' => '1',
        ])
        ->assertForbidden();
});

it('does not render corrupted cross-owner pinned biomarkers in consult overview', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $foreignBiomarker = Biomarker::factory()->for($otherUser)->create(['name' => 'Foreign private marker']);

    PinnedBiomarker::factory()->for($user)->for($foreignBiomarker)->create([
        'note' => 'Foreign private pin note',
    ]);

    $this->actingAs($user)
        ->post(route('consult-overview.index'), [
            'include_pinned' => '1',
        ])
        ->assertOk()
        ->assertDontSee('Foreign private marker')
        ->assertDontSee('Foreign private pin note');
});

it('does not render confirmed results linked to another users biomarker', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $bloodTest = BloodTest::factory()->for($user)->create(['test_date' => '2026-06-01']);
    $foreignMarker = Biomarker::factory()->for($otherUser)->create(['name' => 'Foreign private marker']);

    BiomarkerResult::factory()->for($bloodTest)->for($foreignMarker)->create([
        'value' => 123,
        'unit' => 'mg/L',
        'status' => 'high',
        'confirmed_at' => now(),
    ]);

    $this->actingAs($user)
        ->post(route('consult-overview.index'), [
            'from' => '2026-06-01',
            'to' => '2026-06-01',
            'include_attention' => '1',
            'include_trends' => '1',
        ])
        ->assertOk()
        ->assertDontSee('Foreign private marker')
        ->assertDontSee('123');
});

it('filters context notes by date range when no blood tests are selected', function () {
    $user = User::factory()->create();

    ContextNote::factory()->for($user)->create([
        'note_date' => '2026-06-01',
        'category' => ContextNoteCategory::Sleep->value,
        'body' => 'In-range context note.',
    ]);
    ContextNote::factory()->for($user)->create([
        'note_date' => '2026-04-01',
        'category' => ContextNoteCategory::Stress->value,
        'body' => 'Out-of-range context note.',
    ]);

    $this->actingAs($user)
        ->post(route('consult-overview.index'), [
            'from' => '2026-06-01',
            'to' => '2026-06-30',
            'include_context' => '1',
        ])
        ->assertOk()
        ->assertSee('In-range context note.')
        ->assertDontSee('Out-of-range context note.');
});

it('exports the consult overview structured rows as csv', function () {
    $user = User::factory()->create();
    $ferritin = Biomarker::factory()->for($user)->create(['name' => 'Ferritin']);
    $draft = Biomarker::factory()->for($user)->create(['name' => 'Draft marker']);
    $bloodTest = BloodTest::factory()->for($user)->create(['test_date' => '2026-06-01', 'title' => 'June test']);

    BiomarkerResult::factory()->for($bloodTest)->for($ferritin)->create([
        'value' => 18,
        'unit' => 'ug/L',
        'status' => 'low',
        'confirmed_at' => '2026-06-02 09:00:00',
    ]);
    BiomarkerResult::factory()->for($bloodTest)->for($draft)->create([
        'value' => 999,
        'unit' => 'mg/L',
        'status' => 'high',
        'confirmed_at' => null,
    ]);

    $response = $this->actingAs($user)
        ->post(route('consult-overview.csv'), [
            'from' => '2026-06-01',
            'to' => '2026-06-01',
            'include_attention' => '1',
        ])
        ->assertOk()
        ->assertHeader('content-type', 'text/csv; charset=UTF-8');

    $rows = array_map(
        fn (string $line): array => str_getcsv(rtrim($line, "\r")),
        array_filter(explode("\n", trim($response->getContent()))),
    );

    expect($rows)->toContain(
        ['section', 'date', 'biomarker', 'value', 'unit', 'status', 'source', 'confirmed_at', 'note'],
        ['attention', '2026-06-01', 'Ferritin', '18', 'ug/L', 'low', 'June test', '2026-06-02', ''],
    );

    $response
        ->assertDontSee('Draft marker')
        ->assertDontSee('999');
});

it('escapes spreadsheet formulas in consult csv export cells', function () {
    $user = User::factory()->create();
    $dangerousMarker = Biomarker::factory()->for($user)->create(['name' => '=Ferritin']);
    $bloodTest = BloodTest::factory()->for($user)->create(['test_date' => '2026-06-01', 'title' => '=June test']);

    BiomarkerResult::factory()->for($bloodTest)->for($dangerousMarker)->create([
        'value' => 18,
        'unit' => 'ug/L',
        'status' => 'low',
        'confirmed_at' => '2026-06-02 09:00:00',
        'note' => '+review note',
    ]);
    PinnedBiomarker::factory()->for($user)->for($dangerousMarker)->create(['note' => '@pin note']);
    ContextNote::factory()->for($user)->for($bloodTest)->create([
        'note_date' => '2026-06-01',
        'category' => ContextNoteCategory::Sleep->value,
        'body' => '-context note',
    ]);

    $response = $this->actingAs($user)
        ->post(route('consult-overview.csv'), [
            'from' => '2026-06-01',
            'to' => '2026-06-01',
            'include_pinned' => '1',
            'include_attention' => '1',
            'include_context' => '1',
        ])
        ->assertOk();

    $rows = array_map(
        fn (string $line): array => str_getcsv(rtrim($line, "\r")),
        array_filter(explode("\n", trim($response->getContent()))),
    );

    expect($rows)->toContain(
        ['pinned', '', "'=Ferritin", '', '', '', '', '', "'@pin note"],
        ['attention', '2026-06-01', "'=Ferritin", '18', 'ug/L', 'low', "'=June test", '2026-06-02', "'+review note"],
        ['context', '2026-06-01', 'Sleep', '', '', '', '', '', "'-context note"],
    );
});
