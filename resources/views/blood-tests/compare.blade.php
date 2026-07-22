@php
    $comparisonLabel = fn (string $value): string => match ($value) {
        'not measured' => __('Niet gemeten'),
        'not comparable' => __('Niet vergelijkbaar'),
        'unchanged' => __('Ongewijzigd'),
        default => $value,
    };
@endphp

<x-layouts::app :title="__('Bloedtesten vergelijken')">
    <section class="mx-auto flex w-full max-w-5xl flex-col gap-6">
        <header class="flex flex-col gap-2">
            <flux:heading size="xl">{{ __('Vergelijk bloedtesten') }}</flux:heading>
            <flux:text>
                {{ $first->test_date ? \App\Support\Format::dutchDate($first->test_date) : __('Eerste bloedtest') }}
                ·
                {{ $second->test_date ? \App\Support\Format::dutchDate($second->test_date) : __('Tweede bloedtest') }}
            </flux:text>
        </header>

        <div class="hidden overflow-hidden rounded-lg border border-neutral-200 sm:block dark:border-neutral-700" data-test="blood-test-comparison-table">
            <table class="w-full text-left text-sm">
                <thead class="bg-neutral-50 text-neutral-600 dark:bg-neutral-900 dark:text-neutral-300">
                    <tr>
                        <th class="p-3">{{ __('Biomarker') }}</th>
                        <th class="p-3">{{ __('Vorige waarde') }}</th>
                        <th class="p-3">{{ __('Huidige waarde') }}</th>
                        <th class="p-3">{{ __('Verschil') }}</th>
                        <th class="p-3">{{ __('Status') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr class="border-t border-neutral-200 dark:border-neutral-700" data-test="blood-test-comparison-row">
                            <td class="p-3">{{ $row['biomarker'] }}</td>
                            <td class="p-3">{{ trim($comparisonLabel($row['previous_value']).' '.$row['previous_unit']) }}</td>
                            <td class="p-3">{{ trim($comparisonLabel($row['current_value']).' '.$row['current_unit']) }}</td>
                            <td class="p-3">{{ $comparisonLabel($row['delta']) }}</td>
                            <td class="p-3">{{ (\App\Enums\BiomarkerStatus::tryFrom((string) $row['status']) ?? \App\Enums\BiomarkerStatus::Unknown)->dutchLabel() }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="p-4 text-neutral-600 dark:text-neutral-400">{{ __('Geen bevestigde waarden om te vergelijken.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="space-y-3 sm:hidden" data-test="blood-test-comparison-mobile-list">
            @forelse ($rows as $row)
                <article class="rounded-lg border border-neutral-200 p-4 text-sm dark:border-neutral-700" data-test="blood-test-comparison-mobile-card">
                    <h2 class="font-semibold text-neutral-900 dark:text-white">{{ $row['biomarker'] }}</h2>
                    <dl class="mt-3 grid grid-cols-2 gap-x-4 gap-y-3">
                        <div>
                            <dt class="text-xs font-medium uppercase tracking-wide text-neutral-500 dark:text-neutral-400">{{ __('Vorige waarde') }}</dt>
                            <dd class="mt-1 tabular-nums">{{ trim($comparisonLabel($row['previous_value']).' '.$row['previous_unit']) }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium uppercase tracking-wide text-neutral-500 dark:text-neutral-400">{{ __('Huidige waarde') }}</dt>
                            <dd class="mt-1 tabular-nums">{{ trim($comparisonLabel($row['current_value']).' '.$row['current_unit']) }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium uppercase tracking-wide text-neutral-500 dark:text-neutral-400">{{ __('Verschil') }}</dt>
                            <dd class="mt-1 tabular-nums">{{ $comparisonLabel($row['delta']) }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium uppercase tracking-wide text-neutral-500 dark:text-neutral-400">{{ __('Status') }}</dt>
                            <dd class="mt-1">{{ (\App\Enums\BiomarkerStatus::tryFrom((string) $row['status']) ?? \App\Enums\BiomarkerStatus::Unknown)->dutchLabel() }}</dd>
                        </div>
                    </dl>
                </article>
            @empty
                <div class="rounded-lg border border-dashed border-neutral-300 p-6 text-sm text-neutral-600 dark:border-neutral-700 dark:text-neutral-400">
                    {{ __('Geen bevestigde waarden om te vergelijken.') }}
                </div>
            @endforelse
        </div>
    </section>
</x-layouts::app>
