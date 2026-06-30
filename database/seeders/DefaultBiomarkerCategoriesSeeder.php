<?php

namespace Database\Seeders;

use App\Models\BiomarkerCategory;
use App\Models\User;
use Illuminate\Database\Seeder;

class DefaultBiomarkerCategoriesSeeder extends Seeder
{
    /**
     * @var list<string>
     */
    public const CATEGORY_NAMES = [
        'Bloedbeeld',
        'Ontstekingen',
        'Lever',
        'Nieren',
        'Lipiden',
        'Glucose/stofwisseling',
        'Vitaminen/mineralen',
        'Schildklier',
        'Hormonen',
        'Overig',
    ];

    public function run(): void
    {
        User::query()->each(fn (User $user) => $this->seedForUser($user));
    }

    public function seedForUser(User $user): void
    {
        foreach (self::CATEGORY_NAMES as $name) {
            BiomarkerCategory::query()->firstOrCreate([
                'user_id' => $user->id,
                'name' => $name,
            ]);
        }
    }
}
