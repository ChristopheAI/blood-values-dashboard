<section class="space-y-6" data-test="consult-pack">
    <div class="flex flex-wrap gap-3 print:hidden">
        <form method="POST" action="{{ route('consult-overview.csv') }}" data-test="export-consult-csv-form">
            @csrf

            @if ($filters['from'])
                <input type="hidden" name="from" value="{{ $filters['from'] }}">
            @endif

            @if ($filters['to'])
                <input type="hidden" name="to" value="{{ $filters['to'] }}">
            @endif

            @foreach ($filters['blood_test_ids'] as $bloodTestId)
                <input type="hidden" name="blood_test_ids[]" value="{{ $bloodTestId }}">
            @endforeach

            @if ($filters['include_pinned'])
                <input type="hidden" name="include_pinned" value="1">
            @endif

            @if ($filters['include_attention'])
                <input type="hidden" name="include_attention" value="1">
            @endif

            @if ($filters['include_normal'])
                <input type="hidden" name="include_normal" value="1">
            @endif

            @if ($filters['include_trends'])
                <input type="hidden" name="include_trends" value="1">
            @endif

            @if ($filters['include_context'])
                <input type="hidden" name="include_context" value="1">
            @endif

            @if ($filters['include_source_documents'])
                <input type="hidden" name="include_source_documents" value="1">
            @endif

            <flux:button type="submit" variant="outline" data-test="export-consult-csv-button">{{ __('CSV exporteren') }}</flux:button>
        </form>

        <flux:button type="button" variant="outline" onclick="window.print()" data-test="print-consult-pack-button">{{ __('Consultlijst printen') }}</flux:button>
    </div>

    @if ($filters['include_attention'])
        <section class="space-y-4 rounded-lg border border-amber-200 bg-amber-50/40 p-5 dark:border-amber-900 dark:bg-amber-950/20" data-test="consult-attention-values">
            <flux:heading size="lg">{{ __('Aandachtspunten') }}</flux:heading>

            <div class="overflow-hidden rounded-lg border border-neutral-200 bg-white dark:border-neutral-700 dark:bg-neutral-900">
                <table class="w-full text-left text-sm">
                    <thead class="bg-neutral-50 text-neutral-600 dark:bg-neutral-900 dark:text-neutral-300">
                        <tr>
                            <th class="p-3">{{ __('Datum') }}</th>
                            <th class="p-3">{{ __('Biomarker') }}</th>
                            <th class="p-3">{{ __('Waarde') }}</th>
                            <th class="p-3">{{ __('Status') }}</th>
                            <th class="p-3">{{ __('Herkomst') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($overview['attentionResults'] as $result)
                            <tr class="border-t border-neutral-200 dark:border-neutral-700">
                                <td class="p-3">{{ $result->bloodTest->test_date ? \App\Support\Format::dutchDate($result->bloodTest->test_date) : __('Geen datum') }}</td>
                                <td class="p-3 font-medium">{{ $result->biomarker->name }}</td>
                                <td class="p-3 tabular-nums">{{ \App\Support\Format::biomarkerValue($result->value, $result->source_snippet) }} {{ $result->unit }}</td>
                                <td class="p-3">{{ $result->status }}</td>
                                <td class="p-3 text-neutral-600 dark:text-neutral-400">
                                    <div>{{ __('Bron: :source', ['source' => $result->bloodTest->title ?: __('Bloedtest zonder titel')]) }}</div>
                                    <div>{{ __('Bevestigd: :date', ['date' => $result->confirmed_at ? \App\Support\Format::dutchDate($result->confirmed_at) : __('onbekend')]) }}</div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="p-4 text-neutral-600 dark:text-neutral-400">{{ __('Geen passende bevestigde waarden.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    @if ($filters['include_pinned'])
        <section class="space-y-4 rounded-lg border border-neutral-200 p-5 dark:border-neutral-700" data-test="consult-pinned-biomarkers">
            <flux:heading size="lg">{{ __('Gevolgde biomarkers') }}</flux:heading>

            <div class="space-y-3">
                @forelse ($overview['pinnedBiomarkers'] as $pin)
                    <article class="text-sm">
                        <div class="font-medium">{{ $pin->biomarker->name }}</div>
                        @if ($pin->note)
                            <div class="text-neutral-700 dark:text-neutral-300">{{ $pin->note }}</div>
                        @endif
                    </article>
                @empty
                    <flux:text>{{ __('Geen gevolgde biomarkers geselecteerd.') }}</flux:text>
                @endforelse
            </div>
        </section>
    @endif

    @if ($filters['include_normal'])
        <section class="space-y-4 rounded-lg border border-neutral-200 p-5 dark:border-neutral-700" data-test="consult-normal-values">
            <flux:heading size="lg">{{ __('Normale waarden compact') }}</flux:heading>

            <div class="overflow-hidden rounded-lg border border-neutral-200 bg-white dark:border-neutral-700 dark:bg-neutral-900">
                @forelse ($overview['normalResults'] as $result)
                    <div class="grid gap-2 border-t border-neutral-200 p-3 text-sm first:border-t-0 dark:border-neutral-700 sm:grid-cols-[1fr_auto_auto] sm:items-center">
                        <div>
                            <div class="font-medium">{{ $result->biomarker->name }}</div>
                            <div class="text-neutral-500 dark:text-neutral-400">
                                {{ $result->bloodTest->test_date ? \App\Support\Format::dutchDate($result->bloodTest->test_date) : __('Geen datum') }}
                                · {{ __('Bron: :source', ['source' => $result->bloodTest->title ?: __('Bloedtest zonder titel')]) }}
                                · {{ __('Bevestigd: :date', ['date' => $result->confirmed_at ? \App\Support\Format::dutchDate($result->confirmed_at) : __('onbekend')]) }}
                            </div>
                        </div>
                        <div class="tabular-nums">{{ \App\Support\Format::biomarkerValue($result->value, $result->source_snippet) }} {{ $result->unit }}</div>
                        <div class="text-neutral-600 dark:text-neutral-400">{{ $result->status }}</div>
                    </div>
                @empty
                    <div class="p-4 text-sm text-neutral-600 dark:text-neutral-400">{{ __('Geen normale bevestigde waarden in deze selectie.') }}</div>
                @endforelse
            </div>
        </section>
    @endif

    @if ($filters['include_trends'])
        <section class="space-y-4 rounded-lg border border-neutral-200 p-5 dark:border-neutral-700" data-test="consult-trend-changes">
            <flux:heading size="lg">{{ __('Wijzigingen tegenover vorige geselecteerde test') }}</flux:heading>

            <div class="space-y-2">
                @forelse ($overview['trendChanges'] as $change)
                    <div class="rounded-lg border border-neutral-200 p-3 text-sm dark:border-neutral-700">
                        <div class="font-medium">{{ $change['result']->biomarker->name }}</div>
                        <div class="text-neutral-700 dark:text-neutral-300">
                            {{ $change['previousResult']->bloodTest->test_date ? \App\Support\Format::dutchDate($change['previousResult']->bloodTest->test_date) : __('Geen datum') }}
                            {{ \App\Support\Format::biomarkerValue($change['previousResult']->value, $change['previousResult']->source_snippet) }} {{ $change['previousResult']->unit }}
                            ->
                            {{ $change['result']->bloodTest->test_date ? \App\Support\Format::dutchDate($change['result']->bloodTest->test_date) : __('Geen datum') }}
                            {{ \App\Support\Format::biomarkerValue($change['result']->value, $change['result']->source_snippet) }} {{ $change['result']->unit }}
                            ({{ $change['changeLabel'] }})
                        </div>
                    </div>
                @empty
                    <flux:text>{{ __('Geen vergelijkbare bevestigde wijzigingen in deze selectie.') }}</flux:text>
                @endforelse
            </div>
        </section>

        <section class="space-y-4 rounded-lg border border-neutral-200 p-5 dark:border-neutral-700" data-test="consult-trend-values">
            <flux:heading size="lg">{{ __('Tijdlijn van bevestigde waarden') }}</flux:heading>

            <div class="space-y-2">
                @forelse ($overview['trendResults'] as $result)
                    <div class="text-sm">
                        {{ $result->bloodTest->test_date ? \App\Support\Format::dutchDate($result->bloodTest->test_date) : __('Geen datum') }}
                        · {{ $result->biomarker->name }}
                        · {{ \App\Support\Format::biomarkerValue($result->value, $result->source_snippet) }} {{ $result->unit }}
                        · {{ $result->status }}
                    </div>
                @empty
                    <flux:text>{{ __('Geen bevestigde waarden in deze selectie.') }}</flux:text>
                @endforelse
            </div>
        </section>
    @endif

    @if ($filters['include_source_documents'])
        <section class="space-y-4 rounded-lg border border-neutral-200 p-5 dark:border-neutral-700" data-test="consult-source-documents">
            <flux:heading size="lg">{{ __('Bronbestanden') }}</flux:heading>

            <div class="space-y-2">
                @forelse ($overview['sourceDocuments'] as $document)
                    <div class="text-sm">
                        <a href="{{ route('blood-test-documents.download', $document) }}" class="font-medium text-blue-700 underline dark:text-blue-300">{{ $document->original_filename }}</a>
                        <span class="text-neutral-500 dark:text-neutral-400">
                            · {{ $document->bloodTest->test_date ? \App\Support\Format::dutchDate($document->bloodTest->test_date) : __('Geen datum') }}
                            · {{ $document->bloodTest->title ?: __('Bloedtest zonder titel') }}
                        </span>
                    </div>
                @empty
                    <flux:text>{{ __('Geen bronbestanden gekoppeld aan deze selectie.') }}</flux:text>
                @endforelse
            </div>
        </section>
    @endif

    <section class="space-y-4 rounded-lg border border-neutral-200 p-5 dark:border-neutral-700" data-test="consult-selected-tests">
        <flux:heading size="lg">{{ __('Geselecteerde bloedtesten') }}</flux:heading>

        <div class="space-y-2">
            @forelse ($overview['bloodTests'] as $bloodTest)
                <div class="text-sm">{{ $bloodTest->test_date ? \App\Support\Format::dutchDate($bloodTest->test_date) : __('Geen datum') }} · {{ $bloodTest->title ?: __('Bloedtest zonder titel') }}</div>
            @empty
                <flux:text>{{ __('Geen bloedtesten geselecteerd voor dit overzicht.') }}</flux:text>
            @endforelse
        </div>
    </section>

    @if ($filters['include_context'])
        <section class="space-y-4 rounded-lg border border-neutral-200 p-5 dark:border-neutral-700" data-test="consult-context-notes">
            <flux:heading size="lg">{{ __('Contextnotities') }}</flux:heading>

            <div class="space-y-3">
                @forelse ($overview['contextNotes'] as $note)
                    <article class="text-sm">
                        <div class="font-medium">{{ \App\Support\Format::dutchDate($note->note_date) }} · {{ ucfirst($note->category->value) }}</div>
                        <p class="mt-1 text-neutral-700 dark:text-neutral-300">{{ $note->body }}</p>
                    </article>
                @empty
                    <flux:text>{{ __('Geen contextnotities in deze selectie.') }}</flux:text>
                @endforelse
            </div>
        </section>
    @endif

    @if ($overview['questions'])
        <section class="space-y-4 rounded-lg border border-neutral-200 p-5 dark:border-neutral-700" data-test="consult-questions">
            <flux:heading size="lg">{{ __('Vragen voor de arts') }}</flux:heading>
            <p class="text-sm text-neutral-700 dark:text-neutral-300">{{ $overview['questions'] }}</p>
        </section>
    @endif
</section>
