<x-layouts::app :title="__('Consultlijst')">
    <section class="mx-auto flex w-full max-w-6xl flex-col gap-8">
        <header class="flex flex-col gap-2">
            <flux:heading size="xl">{{ __('Consultlijst') }}</flux:heading>
            <flux:text>
                {{ __('Persoonlijke trackinggegevens uit bevestigde waarden. Bespreek dit overzicht met je arts.') }}
            </flux:text>
        </header>

        @include('consult-overview._pack')

        <details class="rounded-lg border border-neutral-200 p-5 print:hidden dark:border-neutral-700" open>
            <summary class="cursor-pointer text-sm font-medium text-neutral-900 dark:text-white">
                {{ __('Selectie en weergave aanpassen') }}
            </summary>

            <form method="POST" action="{{ route('consult-overview.index') }}" class="mt-5 space-y-5" data-test="consult-overview-form">
                @csrf

                <div class="grid gap-4 md:grid-cols-2">
                    <flux:input name="from" type="date" :label="__('Vanaf')" :value="$filters['from']" data-test="consult-from-input" />
                    <flux:input name="to" type="date" :label="__('Tot')" :value="$filters['to']" data-test="consult-to-input" />
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <fieldset class="space-y-2 rounded-lg border border-neutral-200 p-4 dark:border-neutral-700">
                        <legend class="px-1 text-sm font-medium">{{ __('Bloedtesten') }}</legend>

                        @forelse ($availableBloodTests as $bloodTest)
                            <label class="flex items-center gap-2 text-sm">
                                <input
                                    type="checkbox"
                                    name="blood_test_ids[]"
                                    value="{{ $bloodTest->id }}"
                                    @checked(in_array($bloodTest->id, $filters['blood_test_ids'], true))
                                    data-test="consult-blood-test-checkbox"
                                >
                                <span>{{ $bloodTest->title ?: ($bloodTest->test_date ? \App\Support\Format::dutchDate($bloodTest->test_date) : __('Bloedtest zonder titel')) }}</span>
                            </label>
                        @empty
                            <flux:text>{{ __('Nog geen bloedtesten beschikbaar.') }}</flux:text>
                        @endforelse
                    </fieldset>

                    <fieldset class="space-y-2 rounded-lg border border-neutral-200 p-4 dark:border-neutral-700">
                        <legend class="px-1 text-sm font-medium">{{ __('Opnemen in consultlijst') }}</legend>

                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="include_pinned" value="1" @checked($filters['include_pinned']) data-test="include-pinned-checkbox">
                            <span>{{ __('Gevolgde biomarkers') }}</span>
                        </label>
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="include_attention" value="1" @checked($filters['include_attention']) data-test="include-attention-checkbox">
                            <span>{{ __('Lage, hoge en onbekende bevestigde waarden') }}</span>
                        </label>
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="include_normal" value="1" @checked($filters['include_normal']) data-test="include-normal-checkbox">
                            <span>{{ __('Normale bevestigde waarden') }}</span>
                        </label>
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="include_trends" value="1" @checked($filters['include_trends']) data-test="include-trends-checkbox">
                            <span>{{ __('Tijdlijn van bevestigde waarden') }}</span>
                        </label>
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="include_context" value="1" @checked($filters['include_context']) data-test="include-context-checkbox">
                            <span>{{ __('Contextnotities') }}</span>
                        </label>
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="include_source_documents" value="1" @checked($filters['include_source_documents']) data-test="include-source-documents-checkbox">
                            <span>{{ __('Bronbestanden') }}</span>
                        </label>
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="include_themes" value="1" @checked($filters['include_themes']) data-test="include-themes-checkbox">
                            <span>{{ __('Waarden per thema') }}</span>
                        </label>
                    </fieldset>
                </div>

                <flux:textarea name="questions" :label="__('Vragen voor de arts')" data-test="consult-questions-input">{{ $filters['questions'] }}</flux:textarea>

                <div class="flex flex-wrap gap-3">
                    <flux:button type="submit" variant="primary" data-test="build-consult-overview-button">{{ __('Overzicht maken') }}</flux:button>
                </div>
            </form>
        </details>
    </section>
</x-layouts::app>
