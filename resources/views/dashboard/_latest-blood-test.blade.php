@php
    $latestBloodTest = $bloodTests->first();
    $matchingSummary = $latestUploadSummary
        && $latestBloodTest
        && (int) $latestUploadSummary['bloodTest']->id === (int) $latestBloodTest['id']
            ? $latestUploadSummary
            : null;
@endphp

<section class="overflow-hidden rounded-lg border border-neutral-200 bg-white dark:border-neutral-700 dark:bg-neutral-900" data-test="dashboard-latest-blood-test">
    <div class="border-b border-neutral-200 p-5 dark:border-neutral-700">
        <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
            <div class="space-y-2">
                <flux:heading size="lg">{{ __('Laatste bloedtest') }}</flux:heading>

                @if ($latestBloodTest)
                    <div class="text-xl font-semibold text-neutral-900 dark:text-white">{{ $latestBloodTest['title'] }}</div>
                    <div class="text-sm text-neutral-600 dark:text-neutral-400">
                        {{ __('Afname') }} {{ $latestBloodTest['date'] }}
                    </div>
                @else
                    <div class="text-xl font-semibold text-neutral-900 dark:text-white">{{ __('Nog geen bloedtest') }}</div>
                    <flux:text>{{ __('Upload je eerste lab-PDF om je dossier te starten.') }}</flux:text>
                @endif
            </div>

            @if ($latestBloodTest)
                <flux:button :href="$latestBloodTest['href']" variant="outline" class="shrink-0">
                    {{ __('Bloedtest openen') }}
                </flux:button>
            @endif
        </div>

        @if ($latestBloodTest)
            <div class="mt-4 flex flex-wrap gap-2 text-xs text-neutral-600 dark:text-neutral-300">
                <span class="rounded-md bg-neutral-100 px-2 py-1 dark:bg-neutral-800">{{ $latestBloodTest['confirmedCount'] }} {{ __('bevestigd') }}</span>

                @if ($latestBloodTest['draftCount'] > 0)
                    <span class="rounded-md bg-amber-50 px-2 py-1 text-amber-800 ring-1 ring-amber-200 dark:bg-amber-950/40 dark:text-amber-100 dark:ring-amber-900">{{ $latestBloodTest['draftCount'] }} {{ __('review') }}</span>
                @endif

                @if ($latestBloodTest['documentCount'] > 0)
                    <span class="rounded-md bg-neutral-100 px-2 py-1 dark:bg-neutral-800">{{ $latestBloodTest['documentCount'] }} {{ __('bronbestand') }}</span>
                @endif
            </div>
        @endif
    </div>

    @if ($matchingSummary)
        <div class="p-5" data-test="dashboard-latest-confirmed-values">
            <div class="space-y-1">
                <div class="text-sm font-medium text-neutral-900 dark:text-white">{{ __('Laatste bevestigde waarden') }}</div>
                <p class="text-sm text-neutral-600 dark:text-neutral-400">{{ __('Alleen bevestigde waarden verschijnen hieronder.') }}</p>
            </div>

            <div class="mt-4 flex flex-wrap gap-2 text-sm">
                <span class="rounded-md bg-neutral-100 px-2 py-1 font-medium text-neutral-800 dark:bg-neutral-800 dark:text-neutral-100">
                    {{ $matchingSummary['normalSummaryLabel'] }}
                </span>
                <span class="rounded-md bg-neutral-100 px-2 py-1 font-medium text-neutral-800 dark:bg-neutral-800 dark:text-neutral-100">
                    {{ $matchingSummary['attentionSummaryLabel'] }}
                </span>
            </div>
        </div>

        <div class="divide-y divide-neutral-200 border-t border-neutral-200 dark:divide-neutral-700 dark:border-neutral-700" data-test="dashboard-latest-values-preview">
            @foreach ($matchingSummary['rows']->take(4) as $row)
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
    @elseif ($latestBloodTest)
        <div class="border-t border-neutral-200 p-5 text-sm text-neutral-600 dark:border-neutral-700 dark:text-neutral-400" data-test="dashboard-no-confirmed-values">
            {{ __('Open deze bloedtest om waarden te bevestigen voordat ze in dashboard, trends of consult verschijnen.') }}
        </div>
    @endif
</section>
