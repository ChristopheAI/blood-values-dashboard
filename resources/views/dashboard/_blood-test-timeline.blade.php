<section class="space-y-4" data-test="dashboard-blood-test-timeline">
    <div class="flex items-center justify-between gap-4">
        <flux:heading size="lg">{{ __('Laatste bloedtesten') }}</flux:heading>
        <flux:button :href="route('blood-tests.index')" variant="outline" size="sm">{{ __('Alle bloedtesten') }}</flux:button>
    </div>

    <div class="overflow-hidden rounded-xl border border-neutral-200 bg-white dark:border-neutral-700 dark:bg-neutral-900">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="border-b border-neutral-200 bg-neutral-50 text-left text-xs font-semibold uppercase tracking-wide text-neutral-600 dark:border-neutral-700 dark:bg-neutral-800/60 dark:text-neutral-300">
                    <tr>
                        <th scope="col" class="px-4 py-3">{{ __('Datum') }}</th>
                        <th scope="col" class="px-4 py-3">{{ __('Type') }}</th>
                        <th scope="col" class="px-4 py-3">{{ __('Status') }}</th>
                        <th scope="col" class="px-4 py-3">{{ __('Bron') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100 dark:divide-neutral-800">
                    @forelse ($bloodTests as $bloodTest)
                        <tr class="hover:bg-neutral-50 dark:hover:bg-neutral-800/50">
                            <td class="whitespace-nowrap px-4 py-3 text-neutral-600 dark:text-neutral-400">
                                <a href="{{ $bloodTest['href'] }}" class="block font-medium text-neutral-900 hover:underline dark:text-white" data-test="dashboard-blood-test-row">
                                    {{ $bloodTest['date'] }}
                                </a>
                            </td>
                            <td class="px-4 py-3 font-medium text-neutral-900 dark:text-white">
                                <a href="{{ $bloodTest['href'] }}" class="hover:underline">{{ $bloodTest['title'] }}</a>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-2">
                                    @if ($bloodTest['confirmedCount'] > 0)
                                        <span class="rounded-full bg-emerald-100 px-2 py-1 text-xs font-medium text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-200">
                                            {{ $bloodTest['confirmedCount'] }} {{ __('bevestigd') }}
                                        </span>
                                    @endif

                                    @if ($bloodTest['draftCount'] > 0)
                                        <span class="rounded-full bg-amber-100 px-2 py-1 text-xs font-medium text-amber-800 dark:bg-amber-950/50 dark:text-amber-200">
                                            {{ $bloodTest['draftCount'] }} {{ __('review') }}
                                        </span>
                                    @endif

                                    @if ($bloodTest['confirmedCount'] === 0 && $bloodTest['draftCount'] === 0)
                                        <span class="rounded-full bg-neutral-100 px-2 py-1 text-xs font-medium text-neutral-700 dark:bg-neutral-800 dark:text-neutral-300">
                                            {{ __('Nog geen waarden') }}
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3 text-neutral-600 dark:text-neutral-400">
                                @if ($bloodTest['documentCount'] > 0)
                                    <span class="rounded-full bg-sky-100 px-2 py-1 text-xs font-medium text-sky-800 dark:bg-sky-950/50 dark:text-sky-200">
                                        {{ $bloodTest['documentCount'] }} {{ __('PDF') }}
                                    </span>
                                @else
                                    <span class="text-neutral-500 dark:text-neutral-500">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-5 text-neutral-600 dark:text-neutral-400">
                                {{ __('Nog geen bloedtesten. Upload je eerste lab-PDF om te starten.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>
