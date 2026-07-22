<?php

use App\Domain\Privacy\BuildDataExport;
use App\Domain\Privacy\DeleteAllHealthData;
use App\Enums\ContextNoteCategory;
use App\Models\Biomarker;
use App\Models\BiomarkerCategory;
use App\Models\BiomarkerResult;
use App\Models\BloodTest;
use App\Models\BloodTestDocument;
use App\Models\ContextNote;
use App\Models\ExtractionRun;
use App\Models\PinnedBiomarker;
use App\Models\Reminder;
use App\Models\User;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

it('exports owned health data as a downloadable json file without other users rows', function () {
    Storage::fake('local');
    Carbon::setTestNow('2026-06-18 10:15:00');

    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $category = BiomarkerCategory::factory()->for($user)->create(['name' => 'Inflammation']);
    $biomarker = Biomarker::factory()->for($user)->for($category, 'category')->create([
        'name' => 'Ferritin',
        'short_name' => 'FER',
        'default_unit' => 'ug/L',
        'reference_min' => 30,
        'reference_max' => 150,
        'reference_unit' => 'ug/L',
        'range_note' => 'Lab range copied by user.',
    ]);
    $draftBiomarker = Biomarker::factory()->for($user)->for($category, 'category')->create([
        'name' => 'Draft marker',
    ]);
    $bloodTest = BloodTest::factory()->for($user)->create([
        'test_date' => '2026-06-01',
        'lab_name' => 'Owner Lab',
        'title' => 'Owner June test',
        'notes' => 'Fasted before draw.',
        'status' => 'confirmed',
    ]);
    $document = BloodTestDocument::factory()->for($bloodTest)->create([
        'original_filename' => 'owner-lab.pdf',
        'storage_path' => 'blood-test-documents/owner-lab.pdf',
        'mime_type' => 'application/pdf',
        'file_size' => 2048,
        'created_at' => '2026-06-01 08:00:00',
    ]);
    Storage::disk('local')->put($document->storage_path, 'owner pdf bytes');

    BiomarkerResult::factory()->for($bloodTest)->for($biomarker)->create([
        'value' => 42,
        'unit' => 'ug/L',
        'reference_min' => 30,
        'reference_max' => 150,
        'reference_unit' => 'ug/L',
        'status' => 'normal',
        'entry_source' => 'pdf_reviewed',
        'confirmed_at' => '2026-06-01 09:00:00',
        'note' => 'Confirmed from PDF.',
    ]);
    BiomarkerResult::factory()->for($bloodTest)->for($draftBiomarker)->create([
        'value' => 999,
        'unit' => 'ug/L',
        'status' => 'high',
        'confirmed_at' => null,
        'note' => 'Draft extraction should stay out.',
    ]);
    PinnedBiomarker::factory()->for($user)->for($biomarker)->create(['note' => 'Track before consult']);
    ContextNote::factory()->for($user)->for($bloodTest)->create([
        'note_date' => '2026-06-01',
        'category' => ContextNoteCategory::Sleep->value,
        'body' => 'Short sleep before test.',
    ]);
    Reminder::factory()->for($user)->create([
        'due_date' => '2026-07-15',
        'title' => 'Owner reminder',
        'note' => 'Plan next lab.',
        'completed_at' => null,
    ]);

    $otherCategory = BiomarkerCategory::factory()->for($otherUser)->create(['name' => 'Other category']);
    $otherBiomarker = Biomarker::factory()->for($otherUser)->for($otherCategory, 'category')->create(['name' => 'Other marker']);
    Biomarker::factory()->for($user)->for($otherCategory, 'category')->create(['name' => 'Corrupt categorized marker']);
    $otherBloodTest = BloodTest::factory()->for($otherUser)->create(['title' => 'Other user test']);
    $otherDocument = BloodTestDocument::factory()->for($otherBloodTest)->create(['original_filename' => 'other-lab.pdf']);
    Storage::disk('local')->put($otherDocument->storage_path, 'other pdf bytes');
    BiomarkerResult::factory()->for($otherBloodTest)->for($otherBiomarker)->create(['value' => 123]);
    BiomarkerResult::factory()->for($bloodTest)->for($otherBiomarker)->create([
        'value' => 456,
        'unit' => 'mg/L',
        'status' => 'high',
        'confirmed_at' => '2026-06-01 10:00:00',
        'note' => 'Foreign biomarker link should stay out.',
    ]);
    PinnedBiomarker::factory()->for($otherUser)->for($otherBiomarker)->create(['note' => 'Other pin']);
    PinnedBiomarker::factory()->for($user)->for($otherBiomarker)->create(['note' => 'Foreign biomarker pin should stay out.']);
    ContextNote::factory()->for($user)->create([
        'blood_test_id' => $otherBloodTest->id,
        'body' => 'Owned context with foreign blood test link.',
    ]);
    ContextNote::factory()->for($otherUser)->create(['body' => 'Other context']);
    Reminder::factory()->for($otherUser)->create([
        'title' => 'Other reminder',
        'note' => 'Other reminder note.',
    ]);

    $response = $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->post(route('data.export'));

    $response
        ->assertOk()
        ->assertHeader('content-type', 'application/json')
        ->assertDownload('blood-values-data-export-2026-06-18.json');

    $payload = json_decode($response->streamedContent(), true, flags: JSON_THROW_ON_ERROR);

    expect(array_column($payload['blood_tests'], 'title'))->toContain('Owner June test')->not->toContain('Other user test');
    expect(array_column($payload['biomarker_categories'], 'name'))->toContain('Inflammation')->not->toContain('Other category');
    expect(array_column($payload['biomarkers'], 'name'))->toContain('Ferritin')->not->toContain('Other marker');
    expect(collect($payload['biomarkers'])->firstWhere('name', 'Corrupt categorized marker')['biomarker_category_id'])->toBeNull();
    expect(array_column($payload['biomarker_results'], 'note'))->toContain('Confirmed from PDF.')
        ->not->toContain('Draft extraction should stay out.')
        ->not->toContain('Foreign biomarker link should stay out.');
    expect(array_column($payload['documents'], 'original_filename'))->toContain('owner-lab.pdf')->not->toContain('other-lab.pdf');
    expect(array_column($payload['pinned_biomarkers'], 'note'))->toContain('Track before consult')
        ->not->toContain('Other pin')
        ->not->toContain('Foreign biomarker pin should stay out.');
    expect(array_column($payload['context_notes'], 'body'))->toContain('Short sleep before test.')->not->toContain('Other context');
    expect(collect($payload['context_notes'])->firstWhere('body', 'Owned context with foreign blood test link.')['blood_test_id'])->toBeNull();
    expect(array_column($payload['reminders'], 'title'))->toContain('Owner reminder')->not->toContain('Other reminder');
    expect($payload['reminders'][0]['due_date'])->toBe('2026-07-15');
    expect($payload['reminders'][0]['note'])->toBe('Plan next lab.');
    expect($payload['reminders'][0]['completed_at'])->toBeNull();
    expect($payload['documents'][0])->not->toHaveKey('storage_path');
    expect($payload['documents'][0])->not->toHaveKey('binary');
    expect(privacyExportKeys($payload))->not->toContain('diagnosis', 'treatment', 'advice', 'recommendation', 'score');
});

