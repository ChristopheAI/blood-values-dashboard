<?php

namespace Database\Factories;

use App\Models\BiomarkerCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BiomarkerCategory>
 */
class BiomarkerCategoryFactory extends Factory
{
    protected $model = BiomarkerCategory::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->unique()->randomElement(['Blood count', 'Inflammation', 'Liver', 'Kidney', 'Other']),
        ];
    }
}
