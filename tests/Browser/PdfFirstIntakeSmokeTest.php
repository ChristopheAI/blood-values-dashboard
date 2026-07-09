<?php

use App\Models\Biomarker;
use App\Models\BiomarkerResult;
use App\Models\BloodTest;
use App\Models\BloodTestDocument;
use App\Models\User;
use Database\Seeders\BloodValuesQaScenarioSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Dusk\Browser;

test('pdf first intake browser smoke keeps medical copy out of the core flow', function () {
    $email = 'browser-smoke-'.Str::uuid().'@example.test';
    $password = 'password';

    $this->browse(function (Browser $browser) use ($email, $password) {
        registerVerifiedBrowserUser($browser, 'Browser Smoke', $email, $password);

        assertNoForbiddenMedicalCopyAppears($browser);

        $browser->logout()
            ->visit('/login')
            ->type('email', $email)
            ->type('password', $password)
            ->press('Log in')
            ->waitForLocation('/dashboard')
            ->assertPathIs('/dashboard')
            ->assertAuthenticated();

        assertNoForbiddenMedicalCopyAppears($browser);

        uploadBloodTestPdf($browser, 'tests/Fixtures/lab-result-one.pdf', '2026-05-01', 'Labo Een', 'Mei 2026');
        confirmExtractedDraft($browser, 'Ferritin', '42', 'ug/L');

        uploadBloodTestPdf($browser, 'tests/Fixtures/lab-result-two.pdf', '2026-06-01', 'Labo Twee', 'Juni 2026');
        assertAutoFilledConfirmedValue($browser, 'Ferritin', '48', 'ug/L');

        $user = User::query()->where('email', $email)->firstOrFail();
        $biomarker = Biomarker::query()
            ->where('user_id', $user->id)
            ->where('name', 'Ferritin')
            ->firstOrFail();

        $bloodTests = BloodTest::query()
            ->where('user_id', $user->id)
            ->orderBy('id')
            ->get();

        expect($bloodTests)->toHaveCount(2);

        $browser->visit(route('biomarkers.show', $biomarker, false))
            ->waitForText('Ferritin')
            ->assertSee('42 ug/L')
            ->assertSee('48 ug/L');

        assertNoForbiddenMedicalCopyAppears($browser);

        $browser->visit(route('blood-tests.compare', [
            'first' => $bloodTests[0]->id,
            'second' => $bloodTests[1]->id,
        ], false))
            ->waitForText('Compare blood tests')
            ->assertSee('Ferritin')
            ->assertSee('+6')
            ->assertSee('normaal');

        assertNoForbiddenMedicalCopyAppears($browser);

        pinBiomarker($browser, $biomarker->id);
        addContextNote($browser, $bloodTests[1]->id);
        addReminder($browser);
        buildConsultOverview($browser);
        downloadDataExport($browser, $password);
        deleteAllHealthData($browser);
    });
});

