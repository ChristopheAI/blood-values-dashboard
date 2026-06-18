<?php

namespace Tests\Feature;

use App\Models\Biomarker;
use App\Models\BiomarkerResult;
use App\Models\BloodTest;
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
}
