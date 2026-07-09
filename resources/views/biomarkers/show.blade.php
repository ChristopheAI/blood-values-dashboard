<x-layouts::app :title="$biomarker->name">
    <section class="mx-auto flex w-full max-w-4xl flex-col gap-6">
        <a href="{{ route('blood-results.overview') }}" class="text-sm font-medium text-blue-700 underline dark:text-blue-300" data-test="back-to-blood-results">
            {{ __('Terug naar bloedwaarden') }}
        </a>

        <header class="flex flex-col gap-2">
            <div>
                <flux:heading size="xl">{{ $biomarker->name }}</flux:heading>
                <flux:text>{{ __('Bevestigde waarden door de tijd. Alleen bevestigde waarden tellen mee in deze geschiedenis.') }}</flux:text>
            </div>
        </header>

        <section class="grid gap-3 rounded-lg border border-neutral-200 p-4 text-sm dark:border-neutral-700 md:grid-cols-[1fr_1fr_minmax(14rem,0.8fr)]" data-test="biomarker-trend-context">
            <div>
                <div class="font-medium text-neutral-700 dark:text-neutral-200">{{ __('Referentie') }}</div>
                <div class="text-neutral-600 dark:text-neutral-400">
                    {{ \App\Support\Format::referenceRange(
                        $biomarker->reference_min !== null ? (float) $biomarker->reference_min : null,
                        $biomarker->reference_max !== null ? (float) $biomarker->reference_max : null,
                        $biomarker->reference_unit ?: $biomarker->default_unit,
                    ) }}
                </div>
            </div>

            <div>
                <div class="font-medium text-neutral-700 dark:text-neutral-200">{{ __('Status') }}</div>
                <div class="text-neutral-600 dark:text-neutral-400">
                    {{ __('Laag, normaal, hoog of geen status komen uit de opgeslagen referentie en eenheid.') }}
                </div>
            </div>

            <div class="flex flex-col gap-2">
                <div class="font-medium text-neutral-700 dark:text-neutral-200">{{ __('Vastzetten') }}</div>

                @if ($pin)
                    <form method="POST" action="{{ route('biomarkers.unpin', $biomarker) }}">
                        @csrf
                        @method('DELETE')

                        <flux:button type="submit" variant="outline" size="sm" data-test="unpin-biomarker-button">{{ __('Losmaken') }}</flux:button>
                    </form>
                @else
                    <form method="POST" action="{{ route('biomarkers.pin', $biomarker) }}" class="flex flex-col gap-2">
                        @csrf

                        <flux:input name="note" :label="__('Notitie (optioneel)')" data-test="pin-note-input" />
                        <flux:button type="submit" variant="primary" size="sm" data-test="pin-biomarker-button">{{ __('Vastzetten') }}</flux:button>
                    </form>
                @endif
            </div>
        </section>

        <div class="overflow-hidden rounded-lg border border-neutral-200 dark:border-neutral-700" data-test="biomarker-history-table">
            <table class="w-full text-left text-sm">
                <thead class="bg-neutral-50 text-neutral-600 dark:bg-neutral-900 dark:text-neutral-300">
                    <tr>
                        <th class="p-3">{{ __('Datum') }}</th>
                        <th class="p-3">{{ __('Waarde') }}</th>
                        <th class="p-3">{{ __('Referentie') }}</th>
                        <th class="p-3">{{ __('Status') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($results as $result)
                        <tr class="border-t border-neutral-200 dark:border-neutral-700" data-test="biomarker-history-row">
                            <td class="p-3">{{ $result->bloodTest->test_date ? \App\Support\Format::dutchDate($result->bloodTest->test_date) : __('Geen datum') }}</td>
                            <td class="p-3 font-medium tabular-nums">{{ \App\Support\Format::biomarkerValue($result->value, $result->source_snippet, $result->value_comparator) }} {{ $result->unit }}</td>
                            <td class="p-3 tabular-nums text-neutral-600 dark:text-neutral-400">
                                {{ \App\Support\Format::referenceRange(
                                    $result->reference_min !== null ? (float) $result->reference_min : null,
                                    $result->reference_max !== null ? (float) $result->reference_max : null,
                                    $result->reference_unit ?: $result->unit,
                                ) }}
                            </td>
                            <td class="p-3">
                                <span @class([
                                    'font-medium',
                                    'text-amber-700 dark:text-amber-300' => in_array($result->status, ['high', 'low'], true),
                                    'text-emerald-700 dark:text-emerald-300' => $result->status === 'normal',
                                    'text-neutral-500 dark:text-neutral-400' => ! in_array($result->status, ['high', 'low', 'normal'], true),
                                ])>{{ (\App\Enums\BiomarkerStatus::tryFrom((string) $result->status) ?? \App\Enums\BiomarkerStatus::Unknown)->dutchLabel() }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="p-4 text-neutral-600 dark:text-neutral-400">{{ __('Nog geen bevestigde waarden.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</x-layouts::app>
