<?php

namespace Database\Factories;

use App\Models\Biomarker;
use App\Models\PinnedBiomarker;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PinnedBiomarker>
 */
class PinnedBiomarkerFactory extends Factory
{
    protected $model = PinnedBiomarker::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'biomarker_id' => Biomarker::factory(),
            'note' => null,
        ];
    }
}
