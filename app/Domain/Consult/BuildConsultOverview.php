<?php

namespace App\Domain\Consult;

use App\Models\BiomarkerResult;
use App\Models\BloodTest;
use App\Models\ContextNote;
use App\Models\PinnedBiomarker;
use App\Models\User;
use Illuminate\Support\Collection;

class BuildConsultOverview
{
    /**
     * @param  array{
     *     from?: string|null,
     *     to?: string|null,
     *     blood_test_ids?: list<int>,
     *     include_pinned?: bool,
     *     include_attention?: bool,
     *     include_trends?: bool,
     *     include_context?: bool,
     *     questions?: string|null
     * }  $filters
     * @return array{
     *     bloodTests: Collection<int, BloodTest>,
     *     pinnedBiomarkers: Collection<int, PinnedBiomarker>,
     *     attentionResults: Collection<int, BiomarkerResult>,
     *     trendResults: Collection<int, BiomarkerResult>,
     *     contextNotes: Collection<int, ContextNote>,
     *     questions: string|null
     * }
     */
    public function __invoke(User $user, array $filters): array
    {
        $bloodTests = $this->bloodTests($user, $filters);
        $bloodTestIds = [];

        foreach ($bloodTests as $bloodTest) {
            $bloodTestIds[] = $bloodTest->id;
        }

        return [
            'bloodTests' => $bloodTests,
            'pinnedBiomarkers' => ($filters['include_pinned'] ?? false)
                ? $this->pinnedBiomarkers($user)
                : collect(),
            'attentionResults' => ($filters['include_attention'] ?? false)
                ? $this->confirmedResults($user, $bloodTestIds, ['low', 'high', 'unknown'])
                : collect(),
            'trendResults' => ($filters['include_trends'] ?? false)
                ? $this->confirmedResults($user, $bloodTestIds)
                : collect(),
            'contextNotes' => ($filters['include_context'] ?? false)
                ? $this->contextNotes($user, $filters, $bloodTestIds)
                : collect(),
            'questions' => $filters['questions'] ?? null,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, BloodTest>
     */
    private function bloodTests(User $user, array $filters): Collection
    {
        $query = BloodTest::query()
            ->where('user_id', $user->id)
            ->orderBy('test_date');

        if (! empty($filters['blood_test_ids'])) {
            return $query->whereIn('id', $filters['blood_test_ids'])->get();
        }

        if (! empty($filters['from'])) {
            $query->whereDate('test_date', '>=', $filters['from']);
        }

        if (! empty($filters['to'])) {
            $query->whereDate('test_date', '<=', $filters['to']);
        }

        return $query->get();
    }

    /**
     * @return Collection<int, PinnedBiomarker>
     */
    private function pinnedBiomarkers(User $user): Collection
    {
        return PinnedBiomarker::query()
            ->forUserWithOwnedBiomarker($user->id)
            ->with('biomarker')
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * @param  list<int>  $bloodTestIds
     * @param  list<string>|null  $statuses
     * @return Collection<int, BiomarkerResult>
     */
    private function confirmedResults(User $user, array $bloodTestIds, ?array $statuses = null): Collection
    {
        if ($bloodTestIds === []) {
            return collect();
        }

        $query = BiomarkerResult::query()
            ->confirmedForUser($user->id)
            ->whereIn('biomarker_results.blood_test_id', $bloodTestIds)
            ->with(['biomarker', 'bloodTest'])
            ->join('blood_tests', 'biomarker_results.blood_test_id', '=', 'blood_tests.id')
            ->orderBy('blood_tests.test_date')
            ->orderBy('biomarker_results.id')
            ->select('biomarker_results.*');

        if ($statuses !== null) {
            $query->whereIn('biomarker_results.status', $statuses);
        }

        return $query->get();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @param  list<int>  $bloodTestIds
     * @return Collection<int, ContextNote>
     */
    private function contextNotes(User $user, array $filters, array $bloodTestIds): Collection
    {
        $query = ContextNote::query()
            ->where('user_id', $user->id)
            ->with('bloodTest')
            ->orderBy('note_date');

        if ($bloodTestIds !== []) {
            $query->where(function ($query) use ($bloodTestIds, $filters): void {
                $query->whereIn('blood_test_id', $bloodTestIds);

                if (! empty($filters['from']) || ! empty($filters['to'])) {
                    $query->orWhere(function ($query) use ($filters): void {
                        if (! empty($filters['from'])) {
                            $query->whereDate('note_date', '>=', $filters['from']);
                        }

                        if (! empty($filters['to'])) {
                            $query->whereDate('note_date', '<=', $filters['to']);
                        }
                    });
                }
            });
        } else {
            if (! empty($filters['from'])) {
                $query->whereDate('note_date', '>=', $filters['from']);
            }

            if (! empty($filters['to'])) {
                $query->whereDate('note_date', '<=', $filters['to']);
            }
        }

        return $query->get();
    }
}