test('empty intake uploads through the dropzone and lands on auto-filled results', function () {
    $email = 'browser-dropzone-'.Str::uuid().'@example.test';
    $password = 'password';

    $this->browse(function (Browser $browser) use ($email, $password) {
        registerVerifiedBrowserUser($browser, 'Dropzone Smoke', $email, $password);

        $user = User::query()->where('email', $email)->firstOrFail();
        $ferritin = Biomarker::factory()->for($user)->create(['name' => 'Ferritin']);
        $previousBloodTest = BloodTest::factory()->for($user)->create([
            'test_date' => '2026-05-01',
            'status' => 'confirmed',
        ]);
        BiomarkerResult::factory()->for($previousBloodTest)->for($ferritin)->create([
            'value' => 40,
            'unit' => 'ug/L',
            'status' => 'normal',
            'confirmed_at' => now(),
        ]);

        $browser->visit('/blood-tests')
            ->waitFor('[data-test="lab-pdf-dropzone"]')
            ->assertSee('Sleep je lab-PDF hierheen')
            ->assertPresent('[data-test="lab-pdf-input"]')
            ->assertMissing('[data-test="blood-test-date-input"]')
            ->assertMissing('[data-test="blood-test-lab-input"]')
            ->assertMissing('[data-test="blood-test-title-input"]')
            ->assertMissing('[data-test="upload-pdf-button"]')
            ->assertMissing('input[name="email"]')
            ->assertMissing('input[name="account"]')
            ->attach('document', base_path('tests/Fixtures/assisted-extraction-lab.pdf'))
            ->waitFor('[data-test="blood-test-result"]')
            ->assertPresent('[data-test="intake-progress-stage-extract"]')
            ->assertDataAttribute('[data-test="intake-progress-stage-extract"]', 'state', 'done')
            ->assertPresent('[data-test="intake-progress-stage-values"]')
            ->assertDataAttribute('[data-test="intake-progress-stage-values"]', 'state', 'done')
            ->assertPresent('[data-test="intake-progress-stage-status"]')
            ->assertDataAttribute('[data-test="intake-progress-stage-status"]', 'state', 'done')
            ->assertPresent('[data-test="intake-progress-stage-trend"]')
            ->assertDataAttribute('[data-test="intake-progress-stage-trend"]', 'state', 'done')
            ->assertSee('Bevestigde waarden')
            ->assertSee('Ferritin')
            ->assertSee('42 ug/L')
            ->assertSee('automatisch ingevuld uit PDF')
            ->assertSee('normaal')
            ->assertPresent('[data-test="confirmed-value-trend"][data-state="compared"]')
            ->assertSee('+2 ug/L')
            ->assertSee('vorige 40 ug/L')
            ->assertPresent('[data-test="review-strip"]')
            ->assertPresent('[data-test="extracted-draft-row"][data-state="draft"][data-confidence="low"]')
            ->assertSee('CRP')
            ->assertSee('Vitamin D')
            ->assertDontSee('Nog geen bevestigde waarden.');

        assertNoForbiddenMedicalCopyAppears($browser);
    });
});

