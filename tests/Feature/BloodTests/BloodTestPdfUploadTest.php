<?php

use App\Models\BloodTest;
use App\Models\BloodTestDocument;
use App\Models\ExtractionRun;
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
            'lab_name' => 'Synthetic Lab',
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
    $file = UploadedFile::fake()->create('synthetic-april-lab.pdf', 64, 'application/pdf');

    $this->actingAs($user)
        ->post(route('blood-tests.store'), ['document' => $file])
        ->assertRedirect();

    $bloodTest = BloodTest::query()->firstOrFail();

    expect($bloodTest->title)->toBe('synthetic-april-lab')
        ->and($bloodTest->test_date)->toBeNull()
        ->and($bloodTest->lab_name)->toBeNull();
});

it('renders an upload-first empty intake dropzone without metadata or account fields', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('blood-tests.index'));
    $html = $response->getContent();

    $response
        ->assertOk()
        ->assertSee('Sleep je lab-PDF hierheen')
        ->assertSee('Alleen PDF')
        ->assertSee('data-test="upload-trust-notice"', false)
        ->assertSee('Lokaal gelezen uit de tekstlaag van de PDF. Geen externe verwerking.')
        ->assertSee('Waarden tellen pas mee voor status en trends nadat ze bevestigd zijn.')
        ->assertSee('data-test="lab-pdf-dropzone"', false)
        ->assertSee('data-test="intake-progress"', false)
        ->assertDontSee('data-test="upload-pdf-button"', false)
        ->assertDontSee('data-test="blood-test-date-input"', false)
        ->assertDontSee('data-test="blood-test-lab-input"', false)
        ->assertDontSee('data-test="blood-test-title-input"', false)
        ->assertDontSee('name="email"', false)
        ->assertDontSee('name="account"', false);

    expect($html)
        ->toContain('data-test="intake-progress-stage-extract"')
        ->toContain(':data-state="progressStages.extract"')
        ->toContain('data-test="intake-progress-stage-values"')
        ->toContain(':data-state="progressStages.values"')
        ->toContain('data-test="intake-progress-stage-status"')
        ->toContain(':data-state="progressStages.status"')
        ->toContain('data-test="intake-progress-stage-trend"')
        ->toContain(':data-state="progressStages.trend"')
        ->not->toContain('data-state="pending"');
});

it('lists blood tests by most recent blood test date first', function () {
    $user = User::factory()->create();
    BloodTest::factory()->for($user)->create([
        'title' => 'Current dated blood test',
        'test_date' => '2026-06-15',
        'status' => 'reviewing',
        'created_at' => now()->subDay(),
    ]);
    BloodTest::factory()->for($user)->create([
        'title' => 'Older but later created record',
        'test_date' => '2026-04-15',
        'status' => 'confirmed',
        'created_at' => now(),
    ]);

    $content = $this->actingAs($user)
        ->get(route('blood-tests.index'))
        ->assertOk()
        ->getContent();

    expect($content)
        ->toContain('Current dated blood test')
        ->toContain('Older but later created record')
        ->and(strpos($content, 'Current dated blood test'))
        ->toBeLessThan(strpos($content, 'Older but later created record'));
});

it('paginates the blood test index', function () {
    $user = User::factory()->create();

    foreach (range(1, 16) as $day) {
        BloodTest::factory()->for($user)->create([
            'title' => sprintf('Paged blood test %02d', $day),
            'test_date' => sprintf('2026-01-%02d', $day),
        ]);
    }

    $this->actingAs($user)
        ->get(route('blood-tests.index'))
        ->assertOk()
        ->assertSee('Paged blood test 16')
        ->assertDontSee('Paged blood test 01')
        ->assertSee('data-test="blood-test-pagination"', false);

    $this->actingAs($user)
        ->get(route('blood-tests.index', ['page' => 2]))
        ->assertOk()
        ->assertSee('Paged blood test 01')
        ->assertDontSee('Paged blood test 16');
});

it('renders the dropzone choose control as a button and keeps the input pdf only', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('blood-tests.index'));
    $html = $response->getContent();

    expect($html)
        ->toContain('data-test="lab-pdf-dropzone"')
        ->toContain('fetch(form.action')
        ->toContain('data-test="upload-trust-notice"')
        ->toContain("'Accept': 'application/x-ndjson'")
        ->toContain("'X-Intake-Stream': '1'")
        ->toContain('progressStages[payload.stage] = payload.state')
        ->toContain('window.location.href = payload.redirect')
        ->toContain('@drop.prevent="dragging = false; setFiles($event.dataTransfer.files); if (fileName) $nextTick(() => $el.closest(\'form\').requestSubmit())"')
        ->toContain('@change="fileName = $event.target.files[0]?.name ?? \'\'; if (fileName) $nextTick(() => $el.form.requestSubmit())"')
        ->toContain('data-test="selected-file-name"')
        ->toMatch('/<button\s+[^>]*type="button"[^>]*data-test="choose-pdf-button"/s')
        ->toMatch('/<input\s+[^>]*name="document"[^>]*accept="application\/pdf"[^>]*data-test="lab-pdf-input"/s');
});