it('exports trace metadata for confirmed auto-filled values without exporting drafts', function () {
    $user = User::factory()->create();
    $biomarker = Biomarker::factory()->for($user)->create(['name' => 'Ferritin']);
    $draftBiomarker = Biomarker::factory()->for($user)->create(['name' => 'Draft marker']);
    $bloodTest = BloodTest::factory()->for($user)->create([
        'test_date' => '2026-06-01',
        'status' => 'reviewing',
    ]);

    BiomarkerResult::factory()->for($bloodTest)->for($biomarker)->create([
        'value' => 42,
        'unit' => 'ug/L',
        'status' => 'normal',
        'entry_source' => 'extracted',
        'confirmed_at' => now(),
        'extracted_name' => 'Ferritin',
        'extraction_confidence' => 0.95,
        'source_snippet' => 'Ferritin 42 ug/L ref 30-150 ug/L',
    ]);
    BiomarkerResult::factory()->for($bloodTest)->for($draftBiomarker)->create([
        'value' => 999,
        'unit' => 'ug/L',
        'status' => 'unknown',
        'entry_source' => 'extracted',
        'confirmed_at' => null,
        'extracted_name' => 'Draft marker',
        'extraction_confidence' => 0.6,
        'source_snippet' => 'Draft marker 999 ug/L',
    ]);

    $export = app(BuildDataExport::class)($user);

    expect($export['biomarker_results'])->toHaveCount(1);

    $result = $export['biomarker_results'][0];

    expect($result['entry_source'])->toBe('extracted')
        ->and($result['extracted_name'])->toBe('Ferritin')
        ->and((float) $result['extraction_confidence'])->toBe(0.95)
        ->and($result)->not->toHaveKey('source_snippet')
        ->and(json_encode($export, JSON_THROW_ON_ERROR))->not->toContain('Ferritin 42 ug/L ref 30-150 ug/L')
        ->and($result['value'])->not->toBe(999);
});

