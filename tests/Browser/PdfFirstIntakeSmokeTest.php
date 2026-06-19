<?php

use App\Models\Biomarker;
use App\Models\BloodTest;
use App\Models\User;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Laravel\Dusk\Browser;

test('pdf first intake browser smoke keeps medical copy out of the core flow', function () {
    $email = 'browser-smoke-'.Str::uuid().'@example.test';
    $password = 'password';

    $this->browse(function (Browser $browser) use ($email, $password) {
        $browser->visit('/register')
            ->type('name', 'Browser Smoke')
            ->type('email', $email)
            ->type('password', $password)
            ->type('password_confirmation', $password)
            ->press('Create account')
            ->waitForLocation('/dashboard')
            ->assertPathIs('/dashboard')
            ->assertAuthenticated();

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
            ->assertSee('normal');

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
        $browser->visit('/register')
            ->type('name', 'Dropzone Smoke')
            ->type('email', $email)
            ->type('password', $password)
            ->type('password_confirmation', $password)
            ->press('Create account')
            ->waitForLocation('/dashboard')
            ->assertAuthenticated();

        $user = User::query()->where('email', $email)->firstOrFail();
        Biomarker::factory()->for($user)->create(['name' => 'Ferritin']);

        $browser->visit('/blood-tests')
            ->waitFor('[data-test="lab-pdf-dropzone"]')
            ->assertSee('Sleep je lab-PDF hierheen')
            ->assertPresent('[data-test="lab-pdf-input"]')
            ->assertMissing('[data-test="blood-test-date-input"]')
            ->assertMissing('[data-test="blood-test-lab-input"]')
            ->assertMissing('[data-test="blood-test-title-input"]')
            ->assertMissing('input[name="email"]')
            ->assertMissing('input[name="account"]')
            ->attach('document', base_path('tests/Fixtures/assisted-extraction-lab.pdf'))
            ->scrollIntoView('[data-test="upload-pdf-button"]')
            ->click('[data-test="upload-pdf-button"]')
            ->waitFor('[data-test="blood-test-result"]')
            ->assertPresent('[data-test="intake-progress-stage-extract"]')
            ->assertPresent('[data-test="intake-progress-stage-values"]')
            ->assertPresent('[data-test="intake-progress-stage-status"]')
            ->assertPresent('[data-test="intake-progress-stage-trend"]')
            ->assertSee('Confirmed values')
            ->assertSee('Ferritin')
            ->assertSee('42 ug/L')
            ->assertSee('auto-filled from PDF')
            ->assertSee('normal')
            ->assertPresent('[data-test="review-strip"]')
            ->assertDontSee('No confirmed values yet.');

        assertNoForbiddenMedicalCopyAppears($browser);
    });
});

function uploadBloodTestPdf(Browser $browser, string $fixturePath, string $date, string $lab, string $title): void
{
    $browser->visit('/blood-tests')
        ->waitFor('[data-test="lab-pdf-dropzone"]')
        ->attach('document', base_path($fixturePath))
        ->scrollIntoView('[data-test="upload-pdf-button"]')
        ->click('[data-test="upload-pdf-button"]')
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
        ->assertSee('normal');

    assertNoForbiddenMedicalCopyAppears($browser);
}

function assertAutoFilledConfirmedValue(Browser $browser, string $name, string $value, string $unit): void
{
    $browser->waitForText('Confirmed values')
        ->assertSee($name)
        ->assertSee("{$value} {$unit}")
        ->assertSee('auto-filled from PDF')
        ->assertSee('normal');

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
        ->assertSee('normal');

    assertNoForbiddenMedicalCopyAppears($browser);
}

function pinBiomarker(Browser $browser, int $biomarkerId): void
{
    $browser->visit(route('biomarkers.show', $biomarkerId, false))
        ->waitForText('Ferritin')
        ->type('[data-test="pin-note-input"]', 'Follow around consults')
        ->click('[data-test="pin-biomarker-button"]')
        ->waitForText('Unpin')
        ->assertSee('Ferritin');

    assertNoForbiddenMedicalCopyAppears($browser);
}

function addContextNote(Browser $browser, int $bloodTestId): void
{
    $browser->visit('/context-notes')
        ->waitForText('Context notes')
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
        ->waitForText('Next reminder')
        ->assertSee('Plan next blood test')
        ->assertSee('2026-07-15');

    assertNoForbiddenMedicalCopyAppears($browser);
}

function buildConsultOverview(Browser $browser): void
{
    $browser->visit(route('consult-overview.index', [], false))
        ->waitForText('Consult overview')
        ->value('input[name="from"]', '2026-05-01')
        ->value('input[name="to"]', '2026-06-01')
        ->check('[data-test="include-pinned-checkbox"]')
        ->check('[data-test="include-trends-checkbox"]')
        ->check('[data-test="include-context-checkbox"]')
        ->type('questions', 'What changed between these tests?')
        ->click('[data-test="build-consult-overview-button"]')
        ->waitForText('Self-entered personal tracking data')
        ->assertSee('Self-entered personal tracking data')
        ->assertSee('not medical advice')
        ->assertSee('Ferritin')
        ->assertSee('Follow around consults')
        ->assertSee('Slept poorly before the June test.')
        ->assertSee('What changed between these tests?');

    assertNoForbiddenMedicalCopyAppears($browser);
}

function downloadDataExport(Browser $browser, string $password): void
{
    resetDuskDownloads();

    $browser->visit(route('data.edit', [], false))
        ->waitForText('Confirm password')
        ->type('password', $password)
        ->click('[data-test="confirm-password-button"]')
        ->waitForLocation('/settings/data')
        ->waitForText('Data and privacy')
        ->assertSee('Download my data')
        ->click('[data-test="download-data-button"]');

    $browser->waitUsing(10, 100, function (): bool {
        return duskDownloadedExportPath() !== null;
    }, 'The data export JSON file was not downloaded.');

    $downloadedExportPath = duskDownloadedExportPath();

    expect($downloadedExportPath)->not->toBeNull();

    $payload = json_decode(File::get($downloadedExportPath), true, flags: JSON_THROW_ON_ERROR);

    expect($payload['blood_tests'])->toHaveCount(2);
    expect($payload['biomarker_results'])->toHaveCount(2);
    expect($payload['documents'])->toHaveCount(2);
    expect($payload['pinned_biomarkers'])->toHaveCount(1);
    expect($payload['context_notes'])->toHaveCount(1);
    expect($payload['reminders'])->toHaveCount(1);

    assertNoForbiddenMedicalCopyAppears($browser);
}

function deleteAllHealthData(Browser $browser): void
{
    $browser->visit(route('data.edit', [], false))
        ->waitForText('Data and privacy')
        ->type('[data-test="delete-all-confirmation-input"]', 'DELETE ALL')
        ->click('[data-test="delete-all-health-data-button"]')
        ->waitForText('Your personal tracking records were deleted.')
        ->visit('/dashboard')
        ->waitForText('No blood tests yet.')
        ->assertSee('No pinned biomarkers yet.')
        ->assertSee('No reminders yet.')
        ->assertSee('No confirmed low, high, or unknown values yet.');

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

function duskDownloadDirectory(): string
{
    return storage_path('framework/testing/dusk-downloads');
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
