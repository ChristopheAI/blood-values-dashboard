<section class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4" data-test="dashboard-metrics">
    @foreach ($workstand as $metric)
        <article
            @class([
                'rounded-lg border p-4',
                'border-amber-300 bg-amber-50 dark:border-amber-800 dark:bg-amber-950/30' => $metric['emphasis'],
                'border-neutral-200 bg-white dark:border-neutral-700 dark:bg-neutral-900' => ! $metric['emphasis'],
            ])
            data-test="dashboard-metric-{{ $metric['key'] }}"
        >
            <div @class([
                'text-3xl font-semibold tabular-nums',
                'text-amber-900 dark:text-amber-100' => $metric['emphasis'],
                'text-neutral-900 dark:text-white' => ! $metric['emphasis'],
            ])>
                {{ $metric['count'] }}
            </div>
            <div class="mt-1 text-sm font-medium text-neutral-900 dark:text-white">{{ $metric['label'] }}</div>
            <p class="mt-1 text-xs text-neutral-600 dark:text-neutral-400">{{ $metric['help'] }}</p>
        </article>
    @endforeach
</section>