it('marks the data export schema as v2 when extraction metadata is included', function () {
    $user = User::factory()->create();
    $bloodTest = BloodTest::factory()->for($user)->create(['test_date' => '2026-06-01']);

    ExtractionRun::factory()->for($bloodTest)->create([
        'engine' => 'smalot/pdfparser',
        'status' => 'done',
        'candidate_count' => 1,
    ]);

    $export = app(BuildDataExport::class)($user);

    expect($export['format'])->toBe('blood-values-dashboard.v2')
        ->and($export)->toHaveKey('extraction_runs');
});

it('exports owner scoped extraction run metadata without parser content', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $bloodTest = BloodTest::factory()->for($user)->create(['test_date' => '2026-06-01']);
    $otherBloodTest = BloodTest::factory()->for($otherUser)->create(['test_date' => '2026-06-01']);

    ExtractionRun::factory()->for($bloodTest)->create([
        'engine' => 'smalot/pdfparser',
        'status' => 'done',
        'candidate_count' => 3,
    ]);
    ExtractionRun::factory()->for($otherBloodTest)->create([
        'engine' => 'other-private-engine',
        'status' => 'done',
        'candidate_count' => 99,
    ]);

    $export = app(BuildDataExport::class)($user);

    expect($export['extraction_runs'])->toHaveCount(1);

    $run = $export['extraction_runs'][0];

    expect($run['blood_test_id'])->toBe($bloodTest->id)
        ->and($run['engine'])->toBe('smalot/pdfparser')
        ->and($run['status'])->toBe('done')
        ->and($run['candidate_count'])->toBe(3)
        ->and($run)->not->toHaveKey('source_snippet')
        ->and($run)->not->toHaveKey('parser_output')
        ->and($run)->not->toHaveKey('pdf_text')
        ->and(json_encode($export, JSON_THROW_ON_ERROR))->not->toContain('other-private-engine');
});

it('requires password confirmation before accessing data privacy actions', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('data.edit'))
        ->assertRedirect(route('password.confirm'));

    $this->actingAs($user)
        ->post(route('data.export'))
        ->assertRedirect(route('password.confirm'));

    $this->actingAs($user)
        ->delete(route('data.destroy'), ['confirmation' => 'DELETE ALL'])
        ->assertRedirect(route('password.confirm'));
});

