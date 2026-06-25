<?php

namespace App\Domain\Dashboard;

use App\Models\BiomarkerResult;
use App\Models\BloodTest;
use App\Models\BloodTestDocument;
use App\Models\User;
use App\Support\Format;
use Illuminate\Support\Collection;

class BuildDashboardOverview
{
    public function __construct(private readonly BuildDashboardReadiness $buildDashboardReadiness) {}

    /**
     * @return array{
     *     nextStep: array{kind: string, title: string, body: string, href: string, action: string},
     *     readiness: array{headline: string, variant: string, items: list<array{state: string, label: string}>, consultBloodTestId: int|null, showConsultPost: bool, selectionPills: list<array{key: string, label: string, count: int, tone: string}>},
     *     workstand: Collection<int, array{key: string, label: string, count: int, summary: string, help: string, emphasis: bool, icon: string, tone: string}>,
     *     bloodTests: Collection<int, array{id: int, title: non-falsy-string, href: string, date: string, status: string, confirmedCount: int, draftCount: int, documentCount: int}>,
     *     reviewDraftCount: int,
     *     confirmedValueCount: int,
     *     bloodTestCount: int,
     *     sourceDocumentCount: int
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
                ? Format::dutchDate($bloodTest->test_date)
                : 'Geen datum',
            'status' => $bloodTest->status,
            'confirmedCount' => (int) $bloodTest->getAttribute('confirmed_results_count'),
            'draftCount' => (int) $bloodTest->getAttribute('draft_results_count'),
            'documentCount' => (int) $bloodTest->getAttribute('documents_count'),
        ]);

        $reviewDraftCount = $this->reviewDraftCount($user);
        $confirmedValueCount = BiomarkerResult::confirmedForUser($user->id)->count();
        $bloodTestCount = BloodTest::query()
            ->where('user_id', $user->id)
            ->count();
        $sourceDocumentCount = BloodTestDocument::query()
            ->whereHas('bloodTest', fn ($query) => $query->where('user_id', $user->id))
            ->count();
        $firstReviewBloodTest = $this->firstReviewBloodTest($user);

        return [
            'nextStep' => $this->nextStep($timeline, $reviewDraftCount, $confirmedValueCount, $firstReviewBloodTest),
            'readiness' => ($this->buildDashboardReadiness)(
                $user,
                $timeline,
                $reviewDraftCount,
                $confirmedValueCount,
            ),
            'workstand' => collect([
                [
                    'key' => 'blood-tests',
                    'label' => 'Bloedtesten',
                    'count' => $bloodTestCount,
                    'summary' => $this->countLabel($bloodTestCount, 'bloedtest', 'bloedtesten'),
                    'help' => 'Eigen uploads in deze werkruimte.',
                    'emphasis' => false,
                    'icon' => 'droplet',
                    'tone' => 'rose',
                ],
                [
                    'key' => 'confirmed',
                    'label' => 'Bevestigd',
                    'count' => $confirmedValueCount,
                    'summary' => $this->countLabel($confirmedValueCount, 'bevestigde waarde', 'bevestigde waarden'),
                    'help' => 'Alleen waarden voorbij de confirmatiepoort.',
                    'emphasis' => false,
                    'icon' => 'check-badge',
                    'tone' => 'amber',
                ],
                [
                    'key' => 'review',
                    'label' => 'In review',
                    'count' => $reviewDraftCount,
                    'summary' => $this->countLabel($reviewDraftCount, 'reviewpunt', 'reviewpunten'),
                    'help' => 'Extracties die nog niet downstream mogen.',
                    'emphasis' => $reviewDraftCount > 0,
                    'icon' => 'clipboard',
                    'tone' => 'sky',
                ],
                [
                    'key' => 'sources',
                    'label' => 'Bron-PDF',
                    'count' => $sourceDocumentCount,
                    'summary' => $this->countLabel($sourceDocumentCount, 'bronbestand', 'bronbestanden'),
                    'help' => 'Lokale documenten bij eigen bloedtesten.',
                    'emphasis' => false,
                    'icon' => 'document',
                    'tone' => 'emerald',
                ],
            ]),
            'bloodTests' => $timeline,
            'reviewDraftCount' => $reviewDraftCount,
            'confirmedValueCount' => $confirmedValueCount,
            'bloodTestCount' => $bloodTestCount,
            'sourceDocumentCount' => $sourceDocumentCount,
        ];
    }

    /**
     * @param  Collection<int, array{id: int, title: non-falsy-string, href: string, date: string, status: string, confirmedCount: int, draftCount: int, documentCount: int}>  $bloodTests
     * @return array{kind: string, title: string, body: string, href: string, action: string}
     */
    private function nextStep(Collection $bloodTests, int $reviewDraftCount, int $confirmedValueCount, ?BloodTest $firstReviewBloodTest): array
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
            return [
                'kind' => 'review',
                'title' => 'Waarden nakijken',
                'body' => $reviewDraftCount === 1
                    ? '1 waarde wacht op review voordat ze in overzichten komt.'
                    : $reviewDraftCount.' waarden wachten op review voordat ze in overzichten komen.',
                'href' => $firstReviewBloodTest
                    ? route('blood-tests.show', $firstReviewBloodTest)
                    : $bloodTests->first()['href'],
                'action' => 'Review openen',
            ];
        }

        if ($confirmedValueCount > 0) {
            return [
                'kind' => 'consult',
                'title' => 'Consultlijst voorbereiden',
                'body' => 'Gebruik alleen bevestigde waarden, bronbestanden en context voor een compact overzicht.',
                'href' => route('consult-overview.index'),
                'action' => 'Consultlijst maken',
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

    private function reviewDraftCount(User $user): int
    {
        return BiomarkerResult::query()
            ->whereNull('confirmed_at')
            ->where('entry_source', 'extracted')
            ->whereHas('bloodTest', fn ($query) => $query->where('user_id', $user->id))
            ->where(function ($query) use ($user): void {
                $query
                    ->whereNull('biomarker_id')
                    ->orWhereHas('biomarker', fn ($query) => $query->where('user_id', $user->id));
            })
            ->count();
    }

    private function firstReviewBloodTest(User $user): ?BloodTest
    {
        return BloodTest::query()
            ->where('user_id', $user->id)
            ->whereHas('results', fn ($query) => $query
                ->whereNull('confirmed_at')
                ->where('entry_source', 'extracted')
                ->where(function ($query) use ($user): void {
                    $query
                        ->whereNull('biomarker_id')
                        ->orWhereHas('biomarker', fn ($query) => $query->where('user_id', $user->id));
                }))
            ->recentFirst()
            ->first();
    }

    private function countLabel(int $count, string $singular, string $plural): string
    {
        return $count.' '.($count === 1 ? $singular : $plural);
    }
}
