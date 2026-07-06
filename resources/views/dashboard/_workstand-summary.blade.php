@php
    $toneClasses = fn (string $tone): array => match ($tone) {
        'rose' => [
            'icon' => 'bg-rose-100 text-rose-700 dark:bg-rose-950/50 dark:text-rose-200',
            'count' => 'text-neutral-900 dark:text-white',
        ],
        'amber' => [
            'icon' => 'bg-amber-100 text-amber-800 dark:bg-amber-950/50 dark:text-amber-200',
            'count' => 'text-neutral-900 dark:text-white',
        ],
        'sky' => [
            'icon' => 'bg-sky-100 text-sky-800 dark:bg-sky-950/50 dark:text-sky-200',
            'count' => 'text-neutral-900 dark:text-white',
        ],
        'emerald' => [
            'icon' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-200',
            'count' => 'text-neutral-900 dark:text-white',
        ],
        default => [
            'icon' => 'bg-neutral-100 text-neutral-700 dark:bg-neutral-800 dark:text-neutral-200',
            'count' => 'text-neutral-900 dark:text-white',
        ],
    };
@endphp

<section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4" data-test="dashboard-metrics">
    @foreach ($workstand as $metric)
        @php($tone = $toneClasses($metric['emphasis'] ? 'amber' : $metric['tone']))
        {{-- De bevestigd-teller linkt door naar het volledige waardenoverzicht. --}}
        @php($href = $metric['key'] === 'confirmed' ? route('blood-results.overview') : null)

        <article
            @class([
                'rounded-xl border bg-white p-5 shadow-xs dark:bg-neutral-900',
                'border-amber-300 ring-1 ring-amber-200 dark:border-amber-800 dark:ring-amber-900/40' => $metric['emphasis'],
                'border-neutral-200 dark:border-neutral-700' => ! $metric['emphasis'],
                'transition-shadow hover:shadow-md' => $href !== null,
            ])
            data-test="dashboard-metric-{{ $metric['key'] }}"
        >
            @if ($href !== null)
                <a href="{{ $href }}" wire:navigate class="flex items-start gap-4" data-test="dashboard-metric-link-{{ $metric['key'] }}">
            @else
                <div class="flex items-start gap-4">
            @endif
                <div @class(['flex size-11 shrink-0 items-center justify-center rounded-xl', $tone['icon']])>
                    @include('dashboard._metric-icon', ['icon' => $metric['icon']])
                </div>

                <div class="min-w-0 space-y-1">
                    <div @class(['text-3xl font-semibold tabular-nums leading-none', $tone['count']])>
                        {{ $metric['count'] }}
                    </div>
                    <div class="text-sm font-semibold text-neutral-900 dark:text-white">{{ $metric['label'] }}</div>
                    <p class="text-xs leading-snug text-neutral-600 dark:text-neutral-400">{{ $metric['help'] }}</p>
                </div>
            @if ($href !== null)
                </a>
            @else
                </div>
            @endif
        </article>
    @endforeach
</section>