it('deletes all owned health data and private documents without deleting the account or another user data', function () {
    Storage::fake('local');

    $user = User::factory()->create(['email' => 'owner@example.test']);
    $otherUser = User::factory()->create(['email' => 'other@example.test']);

    $category = BiomarkerCategory::factory()->for($user)->create();
    $biomarker = Biomarker::factory()->for($user)->for($category, 'category')->create();
    $bloodTest = BloodTest::factory()->for($user)->create();
    $document = BloodTestDocument::factory()->for($bloodTest)->create([
        'storage_path' => 'blood-test-documents/owner-delete-all.pdf',
    ]);
    Storage::disk('local')->put($document->storage_path, 'owner pdf bytes');
    BiomarkerResult::factory()->for($bloodTest)->for($biomarker)->create();
    PinnedBiomarker::factory()->for($user)->for($biomarker)->create();
    ContextNote::factory()->for($user)->for($bloodTest)->create();
    Reminder::factory()->for($user)->create(['title' => 'Owner reminder']);

    $otherCategory = BiomarkerCategory::factory()->for($otherUser)->create();
    $otherBiomarker = Biomarker::factory()->for($otherUser)->for($otherCategory, 'category')->create();
    $otherBloodTest = BloodTest::factory()->for($otherUser)->create();
    $otherDocument = BloodTestDocument::factory()->for($otherBloodTest)->create([
        'storage_path' => 'blood-test-documents/other-delete-all.pdf',
    ]);
    Storage::disk('local')->put($otherDocument->storage_path, 'other pdf bytes');
    BiomarkerResult::factory()->for($otherBloodTest)->for($otherBiomarker)->create();
    $otherUsersResultWithOwnerBiomarker = BiomarkerResult::factory()->for($otherBloodTest)->for($biomarker)->create([
        'value' => 321,
        'unit' => 'ug/L',
        'confirmed_at' => now(),
    ]);
    PinnedBiomarker::factory()->for($otherUser)->for($otherBiomarker)->create();
    $otherUsersPinWithOwnerBiomarker = PinnedBiomarker::factory()->for($otherUser)->for($biomarker)->create([
        'note' => 'Foreign pin should be detached, not deleted.',
    ]);
    ContextNote::factory()->for($otherUser)->for($otherBloodTest)->create();
    Reminder::factory()->for($otherUser)->create(['title' => 'Other reminder']);

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->delete(route('data.destroy'), ['confirmation' => 'DELETE ALL'])
        ->assertRedirect(route('data.edit'));

    expect(BloodTest::query()->where('user_id', $user->id)->count())->toBe(0);
    expect(Biomarker::query()->where('user_id', $user->id)->count())->toBe(0);
    expect(BiomarkerCategory::query()->where('user_id', $user->id)->count())->toBe(0);
    expect(PinnedBiomarker::query()->where('user_id', $user->id)->count())->toBe(0);
    expect(ContextNote::query()->where('user_id', $user->id)->count())->toBe(0);
    expect(Reminder::query()->where('user_id', $user->id)->count())->toBe(0);
    expect(BloodTestDocument::query()->whereKey($document->id)->exists())->toBeFalse();
    Storage::disk('local')->assertMissing($document->storage_path);

    expect(User::query()->whereKey($user->id)->exists())->toBeTrue();
    expect(BloodTest::query()->where('user_id', $otherUser->id)->count())->toBe(1);
    expect(Biomarker::query()->where('user_id', $otherUser->id)->count())->toBe(1);
    expect(BiomarkerCategory::query()->where('user_id', $otherUser->id)->count())->toBe(1);
    expect(BiomarkerResult::query()->whereKey($otherUsersResultWithOwnerBiomarker->id)->exists())->toBeTrue();
    expect(BiomarkerResult::query()->whereKey($otherUsersResultWithOwnerBiomarker->id)->value('biomarker_id'))->toBeNull();
    expect(PinnedBiomarker::query()->whereKey($otherUsersPinWithOwnerBiomarker->id)->exists())->toBeTrue();
    expect(PinnedBiomarker::query()->whereKey($otherUsersPinWithOwnerBiomarker->id)->value('biomarker_id'))->toBeNull();
    expect(PinnedBiomarker::query()->where('user_id', $otherUser->id)->count())->toBe(2);
    expect(ContextNote::query()->where('user_id', $otherUser->id)->count())->toBe(1);
    expect(Reminder::query()->where('user_id', $otherUser->id)->count())->toBe(1);
    expect(BloodTestDocument::query()->whereKey($otherDocument->id)->exists())->toBeTrue();
    Storage::disk('local')->assertExists($otherDocument->storage_path);

    $this->post(route('logout'));
    $this->post(route('login.store'), [
        'email' => 'owner@example.test',
        'password' => 'password',
    ])->assertRedirect(route('dashboard', absolute: false));
    $this->assertAuthenticatedAs($user);
});

