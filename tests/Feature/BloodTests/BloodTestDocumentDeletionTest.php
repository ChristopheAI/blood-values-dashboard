<?php

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
