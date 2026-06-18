<?php

use App\Models\BloodTest;
use App\Models\BloodTestDocument;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

it('guest cannot download lab pdf', function () {
    $document = BloodTestDocument::factory()->create();

    $this->get(route('blood-test-documents.download', $document))
        ->assertRedirect(route('login'));
});

it('owner can download their own lab pdf', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $bloodTest = BloodTest::factory()->for($user)->create();
    $document = BloodTestDocument::factory()->for($bloodTest)->create([
        'original_filename' => 'lab-result.pdf',
        'storage_path' => 'blood-test-documents/generated.pdf',
    ]);

    Storage::disk('local')->put($document->storage_path, 'pdf bytes');

    $this->actingAs($user)
        ->get(route('blood-test-documents.download', $document))
        ->assertOk()
        ->assertDownload('lab-result.pdf');
});

it('user cannot download another users lab pdf', function () {
    Storage::fake('local');

    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $bloodTest = BloodTest::factory()->for($owner)->create();
    $document = BloodTestDocument::factory()->for($bloodTest)->create([
        'storage_path' => 'blood-test-documents/private.pdf',
    ]);

    Storage::disk('local')->put($document->storage_path, 'pdf bytes');

    $this->actingAs($otherUser)
        ->get(route('blood-test-documents.download', $document))
        ->assertForbidden();
});

it('user cannot view another users blood test document record', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $bloodTest = BloodTest::factory()->for($owner)->create();

    $this->actingAs($otherUser)
        ->get(route('blood-tests.show', $bloodTest))
        ->assertForbidden();
});

it('lab pdf page does not expose public storage url', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $bloodTest = BloodTest::factory()->for($user)->create();
    $document = BloodTestDocument::factory()->for($bloodTest)->create([
        'storage_path' => 'blood-test-documents/private.pdf',
    ]);

    Storage::disk('local')->put($document->storage_path, 'pdf bytes');

    $this->actingAs($user)
        ->get(route('blood-tests.show', $bloodTest))
        ->assertOk()
        ->assertSee(route('blood-test-documents.download', $document, false))
        ->assertDontSee('/storage/')
        ->assertDontSee(Storage::disk('local')->path($document->storage_path));
});