test('synthetic qa scenario proves the full multi blood test follow up flow', function () {
    Artisan::call('app:seed-blood-test-demo');

    $user = User::query()
        ->where('email', BloodValuesQaScenarioSeeder::USER_EMAIL)
        ->firstOrFail();
    $olderBloodTest = BloodTest::query()
        ->where('user_id', $user->id)
        ->where('title', 'QA Blood Test - Older')
        ->firstOrFail();
    $currentBloodTest = BloodTest::query()
        ->where('user_id', $user->id)
        ->where('title', 'QA Blood Test - Current')
        ->firstOrFail();
    $foreignUser = User::factory()->create([
        'email' => 'qa-foreign-owner@example.test',
        'email_verified_at' => now(),
    ]);
    $foreignBloodTest = BloodTest::factory()->for($foreignUser)->create([
        'title' => 'Foreign Owner Blood Test',
        'test_date' => '2026-06-20',
        'lab_name' => 'Foreign Synthetic Lab',
        'status' => 'confirmed',
    ]);
    $foreignDocument = BloodTestDocument::factory()->for($foreignBloodTest)->create([
        'original_filename' => 'foreign-owner-lab.pdf',
        'storage_path' => 'blood-test-documents/foreign-owner-lab.pdf',
    ]);
    $foreignBiomarker = Biomarker::factory()->for($foreignUser)->create(['name' => 'Foreign private marker']);

    Storage::disk('local')->put($foreignDocument->storage_path, "%PDF-1.4\n% foreign synthetic QA PDF\n%%EOF\n");

    BiomarkerResult::factory()->for($foreignBloodTest)->for($foreignBiomarker)->create([
        'value' => 999,
        'unit' => 'mg/L',
        'status' => 'high',
        'confirmed_at' => now(),
    ]);

    $this->browse(function (Browser $browser) use ($user, $olderBloodTest, $currentBloodTest, $foreignBloodTest, $foreignDocument) {
        $browser->loginAs($user)
            ->visit('/dashboard')
            ->assertPathIs('/dashboard')
            ->assertAuthenticated()
            ->assertPresent('[data-test="dashboard-metrics"]')
            ->assertSee('Laatste bloedtest')
            ->assertSee('Laatste bevestigde waarden')
            ->assertPresent('[data-test="dashboard-latest-blood-test"]')
            ->assertPresent('[data-test="dashboard-latest-values-preview"]')
            ->assertMissing('[data-test="blood-results-overview"]')
            ->assertSee('QA Blood Test - Current')
            ->assertSee('CRP')
            ->assertSee('7.8 mg/L')
            ->assertSee('Vitamin D')
            ->assertDontSee('2.1 mIU/L');

        assertNoForbiddenMedicalCopyAppears($browser);

        $browser->visit('/blood-tests')
            ->waitForText('QA Blood Test - Current')
            ->assertSee('QA Blood Test - Older')
            ->assertSee('Synthetic QA Lab')
            ->assertDontSee('Foreign Owner Blood Test')
            ->assertDontSee('Foreign Synthetic Lab');

        $browser->visit(route('blood-tests.show', $olderBloodTest, false))
            ->waitFor('[data-test="blood-test-result"]')
            ->assertSee('QA Blood Test - Older')
            ->assertSee('qa-older-lab.pdf')
            ->assertSee('Ferritin')
            ->assertSee('48 ug/L')
            ->assertDontSee('Vitamin D')
            ->assertDontSee('2.1 mIU/L')
            ->assertPresent('[data-test="source-document-row"]');

        assertNoForbiddenMedicalCopyAppears($browser);

        $browser->visit(route('blood-tests.show', $currentBloodTest, false))
            ->waitFor('[data-test="blood-test-result"]')
            ->assertSee('QA Blood Test - Current')
            ->assertSee('qa-current-lab.pdf')
            ->assertSee('CRP')
            ->assertSee('7.8 mg/L')
            ->assertSee('TSH')
            ->assertSee('Bevestiging nodig')
            ->assertPresent('[data-test="extracted-draft-row"][data-state="draft"][data-confidence="low"]');

        assertNoForbiddenMedicalCopyAppears($browser);

        $browser->visit(route('blood-tests.compare', [
            'first' => $olderBloodTest->id,
            'second' => $currentBloodTest->id,
        ], false))
            ->waitFor('[data-test="blood-test-comparison-table"]')
            ->assertSee('Ferritin')
            ->assertSee('-12')
            ->assertSee('CRP')
            ->assertSee('+6.6')
            ->assertDontSee('TSH');

        assertNoForbiddenMedicalCopyAppears($browser);

        resetDuskDownloads();

        $browser->visit(route('consult-overview.index', [], false))
            ->waitForText('Consultlijst')
            ->check("input[name='blood_test_ids[]'][value='{$olderBloodTest->id}']")
            ->check("input[name='blood_test_ids[]'][value='{$currentBloodTest->id}']")
            ->check('[data-test="include-pinned-checkbox"]')
            ->check('[data-test="include-attention-checkbox"]')
            ->check('[data-test="include-normal-checkbox"]')
            ->check('[data-test="include-trends-checkbox"]')
            ->check('[data-test="include-context-checkbox"]')
            ->check('[data-test="include-source-documents-checkbox"]')
            ->type('questions', 'What changed since the older test?')
            ->click('[data-test="build-consult-overview-button"]')
            ->waitFor('[data-test="consult-pack"]')
            ->assertPresent('[data-test="consult-attention-values"]')
            ->assertPresent('[data-test="consult-normal-values"]')
            ->assertPresent('[data-test="consult-trend-changes"]')
            ->assertPresent('[data-test="consult-context-notes"]')
            ->assertPresent('[data-test="consult-source-documents"]')
            ->assertSee('CRP')
            ->assertSee('7.8 mg/L')
            ->assertSee('Ferritin')
            ->assertSee('-12 ug/L')
            ->assertSee('Synthetic QA context note before the current blood draw.')
            ->assertSee('qa-current-lab.pdf')
            ->assertSee('What changed since the older test?')
            ->assertDontSee('2.1 mIU/L')
            ->assertSourceMissing('blood-test-documents/qa/')
            ->click('[data-test="export-consult-csv-button"]');

        $browser->waitUsing(10, 100, function (): bool {
            return duskDownloadedConsultCsvPath() !== null;
        }, 'The consult CSV file was not downloaded.');

        $csvPath = duskDownloadedConsultCsvPath();

        expect($csvPath)->not->toBeNull();

        $csv = File::get($csvPath);

        expect($csv)->toContain('attention');
        expect($csv)->toContain('CRP');
        expect($csv)->toContain('7.8');
        expect($csv)->toContain('source_document');
        expect($csv)->toContain('qa-current-lab.pdf');
        expect($csv)->not->toContain('What changed since the older test?');
        expect($csv)->not->toContain('2.1');
        expect($csv)->not->toContain('blood-test-documents/qa/');

        $browser->resize(390, 844)
            ->visit(route('consult-overview.index', [], false))
            ->waitForText('Consultlijst')
            ->assertPresent('[data-test="consult-overview-form"]')
            ->assertDontSee('Foreign Owner Blood Test')
            ->resize(1280, 900)
            ->assertPresent('[data-test="consult-overview-form"]');

        assertNoForbiddenMedicalCopyAppears($browser);

        expect(browserGetStatus($browser, route('blood-tests.show', $foreignBloodTest, false)))->toBe(403);
        expect(browserGetStatus($browser, route('blood-test-documents.download', $foreignDocument, false)))->toBe(403);
        expect(browserGetStatus($browser, route('blood-tests.compare', [
            'first' => $olderBloodTest->id,
            'second' => $foreignBloodTest->id,
        ], false)))->toBe(403);
        expect(browserPostStatus($browser, route('consult-overview.index', [], false), [
            'blood_test_ids' => [$olderBloodTest->id, $foreignBloodTest->id],
            'include_attention' => '1',
            'include_source_documents' => '1',
        ]))->toBe(403);
        expect(browserPostStatus($browser, route('consult-overview.csv', [], false), [
            'blood_test_ids' => [$olderBloodTest->id, $foreignBloodTest->id],
            'include_attention' => '1',
            'include_source_documents' => '1',
        ]))->toBe(403);

        $payload = downloadDataExport($browser, 'password', false);
        $encodedPayload = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);

        expect($encodedPayload)->toContain('QA Blood Test - Older');
        expect($encodedPayload)->toContain('QA Blood Test - Current');
        expect($encodedPayload)->not->toContain('Foreign Owner Blood Test');
        expect($encodedPayload)->not->toContain('Foreign Synthetic Lab');
        expect($encodedPayload)->not->toContain('foreign-owner-lab.pdf');
        expect($encodedPayload)->not->toContain('Foreign private marker');
        expect($encodedPayload)->not->toContain('999');
    });
});

