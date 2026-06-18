<?php

namespace Database\Factories;

use App\Models\Reminder;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Reminder>
 */
class ReminderFactory extends Factory
{
    protected $model = Reminder::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'due_date' => fake()->dateTimeBetween('now', '+3 months')->format('Y-m-d'),
            'title' => 'Plan next blood test',
            'note' => null,
            'completed_at' => null,
        ];
    }
}