it('keeps private documents when delete all fails before the database transaction commits', function () {
    if (DB::connection()->getDriverName() !== 'sqlite') {
        $this->markTestSkipped('This synthetic trigger regression is SQLite-only.');
    }

    Storage::fake('local');

    $user = User::factory()->create();
    $bloodTest = BloodTest::factory()->for($user)->create();
    $document = BloodTestDocument::factory()->for($bloodTest)->create([
        'storage_path' => 'blood-test-documents/delete-all-fails.pdf',
    ]);
    $caught = null;

    Storage::disk('local')->put($document->storage_path, 'pdf bytes');

    DB::unprepared(
        'CREATE TRIGGER fail_delete_all_blood_tests '.
        'BEFORE DELETE ON blood_tests '.
        'WHEN OLD.id = '.$bloodTest->id.' '.
        "BEGIN SELECT RAISE(ABORT, 'synthetic delete all failure'); END;"
    );

    try {
        $this->withoutExceptionHandling()
            ->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->delete(route('data.destroy'), ['confirmation' => 'DELETE ALL']);
    } catch (Throwable $exception) {
        $caught = $exception;
    } finally {
        DB::unprepared('DROP TRIGGER IF EXISTS fail_delete_all_blood_tests;');
    }

    expect($caught?->getMessage())->toContain('synthetic delete all failure')
        ->and(BloodTest::query()->whereKey($bloodTest->id)->exists())->toBeTrue()
        ->and(BloodTestDocument::query()->whereKey($document->id)->exists())->toBeTrue();
    Storage::disk('local')->assertExists($document->storage_path);
});

it('keeps owned health data records when delete all cannot remove a private document', function () {
    $user = User::factory()->create();
    $bloodTest = BloodTest::factory()->for($user)->create();
    $document = BloodTestDocument::factory()->for($bloodTest)->create([
        'storage_disk' => 'local',
        'storage_path' => 'blood-test-documents/delete-all-returned-false.pdf',
    ]);
    $disk = Mockery::mock(Filesystem::class);

    $disk->shouldReceive('exists')
        ->once()
        ->with($document->storage_path)
        ->andReturnTrue();
    $disk->shouldReceive('delete')
        ->once()
        ->with($document->storage_path)
        ->andReturnFalse();
    Storage::shouldReceive('disk')
        ->once()
        ->with('local')
        ->andReturn($disk);

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->delete(route('data.destroy'), ['confirmation' => 'DELETE ALL'])
        ->assertServerError();

    expect(BloodTest::query()->whereKey($bloodTest->id)->exists())->toBeTrue()
        ->and(BloodTestDocument::query()->whereKey($document->id)->exists())->toBeTrue();
});

it('keeps owner records when delete all cannot remove every private document', function () {
    $user = User::factory()->create();
    $bloodTest = BloodTest::factory()->for($user)->create();
    $firstDocument = BloodTestDocument::factory()->for($bloodTest)->create([
        'storage_disk' => 'local',
        'storage_path' => 'blood-test-documents/delete-all-multi-first.pdf',
    ]);
    $secondDocument = BloodTestDocument::factory()->for($bloodTest)->create([
        'storage_disk' => 'local',
        'storage_path' => 'blood-test-documents/delete-all-multi-second.pdf',
    ]);
    $disk = Mockery::mock(Filesystem::class);

    $disk->shouldReceive('exists')->with($firstDocument->storage_path)->once()->andReturnTrue();
    $disk->shouldReceive('exists')->with($secondDocument->storage_path)->once()->andReturnTrue();
    $disk->shouldReceive('delete')->with($firstDocument->storage_path)->andReturnTrue();
    $disk->shouldReceive('delete')->with($secondDocument->storage_path)->andReturnFalse();
    Storage::shouldReceive('disk')->with('local')->andReturn($disk);

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->delete(route('data.destroy'), ['confirmation' => 'DELETE ALL'])
        ->assertServerError();

    expect(BloodTest::query()->whereKey($bloodTest->id)->exists())->toBeTrue()
        ->and(BloodTestDocument::query()->whereKey($firstDocument->id)->exists())->toBeTrue()
        ->and(BloodTestDocument::query()->whereKey($secondDocument->id)->exists())->toBeTrue();
});

