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

            <flux:button type="submit" variant="outline" data-test="export-consult-csv-button">{{ __('Export CSV') }}</flux:button>
        </form>

        <flux:button type="button" variant="outline" onclick="window.print()" data-test="print-consult-pack-button">{{ __('Print consult view') }}</flux:button>
    </div>

    @if ($filters['include_attention'])
        <section class="space-y-4 rounded-lg border border-amber-200 bg-amber-50/40 p-5 dark:border-amber-900 dark:bg-amber-950/20" data-test="consult-attention-values">
            <flux:heading size="lg">{{ __('Attention points') }}</flux:heading>

            <div class="overflow-hidden rounded-lg border border-neutral-200 bg-white dark:border-neutral-700 dark:bg-neutral-900">
                <table class="w-full text-left text-sm">
                    <thead class="bg-neutral-50 text-neutral-600 dark:bg-neutral-900 dark:text-neutral-300">
                        <tr>
                            <th class="p-3">{{ __('Date') }}</th>
                            <th class="p-3">{{ __('Biomarker') }}</th>
                            <th class="p-3">{{ __('Value') }}</th>
                            <th class="p-3">{{ __('Status') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($overview['attentionResults'] as $result)
                            <tr class="border-t border-neutral-200 dark:border-neutral-700">
                                <td class="p-3">{{ $result->bloodTest->test_date?->toDateString() ?? __('No date') }}</td>
                                <td class="p-3 font-medium">{{ $result->biomarker->name }}</td>
                                <td class="p-3 tabular-nums">{{ (float) $result->value }} {{ $result->unit }}</td>
                                <td class="p-3">{{ $result->status }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="p-4 text-neutral-600 dark:text-neutral-400">{{ __('No matching confirmed values.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    <section class="space-y-4 rounded-lg border border-neutral-200 p-5 dark:border-neutral-700" data-test="consult-selected-tests">
        <flux:heading size="lg">{{ __('Selected blood tests') }}</flux:heading>

        <div class="space-y-2">
            @forelse ($overview['bloodTests'] as $bloodTest)
                <div class="text-sm">{{ $bloodTest->test_date?->toDateString() ?? __('No date') }} · {{ $bloodTest->title ?: __('Untitled blood test') }}</div>
            @empty
                <flux:text>{{ __('No blood tests selected for this overview.') }}</flux:text>
            @endforelse
        </div>
    </section>

    @if ($filters['include_pinned'])
        <section class="space-y-4 rounded-lg border border-neutral-200 p-5 dark:border-neutral-700" data-test="consult-pinned-biomarkers">
            <flux:heading size="lg">{{ __('Pinned biomarkers') }}</flux:heading>

            <div class="space-y-3">
                @forelse ($overview['pinnedBiomarkers'] as $pin)
                    <article class="text-sm">
                        <div class="font-medium">{{ $pin->biomarker->name }}</div>
                        @if ($pin->note)
                            <div class="text-neutral-700 dark:text-neutral-300">{{ $pin->note }}</div>
                        @endif
                    </article>
                @empty
                    <flux:text>{{ __('No pinned biomarkers selected.') }}</flux:text>
                @endforelse
            </div>
        </section>
    @endif

    @if ($filters['include_normal'])
        <section class="space-y-4 rounded-lg border border-neutral-200 p-5 dark:border-neutral-700" data-test="consult-normal-values">
            <flux:heading size="lg">{{ __('Normal values') }}</flux:heading>

            <div class="overflow-hidden rounded-lg border border-neutral-200 bg-white dark:border-neutral-700 dark:bg-neutral-900">
                @forelse ($overview['normalResults'] as $result)
                    <div class="grid gap-2 border-t border-neutral-200 p-3 text-sm first:border-t-0 dark:border-neutral-700 sm:grid-cols-[1fr_auto_auto] sm:items-center">
                        <div>
                            <div class="font-medium">{{ $result->biomarker->name }}</div>
                            <div class="text-neutral-500 dark:text-neutral-400">{{ $result->bloodTest->test_date?->toDateString() ?? __('No date') }}</div>
                        </div>
                        <div class="tabular-nums">{{ (float) $result->value }} {{ $result->unit }}</div>
                        <div class="text-neutral-600 dark:text-neutral-400">{{ $result->status }}</div>
                    </div>
                @empty
                    <div class="p-4 text-sm text-neutral-600 dark:text-neutral-400">{{ __('No normal confirmed values in this selection.') }}</div>
                @endforelse
            </div>
        </section>
    @endif

    @if ($filters['include_trends'])
        <section class="space-y-4 rounded-lg border border-neutral-200 p-5 dark:border-neutral-700" data-test="consult-trend-changes">
            <flux:heading size="lg">{{ __('Changes since previous selected test') }}</flux:heading>

            <div class="space-y-2">
                @forelse ($overview['trendChanges'] as $change)
                    <div class="rounded-lg border border-neutral-200 p-3 text-sm dark:border-neutral-700">
                        <div class="font-medium">{{ $change['result']->biomarker->name }}</div>
                        <div class="text-neutral-700 dark:text-neutral-300">
                            {{ $change['previousResult']->bloodTest->test_date?->toDateString() ?? __('No date') }}
                            {{ (float) $change['previousResult']->value }} {{ $change['previousResult']->unit }}
                            ->
                            {{ $change['result']->bloodTest->test_date?->toDateString() ?? __('No date') }}
                            {{ (float) $change['result']->value }} {{ $change['result']->unit }}
                            ({{ $change['changeLabel'] }})
                        </div>
                    </div>
                @empty
                    <flux:text>{{ __('No comparable confirmed changes in this selection.') }}</flux:text>
                @endforelse
            </div>
        </section>

        <section class="space-y-4 rounded-lg border border-neutral-200 p-5 dark:border-neutral-700" data-test="consult-trend-values">
            <flux:heading size="lg">{{ __('Confirmed value timeline') }}</flux:heading>

            <div class="space-y-2">
                @forelse ($overview['trendResults'] as $result)
                    <div class="text-sm">
                        {{ $result->bloodTest->test_date?->toDateString() ?? __('No date') }}
                        · {{ $result->biomarker->name }}
                        · {{ (float) $result->value }} {{ $result->unit }}
                        · {{ $result->status }}
                    </div>
                @empty
                    <flux:text>{{ __('No confirmed values in this selection.') }}</flux:text>
                @endforelse
            </div>
        </section>
    @endif

    @if ($filters['include_source_documents'])
        <section class="space-y-4 rounded-lg border border-neutral-200 p-5 dark:border-neutral-700" data-test="consult-source-documents">
            <flux:heading size="lg">{{ __('Source documents') }}</flux:heading>

            <div class="space-y-2">
                @forelse ($overview['sourceDocuments'] as $document)
                    <div class="text-sm">
                        <a href="{{ route('blood-test-documents.download', $document) }}" class="font-medium text-blue-700 underline dark:text-blue-300">{{ $document->original_filename }}</a>
                        <span class="text-neutral-500 dark:text-neutral-400">
                            · {{ $document->bloodTest->test_date?->toDateString() ?? __('No date') }}
                            · {{ $document->bloodTest->title ?: __('Untitled blood test') }}
                        </span>
                    </div>
                @empty
                    <flux:text>{{ __('No source documents attached to this selection.') }}</flux:text>
                @endforelse
            </div>
        </section>
    @endif

    @if ($filters['include_context'])
        <section class="space-y-4 rounded-lg border border-neutral-200 p-5 dark:border-neutral-700" data-test="consult-context-notes">
            <flux:heading size="lg">{{ __('Context notes') }}</flux:heading>

            <div class="space-y-3">
                @forelse ($overview['contextNotes'] as $note)
                    <article class="text-sm">
                        <div class="font-medium">{{ $note->note_date->toDateString() }} · {{ ucfirst($note->category->value) }}</div>
                        <p class="mt-1 text-neutral-700 dark:text-neutral-300">{{ $note->body }}</p>
                    </article>
                @empty
                    <flux:text>{{ __('No context notes in this selection.') }}</flux:text>
                @endforelse
            </div>
        </section>
    @endif

    @if ($overview['questions'])
        <section class="space-y-4 rounded-lg border border-neutral-200 p-5 dark:border-neutral-700" data-test="consult-questions">
            <flux:heading size="lg">{{ __('Questions for doctor') }}</flux:heading>
            <p class="text-sm text-neutral-700 dark:text-neutral-300">{{ $overview['questions'] }}</p>
        </section>
    @endif
</section>
