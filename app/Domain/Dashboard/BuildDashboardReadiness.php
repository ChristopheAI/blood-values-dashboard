<?php

namespace App\Domain\Dashboard;

use App\Models\BloodTest;
use App\Models\ContextNote;
use App\Models\User;
use Illuminate\Support\Collection;

class BuildDashboardReadiness
{
    /**
     * @param  Collection<int, array{id: int, title: non-falsy-string, href: string, date: string, status: string, confirmedCount: int, draftCount: int, documentCount: int}>  $bloodTests
     * @return array{
     *     headline: string,
     *     items: list<array{state: string, label: string}>,
     *     consultBloodTestId: int|null,
     *     showConsultPost: bool,
     *     selectionPills: list<string>
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

        $latestBloodTest = $this->latestConsultBloodTest($user, $bloodTests);
        $latestTimeline = $bloodTests->firstWhere('id', $latestBloodTest?->id)
            ?? $bloodTests->first();
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

        $items = [
            [
                'state' => 'ok',
                'label' => $this->countLabel(
                    (int) $latestTimeline['confirmedCount'],
                    'bevestigde waarde',
                    'bevestigde waarden',
                ).' ('.$latestTimeline['date'].')',
            ],
            [
                'state' => $latestTimeline['documentCount'] > 0 ? 'ok' : 'optional',
                'label' => $latestTimeline['documentCount'] > 0
                    ? $this->countLabel((int) $latestTimeline['documentCount'], 'bron-PDF gekoppeld', 'bron-PDF\'s gekoppeld')
                    : 'Geen bron-PDF gekoppeld (optioneel)',
            ],
            [
                'state' => $hasComparableHistory ? 'ok' : 'optional',
                'label' => $hasComparableHistory
                    ? 'Wijzigingen t.o.v. eerdere test beschikbaar'
                    : 'Nog geen eerdere test om mee te vergelijken',
            ],
            [
                'state' => 'optional',
                'label' => $contextNoteCount === 0
                    ? 'Geen contextnotities (optioneel)'
                    : $this->countLabel($contextNoteCount, 'contextnotitie gekoppeld', 'contextnotities gekoppeld'),
            ],
        ];

        return [
            'headline' => 'Klaar voor je consult?',
            'items' => $items,
            'consultBloodTestId' => $latestBloodTest?->id,
            'showConsultPost' => $latestBloodTest !== null,
            'selectionPills' => [
                __('aandacht'),
                __('normaal'),
                __('wijzigingen'),
                __('bron'),
            ],
        ];
    }

    /**
     * @param  Collection<int, array{id: int, title: non-falsy-string, href: string, date: string, status: string, confirmedCount: int, draftCount: int, documentCount: int}>  $bloodTests
     */
    private function latestConsultBloodTest(User $user, Collection $bloodTests): ?BloodTest
    {
        $timelineMatch = $bloodTests->first(fn (array $bloodTest): bool => $bloodTest['confirmedCount'] > 0);

        if ($timelineMatch === null) {
            return null;
        }

        return BloodTest::query()
            ->where('user_id', $user->id)
            ->whereKey($timelineMatch['id'])
            ->first();
    }

    private function countLabel(int $count, string $singular, string $plural): string
    {
        return $count.' '.($count === 1 ? $singular : $plural);
    }
}
