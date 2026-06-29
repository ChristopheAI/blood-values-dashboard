<?php

namespace Tests\Feature\Database;

use App\Models\User;
use Database\Seeders\DefaultBiomarkerCategoriesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DefaultBiomarkerCategoriesSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_is_idempotent_for_owner_categories(): void
    {
        $user = User::factory()->create();
        $seeder = new DefaultBiomarkerCategoriesSeeder;

        $first = $seeder->seedForUser($user);
        $second = $seeder->seedForUser($user);

        $this->assertCount(count(DefaultBiomarkerCategoriesSeeder::NAMES), $first);
        $this->assertSame(array_keys($first), array_keys($second));

        foreach (DefaultBiomarkerCategoriesSeeder::NAMES as $name) {
            $this->assertSame($first[$name]->id, $second[$name]->id);
        }
    }
}
