<?php

use App\Models\BiomarkerResult;
use App\Models\BloodTest;
use App\Models\BloodTestDocument;
use App\Models\User;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;

it('deleting blood test removes or blocks its lab pdf', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $bloodTest = BloodTest::factory()->for($user)->create();
    $document = BloodTestDocument::factory()->for($bloodTest)->create([
        'storage_path' => 'blood-test-documents/private.pdf',
    ]);

    Storage::disk('local')->put($document->storage_path, 'pdf bytes');

    $this->actingAs($user)
        ->delete(route('blood-tests.destroy', $bloodTest))
        ->assertRedirect(route('blood-tests.index'));

    Storage::disk('local')->assertMissing($document->storage_path);

    $this->actingAs($user)
        ->get(route('blood-test-documents.download', $document))
        ->assertNotFound();
});

it('rejects deleting another users blood test', function () {
    Storage::fake('local');

    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $bloodTest = BloodTest::factory()->for($owner)->create();
    $document = BloodTestDocument::factory()->for($bloodTest)->create([
        'storage_path' => 'blood-test-documents/foreign-blood-test.pdf',
    ]);

    Storage::disk('local')->put($document->storage_path, 'pdf bytes');

    $this->actingAs($otherUser)
        ->delete(route('blood-tests.destroy', $bloodTest))
        ->assertForbidden();

    expect(BloodTest::query()->whereKey($bloodTest->id)->exists())->toBeTrue()
        ->and(BloodTestDocument::query()->whereKey($document->id)->exists())->toBeTrue();
    Storage::disk('local')->assertExists($document->storage_path);
});

it('keeps the stored lab pdf when blood test deletion fails before the database delete', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $bloodTest = BloodTest::factory()->for($user)->create();
    $document = BloodTestDocument::factory()->for($bloodTest)->create([
        'storage_path' => 'blood-test-documents/private-delete-fails.pdf',
    ]);
    $caught = null;

    Storage::disk('local')->put($document->storage_path, 'pdf bytes');

    BloodTest::deleting(function (): void {
        throw new RuntimeException('Synthetic blood test deletion failure');
    });

    try {
        $this->withoutExceptionHandling()
            ->actingAs($user)
            ->delete(route('blood-tests.destroy', $bloodTest));
    } catch (RuntimeException $exception) {
        $caught = $exception;
    } finally {
        BloodTest::flushEventListeners();
    }

    expect($caught?->getMessage())->toBe('Synthetic blood test deletion failure')
        ->and(BloodTest::query()->whereKey($bloodTest->id)->exists())->toBeTrue()
        ->and(BloodTestDocument::query()->whereKey($document->id)->exists())->toBeTrue();
    Storage::disk('local')->assertExists($document->storage_path);
});

it('keeps the blood test and document record when blood test pdf deletion fails', function () {
    $user = User::factory()->create();
    $bloodTest = BloodTest::factory()->for($user)->create();
    $document = BloodTestDocument::factory()->for($bloodTest)->create([
        'storage_disk' => 'local',
        'storage_path' => 'blood-test-documents/blood-test-delete-returned-false.pdf',
    ]);
    $disk = Mockery::mock(Filesystem::class);

    $disk->shouldReceive('delete')
        ->once()
        ->with($document->storage_path)
        ->andReturnFalse();
    Storage::shouldReceive('disk')
        ->once()
        ->with('local')
        ->andReturn($disk);

    $this->actingAs($user)
        ->delete(route('blood-tests.destroy', $bloodTest))
        ->assertServerError();

    expect(BloodTest::query()->whereKey($bloodTest->id)->exists())->toBeTrue()
        ->and(BloodTestDocument::query()->whereKey($document->id)->exists())->toBeTrue();
});

