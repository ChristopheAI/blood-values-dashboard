@php
    $rows = collect($biomarkers);
    $attentionRows = $rows->filter(fn (array $row): bool => in_array($row['status'], ['low', 'high'], true))->values();
    $normalRows = $rows->where('status', 'normal')->values();
    $unknownRows = $rows->where('status', 'unknown')->values();

    // Eén statusvocabulaire, mét het pijltje dat elke NL/BE-patiënt van het
    // labverslag kent — de enum is de ene bron voor alle oppervlakken.
    $statusPill = fn (string $status): string => (\App\Enums\BiomarkerStatus::tryFrom($status) ?? \App\Enums\BiomarkerStatus::Unknown)->dutchLabel();

    // Getallenlijn-posities draaien op de (float)-cast van de waarde, nooit op de string.
    // Een detectielimiet ('<40') of kwalitatieve waarde ('Negatief') krijgt geen
    // markeerpunt: de marker zou een grens- of niet-numerieke waarde als exacte meting tonen.
    // Een referentie in een andere eenheid krijgt evenmin een band: de geometrie zou liegen.
    $rangeBar = function (array $row): ?array {
        if (($row['is_detection_limit'] ?? false) || ! is_numeric($row['value']) || ($row['reference_unit_mismatch'] ?? false)) {
            return null;
        }

        $min = $row['ref_min'];
        $max = $row['ref_max'];

        if ($min === null || $max === null || $max <= $min) {
            return null;
        }

        $value = (float) $row['value'];
        $span = $max - $min;
        $scaleMin = min($min, $value) - ($span * 0.15);
        $scaleMax = max($max, $value) + ($span * 0.15);

        if ($min >= 0 && $value >= 0) {
            $scaleMin = max(0.0, $scaleMin);
        }

        if ($scaleMax <= $scaleMin) {
            $scaleMax = $scaleMin + 1;
        }

        $positionFor = fn (float $point): float => max(0.0, min(100.0, (($point - $scaleMin) / ($scaleMax - $scaleMin)) * 100));

        return [
            'position' => number_format($positionFor($value), 2, '.', ''),
            'normalStart' => number_format($positionFor($min), 2, '.', ''),
            'normalWidth' => number_format($positionFor($max) - $positionFor($min), 2, '.', ''),
            'minLabel' => \App\Support\Format::number($min),
            'maxLabel' => \App\Support\Format::number($max),
        ];
    };

    $valueLabel = fn (array $row): string => $row['valueLabel'].($row['unit'] ? ' '.$row['unit'] : '');

    // Waarom deze rij geen status heeft — de ene zin die de schijnbare
    // tegenspraak ('<50' naast 'Referentie: ≤ 30') oplost.
    $noStatusReason = function (array $row): ?string {
        if ($row['is_detection_limit'] ?? false) {
            return $row['valueLabel'].($row['unit'] ? ' '.$row['unit'] : '').' is een meetgrens van het lab, geen exacte meting — daarom tonen we geen status.';
        }

        if ($row['no_reference'] ?? false) {
            return 'Geen referentie ingevuld — daarom tonen we geen status.';
        }

        if ($row['reference_unit_mismatch'] ?? false) {
            return 'De referentie is opgegeven in een andere eenheid dan de meting — daarom tonen we geen status.';
        }

        return null;
    };
@endphp

