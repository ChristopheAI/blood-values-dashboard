<x-layouts::app :title="$biomarker->name">
    <section class="mx-auto flex w-full max-w-4xl flex-col gap-6">
        <header>
            <flux:heading size="xl">{{ $biomarker->name }}</flux:heading>
            <flux:text>{{ __('Confirmed values over time.') }}</flux:text>
        </header>

        <div class="overflow-hidden rounded-lg border border-neutral-200 dark:border-neutral-700">
            <table class="w-full text-left text-sm">
                <thead class="bg-neutral-50 text-neutral-600 dark:bg-neutral-900 dark:text-neutral-300">
                    <tr>
                        <th class="p-3">{{ __('Date') }}</th>
                        <th class="p-3">{{ __('Value') }}</th>
                        <th class="p-3">{{ __('Status') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($results as $result)
                        <tr class="border-t border-neutral-200 dark:border-neutral-700">
                            <td class="p-3">{{ $result->bloodTest->test_date?->toDateString() ?? __('No date') }}</td>
                            <td class="p-3">{{ (float) $result->value }} {{ $result->unit }}</td>
                            <td class="p-3">{{ $result->status }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="p-4 text-neutral-600 dark:text-neutral-400">{{ __('No confirmed values yet.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</x-layouts::app>