it('preserves every health record when a later document of a multi-document blood test cannot be deleted', function () {
    $user = User::factory()->create();
    $bloodTest = BloodTest::factory()->for($user)->create();
    $firstDocument = BloodTestDocument::factory()->for($bloodTest)->create([
        'storage_disk' => 'local',
        'storage_path' => 'blood-test-documents/multi-first.pdf',
    ]);
    $secondDocument = BloodTestDocument::factory()->for($bloodTest)->create([
        'storage_disk' => 'local',
        'storage_path' => 'blood-test-documents/multi-second.pdf',
    ]);
    $result = BiomarkerResult::factory()->for($bloodTest)->create([
        'confirmed_at' => now(),
    ]);
    $disk = Mockery::mock(Filesystem::class);

    // First file deletes, second fails: the transaction must roll back so no health
    // record is lost, even though the first file is already physically gone (a
    // recoverable orphaned reference, healed by retrying the deletion).
    $disk->shouldReceive('delete')->with($firstDocument->storage_path)->andReturnTrue();
    $disk->shouldReceive('delete')->with($secondDocument->storage_path)->andReturnFalse();
    Storage::shouldReceive('disk')->with('local')->andReturn($disk);

    $this->actingAs($user)
        ->delete(route('blood-tests.destroy', $bloodTest))
        ->assertServerError();

    expect(BloodTest::query()->whereKey($bloodTest->id)->exists())->toBeTrue()
        ->and(BloodTestDocument::query()->whereKey($firstDocument->id)->exists())->toBeTrue()
        ->and(BloodTestDocument::query()->whereKey($secondDocument->id)->exists())->toBeTrue()
        ->and(BiomarkerResult::query()->whereKey($result->id)->exists())->toBeTrue();
});

it('keeps the stored lab pdf when single document deletion fails before the database delete', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $bloodTest = BloodTest::factory()->for($user)->create();
    $document = BloodTestDocument::factory()->for($bloodTest)->create([
        'storage_path' => 'blood-test-documents/single-delete-fails.pdf',
    ]);
    $caught = null;

    Storage::disk('local')->put($document->storage_path, 'pdf bytes');

    BloodTestDocument::deleting(function (): void {
        throw new RuntimeException('Synthetic document deletion failure');
    });

    try {
        $this->withoutExceptionHandling()
            ->actingAs($user)
            ->delete(route('blood-test-documents.destroy', $document));
    } catch (RuntimeException $exception) {
        $caught = $exception;
    } finally {
        BloodTestDocument::flushEventListeners();
    }

    expect($caught?->getMessage())->toBe('Synthetic document deletion failure')
        ->and(BloodTestDocument::query()->whereKey($document->id)->exists())->toBeTrue();
    Storage::disk('local')->assertExists($document->storage_path);
});

it('clears extracted source snippets when deleting a source document', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $bloodTest = BloodTest::factory()->for($user)->create();
    $document = BloodTestDocument::factory()->for($bloodTest)->create([
        'storage_path' => 'blood-test-documents/with-snippets.pdf',
    ]);
    $confirmed = BiomarkerResult::factory()->for($bloodTest)->create([
        'blood_test_document_id' => $document->id,
        'entry_source' => 'extracted',
        'confirmed_at' => now(),
        'source_snippet' => 'Synthetic PDF evidence for confirmed row',
    ]);
    $draft = BiomarkerResult::factory()->for($bloodTest)->create([
        'blood_test_document_id' => $document->id,
        'entry_source' => 'extracted',
        'confirmed_at' => null,
        'source_snippet' => 'Synthetic PDF evidence for draft row',
    ]);
    $legacy = BiomarkerResult::factory()->for($bloodTest)->create([
        'blood_test_document_id' => null,
        'entry_source' => 'extracted',
        'confirmed_at' => now(),
        'source_snippet' => 'Synthetic legacy PDF evidence without document link',
    ]);

    Storage::disk('local')->put($document->storage_path, 'pdf bytes');

    $this->actingAs($user)
        ->delete(route('blood-test-documents.destroy', $document))
        ->assertRedirect(route('blood-tests.show', $bloodTest));

    expect($confirmed->refresh()->source_snippet)->toBeNull()
        ->and($draft->refresh()->source_snippet)->toBeNull()
        ->and($legacy->refresh()->source_snippet)->toBeNull()
        ->and(BiomarkerResult::query()->whereKey($confirmed->id)->exists())->toBeTrue()
        ->and(BiomarkerResult::query()->whereKey($draft->id)->exists())->toBeTrue()
        ->and(BiomarkerResult::query()->whereKey($legacy->id)->exists())->toBeTrue();
});

