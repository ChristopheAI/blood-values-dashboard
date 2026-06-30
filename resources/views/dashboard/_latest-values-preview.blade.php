<section class="overflow-hidden rounded-lg border border-neutral-200 bg-white dark:border-neutral-700 dark:bg-neutral-900" data-test="dashboard-latest-values-preview">
    <div class="border-b border-neutral-200 p-5 dark:border-neutral-700">
        <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
            <div class="space-y-2">
                <flux:heading size="lg">{{ $summary['bloodTest']->title ?: __('Bloedtest zonder titel') }}</flux:heading>
                <div class="text-sm text-neutral-600 dark:text-neutral-400">
                    {{ $summary['collectedLabel'] }}
                    @if ($summary['bloodTest']->lab_name)
                        · {{ $summary['bloodTest']->lab_name }}
                    @endif
                    · {{ __('alleen bevestigde waarden') }}
                </div>
            </div>

            <form method="POST" action="{{ route('consult-overview.index') }}" class="shrink-0">
                @csrf
                <input type="hidden" name="blood_test_ids[]" value="{{ $summary['bloodTest']->id }}">
                <input type="hidden" name="include_attention" value="1">
                <input type="hidden" name="include_normal" value="1">
                <input type="hidden" name="include_trends" value="1">
                <input type="hidden" name="include_source_documents" value="1">
                <input type="hidden" name="include_themes" value="1">
                <flux:button type="submit" variant="primary" size="sm" data-test="latest-upload-consult-button">{{ __('Maak consultlijst') }}</flux:button>
            </form>
        </div>

        <div class="mt-4 flex flex-wrap gap-2 text-sm">
            <span class="rounded-md bg-neutral-100 px-2 py-1 font-medium text-neutral-800 dark:bg-neutral-800 dark:text-neutral-100">
                {{ $summary['normalSummaryLabel'] }}
            </span>
            <span class="rounded-md bg-neutral-100 px-2 py-1 font-medium text-neutral-800 dark:bg-neutral-800 dark:text-neutral-100">
                {{ $summary['attentionSummaryLabel'] }}
            </span>
        </div>
    </div>

    <div class="divide-y divide-neutral-200 dark:divide-neutral-700">
        @foreach ($summary['rows']->take(5) as $row)
            <article class="grid gap-3 p-4 text-sm md:grid-cols-[minmax(0,1fr)_auto_auto] md:items-center" data-test="dashboard-latest-value-row">
                <div class="min-w-0 space-y-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <h3 class="font-medium text-neutral-900 dark:text-white">{{ $row['name'] }}</h3>
                        <span @class([
                            'rounded-md px-2 py-0.5 text-xs font-medium ring-1',
                            'bg-emerald-50 text-emerald-800 ring-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-100 dark:ring-emerald-900' => $row['status'] === 'normal',
                            'bg-amber-50 text-amber-800 ring-amber-200 dark:bg-amber-950/40 dark:text-amber-100 dark:ring-amber-900' => $row['status'] !== 'normal',
                        ])>{{ $row['statusLabel'] }}</span>
                    </div>
                    <p class="text-neutral-600 dark:text-neutral-400">{{ $row['takeaway'] }}</p>
                </div>

                <div class="font-medium tabular-nums text-neutral-900 dark:text-white md:text-right">{{ $row['valueLabel'] }}</div>
                <div class="text-neutral-500 dark:text-neutral-400 md:text-right">{{ $row['trendLabel'] }}</div>
            </article>
        @endforeach
    </div>

    <div class="flex flex-col gap-3 border-t border-neutral-200 p-4 sm:flex-row sm:items-center sm:justify-between dark:border-neutral-700">
        <p class="text-sm text-neutral-600 dark:text-neutral-400">{{ __('Open de bloedtest voor het volledige waardenoverzicht en broncontext.') }}</p>
        <flux:button :href="route('blood-tests.show', $summary['bloodTest'])" variant="outline" size="sm">{{ __('Bloedtest openen') }}</flux:button>
    </div>
</section>
