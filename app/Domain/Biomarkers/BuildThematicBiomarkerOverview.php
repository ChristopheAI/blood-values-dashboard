<?php

namespace App\Domain\Biomarkers;

use App\Domain\BloodTests\BuildLongitudinalChanges;
use App\Domain\BloodTests\LongitudinalChange;
use App\Models\BiomarkerResult;
use App\Models\BloodTest;
use App\Models\User;
use App\Support\Format;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

final class BuildThematicBiomarkerOverview
{
    private const UNCATEGORIZED_KEY = 'uncategorized';

    private const UNCATEGORIZED_NAME = 'Overig';

    public function __construct(
        private readonly BuildLongitudinalChanges $buildLongitudinalChanges,
        private readonly BiomarkerReferenceDescriptions $descriptions,
        private readonly BiomarkerPresentation $biomarkerPresentation,
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

        $bloodTests = BloodTest::query()
            ->where('user_id', $user->id)
            ->whereIn('id', $bloodTestIds)
            ->orderBy('test_date')
            ->orderBy('id')
            ->get();

        if ($bloodTests->count() !== count($bloodTestIds)) {
            return [
                'categories' => [],
                'uncategorizedCount' => 0,
            ];
        }

        $scopedBloodTestIds = $bloodTests->pluck('id')->all();

        /** @var Collection<int, LongitudinalChange> $changesByResultId */
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
            ->whereIn('blood_test_id', $scopedBloodTestIds)
            ->with(['biomarker.category', 'bloodTest'])
            ->orderBy('id')
            ->get();

        $grouped = [];
        $uncategorizedCount = 0;

        foreach ($results as $result) {
            $category = $result->biomarker->category;
            $key = $category ? Str::slug($category->name) : self::UNCATEGORIZED_KEY;
            $name = $category?->name ?? self::UNCATEGORIZED_NAME;

            if (! $category) {
                $uncategorizedCount++;
            }

            if (! isset($grouped[$key])) {
                $grouped[$key] = [
                    'key' => $key,
                    'name' => $name,
                    'themeDescription' => null,
                    'markers' => [],
                ];
            }

            $grouped[$key]['markers'][] = $this->markerRow(
                $result,
                $changesByResultId->get((int) $result->id),
            );
        }

        $categories = collect($grouped)
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->map(function (array $category): array {
                $category['markers'] = collect($category['markers'])
                    ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
                    ->values()
                    ->all();

                return $category;
            })
            ->values()
            ->all();

        return [
            'categories' => $categories,
            'uncategorizedCount' => $uncategorizedCount,
        ];
    }

    /**
     * @return array{
     *     categories: list<array<string, mixed>>,
     *     uncategorizedCount: int,
     * }
     */
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
     * @return array{
     *     biomarkerId: int,
     *     name: string,
     *     description: string|null,
     *     valueLabel: string,
     *     unit: string|null,
     *     status: string,
     *     statusLabel: string,
     *     trendLabel: string|null,
     *     testDate: string|null,
     *     bloodTestTitle: string|null,
     * }
     */
    private function markerRow(BiomarkerResult $result, ?LongitudinalChange $change): array
    {
        return [
            'biomarkerId' => (int) $result->biomarker_id,
            'name' => $result->biomarker->name,
            'description' => $this->descriptions->forName($result->biomarker->name),
            'valueLabel' => Format::biomarkerValue($result->value, $result->source_snippet),
            'unit' => $result->unit,
            'status' => $result->status,
            'statusLabel' => $this->biomarkerPresentation->statusLabel($result->status),
            'trendLabel' => $this->biomarkerPresentation->compactTrendLabel($change),
            'testDate' => $result->bloodTest->test_date?->toDateString(),
            'bloodTestTitle' => $result->bloodTest->title,
        ];
    }
}
