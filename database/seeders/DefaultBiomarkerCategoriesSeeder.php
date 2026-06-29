<?php

namespace Database\Seeders;

use App\Models\BiomarkerCategory;
use App\Models\User;
use Illuminate\Database\Seeder;

class DefaultBiomarkerCategoriesSeeder extends Seeder
{
    /**
     * Vitasure-inspired lifestyle themes plus an uncategorized bucket.
     *
     * @var list<string>
     */
    public const NAMES = [
        'Hartgezondheid',
        'Ontstekingen',
        'Cognitie',
        'Uithoudingsvermogen',
        'Slaap',
        'Hormoonbalans',
        'Metabolisme',
        'Herstel',
        'Fitness',
        'Overig',
    ];

    public function run(?User $user = null): void
    {
        if (! $user instanceof User) {
            return;
        }

        $this->seedForUser($user);
    }

    /**
     * @return array<string, BiomarkerCategory>
     */
    public function seedForUser(User $user): array
    {
        $categories = [];

        foreach (self::NAMES as $name) {
            $categories[$name] = BiomarkerCategory::query()->updateOrCreate(
                ['user_id' => $user->id, 'name' => $name],
                [],
            );
        }

        return $categories;
    }
}