function uploadBloodTestPdf(Browser $browser, string $fixturePath, string $date, string $lab, string $title): void
{
    $browser->visit('/blood-tests')
        ->waitFor('[data-test="lab-pdf-dropzone"]')
        ->attach('document', base_path($fixturePath))
        ->waitFor('[data-test="blood-test-result"]')
        ->assertPresent('[data-test="blood-test-result"]');

    assertNoForbiddenMedicalCopyAppears($browser);
}

function confirmExtractedDraft(Browser $browser, string $name, string $value, string $unit): void
{
    $browser->assertSee($name)
        ->assertSee("{$value} {$unit}")
        ->click('[data-test="use-draft-button"]');

    $browser->waitUsing(5, 100, function () use ($browser, $value): bool {
        return $browser->value('[data-test="biomarker-value-input"]') === $value;
    }, 'The extracted draft value was not loaded into the review form.');

    $browser->scrollIntoView('[data-test="confirm-value-button"]')
        ->click('[data-test="confirm-value-button"]')
        ->waitForText("{$value} {$unit}")
        ->assertSee($name)
        ->assertSee('normaal');

    assertNoForbiddenMedicalCopyAppears($browser);
}

function assertAutoFilledConfirmedValue(Browser $browser, string $name, string $value, string $unit): void
{
    $browser->waitForText('Bevestigde waarden')
        ->assertSee($name)
        ->assertSee("{$value} {$unit}")
        ->assertSee('automatisch ingevuld uit PDF')
        ->assertSee('normaal');

    assertNoForbiddenMedicalCopyAppears($browser);
}

function confirmBiomarkerValue(
    Browser $browser,
    string $name,
    string $value,
    string $unit,
    string $referenceMin,
    string $referenceMax,
    string $referenceUnit,
): void {
    $browser->type('[data-test="biomarker-name-input"]', $name)
        ->type('[data-test="biomarker-value-input"]', $value)
        ->type('[data-test="biomarker-unit-input"]', $unit)
        ->type('[data-test="reference-min-input"]', $referenceMin)
        ->type('[data-test="reference-max-input"]', $referenceMax)
        ->type('[data-test="reference-unit-input"]', $referenceUnit)
        ->scrollIntoView('[data-test="confirm-value-button"]')
        ->click('[data-test="confirm-value-button"]')
        ->waitForText("{$value} {$unit}")
        ->assertSee($name)
        ->assertSee('normaal');

    assertNoForbiddenMedicalCopyAppears($browser);
}

