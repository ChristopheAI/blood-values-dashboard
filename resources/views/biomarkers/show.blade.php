<x-layouts::app :title="$biomarker->name">
    <section class="mx-auto flex w-full max-w-4xl flex-col gap-6">
        <header class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
            <div>
                <flux:heading size="xl">{{ $biomarker->name }}</flux:heading>
                <flux:text>{{ __('Confirmed values over time.') }}</flux:text>
            </div>

            @if ($pin)
                <form method="POST" action="{{ route('biomarkers.unpin', $biomarker) }}">
                    @csrf
                    @method('DELETE')

                    <flux:button type="submit" variant="outline" data-test="unpin-biomarker-button">{{ __('Unpin') }}</flux:button>
                </form>
            @else
                <form method="POST" action="{{ route('biomarkers.pin', $biomarker) }}" class="flex flex-col gap-2 md:min-w-64">
                    @csrf

                    <flux:input name="note" :label="__('Pin note')" data-test="pin-note-input" />
                    <flux:button type="submit" variant="primary" data-test="pin-biomarker-button">{{ __('Pin') }}</flux:button>
                </form>
            @endif
        </header>

        <div class="overflow-hidden rounded-lg border border-neutral-200 dark:border-neutral-700" data-test="biomarker-history-table">
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
                        <tr class="border-t border-neutral-200 dark:border-neutral-700" data-test="biomarker-history-row">
                            <td class="p-3">{{ $result->bloodTest->test_date ? \App\Support\Format::dutchDate($result->bloodTest->test_date) : __('No date') }}</td>
                            <td class="p-3">{{ \App\Support\Format::biomarkerValue($result->value, $result->source_snippet) }} {{ $result->unit }}</td>
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
