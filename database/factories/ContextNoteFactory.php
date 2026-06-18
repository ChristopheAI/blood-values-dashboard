<?php

namespace Database\Factories;

use App\Enums\ContextNoteCategory;
use App\Models\ContextNote;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContextNote>
 */
class ContextNoteFactory extends Factory
{
    protected $model = ContextNote::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'blood_test_id' => null,
            'note_date' => fake()->dateTimeBetween('-1 year')->format('Y-m-d'),
            'category' => fake()->randomElement(ContextNoteCategory::cases())->value,
            'body' => fake()->sentence(),
        ];
    }
}
