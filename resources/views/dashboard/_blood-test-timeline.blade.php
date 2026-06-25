<section class="space-y-4" data-test="dashboard-blood-test-timeline">
    <div class="flex items-center justify-between gap-4">
        <flux:heading size="lg">{{ __('Bloedtesten') }}</flux:heading>
        <flux:button :href="route('blood-tests.index')" variant="outline" size="sm">{{ __('Alle bloedtesten') }}</flux:button>
    </div>

    <div class="overflow-hidden rounded-lg border border-neutral-200 bg-white dark:border-neutral-700 dark:bg-neutral-900">
        @forelse ($bloodTests as $bloodTest)
            <a href="{{ $bloodTest['href'] }}" class="block border-b border-neutral-100 p-4 last:border-b-0 hover:bg-neutral-50 dark:border-neutral-800 dark:hover:bg-neutral-800/50" data-test="dashboard-blood-test-row">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                    <div class="space-y-1">
                        <div class="font-medium text-neutral-900 dark:text-white">{{ $bloodTest['title'] }}</div>
                        <div class="text-sm text-neutral-600 dark:text-neutral-400">{{ $bloodTest['date'] }}</div>
                    </div>

                    <div class="flex flex-wrap gap-2 text-xs text-neutral-600 dark:text-neutral-300">
                        <span class="rounded-full bg-neutral-100 px-2 py-1 dark:bg-neutral-800">{{ $bloodTest['confirmedCount'] }} {{ __('bevestigd') }}</span>

                        @if ($bloodTest['draftCount'] > 0)
                            <span class="rounded-full bg-amber-100 px-2 py-1 text-amber-800 dark:bg-amber-950/50 dark:text-amber-200">{{ $bloodTest['draftCount'] }} {{ __('review') }}</span>
                        @endif

                        @if ($bloodTest['documentCount'] > 0)
                            <span class="rounded-full bg-neutral-100 px-2 py-1 dark:bg-neutral-800">{{ $bloodTest['documentCount'] }} {{ __('bronbestand') }}</span>
                        @endif
                    </div>
                </div>
            </a>
        @empty
            <div class="p-5 text-sm text-neutral-600 dark:text-neutral-400">
                {{ __('Nog geen bloedtesten. Upload je eerste lab-PDF om te starten.') }}
            </div>
        @endforelse
    </div>
</section>
