@if (empty($thematicOverview['categories']))
    <flux:text>{{ __('Geen bevestigde waarden met thema in deze selectie.') }}</flux:text>
@else
    <div class="space-y-6">
        @foreach ($thematicOverview['categories'] as $category)
            <article class="space-y-3" data-test="thematic-category-{{ $category['key'] }}">
                <div>
                    <flux:heading size="md">{{ $category['name'] }}</flux:heading>
                </div>

                <div class="divide-y divide-neutral-200 rounded-lg border border-neutral-200 dark:divide-neutral-700 dark:border-neutral-700">
                    @foreach ($category['markers'] as $marker)
                        <div class="grid gap-2 p-3 text-sm sm:grid-cols-[minmax(0,1fr)_auto_auto_auto] sm:items-center" data-test="thematic-marker-row">
                            <div class="min-w-0 space-y-1">
                                <div class="font-medium">{{ $marker['name'] }}</div>
                                @if ($marker['description'])
                                    <p class="text-neutral-600 print:hidden dark:text-neutral-400">{{ $marker['description'] }}</p>
                                @endif
                                <p class="text-neutral-500 dark:text-neutral-400">
                                    {{ $marker['testDate'] ?? __('Geen datum') }}
                                    · {{ __('Bron: :source', ['source' => $marker['bloodTestTitle']]) }}
                                </p>
                            </div>
                            <div class="tabular-nums font-medium">{{ $marker['valueLabel'] }} {{ $marker['unit'] }}</div>
                            <div>{{ $marker['statusLabel'] }}</div>
                            <div class="text-neutral-600 dark:text-neutral-400">{{ $marker['trendLabel'] }}</div>
                        </div>
                    @endforeach
                </div>
            </article>
        @endforeach
    </div>
@endif