function pinBiomarker(Browser $browser, int $biomarkerId): void
{
    $browser->visit(route('biomarkers.show', $biomarkerId, false))
        ->waitForText('Ferritin')
        ->type('[data-test="pin-note-input"]', 'Follow around consults')
        ->click('[data-test="pin-biomarker-button"]')
        ->waitForText('Losmaken')
        ->assertSee('Ferritin');

    assertNoForbiddenMedicalCopyAppears($browser);
}

function addContextNote(Browser $browser, int $bloodTestId): void
{
    $browser->visit('/context-notes')
        ->waitForText('Contextnotities')
        ->value('input[name="note_date"]', '2026-06-01')
        ->select('category', 'sleep')
        ->select('blood_test_id', (string) $bloodTestId)
        ->type('body', 'Slept poorly before the June test.')
        ->click('[data-test="save-context-note-button"]')
        ->waitForText('Slept poorly before the June test.');

    assertNoForbiddenMedicalCopyAppears($browser);
}

function addReminder(Browser $browser): void
{
    $browser->visit('/reminders')
        ->waitForText('Reminders')
        ->value('input[name="due_date"]', '2026-07-15')
        ->type('title', 'Plan next blood test')
        ->type('note', 'Check calendar for a morning slot.')
        ->click('[data-test="save-reminder-button"]')
        ->waitForText('Plan next blood test')
        ->visit('/dashboard')
        ->waitForText('Volgende herinnering')
        ->assertSee('Plan next blood test')
        ->assertSee('15 juli 2026');

    assertNoForbiddenMedicalCopyAppears($browser);
}

function buildConsultOverview(Browser $browser): void
{
    $browser->visit(route('consult-overview.index', [], false))
        ->waitForText('Consultlijst')
        ->value('input[name="from"]', '2026-05-01')
        ->value('input[name="to"]', '2026-06-01')
        ->check('[data-test="include-pinned-checkbox"]')
        ->check('[data-test="include-trends-checkbox"]')
        ->check('[data-test="include-context-checkbox"]')
        ->type('questions', 'What changed between these tests?')
        ->click('[data-test="build-consult-overview-button"]')
        ->waitForText('Persoonlijke trackinggegevens uit bevestigde waarden')
        ->assertSee('Persoonlijke trackinggegevens uit bevestigde waarden')
        ->assertSee('Bespreek dit overzicht met je arts')
        ->assertSee('Ferritin')
        ->assertSee('Follow around consults')
        ->assertSee('Slept poorly before the June test.')
        ->assertSee('What changed between these tests?');

    assertNoForbiddenMedicalCopyAppears($browser);
}

/**
 * @return array<string, mixed>
 */
function downloadDataExport(Browser $browser, string $password, bool $assertExpectedCounts = true): array
{
    resetDuskDownloads();

    $browser->visit(route('data.edit', [], false))
        ->waitUsing(5, 100, function () use ($browser): bool {
            return $browser->element('[data-test="confirm-password-button"]') !== null
                || $browser->element('[data-test="download-data-button"]') !== null;
        }, 'The data export page did not load.');

    if ($browser->element('[data-test="confirm-password-button"]') !== null) {
        $browser->type('password', $password)
            ->click('[data-test="confirm-password-button"]')
            ->waitForLocation('/settings/data');
    }

    $browser->waitForText('Data and privacy')
        ->assertSee('Download my data')
        ->click('[data-test="download-data-button"]');

    $browser->waitUsing(10, 100, function (): bool {
        return duskDownloadedExportPath() !== null;
    }, 'The data export JSON file was not downloaded.');

    $downloadedExportPath = duskDownloadedExportPath();

    expect($downloadedExportPath)->not->toBeNull();

    $payload = json_decode(File::get($downloadedExportPath), true, flags: JSON_THROW_ON_ERROR);

    if ($assertExpectedCounts) {
        expect($payload['blood_tests'])->toHaveCount(2);
        expect($payload['biomarker_results'])->toHaveCount(2);
        expect($payload['documents'])->toHaveCount(2);
        expect($payload['pinned_biomarkers'])->toHaveCount(1);
        expect($payload['context_notes'])->toHaveCount(1);
        expect($payload['reminders'])->toHaveCount(1);
    }

    assertNoForbiddenMedicalCopyAppears($browser);

    return $payload;
}

