@php
    $isNormal = $row['status'] === 'normal';
    $rowClass = $isNormal
        ? 'bg-white dark:bg-neutral-900'
        : 'bg-neutral-50 dark:bg-neutral-900';
    $badgeClass = match ($row['status']) {
        'high', 'low' => 'bg-amber-50 text-amber-900 ring-amber-200 dark:bg-amber-950/40 dark:text-amber-100 dark:ring-amber-900',
        'unknown' => 'bg-neutral-100 text-neutral-700 ring-neutral-200 dark:bg-neutral-800 dark:text-neutral-200 dark:ring-neutral-700',
        default => 'bg-emerald-50 text-emerald-800 ring-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-100 dark:ring-emerald-900',
    };
    $markerClass = $isNormal ? 'bg-emerald-600 dark:bg-emerald-400' : 'bg-neutral-500 dark:bg-neutral-300';
    $dataTest = $isNormal ? 'compact-normal-row' : 'compact-review-row';
@endphp

<article class="border-b border-neutral-200 p-4 last:border-b-0 dark:border-neutral-700 {{ $rowClass }}" data-test="{{ $dataTest }}">
    <div class="grid gap-3 md:grid-cols-[minmax(0,1.35fr)_minmax(9rem,0.7fr)_auto] md:items-center">
        <div class="min-w-0 space-y-1">
            <div class="flex flex-wrap items-center gap-2">
                <h3 class="font-semibold text-neutral-900 dark:text-white">{{ $row['name'] }}</h3>
                <span class="rounded-md px-2 py-0.5 text-xs font-medium ring-1 {{ $badgeClass }}">{{ $row['statusLabel'] }}</span>
            </div>
            <p class="text-sm text-neutral-600 dark:text-neutral-400">{{ $row['takeaway'] }}</p>
        </div>

        <div class="space-y-2">
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

                <div class="relative h-2 rounded-full bg-neutral-200 dark:bg-neutral-800" data-test="compact-range-bar">
                    <div class="absolute top-0 h-2 rounded-full bg-emerald-400 dark:bg-emerald-500" style="left: {{ number_format($normalStart, 2, '.', '') }}%; width: {{ number_format($normalWidth, 2, '.', '') }}%;"></div>
                    <div class="absolute top-1/2 size-3 -translate-y-1/2 rounded-full ring-2 ring-white dark:ring-neutral-900 {{ $markerPositionClass }} {{ $markerClass }}" style="{{ $markerPositionStyle }}"></div>
                </div>
                <div class="text-xs text-neutral-500 dark:text-neutral-400">{{ $row['range']['label'] }}</div>
            @else
                <div class="text-xs text-neutral-500 dark:text-neutral-400">{{ $row['range']['label'] }}</div>
            @endif
        </div>

        <div class="space-y-1 md:text-right">
            <div class="font-medium tabular-nums text-neutral-900 dark:text-white">{{ $row['valueLabel'] }}</div>
            <div class="text-xs text-neutral-500 dark:text-neutral-400">{{ $row['trendLabel'] }}</div>
        </div>
    </div>
</article>
