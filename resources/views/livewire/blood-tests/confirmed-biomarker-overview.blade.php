@php
    $rows = collect($biomarkers);
    $attentionRows = $rows->filter(fn (array $row): bool => in_array($row['status'], ['low', 'high'], true))->values();
    $normalRows = $rows->where('status', 'normal')->values();
    $unknownRows = $rows->where('status', 'unknown')->values();

    $statusWord = fn (string $status): string => match ($status) {
        'low' => 'laag',
        'high' => 'hoog',
        'normal' => 'normaal',
        default => 'onbekend',
    };

    // Getallenlijn-posities draaien op de (float)-cast van de waarde, nooit op de string.
    // Een detectielimiet ('<40') of kwalitatieve waarde ('Negatief') krijgt geen
    // markeerpunt: de dot zou een grens- of niet-numerieke waarde als exacte meting tonen.
    $rangeBar = function (array $row): ?array {
        if (($row['is_detection_limit'] ?? false) || ! is_numeric($row['value'])) {
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
        ];
    };

    $valueLabel = fn (array $row): string => $row['valueLabel'].($row['unit'] ? ' '.$row['unit'] : '');
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
                        <dt class="text-neutral-600 dark:text-neutral-400">{{ __('onbekend') }}</dt>
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

                @foreach ($attentionRows as $row)
                    <article class="rounded-lg border border-amber-300 bg-amber-50 p-5 shadow-xs dark:border-amber-800 dark:bg-amber-950/30" data-test="confirmed-attention-card">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div class="min-w-0 space-y-1">
                                <h3 class="text-lg font-semibold text-neutral-900 dark:text-white">{{ $row['label'] }}</h3>
                                <p class="text-sm text-neutral-700 dark:text-neutral-300">
                                    {{ $row['status'] === 'high' ? __('Ligt boven de opgegeven referentie.') : __('Ligt onder de opgegeven referentie.') }}
                                </p>
                                @if ($row['date'])
                                    <p class="text-xs text-neutral-500 dark:text-neutral-400" data-test="confirmed-row-date">{{ __('Gemeten op') }} {{ $row['date'] }}</p>
                                @endif
                            </div>

                            <div class="inline-flex w-fit shrink-0 rounded-md bg-amber-100 px-2.5 py-1 text-sm font-semibold text-amber-900 ring-1 ring-amber-200 dark:bg-amber-900/50 dark:text-amber-100 dark:ring-amber-800">
                                {{ $statusWord($row['status']) }}
                            </div>
                        </div>

                        <div class="mt-7 space-y-3">
                            @php $bar = $rangeBar($row); @endphp

                            @if ($bar !== null)
                                @php
                                    $position = (float) $bar['position'];
                                    $markerPositionClass = $position <= 0 || $position >= 100 ? '' : '-translate-x-1/2';
                                    $markerPositionStyle = $position <= 0
                                        ? 'left: 0;'
                                        : ($position >= 100 ? 'right: 0;' : 'left: '.$bar['position'].'%;');
                                @endphp

                                <div class="relative pt-8" data-test="confirmed-range-bar">
                                    <span class="absolute top-0 rounded-md bg-amber-500 px-2 py-1 text-sm font-semibold text-white shadow-sm tabular-nums {{ $markerPositionClass }}" style="{{ $markerPositionStyle }}">
                                        {{ $valueLabel($row) }}
                                    </span>
                                    <div class="relative h-3 rounded-full bg-neutral-200 dark:bg-neutral-800">
                                        <div class="absolute top-0 h-3 rounded-full bg-emerald-400 dark:bg-emerald-500" style="left: {{ $bar['normalStart'] }}%; width: {{ $bar['normalWidth'] }}%;"></div>
                                        <div class="absolute top-1/2 size-6 -translate-y-1/2 rounded-full border-4 border-white bg-amber-500 shadow-sm ring-4 ring-amber-100 dark:border-neutral-900 dark:ring-amber-950 {{ $markerPositionClass }}" style="{{ $markerPositionStyle }}"></div>
                                    </div>
                                </div>
                            @else
                                <div class="rounded-md border border-amber-200 bg-white/70 p-3 text-sm font-medium tabular-nums text-neutral-800 dark:border-amber-900 dark:bg-neutral-950/30 dark:text-neutral-200" data-test="confirmed-range-missing">
                                    {{ $valueLabel($row) }}
                                </div>
                            @endif

                            <div class="text-sm text-neutral-600 dark:text-neutral-400">
                                {{ __('Referentie') }}: {{ $row['reference'] }}
                            </div>
                        </div>
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
                            <div class="grid gap-3 md:grid-cols-[minmax(0,1.35fr)_minmax(9rem,0.7fr)_auto] md:items-center">
                                <div class="min-w-0 space-y-1">
                                    <div class="flex min-w-0 flex-wrap items-center gap-2">
                                        <h3 class="font-semibold text-neutral-900 dark:text-white">{{ $row['label'] }}</h3>
                                        <span class="rounded-md bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-800 ring-1 ring-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-100 dark:ring-emerald-900">
                                            {{ $statusWord($row['status']) }}
                                        </span>
                                    </div>
                                    @if ($row['date'])
                                        <p class="text-xs text-neutral-500 dark:text-neutral-400" data-test="confirmed-row-date">{{ __('Gemeten op') }} {{ $row['date'] }}</p>
                                    @endif
                                </div>

                                <div class="space-y-2">
                                    @php $bar = $rangeBar($row); @endphp

                                    @if ($bar !== null)
                                        @php
                                            $position = (float) $bar['position'];
                                            $markerPositionClass = $position <= 0 || $position >= 100 ? '' : '-translate-x-1/2';
                                            $markerPositionStyle = $position <= 0
                                                ? 'left: 0;'
                                                : ($position >= 100 ? 'right: 0;' : 'left: '.$bar['position'].'%;');
                                        @endphp

                                        <div class="relative h-2 rounded-full bg-neutral-200 dark:bg-neutral-800" data-test="confirmed-range-bar">
                                            <div class="absolute top-0 h-2 rounded-full bg-emerald-400 dark:bg-emerald-500" style="left: {{ $bar['normalStart'] }}%; width: {{ $bar['normalWidth'] }}%;"></div>
                                            <div class="absolute top-1/2 size-3 -translate-y-1/2 rounded-full bg-emerald-600 ring-2 ring-white dark:bg-emerald-400 dark:ring-neutral-900 {{ $markerPositionClass }}" style="{{ $markerPositionStyle }}"></div>
                                        </div>
                                    @endif

                                    <div class="text-xs text-neutral-500 dark:text-neutral-400">{{ __('Referentie') }}: {{ $row['reference'] }}</div>
                                </div>

                                <div class="font-medium tabular-nums text-neutral-900 dark:text-white md:text-right">
                                    {{ $valueLabel($row) }}
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
                            <div class="grid gap-3 md:grid-cols-[minmax(0,1.35fr)_minmax(9rem,0.7fr)_auto] md:items-center">
                                <div class="min-w-0 space-y-1">
                                    <div class="flex min-w-0 flex-wrap items-center gap-2">
                                        <h3 class="font-semibold text-neutral-900 dark:text-white">{{ $row['label'] }}</h3>
                                        <span class="rounded-md bg-neutral-100 px-2 py-0.5 text-xs font-medium text-neutral-700 ring-1 ring-neutral-200 dark:bg-neutral-800 dark:text-neutral-200 dark:ring-neutral-700">
                                            {{ $statusWord($row['status']) }}
                                        </span>
                                    </div>
                                    @if ($row['date'])
                                        <p class="text-xs text-neutral-500 dark:text-neutral-400" data-test="confirmed-row-date">{{ __('Gemeten op') }} {{ $row['date'] }}</p>
                                    @endif
                                </div>

                                <div class="text-xs text-neutral-500 dark:text-neutral-400">{{ __('Referentie') }}: {{ $row['reference'] }}</div>

                                <div class="font-medium tabular-nums text-neutral-900 dark:text-white md:text-right">
                                    {{ $valueLabel($row) }}
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>
        @endif
    @endif
</section>
