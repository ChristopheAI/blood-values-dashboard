<?php

namespace App\Domain\Dashboard;

use App\Domain\BloodTests\BuildLongitudinalChanges;
use App\Domain\BloodTests\LongitudinalChange;
use App\Models\BiomarkerResult;
use App\Models\BloodTest;
use App\Models\ContextNote;
use App\Models\User;
use App\Support\Format;
use Illuminate\Support\Collection;

class BuildDashboardReadiness
{
    public function __construct(
        private readonly BuildLatestUploadSummary $buildLatestUploadSummary,
        private readonly BuildLongitudinalChanges $buildLongitudinalChanges,
    ) {}

    /**
     * @param  Collection<int, array{id: int, title: non-falsy-string, href: string, date: string, status: string, confirmedCount: int, draftCount: int, documentCount: int}>  $bloodTests
     * @return array{
     *     headline: string,
     *     variant: string,
     *     items: list<array{state: string, label: string}>,
     *     consultBloodTestId: int|null,
     *     showConsultPost: bool,
     *     selectionPills: list<array{key: string, label: string, count: int, tone: string}>
     * }
     */
    public function __invoke(
        User $user,
        Collection $bloodTests,
        int $reviewDraftCount,
        int $confirmedValueCount,
    ): array {
        if ($bloodTests->isEmpty()) {
            return [
                'headline' => 'Start je dossier',
                'variant' => 'default',
                'items' => [
                    [
                        'state' => 'pending',
                        'label' => 'Upload je eerste lab-PDF om waarden te laten verwerken.',
                    ],
                ],
                'consultBloodTestId' => null,
                'showConsultPost' => false,
                'selectionPills' => [],
            ];
        }

        if ($reviewDraftCount > 0) {
            $reviewLabel = $reviewDraftCount === 1
                ? '1 waarde wacht op review en blijft buiten consult.'
                : $reviewDraftCount.' waarden wachten op review en blijven buiten consult.';

            return [
                'headline' => 'Eerst review afronden',
                'variant' => 'review',
                'items' => [
                    [
                        'state' => 'blocked',
                        'label' => $reviewLabel,
                    ],
                    [
                        'state' => 'pending',
                        'label' => $this->countLabel($confirmedValueCount, 'bevestigde waarde blijft beschikbaar', 'bevestigde waarden blijven beschikbaar'),
                    ],
                ],
                'consultBloodTestId' => null,
                'showConsultPost' => false,
                'selectionPills' => [],
            ];
        }

        if ($confirmedValueCount === 0) {
            $latest = $bloodTests->first();

            return [
                'headline' => 'Bevestig waarden',
                'variant' => 'default',
                'items' => [
                    [
                        'state' => 'pending',
                        'label' => 'Er zijn bloedtesten, maar nog geen bevestigde waarden voor dashboard, trends of consult.',
                    ],
                    [
                        'state' => 'optional',
                        'label' => $latest['title'].' · '.$latest['date'],
                    ],
                ],
                'consultBloodTestId' => null,
                'showConsultPost' => false,
                'selectionPills' => [],
            ];
        }

        $latestBloodTest = $this->latestConsultBloodTest($user);
        $latestTimeline = $latestBloodTest === null
            ? $bloodTests->first()
            : ($bloodTests->firstWhere('id', $latestBloodTest->id)
                ?? $this->timelineRowForBloodTest($latestBloodTest));
        $contextNoteCount = $latestBloodTest
            ? ContextNote::query()
                ->where('user_id', $user->id)
                ->where('blood_test_id', $latestBloodTest->id)
                ->count()
            : 0;
        $hasComparableHistory = BloodTest::query()
            ->where('user_id', $user->id)
            ->whereHas('results', fn ($query) => $query
                ->whereNotNull('confirmed_at')
                ->whereHas('biomarker', fn ($query) => $query->where('user_id', $user->id)))
            ->count() >= 2;

        $summary = $latestBloodTest
            ? $this->buildLatestUploadSummary->forBloodTest($user, $latestBloodTest)
            : null;
        $changeCount = $latestBloodTest
            ? $this->comparableChangeCount($user, $latestBloodTest)
            : 0;

        $items = [
            [
                'state' => 'ok',
                'label' => 'Bloedtesten bevestigd',
            ],
            [
                'state' => $latestTimeline['documentCount'] > 0 ? 'ok' : 'optional',
                'label' => $latestTimeline['documentCount'] > 0
                    ? 'Bron-PDF\'s beschikbaar'
                    : 'Geen bron-PDF gekoppeld (optioneel)',
            ],
            [
                'state' => $hasComparableHistory ? 'ok' : 'optional',
                'label' => $hasComparableHistory
                    ? 'Wijzigingen zichtbaar'
                    : 'Nog geen eerdere test om mee te vergelijken',
            ],
            [
                'state' => 'optional',
                'label' => $contextNoteCount === 0
                    ? 'Contextnotities (optioneel)'
                    : $this->countLabel($contextNoteCount, 'contextnotitie gekoppeld', 'contextnotities gekoppeld'),
            ],
        ];

        return [
            'headline' => 'Klaar voor je consult?',
            'variant' => 'success',
            'items' => $items,
            'consultBloodTestId' => $latestBloodTest?->id,
            'showConsultPost' => $latestBloodTest !== null,
            'selectionPills' => $this->selectionPills(
                summary: $summary,
                documentCount: (int) $latestTimeline['documentCount'],
                changeCount: $changeCount,
            ),
        ];
    }

