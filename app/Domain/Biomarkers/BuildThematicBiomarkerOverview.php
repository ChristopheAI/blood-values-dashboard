<?php

namespace App\Domain\Biomarkers;

use App\Domain\BloodTests\BuildLongitudinalChanges;
use App\Domain\BloodTests\LongitudinalChange;
use App\Models\BiomarkerResult;
use App\Models\BloodTest;
use App\Models\User;
use App\Support\Format;
use Database\Seeders\DefaultBiomarkerCategoriesSeeder;
use Illuminate\Support\Str;

final class BuildThematicBiomarkerOverview
{
    private const UNCATEGORIZED_NAME = 'Overig';

    public function __construct(
        private readonly BuildLongitudinalChanges $buildLongitudinalChanges,
        private readonly BiomarkerReferenceDescriptions $descriptions,
    ) {}

    /**
     * @param  list<int>  $bloodTestIds
     * @return array{
     *     categories: list<array{
     *         key: string,
     *         name: string,
     *         themeDescription: string|null,
     *         markers: list<array{
     *             biomarkerId: int,
     *             name: string,
     *             description: string|null,
     *             valueLabel: string,
     *             unit: string|null,
     *             status: string,
     *             statusLabel: string,
     *             trendLabel: string|null,
     *             testDate: string|null,
     *             bloodTestTitle: string|null,
     *         }>
     *     }>,
     *     uncategorizedCount: int,
     * }
     */
    public function forBloodTests(User $user, array $bloodTestIds): array
    {
        $bloodTestIds = array_values(array_unique(array_map('intval', $bloodTestIds)));

        if ($bloodTestIds === []) {
            return [
                'categories' => [],
                'uncategorizedCount' => 0,
            ];
        }

        $ownedBloodTestIds = BloodTest::query()
            ->where('user_id', $user->id)
            ->whereIn('id', $bloodTestIds)
            ->pluck('id')
            ->map(fn (int|string $id): int => (int) $id)
            ->all();

        if ($ownedBloodTestIds === []) {
            return [
                'categories' => [],
                'uncategorizedCount' => 0,
            ];
        }

        $bloodTests = BloodTest::query()
            ->where('user_id', $user->id)
            ->whereIn('id', $ownedBloodTestIds)
            ->orderBy('test_date')
            ->orderBy('id')
            ->get();

        $changesByResultId = $this->buildLongitudinalChanges
            ->across($user, $bloodTests)
            ->mapWithKeys(function (LongitudinalChange $change): array {
                if (! $change->result instanceof BiomarkerResult) {
                    return [];
                }

                return [(int) $change->result->id => $change];
            });

        $results = BiomarkerResult::query()
            ->confirmedForUser($user->id)
            ->whereIn('blood_test_id', $ownedBloodTestIds)
            ->with(['biomarker.category', 'bloodTest'])
            ->join('blood_tests', 'biomarker_results.blood_test_id', '=', 'blood_tests.id')
            ->orderBy('blood_tests.test_date')
            ->orderBy('blood_tests.id')
            ->orderBy('biomarker_results.id')
            ->select('biomarker_results.*')
            ->get();

        /** @var array<string, list<array<string, mixed>>> $grouped */
        $grouped = [];

        foreach ($results as $result) {
            $categoryName = $result->biomarker->category?->name ?? self::UNCATEGORIZED_NAME;
            $grouped[$categoryName][] = $this->summarizeResult(
                $result,
                $changesByResultId->get((int) $result->id),
            );
        }

        if ($grouped === []) {
            return [
                'categories' => [],
                'uncategorizedCount' => 0,
            ];
        }

        $categories = [];
        $uncategorizedCount = count($grouped[self::UNCATEGORIZED_NAME] ?? []);

        foreach ($this->sortedCategoryNames(array_keys($grouped)) as $categoryName) {
            $markers = $grouped[$categoryName];
            usort($markers, fn (array $left, array $right): int => strcmp($left['name'], $right['name']));

            $categories[] = [
                'key' => $categoryName === self::UNCATEGORIZED_NAME
                    ? 'overig'
                    : Str::slug($categoryName),
                'name' => $categoryName,
                'themeDescription' => null,
                'markers' => $markers,
            ];
        }

        return [
            'categories' => $categories,
            'uncategorizedCount' => $uncategorizedCount,
        ];
    }

    public function forBloodTest(User $user, BloodTest $bloodTest): array
    {
        if ((int) $bloodTest->user_id !== $user->id) {
            return [
                'categories' => [],
                'uncategorizedCount' => 0,
            ];
        }

        return $this->forBloodTests($user, [(int) $bloodTest->id]);
    }

    /**
     * @return array<string, mixed>
     */
    private function summarizeResult(BiomarkerResult $result, ?LongitudinalChange $change): array
    {
        return [
            'biomarkerId' => (int) $result->biomarker_id,
            'name' => $result->biomarker->name,
            'description' => $this->descriptions->forName($result->biomarker->name),
            'valueLabel' => Format::biomarkerValue($result->value, $result->source_snippet),
            'unit' => $result->unit,
            'status' => $result->status,
            'statusLabel' => $this->statusLabel($result->status),
            'trendLabel' => $this->trendLabel($change),
            'testDate' => $result->bloodTest->test_date
                ? Format::dutchDate($result->bloodTest->test_date)
                : null,
            'bloodTestTitle' => $result->bloodTest->title ?: 'Bloedtest zonder titel',
        ];
    }

    private function trendLabel(?LongitudinalChange $change): ?string
    {
        if (! $change instanceof LongitudinalChange || ! $change->previousResult instanceof BiomarkerResult) {
            return 'Eerste meting';
        }

        if (! $change->comparable) {
            return match ($change->reason) {
                'missing_unit' => 'Eenheid ontbreekt',
                'unit_mismatch' => 'Eenheid gewijzigd',
                'non_numeric' => 'Niet numeriek vergelijkbaar',
                default => 'Niet vergelijkbaar',
            };
        }

        if ($change->direction === 'unchanged') {
            return 'Geen verandering';
        }

        return (string) $change->changeLabel;
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'normal' => 'In orde',
            'high', 'low' => 'Aandacht',
            default => 'Controle nodig',
        };
    }

    /**
     * @param  list<string>  $categoryNames
     * @return list<string>
     */
    private function sortedCategoryNames(array $categoryNames): array
    {
        $order = array_flip(DefaultBiomarkerCategoriesSeeder::NAMES);

        usort($categoryNames, function (string $left, string $right) use ($order): int {
            $leftOrder = $order[$left] ?? PHP_INT_MAX;
            $rightOrder = $order[$right] ?? PHP_INT_MAX;

            if ($leftOrder === $rightOrder) {
                return strcmp($left, $right);
            }

            return $leftOrder <=> $rightOrder;
        });

        return $categoryNames;
    }
}
