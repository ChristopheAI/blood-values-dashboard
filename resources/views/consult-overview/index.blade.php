<x-layouts::app :title="__('Consult overview')">
    <section class="mx-auto flex w-full max-w-6xl flex-col gap-8">
        <header class="flex flex-col gap-2">
            <flux:heading size="xl">{{ __('Consult overview') }}</flux:heading>
            <flux:text>
                {{ __('Self-entered personal tracking data, not medical advice. Please discuss this overview with your doctor.') }}
            </flux:text>
        </header>

        <form method="POST" action="{{ route('consult-overview.index') }}" class="space-y-5 rounded-lg border border-neutral-200 p-5 dark:border-neutral-700" data-test="consult-overview-form">
            @csrf

            <div class="grid gap-4 md:grid-cols-2">
                <flux:input name="from" type="date" :label="__('From')" :value="$filters['from']" data-test="consult-from-input" />
                <flux:input name="to" type="date" :label="__('To')" :value="$filters['to']" data-test="consult-to-input" />
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <fieldset class="space-y-2 rounded-lg border border-neutral-200 p-4 dark:border-neutral-700">
                    <legend class="px-1 text-sm font-medium">{{ __('Blood tests') }}</legend>

                    @forelse ($availableBloodTests as $bloodTest)
                        <label class="flex items-center gap-2 text-sm">
                            <input
                                type="checkbox"
                                name="blood_test_ids[]"
                                value="{{ $bloodTest->id }}"
                                @checked(in_array($bloodTest->id, $filters['blood_test_ids'], true))
                                data-test="consult-blood-test-checkbox"
                            >
                            <span>{{ $bloodTest->title ?: $bloodTest->test_date?->toDateString() ?? __('Untitled blood test') }}</span>
                        </label>
                    @empty
                        <flux:text>{{ __('No blood tests available yet.') }}</flux:text>
                    @endforelse
                </fieldset>

                <fieldset class="space-y-2 rounded-lg border border-neutral-200 p-4 dark:border-neutral-700">
                    <legend class="px-1 text-sm font-medium">{{ __('Include') }}</legend>

                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" name="include_pinned" value="1" @checked($filters['include_pinned']) data-test="include-pinned-checkbox">
                        <span>{{ __('Pinned biomarkers') }}</span>
                    </label>
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" name="include_attention" value="1" @checked($filters['include_attention']) data-test="include-attention-checkbox">
                        <span>{{ __('Low, high, and unknown confirmed values') }}</span>
                    </label>
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" name="include_trends" value="1" @checked($filters['include_trends']) data-test="include-trends-checkbox">
                        <span>{{ __('Confirmed value timeline') }}</span>
                    </label>
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" name="include_context" value="1" @checked($filters['include_context']) data-test="include-context-checkbox">
                        <span>{{ __('Context notes') }}</span>
                    </label>
                </fieldset>
            </div>

            <flux:textarea name="questions" :label="__('Questions for doctor')" data-test="consult-questions-input">{{ $filters['questions'] }}</flux:textarea>

            <div class="flex flex-wrap gap-3">
                <flux:button type="submit" variant="primary" data-test="build-consult-overview-button">{{ __('Build overview') }}</flux:button>
            </div>
        </form>

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

            @if ($filters['include_trends'])
                <input type="hidden" name="include_trends" value="1">
            @endif

            @if ($filters['include_context'])
                <input type="hidden" name="include_context" value="1">
            @endif

            <flux:button type="submit" variant="outline" data-test="export-consult-csv-button">{{ __('Export CSV') }}</flux:button>
        </form>

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

        @if ($filters['include_attention'])
            <section class="space-y-4 rounded-lg border border-neutral-200 p-5 dark:border-neutral-700" data-test="consult-attention-values">
                <flux:heading size="lg">{{ __('Low, high, and unknown confirmed values') }}</flux:heading>

                <div class="overflow-hidden rounded-lg border border-neutral-200 dark:border-neutral-700">
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
                                    <td class="p-3">{{ $result->biomarker->name }}</td>
                                    <td class="p-3">{{ (float) $result->value }} {{ $result->unit }}</td>
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

        @if ($filters['include_trends'])
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
</x-layouts::app>
