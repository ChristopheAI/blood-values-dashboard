<?php

namespace Tests\Feature;

use App\Models\BiomarkerCategory;
use App\Models\User;
use Database\Seeders\DefaultBiomarkerCategoriesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DefaultBiomarkerCategoriesSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_is_idempotent_for_a_user(): void
    {
        $user = User::factory()->create();
        $seeder = new DefaultBiomarkerCategoriesSeeder;

        $seeder->seedForUser($user);
        $seeder->seedForUser($user);

        $this->assertSame(
            count(DefaultBiomarkerCategoriesSeeder::CATEGORY_NAMES),
            BiomarkerCategory::query()->where('user_id', $user->id)->count(),
        );
    }
}
