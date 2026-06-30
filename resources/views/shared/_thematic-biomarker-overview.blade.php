@if ($thematicOverview && count($thematicOverview['categories']) > 0)
    <div class="space-y-5">
        @foreach ($thematicOverview['categories'] as $category)
            <article class="space-y-3">
                <div>
                    <h4 class="text-base font-semibold text-neutral-900 dark:text-white">{{ $category['name'] }}</h4>
                </div>

                <div class="divide-y divide-neutral-200 rounded-lg border border-neutral-200 bg-white dark:divide-neutral-700 dark:border-neutral-700 dark:bg-neutral-900">
                    @foreach ($category['markers'] as $marker)
                        <div class="grid gap-2 p-3 text-sm sm:grid-cols-[minmax(0,1fr)_auto_auto_auto] sm:items-center" data-test="thematic-marker-row">
                            <div class="min-w-0 space-y-1">
                                <div class="font-medium text-neutral-900 dark:text-white">{{ $marker['name'] }}</div>

                                @if ($marker['description'])
                                    <p class="text-neutral-600 print:hidden dark:text-neutral-400">{{ $marker['description'] }}</p>
                                @endif

                                @if ($marker['bloodTestTitle'] || $marker['testDate'])
                                    <div class="text-xs text-neutral-500 dark:text-neutral-400">
                                        @if ($marker['testDate'])
                                            {{ \App\Support\Format::dutchDate($marker['testDate']) }}
                                        @endif
                                        @if ($marker['bloodTestTitle'])
                                            · {{ $marker['bloodTestTitle'] }}
                                        @endif
                                    </div>
                                @endif
                            </div>

                            <div class="tabular-nums text-neutral-900 dark:text-white">
                                {{ $marker['valueLabel'] }}@if ($marker['unit']) {{ $marker['unit'] }}@endif
                            </div>

                            <div class="text-neutral-600 dark:text-neutral-400">{{ $marker['statusLabel'] }}</div>
                            <div class="text-neutral-500 dark:text-neutral-400">{{ $marker['trendLabel'] ?? '—' }}</div>
                        </div>
                    @endforeach
                </div>
            </article>
        @endforeach
    </div>
@endif
