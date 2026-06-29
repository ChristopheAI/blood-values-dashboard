<?php

namespace App\Domain\Biomarkers;

use Database\Seeders\DefaultBiomarkerCategoriesSeeder;
use Illuminate\Support\Str;

final class ResolveBiomarkerThemeCategory
{
    /** @var list<array{category: string, alias: string}>|null */
    private ?array $rules = null;

    public function resolve(string $biomarkerName): ?string
    {
        $normalized = $this->normalize($biomarkerName);

        if ($normalized === '') {
            return null;
        }

        foreach ($this->rules() as $rule) {
            if ($this->matches($normalized, $rule['alias'])) {
                return $rule['category'];
            }
        }

        return null;
    }

    /**
     * @return list<array{category: string, alias: string}>
     */
    private function rules(): array
    {
        if ($this->rules !== null) {
            return $this->rules;
        }

        /** @var array<string, list<string>> $mapping */
        $mapping = config('biomarker_theme_mapping', []);
        $rules = [];

        foreach ($mapping as $category => $aliases) {
            if (! in_array($category, DefaultBiomarkerCategoriesSeeder::NAMES, true)) {
                continue;
            }

            if ($category === 'Overig') {
                continue;
            }

            foreach ($aliases as $alias) {
                $normalizedAlias = $this->normalize($alias);

                if ($normalizedAlias === '') {
                    continue;
                }

                $rules[] = [
                    'category' => $category,
                    'alias' => $normalizedAlias,
                ];
            }
        }

        usort($rules, fn (array $left, array $right): int => strlen($right['alias']) <=> strlen($left['alias']));

        return $this->rules = $rules;
    }

    private function matches(string $normalizedName, string $normalizedAlias): bool
    {
        if ($normalizedName === $normalizedAlias) {
            return true;
        }

        if (strlen($normalizedAlias) >= 4 && str_contains($normalizedName, $normalizedAlias)) {
            return true;
        }

        $pattern = '/(?:^|[^a-z0-9])'.preg_quote($normalizedAlias, '/').'(?:[^a-z0-9]|$)/';

        return preg_match($pattern, $normalizedName) === 1;
    }

    private function normalize(string $value): string
    {
        $value = Str::lower(trim($value));
        $value = str_replace(['*', '(', ')', '[', ']', '.', ',', ';', ':'], ' ', $value);
        $value = preg_replace('/\s+/', ' ', $value) ?? $value;

        return trim($value);
    }
}