it('completes a retry after an earlier private document was already removed', function () {
    $user = User::factory()->create();
    $bloodTest = BloodTest::factory()->for($user)->create();
    $firstDocument = BloodTestDocument::factory()->for($bloodTest)->create([
        'storage_disk' => 'local',
        'storage_path' => 'blood-test-documents/retry-first.pdf',
    ]);
    $secondDocument = BloodTestDocument::factory()->for($bloodTest)->create([
        'storage_disk' => 'local',
        'storage_path' => 'blood-test-documents/retry-second.pdf',
    ]);
    $disk = Mockery::mock(Filesystem::class);

    $disk->shouldReceive('exists')
        ->with($firstDocument->storage_path)
        ->twice()
        ->andReturn(true, false);
    $disk->shouldReceive('exists')
        ->with($secondDocument->storage_path)
        ->twice()
        ->andReturnTrue();
    $disk->shouldReceive('delete')->with($firstDocument->storage_path)->once()->andReturnTrue();
    $disk->shouldReceive('delete')
        ->with($secondDocument->storage_path)
        ->twice()
        ->andReturn(false, true);
    Storage::shouldReceive('disk')->with('local')->times(4)->andReturn($disk);

    expect(fn () => app(DeleteAllHealthData::class)($user))
        ->toThrow(RuntimeException::class, 'Failed to delete stored lab PDF.');

    expect(BloodTest::query()->whereKey($bloodTest->id)->exists())->toBeTrue();

    app(DeleteAllHealthData::class)($user);

    expect(BloodTest::query()->whereKey($bloodTest->id)->exists())->toBeFalse()
        ->and(BloodTestDocument::query()->whereKey($firstDocument->id)->exists())->toBeFalse()
        ->and(BloodTestDocument::query()->whereKey($secondDocument->id)->exists())->toBeFalse();
});

it('requires explicit typed confirmation before delete all removes health data', function () {
    $user = User::factory()->create();
    $bloodTest = BloodTest::factory()->for($user)->create();

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->delete(route('data.destroy'), ['confirmation' => 'delete all'])
        ->assertSessionHasErrors('confirmation');

    expect(BloodTest::query()->whereKey($bloodTest->id)->exists())->toBeTrue();
});

it('deletes a single owned document and blocks download afterward', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $bloodTest = BloodTest::factory()->for($user)->create();
    $document = BloodTestDocument::factory()->for($bloodTest)->create([
        'storage_path' => 'blood-test-documents/single-delete.pdf',
    ]);
    Storage::disk('local')->put($document->storage_path, 'pdf bytes');

    $this->actingAs($user)
        ->delete(route('blood-test-documents.destroy', $document))
        ->assertRedirect(route('blood-tests.show', $bloodTest));

    expect(BloodTestDocument::query()->whereKey($document->id)->exists())->toBeFalse();
    Storage::disk('local')->assertMissing($document->storage_path);

    $this->actingAs($user)
        ->get(route('blood-test-documents.download', $document))
        ->assertNotFound();
});

it('blocks deleting another users document', function () {
    Storage::fake('local');

    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $bloodTest = BloodTest::factory()->for($owner)->create();
    $document = BloodTestDocument::factory()->for($bloodTest)->create([
        'storage_path' => 'blood-test-documents/other-single-delete.pdf',
    ]);
    Storage::disk('local')->put($document->storage_path, 'pdf bytes');

    $this->actingAs($otherUser)
        ->delete(route('blood-test-documents.destroy', $document))
        ->assertForbidden();

    expect(BloodTestDocument::query()->whereKey($document->id)->exists())->toBeTrue();
    Storage::disk('local')->assertExists($document->storage_path);
});

it('renders the password confirmed settings data page with export and delete controls', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('data.edit'))
        ->assertOk()
        ->assertSee('Gegevens en privacy')
        ->assertSee('action="'.route('data.export').'"', false)
        ->assertSee('action="'.route('data.destroy').'"', false)
        ->assertSee('method="POST"', false)
        ->assertSee('name="_method" value="DELETE"', false)
        ->assertSee('name="confirmation"', false)
        ->assertSee('DELETE ALL')
        ->assertSee('data-test="download-data-button"', false)
        ->assertSee('data-test="delete-all-health-data-button"', false);
});

/**
 * @return list<string>
 */
function privacyExportKeys(array $payload): array
{
    $keys = [];

    foreach ($payload as $key => $value) {
        $keys[] = $key;

        if (is_array($value)) {
            array_push($keys, ...privacyExportKeys($value));
        }
    }

    return $keys;
}
