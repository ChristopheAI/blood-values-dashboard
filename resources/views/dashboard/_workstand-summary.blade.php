<section class="space-y-4" data-test="dashboard-workstand-summary">
    <flux:heading size="lg">{{ __('Werkstand') }}</flux:heading>

    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ($workstand as $item)
            <div class="rounded-lg border border-neutral-200 bg-white p-4 dark:border-neutral-700 dark:bg-neutral-900">
                <div class="text-xs font-medium text-neutral-500 dark:text-neutral-400">{{ $item['label'] }}</div>
                <div class="mt-2 text-lg font-semibold tabular-nums text-neutral-900 dark:text-white">{{ $item['summary'] }}</div>
                <p class="mt-1 text-sm text-neutral-600 dark:text-neutral-400">{{ $item['help'] }}</p>
            </div>
        @endforeach
    </div>
</section>
