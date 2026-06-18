<?php

namespace Database\Factories;

use App\Models\Biomarker;
use App\Models\BiomarkerResult;
use App\Models\BloodTest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BiomarkerResult>
 */
class BiomarkerResultFactory extends Factory
{
    protected $model = BiomarkerResult::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'blood_test_id' => BloodTest::factory(),
            'biomarker_id' => Biomarker::factory(),
            'value' => fake()->randomFloat(2, 1, 100),
            'unit' => 'mg/L',
            'reference_min' => null,
            'reference_max' => null,
            'reference_unit' => null,
            'status' => 'unknown',
            'entry_source' => 'pdf_reviewed',
            'confirmed_at' => now(),
            'note' => null,
        ];
    }
}
