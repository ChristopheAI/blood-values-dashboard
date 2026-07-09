@php
    // Groepering, tellingen en statuslogica leven in BuildBloodResultsOverview;
    // deze view rendert alleen. De builder levert per rij ook statusLabel
    // (de ene vocabulaire), bar-geometrie en de no-status-redencode.
    $attentionRows = $overview['attention'];
    $normalRows = $overview['normal'];
    $unknownRows = $overview['unknown'];
    $counts = $overview['counts'];
    $attentionCountCardClass = fn (int $count): string => $count > 0
        ? 'rounded-md border border-amber-300 bg-amber-50 px-3 py-2 ring-1 ring-amber-200 dark:border-amber-800 dark:bg-amber-950/30 dark:ring-amber-900'
        : 'rounded-md border border-neutral-200 px-3 py-2 dark:border-neutral-700';
    $attentionCountValueClass = fn (int $count): string => $count > 0
        ? 'font-bold tabular-nums text-amber-900 dark:text-amber-100'
        : 'font-semibold tabular-nums text-neutral-900 dark:text-white';

    // Waarom deze rij geen status heeft — de ene zin die de schijnbare
    // tegenspraak ('<50' naast 'Referentie: ≤ 30') oplost. De builder bepaalt
    // de reden; de zin zelf is kopij en hoort hier.
    $noStatusSentence = fn (array $row): ?string => match ($row['no_status_reason']) {
        'detection_limit' => $row['valueWithUnit'].' is een meetgrens van het lab, geen exacte meting — daarom tonen we geen status.',
        'no_reference' => 'Geen referentie ingevuld — daarom tonen we geen status.',
        'unit_mismatch' => 'De referentie is opgegeven in een andere eenheid dan de meting — daarom tonen we geen status.',
        'not_classified' => 'De waarde en de referentie konden niet automatisch vergeleken worden — daarom tonen we geen status.',
        default => null,
    };
@endphp