<section class="mx-auto flex w-full max-w-5xl flex-col gap-8" data-test="confirmed-biomarker-overview">
    <header class="flex flex-col gap-2">
        <flux:heading size="xl">{{ __('Bevestigde bloedwaarden') }}</flux:heading>
        <flux:text>
            {{ __('Persoonlijk overzicht van al je bevestigde waarden. Status op basis van de ingevoerde referentierange — bespreek je waarden met je arts.') }}
        </flux:text>
    </header>

    @if ($rows->isEmpty())
        <div class="rounded-lg border border-dashed border-neutral-300 p-8 text-center text-sm text-neutral-600 dark:border-neutral-700 dark:text-neutral-400" data-test="confirmed-overview-empty">
            {{ __('Nog geen bevestigde waarden. Waarden verschijnen hier na bevestiging vanuit een bloedtest.') }}
        </div>
    @else
        <section class="rounded-lg border border-neutral-200 bg-white p-5 shadow-xs dark:border-neutral-700 dark:bg-neutral-900" data-test="confirmed-overview-summary">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="space-y-1">
                    <div class="text-xl font-semibold text-neutral-900 dark:text-white">
                        {{ $rows->count() }} {{ $rows->count() === 1 ? __('biomarker') : __('biomarkers') }}
                    </div>
                    <p class="text-sm text-neutral-600 dark:text-neutral-400">
                        {{ __('Laatste bevestigde waarde per biomarker, gebaseerd op') }}
                        {{ $measurementCount }} {{ $measurementCount === 1 ? __('bevestigde waarde') : __('bevestigde waarden') }}.
                    </p>
                </div>

                <dl class="grid grid-cols-2 gap-3 text-sm sm:grid-cols-4">
                    <div class="rounded-md border border-neutral-200 px-3 py-2 dark:border-neutral-700">
                        <dt class="text-neutral-600 dark:text-neutral-400">{{ __('laag') }}</dt>
                        <dd class="font-semibold tabular-nums text-neutral-900 dark:text-white">{{ $rows->where('status', 'low')->count() }}</dd>
                    </div>
                    <div class="rounded-md border border-neutral-200 px-3 py-2 dark:border-neutral-700">
                        <dt class="text-neutral-600 dark:text-neutral-400">{{ __('hoog') }}</dt>
                        <dd class="font-semibold tabular-nums text-neutral-900 dark:text-white">{{ $rows->where('status', 'high')->count() }}</dd>
                    </div>
                    <div class="rounded-md border border-neutral-200 px-3 py-2 dark:border-neutral-700">
                        <dt class="text-neutral-600 dark:text-neutral-400">{{ __('normaal') }}</dt>
                        <dd class="font-semibold tabular-nums text-neutral-900 dark:text-white">{{ $normalRows->count() }}</dd>
                    </div>
                    <div class="rounded-md border border-neutral-200 px-3 py-2 dark:border-neutral-700">
                        <dt class="text-neutral-600 dark:text-neutral-400">{{ __('geen status') }}</dt>
                        <dd class="font-semibold tabular-nums text-neutral-900 dark:text-white">{{ $unknownRows->count() }}</dd>
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
                    {{ __('Referentiewaarden verschillen per lab en zijn zo gekozen dat ook gezonde mensen er soms buiten vallen. Bespreek je waarden met je arts.') }}
                </p>

                @foreach ($attentionRows as $row)
                    <article class="rounded-lg border border-amber-300 bg-amber-50 p-5 shadow-xs dark:border-amber-800 dark:bg-amber-950/30" data-test="confirmed-attention-card">
                        {{-- Leesmodel 1-2: naam, dan het pijltje + statuswoord, datum als context. --}}
                        <div class="flex flex-wrap items-center gap-x-3 gap-y-2">
                            <h3 class="text-lg font-semibold text-neutral-900 dark:text-white">{{ $row['label'] }}</h3>
                            <span class="inline-flex items-center rounded-full border-2 border-amber-400 px-2.5 py-0.5 text-sm font-bold text-amber-800 dark:border-amber-700 dark:text-amber-200">
                                {{ $statusPill($row['status']) }}
                            </span>
                            @if ($row['date'])
                                <span class="ms-auto text-xs text-neutral-500 dark:text-neutral-400" data-test="confirmed-row-date">{{ __('Gemeten op') }} {{ $row['date'] }}</span>
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
                        @php $bar = $rangeBar($row); @endphp

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
                                        <h3 class="font-semibold text-neutral-900 dark:text-white">{{ $row['label'] }}</h3>
                                        <span class="inline-flex items-center rounded-full border border-emerald-400 px-2 py-0.5 text-xs font-semibold text-emerald-800 dark:border-emerald-700 dark:text-emerald-200">
                                            {{ $statusPill($row['status']) }}
                                        </span>
                                    </div>
                                    @if ($row['date'])
                                        <p class="text-xs text-neutral-500 dark:text-neutral-400" data-test="confirmed-row-date">{{ __('Gemeten op') }} {{ $row['date'] }}</p>
                                    @endif
                                </div>

                                <div>
                                    @php $bar = $rangeBar($row); @endphp

                                    @if ($bar !== null)
                                        <div class="relative h-4" data-test="confirmed-range-bar">
                                            <div class="absolute inset-x-0 top-1.5 h-0.5 rounded-full bg-neutral-300/70 dark:bg-neutral-700"></div>
                                            <div class="absolute top-0 h-3.5 rounded border border-neutral-400/70 bg-white/70 dark:border-neutral-500 dark:bg-neutral-900/40" style="left: {{ $bar['normalStart'] }}%; width: {{ $bar['normalWidth'] }}%;"></div>
                                            <div class="absolute -top-0.5 h-4.5 w-0.5 -translate-x-1/2 rounded-sm bg-neutral-700 dark:bg-neutral-200" style="left: {{ $bar['position'] }}%;"></div>
                                        </div>
                                    @endif
                                </div>

                                {{-- Leesmodel 3: waarde en referentie als één rechtsgroep — nooit gescheiden. --}}
                                <div class="text-right tabular-nums">
                                    <div class="font-semibold text-neutral-900 dark:text-white">{{ $valueLabel($row) }}</div>
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
                                        <h3 class="font-semibold text-neutral-900 dark:text-white">{{ $row['label'] }}</h3>
                                        <span class="inline-flex items-center rounded-full border border-neutral-300 px-2 py-0.5 text-xs font-semibold text-neutral-600 dark:border-neutral-600 dark:text-neutral-300">
                                            {{ $statusPill($row['status']) }}
                                        </span>
                                    </div>
                                    @if ($row['date'])
                                        <p class="text-xs text-neutral-500 dark:text-neutral-400" data-test="confirmed-row-date">{{ __('Gemeten op') }} {{ $row['date'] }}</p>
                                    @endif
                                </div>

                                {{-- De ene zin die de schijnbare tegenspraak oplost. --}}
                                <div class="text-xs text-neutral-500 dark:text-neutral-400" data-test="confirmed-no-status-reason">
                                    {{ $noStatusReason($row) ?? '' }}
                                </div>

                                <div class="text-right tabular-nums">
                                    <div class="font-medium text-neutral-900 dark:text-white">{{ $valueLabel($row) }}</div>
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
