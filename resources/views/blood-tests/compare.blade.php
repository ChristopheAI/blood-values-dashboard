<x-layouts::app :title="__('Compare blood tests')">
    <section class="mx-auto flex w-full max-w-5xl flex-col gap-6">
        <header>
            <flux:heading size="xl">{{ __('Compare blood tests') }}</flux:heading>
            <flux:text>
                {{ $first->test_date?->toDateString() ?? __('First test') }}
                ·
                {{ $second->test_date?->toDateString() ?? __('Second test') }}
            </flux:text>
        </header>

        <div class="overflow-hidden rounded-lg border border-neutral-200 dark:border-neutral-700" data-test="blood-test-comparison-table">
            <table class="w-full text-left text-sm">
                <thead class="bg-neutral-50 text-neutral-600 dark:bg-neutral-900 dark:text-neutral-300">
                    <tr>
                        <th class="p-3">{{ __('Biomarker') }}</th>
                        <th class="p-3">{{ __('Previous') }}</th>
                        <th class="p-3">{{ __('Current') }}</th>
                        <th class="p-3">{{ __('Delta') }}</th>
                        <th class="p-3">{{ __('Status') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr class="border-t border-neutral-200 dark:border-neutral-700" data-test="blood-test-comparison-row">
                            <td class="p-3">{{ $row['biomarker'] }}</td>
                            <td class="p-3">{{ $row['previous_value'] }} {{ $row['unit'] }}</td>
                            <td class="p-3">{{ $row['current_value'] }} {{ $row['unit'] }}</td>
                            <td class="p-3">{{ $row['delta'] }}</td>
                            <td class="p-3">{{ $row['status'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="p-4 text-neutral-600 dark:text-neutral-400">{{ __('No confirmed values to compare.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</x-layouts::app>
