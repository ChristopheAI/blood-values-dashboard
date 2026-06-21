<?php

namespace Tests\Feature;

use App\Domain\Dashboard\BuildLatestUploadSummary;
use App\Models\Biomarker;
use App\Models\BiomarkerResult;
use App\Models\BloodTest;
use App\Models\Reminder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_the_dashboard(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));
        $response->assertOk();
    }

    public function test_empty_dashboard_is_upload_first_without_metadata_or_account_fields(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Sleep je lab-PDF hierheen')
            ->assertSee('PDF only')
            ->assertSee('data-test="lab-pdf-dropzone"', false)
            ->assertSee('data-test="lab-pdf-input"', false)
            ->assertSee('data-test="choose-pdf-button"', false)
            ->assertSee('data-test="selected-file-name"', false)
            ->assertSee('data-test="intake-progress"', false)
            ->assertDontSee('Personal overview')
            ->assertDontSee('Je bloedresultaten')
            ->assertDontSee('data-test="blood-test-date-input"', false)
            ->assertDontSee('data-test="blood-test-lab-input"', false)
            ->assertDontSee('data-test="blood-test-title-input"', false)
            ->assertDontSee('name="email"', false)
            ->assertDontSee('name="account"', false);
    }

    public function test_dashboard_shows_only_owned_confirmed_values_needing_attention(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $bloodTest = BloodTest::factory()->for($user)->create(['test_date' => '2026-06-01']);
        $otherBloodTest = BloodTest::factory()->for($otherUser)->create(['test_date' => '2026-06-01']);
        $low = Biomarker::factory()->for($user)->create(['name' => 'Low marker']);
        $normal = Biomarker::factory()->for($user)->create(['name' => 'Normal marker']);
        $draft = Biomarker::factory()->for($user)->create(['name' => 'Draft marker']);
        $other = Biomarker::factory()->for($otherUser)->create(['name' => 'Other marker']);

        BiomarkerResult::factory()->for($bloodTest)->for($low)->create([
            'status' => 'low',
            'confirmed_at' => now(),
        ]);
        BiomarkerResult::factory()->for($bloodTest)->for($normal)->create([
            'status' => 'normal',
            'confirmed_at' => now(),
        ]);
        BiomarkerResult::factory()->for($bloodTest)->for($draft)->create([
            'status' => 'high',
            'confirmed_at' => null,
        ]);
        BiomarkerResult::factory()->for($otherBloodTest)->for($other)->create([
            'status' => 'high',
            'confirmed_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('data-test="blood-results-overview"', false)
            ->assertSee('Deze waarde vraagt aandacht')
            ->assertSee('data-test="attention-values-section"', false)
            ->assertSee('data-test="normal-values-section"', false)
            ->assertSee('Low marker')
            ->assertSee('Normal marker')
            ->assertDontSee('Draft marker')
            ->assertDontSee('Other marker')
            ->assertDontSee('data-test="dashboard-attention-results"', false);

        $attentionSection = str($response->getContent())
            ->after('data-test="attention-values-section"')
            ->before('data-test="normal-values-section"')
            ->toString();

        $this->assertStringContainsString('Low marker', $attentionSection);
        $this->assertStringNotContainsString('Normal marker', $attentionSection);
    }

    public function test_upload_summary_compares_against_previous_blood_test_only(): void
    {
        $user = User::factory()->create();
        $ferritin = Biomarker::factory()->for($user)->create(['name' => 'Ferritine']);
        $previous = BloodTest::factory()->for($user)->create([
            'title' => 'Previous blood test',
            'test_date' => '2026-03-01',
        ]);
        $selected = BloodTest::factory()->for($user)->create([
            'title' => 'Selected older blood test',
            'test_date' => '2026-04-08',
        ]);
        $future = BloodTest::factory()->for($user)->create([
            'title' => 'Future blood test',
            'test_date' => '2026-06-01',
        ]);

        BiomarkerResult::factory()->for($previous)->for($ferritin)->create([
            'value' => 35,
            'unit' => 'ug/L',
            'confirmed_at' => now()->subMonths(2),
        ]);
        BiomarkerResult::factory()->for($selected)->for($ferritin)->create([
            'value' => 42,
            'unit' => 'ug/L',
            'status' => 'normal',
            'confirmed_at' => now()->subMonth(),
        ]);
        BiomarkerResult::factory()->for($future)->for($ferritin)->create([
            'value' => 12,
            'unit' => 'ug/L',
            'status' => 'normal',
            'confirmed_at' => now(),
        ]);

        $summary = app(BuildLatestUploadSummary::class)->forBloodTest($user, $selected);

        $this->assertNotNull($summary);

        $ferritinRow = $summary['rows']->firstWhere('name', 'Ferritine');

        $this->assertSame('+7 ug/L', $ferritinRow['trendLabel']);
    }

    public function test_dashboard_shows_a_confirmed_latest_upload_digest(): void
    {
        $user = User::factory()->create();
        $ferritin = Biomarker::factory()->for($user)->create(['name' => 'Ferritine']);
        $crp = Biomarker::factory()->for($user)->create(['name' => 'CRP']);
        $hemoglobin = Biomarker::factory()->for($user)->create(['name' => 'Hemoglobine']);
        $previous = BloodTest::factory()->for($user)->create([
            'title' => 'Previous blood test',
            'test_date' => '2026-03-01',
            'created_at' => now()->subMonth(),
        ]);
        $latest = BloodTest::factory()->for($user)->create([
            'title' => 'Bloedafname Christophe VH',
            'test_date' => '2026-04-08',
            'status' => 'confirmed',
            'created_at' => now(),
        ]);

        BiomarkerResult::factory()->for($previous)->for($ferritin)->create([
            'value' => 35,
            'unit' => 'ug/L',
            'confirmed_at' => now()->subMonth(),
        ]);
        BiomarkerResult::factory()->for($latest)->for($ferritin)->create([
            'value' => 42,
            'unit' => 'ug/L',
            'reference_min' => '30',
            'reference_max' => '150',
            'reference_unit' => 'ug/L',
            'status' => 'normal',
            'entry_source' => 'extracted',
            'confirmed_at' => now(),
        ]);
        BiomarkerResult::factory()->for($latest)->for($crp)->create([
            'value' => 4.2,
            'unit' => 'mg/L',
            'reference_min' => null,
            'reference_max' => null,
            'reference_unit' => 'mg/L',
            'status' => 'unknown',
            'entry_source' => 'extracted',
            'confirmed_at' => now(),
        ]);
        BiomarkerResult::factory()->for($latest)->for($hemoglobin)->create([
            'value' => 18.1,
            'unit' => 'g/dL',
            'reference_min' => '13',
            'reference_max' => '17',
            'reference_unit' => 'g/dL',
            'status' => 'high',
            'entry_source' => 'extracted',
            'confirmed_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('data-test="blood-results-overview"', false)
            ->assertSee('Je bloedresultaten')
            ->assertSee('Afname 8 april 2026')
            ->assertSee('Bloedafname Christophe VH')
            ->assertSee('1/3 waarde is normaal')
            ->assertSee('2 waarden vragen aandacht')
            ->assertSee('Deze waarden vragen aandacht')
            ->assertSee('Deze waarde is in orde')
            ->assertSee('data-test="featured-attention-card"', false)
            ->assertSee('data-test="attention-featured-row"', false)
            ->assertSee('data-test="normal-values-panel"', false)
            ->assertSee('data-test="compact-normal-row"', false)
            ->assertSee('data-test="compact-review-row"', false)
            ->assertSee('Ferritine')
            ->assertSee('+7 ug/L')
            ->assertSee('CRP')
            ->assertSee('Controle nodig')
            ->assertSee('Hemoglobine')
            ->assertSee('Aandacht')
            ->assertSee('Zit binnen de opgegeven referentie.')
            ->assertSee('Ligt boven de opgegeven referentie.')
            ->assertSee('data-test="biomarker-range-bar"', false)
            ->assertSee('Maak consultlijst')
            ->assertDontSee('Latest upload')
            ->assertDontSee('What changed')
            ->assertDontSee('Range context')
            ->assertDontSee('data-test="biomarker-status-card"', false)
            ->assertDontSee('data-test="dashboard-attention-results"', false)
            ->assertDontSee('slechte cholesterol');

        $response = $this->actingAs($user)->get(route('dashboard'));
        $normalSection = str($response->getContent())
            ->after('data-test="normal-values-section"')
            ->before('data-test="dashboard-supporting-links"')
            ->toString();

        $this->assertStringContainsString('data-test="compact-normal-row"', $normalSection);
        $this->assertStringNotContainsString('data-test="biomarker-status-card"', $normalSection);
    }

    public function test_dashboard_latest_upload_digest_uses_confirmed_owned_results_only(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $owned = Biomarker::factory()->for($user)->create(['name' => 'Owned confirmed marker']);
        $draft = Biomarker::factory()->for($user)->create(['name' => 'Draft marker']);
        $foreign = Biomarker::factory()->for($otherUser)->create(['name' => 'Foreign private marker']);
        $latest = BloodTest::factory()->for($user)->create([
            'title' => 'Latest confirmed-only digest',
            'status' => 'confirmed',
        ]);

        BiomarkerResult::factory()->for($latest)->for($owned)->create([
            'value' => 12,
            'unit' => 'mg/L',
            'status' => 'normal',
            'confirmed_at' => now(),
        ]);
        BiomarkerResult::factory()->for($latest)->for($draft)->create([
            'value' => 999,
            'unit' => 'mg/L',
            'status' => 'high',
            'confirmed_at' => null,
        ]);
        BiomarkerResult::factory()->for($latest)->for($foreign)->create([
            'value' => 888,
            'unit' => 'mg/L',
            'status' => 'high',
            'confirmed_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Latest confirmed-only digest')
            ->assertSee('data-test="blood-results-overview"', false)
            ->assertSee('1/1 waarde is normaal')
            ->assertSee('Owned confirmed marker')
            ->assertDontSee('Draft marker')
            ->assertDontSee('999')
            ->assertDontSee('Foreign private marker')
            ->assertDontSee('888');
    }

    public function test_dashboard_ignores_confirmed_results_linked_to_another_users_biomarker(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $bloodTest = BloodTest::factory()->for($user)->create(['test_date' => '2026-06-01']);
        $foreignMarker = Biomarker::factory()->for($otherUser)->create(['name' => 'Foreign private marker']);

        BiomarkerResult::factory()->for($bloodTest)->for($foreignMarker)->create([
            'status' => 'high',
            'confirmed_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Foreign private marker');
    }

    public function test_dashboard_shows_the_next_open_reminder_only(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        Reminder::factory()->for($user)->create([
            'due_date' => '2026-08-01',
            'title' => 'Later open reminder',
            'completed_at' => null,
        ]);
        Reminder::factory()->for($user)->create([
            'due_date' => '2026-07-01',
            'title' => 'Next open reminder',
            'note' => 'Plan the next blood test.',
            'completed_at' => null,
        ]);
        Reminder::factory()->for($user)->create([
            'due_date' => '2026-06-01',
            'title' => 'Completed reminder',
            'completed_at' => now(),
        ]);
        Reminder::factory()->for($otherUser)->create([
            'due_date' => '2026-05-01',
            'title' => 'Other user reminder',
            'completed_at' => null,
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Next reminder')
            ->assertSee('Next open reminder')
            ->assertSee('2026-07-01')
            ->assertSee('Plan the next blood test.')
            ->assertDontSee('Later open reminder')
            ->assertDontSee('Completed reminder')
            ->assertDontSee('Other user reminder');
    }
}
