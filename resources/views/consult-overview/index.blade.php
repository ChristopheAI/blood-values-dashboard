<x-layouts::app :title="__('Consult overview')">
    <section class="mx-auto flex w-full max-w-6xl flex-col gap-8">
        <header class="flex flex-col gap-2">
            <flux:heading size="xl">{{ __('Consult overview') }}</flux:heading>
            <flux:text>
                {{ __('Self-entered personal tracking data, not medical advice. Please discuss this overview with your doctor.') }}
            </flux:text>
        </header>

        <form method="POST" action="{{ route('consult-overview.index') }}" class="space-y-5 rounded-lg border border-neutral-200 p-5 print:hidden dark:border-neutral-700" data-test="consult-overview-form">
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
                        <input type="checkbox" name="include_normal" value="1" @checked($filters['include_normal']) data-test="include-normal-checkbox">
                        <span>{{ __('Normal confirmed values') }}</span>
                    </label>
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" name="include_trends" value="1" @checked($filters['include_trends']) data-test="include-trends-checkbox">
                        <span>{{ __('Confirmed value timeline') }}</span>
                    </label>
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" name="include_context" value="1" @checked($filters['include_context']) data-test="include-context-checkbox">
                        <span>{{ __('Context notes') }}</span>
                    </label>
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" name="include_source_documents" value="1" @checked($filters['include_source_documents']) data-test="include-source-documents-checkbox">
                        <span>{{ __('Source documents') }}</span>
                    </label>
                </fieldset>
            </div>

            <flux:textarea name="questions" :label="__('Questions for doctor')" data-test="consult-questions-input">{{ $filters['questions'] }}</flux:textarea>

            <div class="flex flex-wrap gap-3">
                <flux:button type="submit" variant="primary" data-test="build-consult-overview-button">{{ __('Build overview') }}</flux:button>
            </div>
        </form>

        @include('consult-overview._pack')
    </section>
</x-layouts::app>
