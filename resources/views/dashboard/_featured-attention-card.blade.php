@php
    $markerClass = 'bg-amber-500 ring-amber-100 dark:ring-amber-950';
    $valuePillClass = 'bg-amber-500 text-white';
@endphp

<article class="rounded-lg border border-amber-300 bg-amber-50 p-5 shadow-xs dark:border-amber-800 dark:bg-amber-950/30" data-test="featured-attention-card">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div class="min-w-0 space-y-1">
            <h3 class="text-lg font-semibold text-neutral-900 dark:text-white">{{ $row['name'] }}</h3>
            <p class="max-w-3xl text-sm text-neutral-700 dark:text-neutral-300">{{ $row['takeaway'] }}</p>
        </div>

        <div class="inline-flex w-fit shrink-0 rounded-md bg-amber-100 px-2.5 py-1 text-sm font-semibold text-amber-900 ring-1 ring-amber-200 dark:bg-amber-900/50 dark:text-amber-100 dark:ring-amber-800">
            {{ $row['statusLabel'] }}
        </div>
    </div>

    <div class="mt-7 space-y-3">
        @if ($row['range']['available'])
            @php
                $position = (float) $row['range']['position'];
                $normalStart = (float) $row['range']['normalStart'];
                $normalWidth = (float) $row['range']['normalWidth'];
                $markerPositionClass = $position <= 0 || $position >= 100 ? '' : '-translate-x-1/2';
                $markerPositionStyle = $position <= 0
                    ? 'left: 0;'
                    : ($position >= 100
                        ? 'right: 0;'
                        : 'left: '.number_format($position, 2, '.', '').'%;');
            @endphp

            <div class="relative pt-8" data-test="biomarker-range-bar">
                <span class="absolute top-0 rounded-md px-2 py-1 text-sm font-semibold shadow-sm tabular-nums {{ $valuePillClass }} {{ $markerPositionClass }}" style="{{ $markerPositionStyle }}">
                    {{ $row['valueLabel'] }}
                </span>
                <div class="relative h-3 rounded-full bg-neutral-200 dark:bg-neutral-800">
                    <div class="absolute top-0 h-3 rounded-full bg-emerald-400 dark:bg-emerald-500" style="left: {{ number_format($normalStart, 2, '.', '') }}%; width: {{ number_format($normalWidth, 2, '.', '') }}%;"></div>
                    <div class="absolute top-1/2 size-6 -translate-y-1/2 rounded-full border-4 border-white shadow-sm ring-4 dark:border-neutral-900 {{ $markerPositionClass }} {{ $markerClass }}" style="{{ $markerPositionStyle }}"></div>
                </div>
            </div>

            <div class="flex flex-col gap-1 text-sm text-neutral-600 dark:text-neutral-400 sm:flex-row sm:items-center sm:justify-between">
                <span>{{ $row['range']['label'] }}</span>
                <span class="font-medium text-neutral-800 dark:text-neutral-200">{{ $row['trendLabel'] }}@if (!empty($row['trendDetail'])) <span class="font-normal text-neutral-500 dark:text-neutral-400">({{ $row['trendDetail'] }})</span>@endif</span>
            </div>
        @else
            <div class="rounded-md border border-amber-200 bg-white/70 p-3 text-sm text-neutral-700 dark:border-amber-900 dark:bg-neutral-950/30 dark:text-neutral-300" data-test="biomarker-range-missing">
                {{ $row['valueLabel'] }} · {{ $row['range']['label'] }} · {{ $row['trendLabel'] }}@if (!empty($row['trendDetail'])) ({{ $row['trendDetail'] }})@endif
            </div>
        @endif

        {{-- Een kalme volgende stap op het moment van het signaal: geen
             urgentie, geen duiding — alleen de bestaande consultroute. --}}
        <p class="border-t border-amber-200/70 pt-3 text-sm text-neutral-700 dark:border-amber-900/70 dark:text-neutral-300" data-test="attention-next-step">
            {{ __('Bespreek deze waarde met je arts.') }}
            <a href="{{ route('consult-overview.index', ['blood_test_ids' => [$summary['bloodTest']->id]]) }}" class="font-medium underline">
                {{ __('Zet hem klaar op je consultlijst') }}
            </a>
        </p>
    </div>
</article>
