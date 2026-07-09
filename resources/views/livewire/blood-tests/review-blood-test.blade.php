@php
    $draftResults = $bloodTest->results
        ->whereNull('confirmed_at')
        ->where('entry_source', 'extracted');
    $confirmedResults = $bloodTest->results->whereNotNull('confirmed_at');
    $hasSourceDocuments = $bloodTest->documents->isNotEmpty();
    $latestExtractionRun = $bloodTest->extractionRuns->sortByDesc('created_at')->first();
    $hasDraftResults = $draftResults->isNotEmpty();
    $confirmedCount = $confirmedResults->count();
    $draftCount = $draftResults->count();
    $extractionFoundNoDrafts = $latestExtractionRun?->status === 'done'
        && (int) $latestExtractionRun->candidate_count === 0
        && ! $hasDraftResults;
    $showReviewStrip = $hasDraftResults
        || $latestExtractionRun?->status === 'failed'
        || $extractionFoundNoDrafts;
    $showManualForm = $hasDraftResults
        || $confirmedCount === 0
        || $extractionFoundNoDrafts
        || $latestExtractionRun?->status === 'failed'
        || $this->showManualEntryForm
        || $this->draftResultId !== null
        || $this->editingResultId !== null;
    $reviewGridColumnsClass = $showReviewStrip || $showManualForm
        ? 'lg:grid-cols-[1fr_1.2fr]'
        : '';
    $extractStageState = match ($latestExtractionRun?->status) {
        'done' => 'done',
        'failed' => 'failed',
        default => 'pending',
    };
    $valuesStageState = $confirmedCount > 0 || $draftCount > 0
        ? 'done'
        : 'pending';
    $statusStageState = $confirmedCount > 0 ? 'done' : 'pending';
    $trendStageState = $confirmedCount > 0 ? 'done' : 'pending';
    $progressStageClass = fn (string $state): string => match ($state) {
        'done' => 'bg-green-50 text-green-800 dark:bg-green-950 dark:text-green-200',
        'failed' => 'bg-red-50 text-red-800 dark:bg-red-950 dark:text-red-200',
        default => 'bg-neutral-100 text-neutral-700 dark:bg-neutral-900 dark:text-neutral-300',
    };
@endphp

