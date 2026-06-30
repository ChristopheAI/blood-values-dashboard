<section class="space-y-8" data-test="blood-results-overview">
    <header class="flex flex-col gap-2">
        <flux:heading size="xl">{{ __('Je bloedresultaten') }}</flux:heading>
        <flux:text>
            {{ $summary['collectedLabel'] }}
            @if ($summary['bloodTest']->lab_name)
                · {{ $summary['bloodTest']->lab_name }}
            @endif
            · {{ __('alleen bevestigde waarden') }}
        </flux:text>
    </header>

    <section class="rounded-lg border border-neutral-200 bg-white p-5 shadow-xs dark:border-neutral-700 dark:bg-neutral-900">
        <div class="flex flex-col gap-5 md:flex-row md:items-center md:justify-between">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                <div class="text-5xl font-semibold leading-none tabular-nums text-emerald-700 dark:text-emerald-300">
                    {{ $summary['normalCount'] }}<span class="text-2xl text-neutral-300 dark:text-neutral-600">/{{ $summary['confirmedCount'] }}</span>
                </div>

                <div class="space-y-1">
                    <div class="text-xl font-semibold text-neutral-900 dark:text-white">{{ $summary['normalSummaryLabel'] }}</div>
                    <p class="text-sm text-neutral-600 dark:text-neutral-400">
                        {{ $summary['attentionSummaryLabel'] }}.
                        {{ __('Status is gebaseerd op de referenties die uit je PDF zijn overgenomen.') }}
                    </p>
                </div>
            </div>

            <form method="POST" action="{{ route('consult-overview.index') }}" class="shrink-0">
                @csrf
                <input type="hidden" name="blood_test_ids[]" value="{{ $summary['bloodTest']->id }}">
                <input type="hidden" name="include_attention" value="1">
                <input type="hidden" name="include_normal" value="1">
                <input type="hidden" name="include_trends" value="1">
                <input type="hidden" name="include_source_documents" value="1">
                <input type="hidden" name="include_themes" value="1">
                <flux:button type="submit" variant="primary" data-test="latest-upload-consult-button">{{ __('Maak consultlijst') }}</flux:button>
            </form>
        </div>

        <div class="mt-5 border-t border-neutral-200 pt-4 text-sm text-neutral-700 dark:border-neutral-700 dark:text-neutral-300">
            <span class="font-medium text-neutral-900 dark:text-white">{{ $summary['bloodTest']->title ?: __('Bloedtest zonder titel') }}</span>
            <span class="text-neutral-500 dark:text-neutral-400">· {{ $summary['confirmedLabel'] }}</span>
        </div>
    </section>

    @if ($summary['attentionRows']->isNotEmpty())
        <section class="space-y-4" data-test="attention-values-section">
            <div class="flex items-center gap-3">
                <span class="flex size-9 items-center justify-center rounded-full bg-amber-100 text-amber-800 ring-1 ring-amber-200 dark:bg-amber-950/50 dark:text-amber-200 dark:ring-amber-900">
                    <svg class="size-5" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                        <path d="M10 6v5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                        <path d="M10 14.2h.01" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" />
                        <path d="M8.8 3.4 2.4 14.5c-.5.9.1 2 1.2 2h12.8c1.1 0 1.7-1.1 1.2-2L11.2 3.4c-.5-.9-1.9-.9-2.4 0Z" stroke="currentColor" stroke-width="1.5" />
                    </svg>
                </span>
                <flux:heading size="lg">{{ $summary['attentionHeading'] }}</flux:heading>
            </div>

            <div class="space-y-4">
                @foreach ($summary['featuredAttentionRows'] as $row)
                    <div data-test="attention-featured-row">
                        @include('dashboard._featured-attention-card', ['row' => $row])
                    </div>
                @endforeach

                @if ($summary['reviewRows']->isNotEmpty())
                    <div class="overflow-hidden rounded-lg border border-neutral-200 bg-white dark:border-neutral-700 dark:bg-neutral-900" data-test="attention-review-panel">
                        @foreach ($summary['reviewRows'] as $row)
                            @include('dashboard._compact-value-row', ['row' => $row])
                        @endforeach
                    </div>
                @endif
            </div>
        </section>
    @endif

    <section class="space-y-4" data-test="normal-values-section">
        <div class="flex items-center gap-3">
            <span class="flex size-9 items-center justify-center rounded-full bg-emerald-100 text-emerald-800 ring-1 ring-emerald-200 dark:bg-emerald-950/50 dark:text-emerald-200 dark:ring-emerald-900">
                <svg class="size-5" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                    <path d="m4.5 10.5 3.4 3.3 7.6-8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </span>
            <flux:heading size="lg">{{ $summary['normalHeading'] }}</flux:heading>
        </div>

        <div class="overflow-hidden rounded-lg border border-neutral-200 bg-white shadow-xs dark:border-neutral-700 dark:bg-neutral-900" data-test="normal-values-panel">
            @forelse ($summary['normalRows'] as $row)
                @include('dashboard._compact-value-row', ['row' => $row])
            @empty
                <div class="p-5 text-sm text-neutral-600 dark:text-neutral-400">
                    {{ __('Geen bevestigde waarden met status normaal in deze upload.') }}
                </div>
            @endforelse
        </div>
    </section>
</section>
