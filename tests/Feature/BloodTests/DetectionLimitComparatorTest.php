<?php

use App\Domain\Dashboard\BuildBloodResultsOverview;
use App\Livewire\BloodTests\ReviewBloodTest;
use App\Models\Biomarker;
use App\Models\BiomarkerResult;
use App\Models\BloodTest;
use App\Models\BloodTestDocument;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

// The '<'/'>' comparator used to live only in source_snippet, which manual
// entries never get and document deletion wipes — so '<50' silently became an
// exact '50' and the overview re-derived its deliberate 'unknown' into 'high'.
// These tests pin the persisted value_comparator column end to end.

it('persists the comparator when manually confirming a detection-limit value', function () {
    $user = User::factory()->create();
    $biomarker = Biomarker::factory()->for($user)->create(['name' => 'CMV IgM']);
    $bloodTest = BloodTest::factory()->for($user)->create(['status' => 'reviewing']);

    Livewire::actingAs($user)
        ->test(ReviewBloodTest::class, ['bloodTest' => $bloodTest])
        ->set('resultForm.biomarker_id', $biomarker->id)
        ->set('resultForm.value', '<50')
        ->set('resultForm.unit', 'U/L')
        ->set('resultForm.reference_max', 30)
        ->call('confirmResult')
        ->assertHasNoErrors();

    $result = BiomarkerResult::query()->whereNotNull('confirmed_at')->firstOrFail();

    // '<50' against ref max 30 cannot prove a classification, so unknown is
    // deliberate — and the comparator now survives without a snippet.
    expect($result->value_comparator)->toBe('<')
        ->and((float) $result->value)->toBe(50.0)
        ->and($result->status)->toBe('unknown')
        ->and($result->source_snippet)->toBeNull();
});

it('keeps a manually entered detection limit in the unknown group and shows its prefix on the overview', function () {
    // This file also runs in the assets-less Postgres CI job.
    $this->withoutVite();

    $user = User::factory()->create();
    $biomarker = Biomarker::factory()->for($user)->create(['name' => 'CMV IgM']);
    $bloodTest = BloodTest::factory()->for($user)->create(['test_date' => '2026-06-15']);

    // Exactly what confirmResult writes for a manual '<50': numeric bound,
    // comparator column set, no snippet, deliberate unknown status.
    BiomarkerResult::factory()->for($bloodTest)->for($biomarker)->create([
        'value' => '50', 'value_comparator' => '<', 'unit' => 'U/L',
        'reference_min' => null, 'reference_max' => 30,
        'status' => 'unknown', 'confirmed_at' => now(), 'source_snippet' => null,
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

it('keeps the detection-limit prefix after the source document (and its snippets) are deleted', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $biomarker = Biomarker::factory()->for($user)->create(['name' => 'RA factor']);
    $bloodTest = BloodTest::factory()->for($user)->create(['test_date' => '2026-06-15']);
    Storage::disk('local')->put('blood-documents/test.pdf', 'pdf');
    $document = BloodTestDocument::factory()->for($bloodTest)->create([
        'storage_path' => 'blood-documents/test.pdf',
    ]);

    $result = BiomarkerResult::factory()->for($bloodTest)->for($biomarker)->create([
        'value' => '10', 'value_comparator' => '<', 'unit' => 'kIU/L',
        'reference_max' => 14, 'status' => 'normal',
        'entry_source' => 'extracted', 'confirmed_at' => now(),
        'blood_test_document_id' => $document->id,
        'source_snippet' => 'RA factor <10 kIU/L',
    ]);

    $this->actingAs($user)
        ->delete(route('blood-test-documents.destroy', $document))
        ->assertRedirect();

    $result->refresh();

    expect($result->source_snippet)->toBeNull()
        ->and($result->value_comparator)->toBe('<');

    $row = app(BuildBloodResultsOverview::class)($user)->firstWhere('label', 'RA factor');

    expect($row['valueLabel'])->toBe('<10')
        ->and($row['is_detection_limit'])->toBeTrue();
});