<section class="mx-auto flex w-full max-w-5xl flex-col gap-8" data-test="blood-test-result">
    @if ($bloodTestOverview)
        @include('dashboard._blood-results-overview', ['summary' => $bloodTestOverview])
    @else
        <header class="flex flex-col gap-2">
            <flux:heading size="xl">{{ $bloodTest->title ?: __('Resultaat') }}</flux:heading>
            <flux:text>
                {{ $bloodTest->test_date ? \App\Support\Format::dutchDate($bloodTest->test_date) : __('Nog geen datum') }}
                · {{ $bloodTest->lab_name ?: __('Onbekend labo') }}
                · {{ $bloodTest->status }}
            </flux:text>
        </header>
    @endif

    <section class="rounded-lg border border-neutral-200 p-5 dark:border-neutral-700" data-test="intake-progress">
        <div class="grid gap-3 text-sm sm:grid-cols-4">
            <div class="rounded-md p-3 font-medium {{ $progressStageClass($extractStageState) }}" data-test="intake-progress-stage-extract" data-state="{{ $extractStageState }}">{{ __('extract') }}</div>
            <div class="rounded-md p-3 font-medium {{ $progressStageClass($valuesStageState) }}" data-test="intake-progress-stage-values" data-state="{{ $valuesStageState }}">{{ __('waarden') }}</div>
            <div class="rounded-md p-3 font-medium {{ $progressStageClass($statusStageState) }}" data-test="intake-progress-stage-status" data-state="{{ $statusStageState }}">{{ __('status') }}</div>
            <div class="rounded-md p-3 font-medium {{ $progressStageClass($trendStageState) }}" data-test="intake-progress-stage-trend" data-state="{{ $trendStageState }}">{{ __('trend') }}</div>
        </div>
    </section>

    <section class="space-y-4">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <flux:heading size="lg">{{ __('Bevestigde waarden') }}</flux:heading>
                <flux:text>{{ trans_choice(':count waarde klaar voor status en trends|:count waarden klaar voor status en trends', $confirmedCount, ['count' => $confirmedCount]) }}</flux:text>
            </div>

            @if ($draftCount > 0)
                <span class="text-sm font-medium text-amber-700 dark:text-amber-300">{{ trans_choice(':count rij vraagt review|:count rijen vragen review', $draftCount, ['count' => $draftCount]) }}</span>
            @endif
        </div>

        <div class="overflow-hidden rounded-lg border border-neutral-200 dark:border-neutral-700">
            <table class="w-full text-left text-sm">
                <thead class="bg-neutral-50 text-neutral-600 dark:bg-neutral-900 dark:text-neutral-300">
                    <tr>
                        <th class="p-3">{{ __('Biomarker') }}</th>
                        <th class="p-3">{{ __('Waarde') }}</th>
                        <th class="p-3">{{ __('Referentie') }}</th>
                        <th class="p-3">{{ __('Status') }}</th>
                        <th class="p-3 text-right">{{ __('Trend') }}</th>
                        <th class="p-3 text-right">{{ __('Acties') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($confirmedResults as $result)
                        @php
                            $trendSummary = $trendSummaries[$result->id] ?? [
                                'state' => 'first',
                                'label' => __('Eerste meting'),
                                'detail' => null,
                            ];
                        @endphp

                        <tr class="border-t border-neutral-200 dark:border-neutral-700" data-test="confirmed-value-row">
                            <td class="p-3">{{ $result->biomarker->name }}</td>
                            <td class="p-3">{{ \App\Support\Format::biomarkerValue($result->value, $result->source_snippet, $result->value_comparator) }} {{ $result->unit }}</td>
                            <td class="p-3 text-neutral-600 dark:text-neutral-400">
                                {{ \App\Support\Format::referenceRange(
                                    $result->reference_min !== null ? (float) $result->reference_min : null,
                                    $result->reference_max !== null ? (float) $result->reference_max : null,
                                    $result->reference_unit ?: $result->unit,
                                ) }}
                            </td>
                            <td class="p-3">
                                <div class="flex flex-col gap-1">
                                    <span @class([
                                        'font-medium',
                                        'text-amber-700 dark:text-amber-300' => in_array($result->status, ['high', 'low'], true),
                                        'text-emerald-700 dark:text-emerald-300' => $result->status === 'normal',
                                        'text-neutral-500 dark:text-neutral-400' => ! in_array($result->status, ['high', 'low', 'normal'], true),
                                    ])>{{ (\App\Enums\BiomarkerStatus::tryFrom((string) $result->status) ?? \App\Enums\BiomarkerStatus::Unknown)->dutchLabel() }}</span>
                                    @if ($result->entry_source === 'extracted')
                                        <span class="text-xs font-medium tracking-wide text-neutral-500 dark:text-neutral-400">
                                            {{ $hasSourceDocuments ? __('automatisch ingevuld uit PDF') : __('automatisch ingevuld uit PDF (bron verwijderd)') }}
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="p-3 text-right">
                                <div class="flex flex-col items-end gap-1" data-test="confirmed-value-trend" data-state="{{ $trendSummary['state'] }}">
                                    <span class="font-medium">{{ $trendSummary['label'] }}</span>

                                    @if ($trendSummary['detail'] !== null)
                                        <span class="text-xs text-neutral-500 dark:text-neutral-400">{{ $trendSummary['detail'] }}</span>
                                    @endif

                                    <a href="{{ route('biomarkers.show', $result->biomarker) }}" class="text-sm font-medium text-blue-700 underline dark:text-blue-300" data-test="open-trend-button">{{ __('Trend openen') }}</a>
                                </div>
                            </td>
                            <td class="p-3">
                                <div class="flex justify-end gap-2">
                                    <flux:button type="button" size="sm" wire:click="editConfirmedResult({{ $result->id }})" data-test="edit-confirmed-value-button">
                                        {{ __('Bewerken') }}
                                    </flux:button>
                                    <flux:button type="button" variant="danger" size="sm" wire:click="deleteConfirmedResult({{ $result->id }})" data-test="delete-confirmed-value-button">
                                        {{ __('Verwijderen') }}
                                    </flux:button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-4 text-neutral-600 dark:text-neutral-400">{{ __('Nog geen bevestigde waarden.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <div class="grid gap-6 {{ $reviewGridColumnsClass }}">
        <section class="space-y-4 rounded-lg border border-neutral-200 p-5 dark:border-neutral-700">
            <flux:heading size="lg">{{ __('Bronbestand') }}</flux:heading>

            @forelse ($bloodTest->documents as $document)
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between" data-test="source-document-row">
                    <a class="inline-flex text-sm font-medium text-blue-700 underline dark:text-blue-300" href="{{ route('blood-test-documents.download', $document) }}">
                        {{ $document->original_filename }}
                    </a>

                    <form method="POST" action="{{ route('blood-test-documents.destroy', $document) }}">
                        @csrf
                        @method('DELETE')

                        <flux:button type="submit" variant="danger" size="sm" data-test="delete-document-button">
                            {{ __('Document verwijderen') }}
                        </flux:button>
                    </form>
                </div>
            @empty
                <flux:text>{{ __('Geen bronbestand gekoppeld.') }}</flux:text>
            @endforelse
        </section>

        @if ($showReviewStrip)
            <section class="space-y-4 rounded-lg border border-neutral-200 p-5 dark:border-neutral-700" data-test="review-strip">
            <flux:heading size="lg">{{ __('Nog te reviewen') }}</flux:heading>

            @if ($latestExtractionRun?->status === 'failed')
                <flux:text data-test="extraction-status">{{ __('Extractie mislukt. Manuele invoer blijft beschikbaar.') }}</flux:text>
            @elseif ($extractionFoundNoDrafts)
                <flux:text data-test="extraction-status">{{ __('We konden geen waarden automatisch uit deze PDF lezen. Manuele invoer blijft beschikbaar.') }}</flux:text>
            @endif

            <div class="space-y-3">
                @forelse ($draftResults as $draft)
                    @php
                        $referenceUnit = $draft->reference_unit ?: $draft->unit;
                        $referenceRange = null;

                        if ($draft->reference_min !== null && $draft->reference_max !== null) {
                            $referenceRange = (float) $draft->reference_min.'-'.(float) $draft->reference_max.' '.$referenceUnit;
                        } elseif ($draft->reference_min !== null) {
                            $referenceRange = '>= '.(float) $draft->reference_min.' '.$referenceUnit;
                        } elseif ($draft->reference_max !== null) {
                            $referenceRange = '<= '.(float) $draft->reference_max.' '.$referenceUnit;
                        }

                        $confidenceLevel = $draft->extraction_confidence !== null && (float) $draft->extraction_confidence < \App\Domain\Intake\RunBloodTestExtraction::AUTO_CONFIRM_CONFIDENCE_THRESHOLD
                            ? 'low'
                            : 'standard';
                    @endphp

                    <article class="space-y-3 rounded-md border border-amber-200 bg-amber-50 p-3 text-sm dark:border-amber-900 dark:bg-amber-950/40" data-test="extracted-draft-row" data-state="draft" data-confidence="{{ $confidenceLevel }}">
                        <div class="grid gap-3 sm:grid-cols-2 sm:items-start">
                            <div>
                                <div class="text-xs font-medium uppercase tracking-wide text-neutral-500 dark:text-neutral-400">{{ __('Biomarker') }}</div>
                                <div class="font-medium">{{ $draft->biomarker?->name ?? $draft->extracted_name ?? __('Onbekende marker') }}</div>
                            </div>

                            <div data-test="draft-value">
                                <div class="text-xs font-medium uppercase tracking-wide text-neutral-500 dark:text-neutral-400">{{ __('Waarde') }}</div>
                                <div class="font-medium text-neutral-900 dark:text-white">{{ \App\Support\Format::biomarkerValue($draft->value, $draft->source_snippet, $draft->value_comparator) }} {{ $draft->unit }}</div>
                            </div>

                            <div data-test="draft-reference">
                                <div class="text-xs font-medium uppercase tracking-wide text-neutral-500 dark:text-neutral-400">{{ __('Referentie') }}</div>
                                <div class="text-neutral-700 dark:text-neutral-300">{{ $referenceRange ?? __('Onbekend') }}</div>
                            </div>

                            <div data-test="draft-review-state">
                                <div class="text-xs font-medium uppercase tracking-wide text-neutral-500 dark:text-neutral-400">{{ __('Reviewstatus') }}</div>
                                <div class="text-neutral-700 dark:text-neutral-300">{{ __('Bevestiging nodig') }}</div>
                            </div>

                            <div class="flex flex-wrap gap-2 sm:col-span-2 sm:justify-end">
                                <flux:button type="button" size="sm" wire:click="useDraft({{ $draft->id }})" data-test="use-draft-button">
                                    {{ __('Draft gebruiken') }}
                                </flux:button>
                                <flux:button type="button" variant="danger" size="sm" wire:click="deleteDraft({{ $draft->id }})" data-test="delete-draft-button">
                                    {{ __('Draft verwijderen') }}
                                </flux:button>
                            </div>
                        </div>

                        <div class="text-xs font-medium uppercase tracking-wide text-neutral-500 dark:text-neutral-400">
                            {{ __('Geextraheerd - bevestig eerst') }}
                            @if ($draft->extraction_confidence !== null && (float) $draft->extraction_confidence < \App\Domain\Intake\RunBloodTestExtraction::AUTO_CONFIRM_CONFIDENCE_THRESHOLD)
                                · {{ __('Lage betrouwbaarheid') }}
                            @endif
                        </div>
                    </article>
                @empty
                    @if ($latestExtractionRun === null)
                        <flux:text>{{ __('Nog geen geextraheerde drafts.') }}</flux:text>
                    @elseif ($latestExtractionRun->status === 'done' && (int) $latestExtractionRun->candidate_count > 0)
                        <flux:text>{{ __('Geen geextraheerde drafts gevonden.') }}</flux:text>
                    @endif
                @endforelse
            </div>
            </section>
        @endif

        @if (! $showManualForm)
            <div class="flex justify-end">
                <flux:button type="button" wire:click="showManualEntry" data-test="show-manual-entry-button">
                    {{ __('Waarde manueel toevoegen') }}
                </flux:button>
            </div>
        @else
            <form method="POST" wire:submit="confirmResult" class="space-y-4 rounded-lg border border-neutral-200 p-5 dark:border-neutral-700" data-test="confirm-biomarker-form">
            @csrf
            <div class="space-y-2">
                <flux:heading size="lg">{{ $hasDraftResults ? __('Geextraheerde waarden reviewen') : __('Waarden toevoegen') }}</flux:heading>
                <flux:text>
                    @if ($hasDraftResults && $confirmedCount > 0)
                        {{ __('Sommige waarden tellen al mee voor status en trends. Review alleen de resterende geextraheerde rijen.') }}
                    @elseif ($hasDraftResults && ! $hasSourceDocuments)
                        {{ __('Review de geextraheerde rijen; de bron-PDF is niet meer gekoppeld. Niets telt mee totdat je een rij bevestigt.') }}
                    @elseif ($hasDraftResults)
                        {{ __('Gelezen uit je PDF; niets telt mee totdat je elke waarde bevestigt.') }}
                    @elseif ($extractionFoundNoDrafts && $hasSourceDocuments)
                        {{ __('We konden geen waarden automatisch uit deze PDF lezen. Voeg ze toe naast het document hieronder.') }}
                    @elseif ($extractionFoundNoDrafts)
                        {{ __('We konden geen waarden automatisch uit deze PDF lezen. Voeg waarden manueel toe wanneer je klaar bent.') }}
                    @elseif (! $hasSourceDocuments)
                        {{ __('Voeg waarden manueel toe wanneer je klaar bent.') }}
                    @else
                        {{ __('Voeg waarden uit het bronbestand toe wanneer je klaar bent.') }}
                    @endif
                </flux:text>
            </div>

            <flux:select wire:model="resultForm.biomarker_id" :label="__('Bestaande biomarker')" data-test="existing-biomarker-select">
                <option value="">{{ __('Nieuwe aanmaken') }}</option>
                @foreach ($biomarkers as $biomarker)
                    <option value="{{ $biomarker->id }}">{{ $biomarker->name }}</option>
                @endforeach
            </flux:select>

            <flux:input wire:model="resultForm.name" :label="__('Naam biomarker')" data-test="biomarker-name-input" />
            <flux:input wire:model="resultForm.value" :label="__('Waarde')" inputmode="decimal" data-test="biomarker-value-input" />
            <flux:input wire:model="resultForm.unit" :label="__('Eenheid')" data-test="biomarker-unit-input" />

            <div class="grid gap-4 md:grid-cols-3">
                <flux:input wire:model="resultForm.reference_min" :label="__('Referentie min')" inputmode="decimal" data-test="reference-min-input" />
                <flux:input wire:model="resultForm.reference_max" :label="__('Referentie max')" inputmode="decimal" data-test="reference-max-input" />
                <flux:input wire:model="resultForm.reference_unit" :label="__('Referentie-eenheid')" data-test="reference-unit-input" />
            </div>

            <flux:textarea wire:model="resultForm.note" :label="__('Notitie')" data-test="result-note-input" />
            <div class="flex flex-wrap gap-3">
                <flux:button type="submit" variant="primary" data-test="confirm-value-button">{{ $hasDraftResults ? __('Waarde bevestigen') : __('Waarde toevoegen') }}</flux:button>

                @if (! $hasDraftResults && $confirmedCount > 0)
                    <flux:button type="button" wire:click="cancelManualEntry" data-test="cancel-manual-entry-button">
                        {{ __('Annuleren') }}
                    </flux:button>
                @endif
            </div>
            </form>
        @endif
    </div>

    <section class="space-y-4" data-test="blood-test-context-notes">
        <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
            <flux:heading size="lg">{{ __('Contextnotities') }}</flux:heading>
            <flux:button :href="route('context-notes.index')" variant="outline">{{ __('Contextnotitie toevoegen') }}</flux:button>
        </div>

        <div class="space-y-3">
            @forelse ($bloodTest->contextNotes as $note)
                <article class="rounded-lg border border-neutral-200 p-4 text-sm dark:border-neutral-700" data-test="blood-test-context-note-row">
                    <div class="font-medium">{{ \App\Support\Format::dutchDate($note->note_date) }} · {{ ucfirst($note->category->value) }}</div>
                    <p class="mt-2 text-neutral-700 dark:text-neutral-300">{{ $note->body }}</p>
                </article>
            @empty
                <div class="rounded-lg border border-dashed border-neutral-300 p-6 text-sm text-neutral-600 dark:border-neutral-700 dark:text-neutral-400">
                    {{ __('Nog geen contextnotities gekoppeld aan deze bloedtest.') }}
                </div>
            @endforelse
        </div>
    </section>
</section>