<section class="mx-auto flex w-full max-w-5xl flex-col gap-8" data-test="confirmed-biomarker-overview">
    <header class="flex flex-col gap-2">
        <flux:heading size="xl">{{ __('Bevestigde bloedwaarden') }}</flux:heading>
        <flux:text>
            {{ __('Persoonlijk overzicht van al je bevestigde waarden. Status op basis van de ingevoerde referentierange.') }}
        </flux:text>
    </header>

    @if ($counts['biomarkers'] === 0)
        <div class="flex flex-col items-center gap-4 rounded-lg border border-dashed border-neutral-300 p-8 text-center text-sm text-neutral-600 dark:border-neutral-700 dark:text-neutral-400" data-test="confirmed-overview-empty">
            <span>{{ __('Nog geen bevestigde waarden. Waarden verschijnen hier na bevestiging vanuit een bloedtest.') }}</span>
            <flux:button :href="route('blood-tests.index')" variant="outline" data-test="upload-first-blood-test-button">{{ __('Bloedtest uploaden') }}</flux:button>
        </div>
    @else
        <section class="rounded-lg border border-neutral-200 bg-white p-5 shadow-xs dark:border-neutral-700 dark:bg-neutral-900" data-test="confirmed-overview-summary">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="space-y-1">
                    <div class="text-xl font-semibold text-neutral-900 dark:text-white">
                        {{ $counts['biomarkers'] }} {{ $counts['biomarkers'] === 1 ? __('biomarker') : __('biomarkers') }}
                    </div>
                    <p class="text-sm text-neutral-600 dark:text-neutral-400">
                        {{ __('Laatste bevestigde waarde per biomarker, gebaseerd op') }}
                        {{ $counts['measurements'] }} {{ $counts['measurements'] === 1 ? __('bevestigde waarde') : __('bevestigde waarden') }}.
                    </p>
                </div>

                <dl class="grid grid-cols-2 gap-3 text-sm sm:grid-cols-4">
                    <div class="{{ $attentionCountCardClass((int) $counts['low']) }}" data-test="confirmed-summary-count-low">
                        <dt class="text-neutral-600 dark:text-neutral-400">{{ __('laag') }}</dt>
                        <dd class="{{ $attentionCountValueClass((int) $counts['low']) }}">{{ $counts['low'] }}</dd>
                    </div>
                    <div class="{{ $attentionCountCardClass((int) $counts['high']) }}" data-test="confirmed-summary-count-high">
                        <dt class="text-neutral-600 dark:text-neutral-400">{{ __('hoog') }}</dt>
                        <dd class="{{ $attentionCountValueClass((int) $counts['high']) }}">{{ $counts['high'] }}</dd>
                    </div>
                    <div class="rounded-md border border-neutral-200 px-3 py-2 dark:border-neutral-700">
                        <dt class="text-neutral-600 dark:text-neutral-400">{{ __('normaal') }}</dt>
                        <dd class="font-semibold tabular-nums text-neutral-900 dark:text-white">{{ $counts['normal'] }}</dd>
                    </div>
                    <div class="rounded-md border border-neutral-200 px-3 py-2 dark:border-neutral-700" data-test="confirmed-summary-count-unknown">
                        <dt class="text-neutral-600 dark:text-neutral-400">{{ __('geen status') }}</dt>
                        <dd class="font-semibold tabular-nums text-neutral-900 dark:text-white">{{ $counts['unknown'] }}</dd>
                    </div>
                </dl>
            </div>
        </section>

        @if ($attentionRows->isNotEmpty())
            <section class="space-y-4" data-test="confirmed-overview-attention">
                <flux:heading size="lg">
                    {{ $attentionRows->count() === 1 ? __('Deze waarde valt buiten de ingevoerde referentie') : __('Deze waarden vallen buiten de ingevoerde referentie') }}
                </flux:heading>

                {{-- Geruststelling, één keer per sectie: wat een referentiewaarde ís (geen interpretatie van déze waarde). --}}
                <p class="text-sm text-neutral-600 dark:text-neutral-400" data-test="confirmed-reference-context">
                    {{ __('Referentiewaarden verschillen per lab. De status gebruikt alleen de ingevoerde referentierange.') }}
                </p>

                @foreach ($attentionRows as $row)
                    <article class="rounded-lg border border-amber-300 bg-amber-50 p-5 shadow-xs dark:border-amber-800 dark:bg-amber-950/30" data-test="confirmed-attention-card">
                        {{-- Leesmodel 1-2: naam, dan het pijltje + statuswoord, datum als context. --}}
                        <div class="flex flex-wrap items-center gap-x-3 gap-y-2">
                            <a href="{{ route('biomarkers.show', $row['biomarker_id']) }}" class="text-lg font-semibold text-blue-700 underline dark:text-blue-300" data-test="open-biomarker-history">{{ $row['label'] }}</a>
                            <span class="inline-flex items-center rounded-full border-2 border-amber-400 px-2.5 py-0.5 text-sm font-bold text-amber-800 dark:border-amber-700 dark:text-amber-200">
                                {{ $row['statusLabel'] }}
                            </span>
                            @if ($row['date'])
                                <span class="ms-auto text-xs text-neutral-500 dark:text-neutral-400" data-test="confirmed-row-date">{{ __('Gemeten op') }} {{ $row['date'] }}</span>
                            @else
                                <span class="ms-auto text-xs text-neutral-500 dark:text-neutral-400" data-test="confirmed-row-date">{{ __('Geen datum') }}</span>
                            @endif
                        </div>

                        {{-- Leesmodel 3: waarde en referentie als één leesanker. --}}
                        <div class="mt-4 flex flex-wrap items-baseline gap-x-4 gap-y-1 tabular-nums">
                            <span>
                                <span class="text-2xl font-bold text-neutral-900 dark:text-white">{{ $row['valueLabel'] }}</span>
                                @if ($row['unit'])
                                    <span class="text-base font-medium text-neutral-700 dark:text-neutral-300">{{ $row['unit'] }}</span>
                                @endif
                            </span>
                            <span class="text-sm text-neutral-600 dark:text-neutral-400">{{ __('Referentie') }}: {{ $row['reference'] }}</span>
                        </div>

                        {{-- Leesmodel 4: richting + grootte als één feitelijke zin. --}}
                        <p class="mt-1 text-sm text-neutral-700 dark:text-neutral-300" data-test="confirmed-beyond-sentence">
                            @if ($row['beyond'] !== null)
                                {{ __('Ligt') }} {{ $row['beyond']['label'] }}{{ $row['unit'] ? ' '.$row['unit'] : '' }} {{ $row['beyond']['direction'] === 'above' ? __('boven de opgegeven referentie.') : __('onder de opgegeven referentie.') }}
                            @else
                                {{ $row['status'] === 'high' ? __('Ligt boven de opgegeven referentie.') : __('Ligt onder de opgegeven referentie.') }}
                            @endif
                        </p>

                        {{-- Leesmodel 5: de zone als bevestiging — omlijnde band met ticklabels, streep-marker. --}}
                        @php $bar = $row['bar']; @endphp

                        @if ($bar !== null)
                            @php
                                $position = (float) $bar['position'];
                                $pinnedLow = $position <= 0;
                                $pinnedHigh = $position >= 100;
                            @endphp

                            <div class="mt-5 pb-4" data-test="confirmed-range-bar">
                                <div class="relative h-7">
                                    <div class="absolute inset-x-0 top-3 h-1 rounded-full bg-neutral-300/70 dark:bg-neutral-700"></div>
                                    <div class="absolute top-1 h-5 rounded-md border-2 border-neutral-400/80 bg-white/70 dark:border-neutral-500 dark:bg-neutral-900/40" style="left: {{ $bar['normalStart'] }}%; width: {{ $bar['normalWidth'] }}%;"></div>
                                    @if ($pinnedLow || $pinnedHigh)
                                        <span class="absolute top-0 text-base font-bold leading-7 text-amber-700 dark:text-amber-300" style="{{ $pinnedLow ? 'left: 0;' : 'right: 0;' }}">{{ $pinnedLow ? '‹' : '›' }}</span>
                                    @else
                                        <div class="absolute top-0 h-7 w-1 -translate-x-1/2 rounded-sm bg-amber-600 dark:bg-amber-400" style="left: {{ $bar['position'] }}%;"></div>
                                    @endif
                                </div>
                                <div class="relative mt-0.5 h-4 text-[11px] tabular-nums text-neutral-500 dark:text-neutral-400">
                                    <span class="absolute -translate-x-1/2" style="left: {{ $bar['normalStart'] }}%;">{{ $bar['minLabel'] }}</span>
                                    <span class="absolute -translate-x-1/2" style="left: {{ number_format((float) $bar['normalStart'] + (float) $bar['normalWidth'], 2, '.', '') }}%;">{{ $bar['maxLabel'] }}</span>
                                    <span class="absolute -translate-x-1/2 uppercase tracking-wide" style="left: {{ number_format((float) $bar['normalStart'] + ((float) $bar['normalWidth'] / 2), 2, '.', '') }}%;">{{ __('referentie') }}</span>
                                </div>
                            </div>
                        @endif

                        {{-- Leesmodel 6: eigen historie mét datum-referent, feitelijk en zonder goed/slecht-kleur. --}}
                        @if ($row['history'] !== null)
                            <p class="mt-3 text-sm text-neutral-500 dark:text-neutral-400" data-test="confirmed-history">
                                {{ __('Vorige meting') }}@if ($row['history']['previousDate']) ({{ $row['history']['previousDate'] }})@endif: {{ $row['history']['previousLabel'] }}@if ($row['history']['delta']) — {{ __('nu') }} {{ $row['history']['delta'] }}@endif
                            </p>
                        @endif
                    </article>
                @endforeach
            </section>
        @endif

        @if ($normalRows->isNotEmpty())
            <section class="space-y-4" data-test="confirmed-overview-normal">
                <flux:heading size="lg">
                    {{ $normalRows->count() === 1 ? __('Deze waarde zit binnen de ingevoerde referentie') : __('Deze waarden zitten binnen de ingevoerde referentie') }}
                </flux:heading>

                <div class="overflow-hidden rounded-lg border border-emerald-200 bg-emerald-50/40 dark:border-emerald-900 dark:bg-emerald-950/20">
                    @foreach ($normalRows as $row)
                        <article class="border-b border-emerald-100 p-4 last:border-b-0 dark:border-emerald-950" data-test="confirmed-normal-row">
                            <div class="grid gap-3 md:grid-cols-[minmax(0,1.2fr)_minmax(9rem,0.8fr)_auto] md:items-center">
                                <div class="min-w-0 space-y-1">
                                    <div class="flex min-w-0 flex-wrap items-center gap-2">
                                        <a href="{{ route('biomarkers.show', $row['biomarker_id']) }}" class="font-semibold text-blue-700 underline dark:text-blue-300" data-test="open-biomarker-history">{{ $row['label'] }}</a>
                                        <span class="inline-flex items-center rounded-full border border-emerald-400 px-2 py-0.5 text-xs font-semibold text-emerald-800 dark:border-emerald-700 dark:text-emerald-200">
                                            {{ $row['statusLabel'] }}
                                        </span>
                                    </div>
                                    @if ($row['date'])
                                        <p class="text-xs text-neutral-500 dark:text-neutral-400" data-test="confirmed-row-date">{{ __('Gemeten op') }} {{ $row['date'] }}</p>
                                    @else
                                        <p class="text-xs text-neutral-500 dark:text-neutral-400" data-test="confirmed-row-date">{{ __('Geen datum') }}</p>
                                    @endif
                                </div>

                                <div>
                                    @php $bar = $row['bar']; @endphp

                                    @if ($bar !== null)
                                        <div class="relative h-4" data-test="confirmed-range-bar">
                                            <div class="absolute inset-x-0 top-1.5 h-0.5 rounded-full bg-neutral-300/70 dark:bg-neutral-700"></div>
                                            <div class="absolute top-0 h-3.5 rounded border border-neutral-400/70 bg-white/70 dark:border-neutral-500 dark:bg-neutral-900/40" style="left: {{ $bar['normalStart'] }}%; width: {{ $bar['normalWidth'] }}%;"></div>
                                            <div class="absolute -top-0.5 h-4.5 w-0.5 -translate-x-1/2 rounded-sm bg-neutral-700 dark:bg-neutral-200" style="left: {{ $bar['position'] }}%;"></div>
                                        </div>
                                        <div class="relative mt-1 h-3 text-[10px] tabular-nums text-neutral-500 dark:text-neutral-400" data-test="confirmed-range-bound-labels">
                                            <span class="absolute -translate-x-1/2" style="left: {{ $bar['normalStart'] }}%;">{{ $bar['minLabel'] }}</span>
                                            <span class="absolute -translate-x-1/2" style="left: {{ number_format((float) $bar['normalStart'] + (float) $bar['normalWidth'], 2, '.', '') }}%;">{{ $bar['maxLabel'] }}</span>
                                        </div>
                                    @endif
                                </div>

                                {{-- Leesmodel 3: waarde en referentie als één rechtsgroep — nooit gescheiden. --}}
                                <div class="text-right tabular-nums">
                                    <div class="font-semibold text-neutral-900 dark:text-white">{{ $row['valueWithUnit'] }}</div>
                                    <div class="text-xs text-neutral-500 dark:text-neutral-400">{{ __('Referentie') }}: {{ $row['reference'] }}</div>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>
        @endif

        @if ($unknownRows->isNotEmpty())
            <section class="space-y-4" data-test="confirmed-overview-unknown">
                <flux:heading size="lg">
                    {{ $unknownRows->count() === 1 ? __('Deze waarde heeft geen bruikbare referentie') : __('Deze waarden hebben geen bruikbare referentie') }}
                </flux:heading>

                <div class="overflow-hidden rounded-lg border border-neutral-200 bg-white dark:border-neutral-700 dark:bg-neutral-900">
                    @foreach ($unknownRows as $row)
                        <article class="border-b border-neutral-200 p-4 last:border-b-0 dark:border-neutral-700" data-test="confirmed-unknown-row">
                            <div class="grid gap-3 md:grid-cols-[minmax(0,1.2fr)_minmax(9rem,0.8fr)_auto] md:items-center">
                                <div class="min-w-0 space-y-1">
                                    <div class="flex min-w-0 flex-wrap items-center gap-2">
                                        <a href="{{ route('biomarkers.show', $row['biomarker_id']) }}" class="font-semibold text-blue-700 underline dark:text-blue-300" data-test="open-biomarker-history">{{ $row['label'] }}</a>
                                        <span class="inline-flex items-center rounded-full border border-neutral-300 px-2 py-0.5 text-xs font-semibold text-neutral-600 dark:border-neutral-600 dark:text-neutral-300">
                                            {{ $row['statusLabel'] }}
                                        </span>
                                    </div>
                                    @if ($row['date'])
                                        <p class="text-xs text-neutral-500 dark:text-neutral-400" data-test="confirmed-row-date">{{ __('Gemeten op') }} {{ $row['date'] }}</p>
                                    @else
                                        <p class="text-xs text-neutral-500 dark:text-neutral-400" data-test="confirmed-row-date">{{ __('Geen datum') }}</p>
                                    @endif
                                </div>

                                {{-- De ene zin die de schijnbare tegenspraak oplost. --}}
                                <div class="text-xs text-neutral-500 dark:text-neutral-400" data-test="confirmed-no-status-reason">
                                    {{ $noStatusSentence($row) ?? '' }}
                                </div>

                                <div class="text-right tabular-nums">
                                    <div class="font-medium text-neutral-900 dark:text-white">{{ $row['valueWithUnit'] }}</div>
                                    <div class="text-xs text-neutral-500 dark:text-neutral-400">{{ __('Referentie') }}: {{ $row['reference'] }}</div>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>
        @endif
    @endif
</section>