function deleteAllHealthData(Browser $browser): void
{
    $browser->visit(route('data.edit', [], false))
        ->waitForText('Data and privacy')
        ->type('[data-test="delete-all-confirmation-input"]', 'DELETE ALL')
        ->click('[data-test="delete-all-health-data-button"]')
        ->waitForText('Your personal tracking records were deleted.')
        ->visit('/dashboard')
        ->waitFor('[data-test="lab-pdf-dropzone"]')
        ->assertPresent('[data-test="dashboard-next-step"]')
        ->assertSee('Eerste lab-PDF toevoegen')
        ->assertPresent('[data-test="dashboard-blood-test-timeline"]')
        ->assertSee('Sleep je lab-PDF hierheen')
        ->assertPresent('[data-test="lab-pdf-input"]')
        ->assertPresent('[data-test="choose-pdf-button"]')
        ->assertMissing('[data-test="dashboard-latest-confirmed-values"]')
        ->assertDontSee('Ferritin')
        ->assertDontSee('42 ug/L')
        ->assertDontSee('48 ug/L');

    assertNoForbiddenMedicalCopyAppears($browser);
}

function resetDuskDownloads(): void
{
    File::ensureDirectoryExists(duskDownloadDirectory());

    foreach (File::glob(duskDownloadDirectory().'/*') ?: [] as $file) {
        File::delete($file);
    }
}

function duskDownloadedExportPath(): ?string
{
    $paths = File::glob(duskDownloadDirectory().'/blood-values-data-export-*.json') ?: [];

    return $paths[0] ?? null;
}

function duskDownloadedConsultCsvPath(): ?string
{
    $paths = File::glob(duskDownloadDirectory().'/consult-overview*.csv') ?: [];

    return $paths[0] ?? null;
}

function duskDownloadDirectory(): string
{
    return storage_path('framework/testing/dusk-downloads');
}

function browserGetStatus(Browser $browser, string $url): int
{
    $result = $browser->script(sprintf(
        <<<'JS'
const request = new XMLHttpRequest();
request.open('GET', %s, false);
request.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
request.send();
return request.status;
JS,
        json_encode($url, JSON_THROW_ON_ERROR),
    ));

    return (int) ($result[0] ?? 0);
}

/**
 * @param  array<string, mixed>  $fields
 */
function browserPostStatus(Browser $browser, string $url, array $fields): int
{
    $body = http_build_query($fields);
    $result = $browser->script(sprintf(
        <<<'JS'
const token = document.querySelector('input[name="_token"]')?.value;
const request = new XMLHttpRequest();
request.open('POST', %s, false);
request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
request.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
if (token) {
    request.setRequestHeader('X-CSRF-TOKEN', token);
}
request.send(%s);
return request.status;
JS,
        json_encode($url, JSON_THROW_ON_ERROR),
        json_encode($body, JSON_THROW_ON_ERROR),
    ));

    return (int) ($result[0] ?? 0);
}

function registerVerifiedBrowserUser(Browser $browser, string $name, string $email, string $password): void
{
    $verificationNoticePath = route('verification.notice', [], false);

    $browser->visit('/register')
        ->type('name', $name)
        ->type('email', $email)
        ->type('password', $password)
        ->type('password_confirmation', $password)
        ->press('Create account')
        ->waitForLocation($verificationNoticePath)
        ->assertPathIs($verificationNoticePath)
        ->assertAuthenticated();

    User::query()
        ->where('email', $email)
        ->firstOrFail()
        ->forceFill(['email_verified_at' => now()])
        ->save();

    $browser->visit('/dashboard')
        ->assertPathIs('/dashboard')
        ->assertAuthenticated();
}

function assertNoForbiddenMedicalCopyAppears(Browser $browser): void
{
    foreach (forbiddenMedicalCopyTerms() as $term) {
        $browser->assertDontSee($term, true);
    }
}

/**
 * @return list<string>
 */
function forbiddenMedicalCopyTerms(): array
{
    return [
        'diagnose',
        'behandeling',
        'advies',
        'aanbevolen supplement',
        'gezondheidsscore',
        'optimaal voor jou',
        'risico voorspeld',
        'medisch oordeel',
    ];
}
