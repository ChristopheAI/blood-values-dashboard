<?php

namespace Tests\Feature;

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

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Values needing attention')
            ->assertSee('Low marker')
            ->assertDontSee('Normal marker')
            ->assertDontSee('Draft marker')
            ->assertDontSee('Other marker');
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
