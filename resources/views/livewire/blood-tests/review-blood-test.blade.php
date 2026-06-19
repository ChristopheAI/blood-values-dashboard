@php
    $draftResults = $bloodTest->results
        ->whereNull('confirmed_at')
        ->where('entry_source', 'extracted');
    $confirmedResults = $bloodTest->results->whereNotNull('confirmed_at');
    $latestExtractionRun = $bloodTest->extractionRuns->sortByDesc('created_at')->first();
    $hasDraftResults = $draftResults->isNotEmpty();
    $confirmedCount = $confirmedResults->count();
    $draftCount = $draftResults->count();
    $extractionFoundNoDrafts = $latestExtractionRun?->status === 'done'
        && (int) $latestExtractionRun->candidate_count === 0
        && ! $hasDraftResults;
@endphp

<section class="mx-auto flex w-full max-w-5xl flex-col gap-8" data-test="blood-test-result">
    <header class="flex flex-col gap-2">
        <flux:heading size="xl">{{ $bloodTest->title ?: __('Resultaat') }}</flux:heading>
        <flux:text>
            {{ $bloodTest->test_date?->toDateString() ?? __('No date yet') }}
            · {{ $bloodTest->lab_name ?: __('Unknown lab') }}
            · {{ $bloodTest->status }}
        </flux:text>
    </header>

    <section class="rounded-lg border border-neutral-200 p-5 dark:border-neutral-700" data-test="intake-progress">
        <div class="grid gap-3 text-sm sm:grid-cols-4">
            <div class="rounded-md bg-green-50 p-3 font-medium text-green-800 dark:bg-green-950 dark:text-green-200" data-test="intake-progress-stage-extract">{{ __('extract') }}</div>
            <div class="rounded-md bg-green-50 p-3 font-medium text-green-800 dark:bg-green-950 dark:text-green-200" data-test="intake-progress-stage-values">{{ __('waarden') }}</div>
            <div class="rounded-md bg-green-50 p-3 font-medium text-green-800 dark:bg-green-950 dark:text-green-200" data-test="intake-progress-stage-status">{{ __('status') }}</div>
            <div class="rounded-md bg-green-50 p-3 font-medium text-green-800 dark:bg-green-950 dark:text-green-200" data-test="intake-progress-stage-trend">{{ __('trend') }}</div>
        </div>
    </section>

    <section class="space-y-4">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <flux:heading size="lg">{{ __('Confirmed values') }}</flux:heading>
                <flux:text>{{ trans_choice(':count value ready for status and trends|:count values ready for status and trends', $confirmedCount, ['count' => $confirmedCount]) }}</flux:text>
            </div>

            @if ($draftCount > 0)
                <span class="text-sm font-medium text-amber-700 dark:text-amber-300">{{ trans_choice(':count row needs review|:count rows need review', $draftCount, ['count' => $draftCount]) }}</span>
            @endif
        </div>

        <div class="overflow-hidden rounded-lg border border-neutral-200 dark:border-neutral-700">
            <table class="w-full text-left text-sm">
                <thead class="bg-neutral-50 text-neutral-600 dark:bg-neutral-900 dark:text-neutral-300">
                    <tr>
                        <th class="p-3">{{ __('Biomarker') }}</th>
                        <th class="p-3">{{ __('Value') }}</th>
                        <th class="p-3">{{ __('Status') }}</th>
                        <th class="p-3 text-right">{{ __('Trend') }}</th>
                        <th class="p-3 text-right">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($confirmedResults as $result)
                        <tr class="border-t border-neutral-200 dark:border-neutral-700" data-test="confirmed-value-row">
                            <td class="p-3">{{ $result->biomarker->name }}</td>
                            <td class="p-3">{{ (float) $result->value }} {{ $result->unit }}</td>
                            <td class="p-3">
                                <div class="flex flex-col gap-1">
                                    <span>{{ $result->status }}</span>
                                    @if ($result->entry_source === 'extracted')
                                        <span class="text-xs font-medium tracking-wide text-neutral-500 dark:text-neutral-400">
                                            {{ __('auto-filled from PDF') }}
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="p-3 text-right">
                                <a href="{{ route('biomarkers.show', $result->biomarker) }}" class="text-sm font-medium text-blue-700 underline dark:text-blue-300" data-test="open-trend-button">{{ __('Open trend') }}</a>
                            </td>
                            <td class="p-3">
                                <div class="flex justify-end gap-2">
                                    <flux:button type="button" size="sm" wire:click="editConfirmedResult({{ $result->id }})" data-test="edit-confirmed-value-button">
                                        {{ __('Edit') }}
                                    </flux:button>
                                    <flux:button type="button" variant="danger" size="sm" wire:click="deleteConfirmedResult({{ $result->id }})" data-test="delete-confirmed-value-button">
                                        {{ __('Delete') }}
                                    </flux:button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="p-4 text-neutral-600 dark:text-neutral-400">{{ __('No confirmed values yet.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <div class="grid gap-6 lg:grid-cols-[1fr_1.2fr]">
        <section class="space-y-4 rounded-lg border border-neutral-200 p-5 dark:border-neutral-700">
            <flux:heading size="lg">{{ __('Source document') }}</flux:heading>

            @forelse ($bloodTest->documents as $document)
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between" data-test="source-document-row">
                    <a class="inline-flex text-sm font-medium text-blue-700 underline dark:text-blue-300" href="{{ route('blood-test-documents.download', $document) }}">
                        {{ $document->original_filename }}
                    </a>

                    <form method="POST" action="{{ route('blood-test-documents.destroy', $document) }}">
                        @csrf
                        @method('DELETE')

                        <flux:button type="submit" variant="danger" size="sm" data-test="delete-document-button">
                            {{ __('Delete document') }}
                        </flux:button>
                    </form>
                </div>
            @empty
                <flux:text>{{ __('No source document is attached.') }}</flux:text>
            @endforelse
        </section>

        <section class="space-y-4 rounded-lg border border-neutral-200 p-5 dark:border-neutral-700" data-test="review-strip">
            <flux:heading size="lg">{{ __('Review strip') }}</flux:heading>

            @if ($latestExtractionRun?->status === 'failed')
                <flux:text data-test="extraction-status">{{ __('Extraction failed. Manual entry is still available.') }}</flux:text>
            @elseif ($extractionFoundNoDrafts)
                <flux:text data-test="extraction-status">{{ __("We couldn't read values from this PDF automatically. Manual entry is still available.") }}</flux:text>
            @endif

            <div class="space-y-3">
                @forelse ($draftResults as $draft)
                    <article class="space-y-3 rounded-md border border-amber-200 bg-amber-50 p-3 text-sm dark:border-amber-900 dark:bg-amber-950/40" data-test="extracted-draft-row">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <div class="font-medium">{{ $draft->biomarker?->name ?? $draft->extracted_name ?? __('Unknown marker') }}</div>
                                <div class="text-neutral-600 dark:text-neutral-400">
                                    {{ (float) $draft->value }} {{ $draft->unit }}
                                    @if ($draft->reference_min !== null && $draft->reference_max !== null)
                                        · {{ (float) $draft->reference_min }}-{{ (float) $draft->reference_max }} {{ $draft->reference_unit }}
                                    @endif
                                </div>
                            </div>

                            <div class="flex flex-wrap gap-2">
                                <flux:button type="button" size="sm" wire:click="useDraft({{ $draft->id }})" data-test="use-draft-button">
                                    {{ __('Use draft') }}
                                </flux:button>
                                <flux:button type="button" variant="danger" size="sm" wire:click="deleteDraft({{ $draft->id }})" data-test="delete-draft-button">
                                    {{ __('Delete draft') }}
                                </flux:button>
                            </div>
                        </div>

                        <div class="text-xs font-medium uppercase tracking-wide text-neutral-500 dark:text-neutral-400">
                            {{ __('Extracted - please confirm') }}
                            @if ($draft->extraction_confidence !== null && (float) $draft->extraction_confidence < \App\Domain\Intake\RunBloodTestExtraction::AUTO_CONFIRM_CONFIDENCE_THRESHOLD)
                                · {{ __('Low confidence') }}
                            @endif
                        </div>
                    </article>
                @empty
                    @if ($latestExtractionRun === null)
                        <flux:text>{{ __('No extracted drafts yet.') }}</flux:text>
                    @else
                        <flux:text>{{ __('No below-threshold rows need review.') }}</flux:text>
                    @endif
                @endforelse
            </div>
        </section>

        <form wire:submit="confirmResult" class="space-y-4 rounded-lg border border-neutral-200 p-5 dark:border-neutral-700" data-test="confirm-biomarker-form">
            <div class="space-y-2">
                <flux:heading size="lg">{{ $hasDraftResults ? __('Review extracted values') : __('Add your values') }}</flux:heading>
                <flux:text>
                    @if ($hasDraftResults)
                        {{ __('Read from your PDF; nothing counts until you confirm each one.') }}
                    @elseif ($extractionFoundNoDrafts)
                        {{ __("We couldn't read values from this PDF automatically. Add them next to the document below.") }}
                    @else
                        {{ __('Add values from the source document when you are ready.') }}
                    @endif
                </flux:text>
            </div>

            <flux:select wire:model="resultForm.biomarker_id" :label="__('Existing biomarker')" data-test="existing-biomarker-select">
                <option value="">{{ __('Create new') }}</option>
                @foreach ($biomarkers as $biomarker)
                    <option value="{{ $biomarker->id }}">{{ $biomarker->name }}</option>
                @endforeach
            </flux:select>

            <flux:input wire:model="resultForm.name" :label="__('Biomarker name')" data-test="biomarker-name-input" />
            <flux:input wire:model="resultForm.value" :label="__('Value')" inputmode="decimal" data-test="biomarker-value-input" />
            <flux:input wire:model="resultForm.unit" :label="__('Unit')" data-test="biomarker-unit-input" />

            <div class="grid gap-4 md:grid-cols-3">
                <flux:input wire:model="resultForm.reference_min" :label="__('Range min')" inputmode="decimal" data-test="reference-min-input" />
                <flux:input wire:model="resultForm.reference_max" :label="__('Range max')" inputmode="decimal" data-test="reference-max-input" />
                <flux:input wire:model="resultForm.reference_unit" :label="__('Range unit')" data-test="reference-unit-input" />
            </div>

            <flux:textarea wire:model="resultForm.note" :label="__('Note')" data-test="result-note-input" />
            <flux:button type="submit" variant="primary" data-test="confirm-value-button">{{ $hasDraftResults ? __('Confirm value') : __('Add value') }}</flux:button>
        </form>
    </div>

    <section class="space-y-4" data-test="blood-test-context-notes">
        <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
            <flux:heading size="lg">{{ __('Context notes') }}</flux:heading>
            <flux:button :href="route('context-notes.index')" variant="outline">{{ __('Add context note') }}</flux:button>
        </div>

        <div class="space-y-3">
            @forelse ($bloodTest->contextNotes as $note)
                <article class="rounded-lg border border-neutral-200 p-4 text-sm dark:border-neutral-700" data-test="blood-test-context-note-row">
                    <div class="font-medium">{{ $note->note_date->toDateString() }} · {{ ucfirst($note->category->value) }}</div>
                    <p class="mt-2 text-neutral-700 dark:text-neutral-300">{{ $note->body }}</p>
                </article>
            @empty
                <div class="rounded-lg border border-dashed border-neutral-300 p-6 text-sm text-neutral-600 dark:border-neutral-700 dark:text-neutral-400">
                    {{ __('No context notes linked to this blood test yet.') }}
                </div>
            @endforelse
        </div>
    </section>
</section>
