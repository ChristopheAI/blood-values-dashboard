<?php

use App\Models\BloodTest;
use App\Models\BloodTestDocument;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('owner can upload a lab pdf to private storage', function () {
    Storage::fake('local');
    Storage::fake('public');

    $user = User::factory()->create();
    $file = UploadedFile::fake()->create('lab-result.pdf', 64, 'application/pdf');

    $this->actingAs($user)
        ->post(route('blood-tests.store'), [
            'document' => $file,
            'test_date' => '2026-05-19',
            'lab_name' => 'CMA Antwerpen',
            'title' => 'Mei 2026',
        ])
        ->assertRedirect();

    $bloodTest = BloodTest::query()->firstOrFail();
    $document = BloodTestDocument::query()->firstOrFail();

    expect($bloodTest->user_id)->toBe($user->id)
        ->and($bloodTest->status)->toBe('reviewing')
        ->and($document->blood_test_id)->toBe($bloodTest->id)
        ->and($document->storage_disk)->toBe('local');

    Storage::disk('local')->assertExists($document->storage_path);
    Storage::disk('public')->assertMissing($document->storage_path);
});

it('lab pdf storage path does not use original filename', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $file = UploadedFile::fake()->create('Van_Hoof-Christophe-20260519-Labo_CMA.pdf', 64, 'application/pdf');

    $this->actingAs($user)
        ->post(route('blood-tests.store'), ['document' => $file])
        ->assertRedirect();

    $document = BloodTestDocument::query()->firstOrFail();

    expect($document->storage_path)->not->toContain('Van_Hoof-Christophe-20260519-Labo_CMA.pdf')
        ->and($document->original_filename)->toBe('Van_Hoof-Christophe-20260519-Labo_CMA.pdf');
});

it('original filename is sanitized before display storage', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $file = UploadedFile::fake()->create('../bad<script>-name.pdf', 64, 'application/pdf');

    $this->actingAs($user)
        ->post(route('blood-tests.store'), ['document' => $file])
        ->assertRedirect();

    $document = BloodTestDocument::query()->firstOrFail();

    expect($document->original_filename)
        ->not->toContain('/')
        ->not->toContain('\\')
        ->not->toContain('<')
        ->not->toContain('>');
});

it('pdf upload rejects non pdf files', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $file = UploadedFile::fake()->create('not-a-pdf.txt', 8, 'text/plain');

    $this->actingAs($user)
        ->from(route('blood-tests.index'))
        ->post(route('blood-tests.store'), ['document' => $file])
        ->assertRedirect(route('blood-tests.index'))
        ->assertSessionHasErrors('document');

    expect(BloodTest::query()->count())->toBe(0);
});

it('pdf upload rejects files above configured size', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $file = UploadedFile::fake()->create('large-lab.pdf', 12_001, 'application/pdf');

    $this->actingAs($user)
        ->from(route('blood-tests.index'))
        ->post(route('blood-tests.store'), ['document' => $file])
        ->assertRedirect(route('blood-tests.index'))
        ->assertSessionHasErrors('document');

    expect(BloodTest::query()->count())->toBe(0);
});

it('guest cannot access pdf intake', function () {
    $this->get(route('blood-tests.index'))->assertRedirect(route('login'));

    $this->post(route('blood-tests.store'), [
        'document' => UploadedFile::fake()->create('lab.pdf', 64, 'application/pdf'),
    ])->assertRedirect(route('login'));
});
