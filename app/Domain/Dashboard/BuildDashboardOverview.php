<?php

namespace App\Domain\Dashboard;

use App\Models\BloodTest;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class BuildDashboardOverview
{
    /**
     * @return array{
     *     nextStep: array{kind: string, title: string, body: string, href: string, action: string},
     *     bloodTests: Collection<int, array{id: int, title: non-falsy-string, href: string, date: string, status: string, confirmedCount: int, draftCount: int, documentCount: int}>,
     *     reviewDraftCount: int,
     *     confirmedValueCount: int,
     *     bloodTestCount: int
     * }
     */
    public function __invoke(User $user): array
    {
        $bloodTests = BloodTest::query()
            ->where('user_id', $user->id)
            ->withCount([
                'documents',
                'results as confirmed_results_count' => fn ($query) => $query
                    ->whereNotNull('confirmed_at')
                    ->whereHas('biomarker', fn ($query) => $query->where('user_id', $user->id)),
                'results as draft_results_count' => fn ($query) => $query
                    ->whereNull('confirmed_at')
                    ->where('entry_source', 'extracted')
                    ->where(function ($query) use ($user): void {
                        $query
                            ->whereNull('biomarker_id')
                            ->orWhereHas('biomarker', fn ($query) => $query->where('user_id', $user->id));
                    }),
            ])
            ->recentFirst()
            ->limit(5)
            ->get();

        $timeline = $bloodTests->map(fn (BloodTest $bloodTest): array => [
            'id' => $bloodTest->id,
            'title' => $bloodTest->title ?: 'Bloedtest zonder titel',
            'href' => route('blood-tests.show', $bloodTest),
            'date' => $bloodTest->test_date
                ? $this->formatDutchDate($bloodTest->test_date)
                : 'Geen datum',
            'status' => $bloodTest->status,
            'confirmedCount' => (int) $bloodTest->getAttribute('confirmed_results_count'),
            'draftCount' => (int) $bloodTest->getAttribute('draft_results_count'),
            'documentCount' => (int) $bloodTest->getAttribute('documents_count'),
        ]);

        $reviewDraftCount = (int) $timeline->sum('draftCount');
        $confirmedValueCount = (int) $timeline->sum('confirmedCount');

        return [
            'nextStep' => $this->nextStep($timeline, $reviewDraftCount, $confirmedValueCount),
            'bloodTests' => $timeline,
            'reviewDraftCount' => $reviewDraftCount,
            'confirmedValueCount' => $confirmedValueCount,
            'bloodTestCount' => $timeline->count(),
        ];
    }

    /**
     * @param  Collection<int, array{id: int, title: non-falsy-string, href: string, date: string, status: string, confirmedCount: int, draftCount: int, documentCount: int}>  $bloodTests
     * @return array{kind: string, title: string, body: string, href: string, action: string}
     */
    private function nextStep(Collection $bloodTests, int $reviewDraftCount, int $confirmedValueCount): array
    {
        if ($bloodTests->isEmpty()) {
            return [
                'kind' => 'upload',
                'title' => 'Eerste lab-PDF toevoegen',
                'body' => 'Start met je originele PDF. Waarden komen pas in overzichten na bevestiging.',
                'href' => route('blood-tests.index'),
                'action' => 'Lab-PDF uploaden',
            ];
        }

        if ($reviewDraftCount > 0) {
            $firstReviewBloodTest = $bloodTests->first(fn (array $bloodTest): bool => $bloodTest['draftCount'] > 0);

            return [
                'kind' => 'review',
                'title' => 'Waarden nakijken',
                'body' => $reviewDraftCount === 1
                    ? '1 waarde wacht op review voordat ze in overzichten komt.'
                    : $reviewDraftCount.' waarden wachten op review voordat ze in overzichten komen.',
                'href' => $firstReviewBloodTest['href'],
                'action' => 'Review openen',
            ];
        }

        if ($confirmedValueCount > 0) {
            return [
                'kind' => 'consult',
                'title' => 'Consultlijst voorbereiden',
                'body' => 'Gebruik alleen bevestigde waarden, bronbestanden en context voor een compact overzicht.',
                'href' => route('consult-overview.index'),
                'action' => 'Consultlijst openen',
            ];
        }

        return [
            'kind' => 'add-values',
            'title' => 'Waarden toevoegen',
            'body' => 'Er zijn bloedtesten, maar nog geen bevestigde waarden voor dashboard, trends of consult.',
            'href' => $bloodTests->first()['href'],
            'action' => 'Bloedtest openen',
        ];
    }

    private function formatDutchDate(CarbonInterface $date): string
    {
        $months = [
            1 => 'januari',
            2 => 'februari',
            3 => 'maart',
            4 => 'april',
            5 => 'mei',
            6 => 'juni',
            7 => 'juli',
            8 => 'augustus',
            9 => 'september',
            10 => 'oktober',
            11 => 'november',
            12 => 'december',
        ];

        return $date->day.' '.$months[$date->month].' '.$date->year;
    }
}
