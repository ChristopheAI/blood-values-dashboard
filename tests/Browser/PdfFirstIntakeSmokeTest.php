<?php

use App\Models\Biomarker;
use App\Models\BloodTest;
use App\Models\User;
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
        confirmBiomarkerValue($browser, 'Ferritin', '42', 'ug/L', '30', '150', 'ug/L');

        uploadBloodTestPdf($browser, 'tests/Fixtures/lab-result-two.pdf', '2026-06-01', 'Labo Twee', 'Juni 2026');
        confirmBiomarkerValue($browser, 'Ferritin', '48', 'ug/L', '30', '150', 'ug/L');

        $user = User::query()->where('email', $email)->firstOrFail();
        $biomarker = Biomarker::query()
            ->where('user_id', $user->id)
            ->where('name', 'Ferritin')
            ->firstOrFail();

        $bloodTests = BloodTest::query()
            ->where('user_id', $user->id)
            ->orderBy('test_date')
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
        buildConsultOverview($browser);
    });
});

function uploadBloodTestPdf(Browser $browser, string $fixturePath, string $date, string $lab, string $title): void
{
    $browser->visit('/blood-tests')
        ->waitForText('Blood tests')
        ->attach('document', base_path($fixturePath))
        ->value('input[name="test_date"]', $date)
        ->type('lab_name', $lab)
        ->type('title', $title)
        ->scrollIntoView('[data-test="upload-pdf-button"]')
        ->click('[data-test="upload-pdf-button"]')
        ->waitForText('Confirm a biomarker value')
        ->assertSee($title);

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

function buildConsultOverview(Browser $browser): void
{
    $browser->visit(route('consult-overview.index', [
        'from' => '2026-05-01',
        'to' => '2026-06-01',
        'include_pinned' => '1',
        'include_trends' => '1',
        'include_context' => '1',
        'questions' => 'What changed between these tests?',
    ], false))
        ->waitForText('Consult overview')
        ->assertSee('Self-entered personal tracking data')
        ->assertSee('not medical advice')
        ->assertSee('Ferritin')
        ->assertSee('Follow around consults')
        ->assertSee('Slept poorly before the June test.')
        ->assertSee('What changed between these tests?');

    assertNoForbiddenMedicalCopyAppears($browser);
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
