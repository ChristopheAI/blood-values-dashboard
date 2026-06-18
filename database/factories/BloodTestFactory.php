<?php

namespace Database\Factories;

use App\Models\BloodTest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BloodTest>
 */
class BloodTestFactory extends Factory
{
    protected $model = BloodTest::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'test_date' => fake()->dateTimeBetween('-2 years')->format('Y-m-d'),
            'lab_name' => 'Unknown lab',
            'title' => 'Blood test',
            'notes' => null,
            'status' => 'uploaded',
        ];
    }
}
