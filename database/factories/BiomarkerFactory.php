<?php

namespace Database\Factories;

use App\Models\Biomarker;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Biomarker>
 */
class BiomarkerFactory extends Factory
{
    protected $model = Biomarker::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->randomElement(['Ferritin', 'Vitamin D', 'CRP', 'Hemoglobin', 'TSH']);

        return [
            'user_id' => User::factory(),
            'biomarker_category_id' => null,
            'name' => $name,
            'short_name' => null,
            'default_unit' => null,
            'reference_min' => null,
            'reference_max' => null,
            'reference_unit' => null,
            'range_note' => null,
            'active' => true,
        ];
    }
}