it('keeps source snippets for confirmed and draft rows from remaining documents', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $bloodTest = BloodTest::factory()->for($user)->create();
    $deletedDocument = BloodTestDocument::factory()->for($bloodTest)->create([
        'storage_path' => 'blood-test-documents/deleted.pdf',
    ]);
    $remainingDocument = BloodTestDocument::factory()->for($bloodTest)->create([
        'storage_path' => 'blood-test-documents/remaining.pdf',
    ]);
    $deletedResult = BiomarkerResult::factory()->for($bloodTest)->create([
        'blood_test_document_id' => $deletedDocument->id,
        'entry_source' => 'extracted',
        'confirmed_at' => now(),
        'source_snippet' => 'Synthetic evidence from deleted PDF',
    ]);
    $remainingResult = BiomarkerResult::factory()->for($bloodTest)->create([
        'blood_test_document_id' => $remainingDocument->id,
        'entry_source' => 'extracted',
        'confirmed_at' => now(),
        'source_snippet' => 'Synthetic evidence from remaining PDF',
    ]);
    $remainingDraft = BiomarkerResult::factory()->for($bloodTest)->create([
        'blood_test_document_id' => $remainingDocument->id,
        'entry_source' => 'extracted',
        'confirmed_at' => null,
        'source_snippet' => 'Synthetic draft evidence from remaining PDF',
    ]);
    $legacyResult = BiomarkerResult::factory()->for($bloodTest)->create([
        'blood_test_document_id' => null,
        'entry_source' => 'extracted',
        'confirmed_at' => now(),
        'source_snippet' => 'Synthetic legacy evidence without document link',
    ]);

    Storage::disk('local')->put($deletedDocument->storage_path, 'pdf bytes');
    Storage::disk('local')->put($remainingDocument->storage_path, 'pdf bytes');

    $this->actingAs($user)
        ->delete(route('blood-test-documents.destroy', $deletedDocument))
        ->assertRedirect(route('blood-tests.show', $bloodTest));

    expect($deletedResult->refresh()->source_snippet)->toBeNull()
        ->and($remainingResult->refresh()->source_snippet)->toBe('Synthetic evidence from remaining PDF')
        ->and($remainingDraft->refresh()->source_snippet)->toBe('Synthetic draft evidence from remaining PDF')
        ->and($legacyResult->refresh()->source_snippet)->toBe('Synthetic legacy evidence without document link');
});

it('rolls back snippet clearing when deleting one pdf of a multi-document blood test fails', function () {
    $user = User::factory()->create();
    $bloodTest = BloodTest::factory()->for($user)->create();
    $deletedDocument = BloodTestDocument::factory()->for($bloodTest)->create([
        'storage_disk' => 'local',
        'storage_path' => 'blood-test-documents/multi-delete-fails.pdf',
    ]);
    $remainingDocument = BloodTestDocument::factory()->for($bloodTest)->create([
        'storage_disk' => 'local',
        'storage_path' => 'blood-test-documents/multi-remaining.pdf',
    ]);
    $deletedResult = BiomarkerResult::factory()->for($bloodTest)->create([
        'blood_test_document_id' => $deletedDocument->id,
        'entry_source' => 'extracted',
        'confirmed_at' => now(),
        'source_snippet' => 'Synthetic evidence from deleted PDF',
    ]);
    $remainingResult = BiomarkerResult::factory()->for($bloodTest)->create([
        'blood_test_document_id' => $remainingDocument->id,
        'entry_source' => 'extracted',
        'confirmed_at' => now(),
        'source_snippet' => 'Synthetic evidence from remaining PDF',
    ]);
    $disk = Mockery::mock(Filesystem::class);

    $disk->shouldReceive('delete')
        ->once()
        ->with($deletedDocument->storage_path)
        ->andReturnFalse();
    Storage::shouldReceive('disk')
        ->once()
        ->with('local')
        ->andReturn($disk);

    $this->actingAs($user)
        ->delete(route('blood-test-documents.destroy', $deletedDocument))
        ->assertServerError();

    expect(BloodTestDocument::query()->whereKey($deletedDocument->id)->exists())->toBeTrue()
        ->and(BloodTestDocument::query()->whereKey($remainingDocument->id)->exists())->toBeTrue()
        ->and($deletedResult->refresh()->source_snippet)->toBe('Synthetic evidence from deleted PDF')
        ->and($remainingResult->refresh()->source_snippet)->toBe('Synthetic evidence from remaining PDF');
});

it('keeps the document record when physical pdf deletion fails', function () {
    $user = User::factory()->create();
    $bloodTest = BloodTest::factory()->for($user)->create();
    $document = BloodTestDocument::factory()->for($bloodTest)->create([
        'storage_disk' => 'local',
        'storage_path' => 'blood-test-documents/delete-returned-false.pdf',
    ]);
    $disk = Mockery::mock(Filesystem::class);

    $disk->shouldReceive('delete')
        ->once()
        ->with($document->storage_path)
        ->andReturnFalse();
    Storage::shouldReceive('disk')
        ->once()
        ->with('local')
        ->andReturn($disk);

    $this->actingAs($user)
        ->delete(route('blood-test-documents.destroy', $document))
        ->assertServerError();

    expect(BloodTestDocument::query()->whereKey($document->id)->exists())->toBeTrue();
});
