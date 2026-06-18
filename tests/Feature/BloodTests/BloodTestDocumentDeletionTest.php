<?php

use App\Models\BloodTest;
use App\Models\BloodTestDocument;
use App\Models\User;
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