it('streams safe real-stage progress for enhanced pdf uploads', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $file = new UploadedFile(
        base_path('tests/Fixtures/assisted-extraction-lab.pdf'),
        'assisted-extraction-lab.pdf',
        'application/pdf',
        null,
        true,
    );

    $response = $this->actingAs($user)
        ->post(route('blood-tests.store'), [
            'document' => $file,
        ], [
            'Accept' => 'application/x-ndjson',
            'X-Intake-Stream' => '1',
        ]);

    $response->assertOk()
        ->assertHeader('Content-Type', 'application/x-ndjson');

    $content = trim($response->streamedContent());
    $events = collect(explode("\n", $content))
        ->map(fn (string $line): array => json_decode($line, true, flags: JSON_THROW_ON_ERROR))
        ->all();

    expect($events)->sequence(
        fn ($event) => $event->toMatchArray(['stage' => 'extract', 'state' => 'active']),
        fn ($event) => $event->toMatchArray(['stage' => 'extract', 'state' => 'done']),
        fn ($event) => $event->toMatchArray(['stage' => 'values', 'state' => 'active']),
        fn ($event) => $event->toMatchArray(['stage' => 'values', 'state' => 'done']),
        fn ($event) => $event->toMatchArray(['stage' => 'status', 'state' => 'active']),
        fn ($event) => $event->toMatchArray(['stage' => 'status', 'state' => 'done']),
        fn ($event) => $event->toMatchArray(['stage' => 'trend', 'state' => 'active']),
        fn ($event) => $event->toMatchArray(['stage' => 'trend', 'state' => 'done']),
        fn ($event) => $event->toHaveKey('redirect'),
    );

    expect($content)
        ->not->toContain('Ferritin')
        ->not->toContain('42')
        ->not->toContain('assisted-extraction-lab.pdf');

    $bloodTest = BloodTest::query()->firstOrFail();

    expect($events[8]['redirect'])->toBe(route('blood-tests.show', $bloodTest, false));
});

it('streams a safe failed extract stage when enhanced pdf parsing fails', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $file = UploadedFile::fake()->create('malformed-lab.pdf', 64, 'application/pdf');

    $response = $this->actingAs($user)
        ->post(route('blood-tests.store'), [
            'document' => $file,
        ], [
            'Accept' => 'application/x-ndjson',
            'X-Intake-Stream' => '1',
        ]);

    $response->assertOk()
        ->assertHeader('Content-Type', 'application/x-ndjson');

    $content = trim($response->streamedContent());
    $events = collect(explode("\n", $content))
        ->map(fn (string $line): array => json_decode($line, true, flags: JSON_THROW_ON_ERROR))
        ->all();

    expect($events)->sequence(
        fn ($event) => $event->toMatchArray(['stage' => 'extract', 'state' => 'active']),
        fn ($event) => $event->toMatchArray(['stage' => 'extract', 'state' => 'failed']),
    );

    expect(collect($events)->contains(fn (array $event): bool => array_key_exists('redirect', $event)))->toBeFalse();

    expect($content)
        ->not->toContain('malformed-lab.pdf')
        ->not->toContain('Ferritin')
        ->not->toContain('42');

    $bloodTest = BloodTest::query()->firstOrFail();
    $run = ExtractionRun::query()->firstOrFail();

    expect($run->status)->toBe('failed')
        ->and($run->candidate_count)->toBe(0)
        ->and($bloodTest->refresh()->status)->toBe('reviewing');
});

it('redirects failed non-stream uploads back to the intake index', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $file = UploadedFile::fake()->create('malformed-lab.pdf', 64, 'application/pdf');

    $this->actingAs($user)
        ->post(route('blood-tests.store'), [
            'document' => $file,
        ])
        ->assertRedirect(route('blood-tests.index'));

    expect(BloodTest::query()->count())->toBe(1)
        ->and(ExtractionRun::query()->firstOrFail()->status)->toBe('failed');
});

it('lab pdf storage path does not use original filename', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $file = UploadedFile::fake()->create('synthetic-private-lab-2026-05-19.pdf', 64, 'application/pdf');

    $this->actingAs($user)
        ->post(route('blood-tests.store'), ['document' => $file])
        ->assertRedirect();

    $document = BloodTestDocument::query()->firstOrFail();

    expect($document->storage_path)->not->toContain('synthetic-private-lab-2026-05-19.pdf')
        ->and($document->original_filename)->toBe('synthetic-private-lab-2026-05-19.pdf');
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