    /**
     * @param  array<string, mixed>|null  $summary
     * @return list<array{key: string, label: string, count: int, tone: string}>
     */
    private function selectionPills(?array $summary, int $documentCount, int $changeCount): array
    {
        return [
            [
                'key' => 'attention',
                'label' => 'Aandacht',
                'count' => $summary ? (int) $summary['attentionCount'] : 0,
                'tone' => 'rose',
            ],
            [
                'key' => 'normal',
                'label' => 'Normaal',
                'count' => $summary ? (int) $summary['normalCount'] : 0,
                'tone' => 'emerald',
            ],
            [
                'key' => 'changes',
                'label' => 'Wijzigingen',
                'count' => $changeCount,
                'tone' => 'amber',
            ],
            [
                'key' => 'sources',
                'label' => 'Bron-PDF',
                'count' => $documentCount,
                'tone' => 'sky',
            ],
        ];
    }

    private function comparableChangeCount(User $user, BloodTest $bloodTest): int
    {
        return $this->buildLongitudinalChanges
            ->across($user, $user->bloodTestsUpToAndIncluding($bloodTest))
            ->filter(function (LongitudinalChange $change) use ($bloodTest): bool {
                if (! $change->result instanceof BiomarkerResult) {
                    return false;
                }

                if ((int) $change->result->blood_test_id !== (int) $bloodTest->id) {
                    return false;
                }

                return $change->comparable && $change->direction !== 'unchanged';
            })
            ->count();
    }

    private function latestConsultBloodTest(User $user): ?BloodTest
    {
        return BloodTest::query()
            ->where('user_id', $user->id)
            ->withCount([
                'documents',
                'results as confirmed_results_count' => fn ($query) => $query
                    ->whereNotNull('confirmed_at')
                    ->whereHas('biomarker', fn ($query) => $query->where('user_id', $user->id)),
            ])
            ->whereHas('results', fn ($query) => $query
                ->whereNotNull('confirmed_at')
                ->whereHas('biomarker', fn ($query) => $query->where('user_id', $user->id)))
            ->recentFirst()
            ->first();
    }

    /**
     * @return array{id: int, title: non-falsy-string, date: string, confirmedCount: int, documentCount: int}
     */
    private function timelineRowForBloodTest(BloodTest $bloodTest): array
    {
        return [
            'id' => $bloodTest->id,
            'title' => $bloodTest->title ?: 'Bloedtest zonder titel',
            'date' => $bloodTest->test_date
                ? Format::dutchDate($bloodTest->test_date)
                : 'Geen datum',
            'confirmedCount' => (int) $bloodTest->getAttribute('confirmed_results_count'),
            'documentCount' => (int) $bloodTest->getAttribute('documents_count'),
        ];
    }

    private function countLabel(int $count, string $singular, string $plural): string
    {
        return $count.' '.($count === 1 ? $singular : $plural);
    }
}
