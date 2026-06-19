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

it('uses the sanitized pdf filename as the upload-first title when no metadata is posted', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $file = UploadedFile::fake()->create('bloedafname 8april2026.pdf', 64, 'application/pdf');

    $this->actingAs($user)
        ->post(route('blood-tests.store'), ['document' => $file])
        ->assertRedirect();

    $bloodTest = BloodTest::query()->firstOrFail();

    expect($bloodTest->title)->toBe('bloedafname 8april2026')
        ->and($bloodTest->test_date)->toBeNull()
        ->and($bloodTest->lab_name)->toBeNull();
});

it('renders an upload-first empty intake dropzone without metadata or account fields', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('blood-tests.index'))
        ->assertOk()
        ->assertSee('Sleep je lab-PDF hierheen')
        ->assertSee('PDF only')
        ->assertSee('data-test="lab-pdf-dropzone"', false)
        ->assertSee('data-test="intake-progress"', false)
        ->assertDontSee('data-test="upload-pdf-button"', false)
        ->assertDontSee('data-test="blood-test-date-input"', false)
        ->assertDontSee('data-test="blood-test-lab-input"', false)
        ->assertDontSee('data-test="blood-test-title-input"', false)
        ->assertDontSee('name="email"', false)
        ->assertDontSee('name="account"', false);
});

it('renders the dropzone choose control as a button and keeps the input pdf only', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('blood-tests.index'));
    $html = $response->getContent();

    expect($html)
        ->toContain('data-test="lab-pdf-dropzone"')
        ->toContain('@drop.prevent="dragging = false; setFiles($event.dataTransfer.files); if (fileName) $nextTick(() => $el.closest(\'form\').requestSubmit())"')
        ->toContain('@change="fileName = $event.target.files[0]?.name ?? \'\'; if (fileName) $nextTick(() => $el.form.requestSubmit())"')
        ->toContain('data-test="selected-file-name"')
        ->toMatch('/<button\s+[^>]*type="button"[^>]*data-test="choose-pdf-button"/s')
        ->toMatch('/<input\s+[^>]*name="document"[^>]*accept="application\/pdf"[^>]*data-test="lab-pdf-input"/s');
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

it('limits the sanitized original filename to the document metadata column length', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $longFilename = str_repeat('very-long-lab-name-', 20).'result.pdf';
    $file = UploadedFile::fake()->create($longFilename, 64, 'application/pdf');

    $this->actingAs($user)
        ->post(route('blood-tests.store'), ['document' => $file])
        ->assertRedirect();

    $document = BloodTestDocument::query()->firstOrFail();

    expect(strlen($document->original_filename))->toBeLessThanOrEqual(255)
        ->and($document->original_filename)->toEndWith('.pdf');
});

it('removes the stored pdf when intake persistence fails after upload', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $file = UploadedFile::fake()->create('lab-result.pdf', 64, 'application/pdf');
    $caught = null;

    BloodTestDocument::created(function (): void {
        throw new RuntimeException('Synthetic document persistence failure');
    });

    try {
        $this->withoutExceptionHandling()
            ->actingAs($user)
            ->post(route('blood-tests.store'), ['document' => $file]);
    } catch (RuntimeException $exception) {
        $caught = $exception;
    } finally {
        BloodTestDocument::flushEventListeners();
    }

    expect($caught?->getMessage())->toBe('Synthetic document persistence failure')
        ->and(BloodTest::query()->count())->toBe(0)
        ->and(BloodTestDocument::query()->count())->toBe(0)
        ->and(Storage::disk('local')->allFiles('blood-test-documents'))->toBe([]);
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
