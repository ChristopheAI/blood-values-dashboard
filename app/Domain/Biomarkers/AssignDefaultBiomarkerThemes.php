<?php

namespace App\Domain\Biomarkers;

use App\Models\Biomarker;
use App\Models\User;
use Database\Seeders\DefaultBiomarkerCategoriesSeeder;

final class AssignDefaultBiomarkerThemes
{
    public function __construct(private readonly ResolveBiomarkerThemeCategory $resolveBiomarkerThemeCategory) {}

    /**
     * @return array{assigned: int, skipped: int, unresolved: int}
     */
    public function forUser(User $user, bool $overwrite = false): array
    {
        $categories = (new DefaultBiomarkerCategoriesSeeder)->seedForUser($user);
        $assigned = 0;
        $skipped = 0;
        $unresolved = 0;

        $biomarkers = Biomarker::query()
            ->where('user_id', $user->id)
            ->orderBy('id')
            ->get();

        foreach ($biomarkers as $biomarker) {
            if ($biomarker->biomarker_category_id !== null && ! $overwrite) {
                $skipped++;

                continue;
            }

            $themeName = $this->resolveBiomarkerThemeCategory->resolve($biomarker->name);

            if ($themeName === null) {
                $unresolved++;

                continue;
            }

            $category = $categories[$themeName] ?? null;

            if ($category === null) {
                $unresolved++;

                continue;
            }

            if ((int) $biomarker->biomarker_category_id === (int) $category->id) {
                $skipped++;

                continue;
            }

            $biomarker->update(['biomarker_category_id' => $category->id]);
            $assigned++;
        }

        return [
            'assigned' => $assigned,
            'skipped' => $skipped,
            'unresolved' => $unresolved,
        ];
    }
}
