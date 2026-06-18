<?php

namespace Database\Factories;

use App\Models\BloodTest;
use App\Models\ExtractionRun;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExtractionRun>
 */
class ExtractionRunFactory extends Factory
{
    protected $model = ExtractionRun::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'blood_test_id' => BloodTest::factory(),
            'engine' => 'smalot/pdfparser',
            'status' => 'done',
            'candidate_count' => 0,
        ];
    }
}
