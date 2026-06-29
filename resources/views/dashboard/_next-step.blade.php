@php
    $stateIcon = fn (string $state): string => match ($state) {
        'ok' => '✓',
        'blocked' => '✗',
        'optional' => '○',
        default => '•',
    };

    $stateClass = fn (string $state): string => match ($state) {
        'ok' => 'text-emerald-700 dark:text-emerald-300',
        'blocked' => 'text-amber-800 dark:text-amber-200',
        'optional' => 'text-neutral-500 dark:text-neutral-400',
        default => 'text-neutral-700 dark:text-neutral-300',
    };

    $pillToneClass = fn (string $tone): string => match ($tone) {
        'rose' => 'bg-rose-100 text-rose-800 ring-rose-200 dark:bg-rose-950/50 dark:text-rose-100 dark:ring-rose-900/60',
        'emerald' => 'bg-emerald-100 text-emerald-800 ring-emerald-200 dark:bg-emerald-950/50 dark:text-emerald-100 dark:ring-emerald-900/60',
        'amber' => 'bg-amber-100 text-amber-800 ring-amber-200 dark:bg-amber-950/50 dark:text-amber-100 dark:ring-amber-900/60',
        'sky' => 'bg-sky-100 text-sky-800 ring-sky-200 dark:bg-sky-950/50 dark:text-sky-100 dark:ring-sky-900/60',
        default => 'bg-neutral-100 text-neutral-800 ring-neutral-200 dark:bg-neutral-800 dark:text-neutral-100 dark:ring-neutral-700',
    };

    $variant = $readiness['variant'] ?? 'default';
@endphp

<section
    @class([
        'rounded-xl border p-5 shadow-xs',
        'border-emerald-300 bg-emerald-50 dark:border-emerald-800 dark:bg-emerald-950/30' => $variant === 'success',
        'border-amber-300 bg-amber-50 dark:border-amber-800 dark:bg-amber-950/20' => $variant === 'review',
        'border-neutral-200 bg-white dark:border-neutral-700 dark:bg-neutral-900' => ! in_array($variant, ['success', 'review'], true),
    ])
    data-test="dashboard-next-step"
>
    <div class="space-y-5">
        <div class="space-y-2">
            <flux:heading size="lg">{{ $readiness['headline'] }}</flux:heading>

            @unless ($readiness['showConsultPost'])
                <div class="text-xl font-semibold text-neutral-900 dark:text-white" data-test="dashboard-next-step-title">{{ __($nextStep['title']) }}</div>
                <flux:text data-test="dashboard-next-step-body">{{ __($nextStep['body']) }}</flux:text>
            @endunless
        </div>

        <ul class="space-y-2 text-sm" data-test="dashboard-readiness-checklist">
            @foreach ($readiness['items'] as $item)
                <li @class(['flex gap-2', $stateClass($item['state'])]) data-test="dashboard-readiness-item-{{ $item['state'] }}">
                    <span class="font-semibold" aria-hidden="true">{{ $stateIcon($item['state']) }}</span>
                    <span>{{ $item['label'] }}</span>
                </li>
            @endforeach
        </ul>

        @if ($readiness['showConsultPost'] && $readiness['consultBloodTestId'])
            <div class="space-y-3" data-test="dashboard-consult-selection">
                <div class="text-sm font-semibold text-neutral-900 dark:text-white">{{ __('Jouw selectie') }}</div>

                <div class="flex flex-wrap gap-2" data-test="dashboard-consult-selection-pills">
                    @foreach ($readiness['selectionPills'] as $pill)
                        <span @class(['inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-xs font-semibold ring-1', $pillToneClass($pill['tone'])]) data-test="dashboard-selection-pill-{{ $pill['key'] }}">
                            <span>{{ $pill['label'] }}</span>
                            <span class="tabular-nums">{{ $pill['count'] }}</span>
                        </span>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-stretch">
            @if ($readiness['showConsultPost'] && $readiness['consultBloodTestId'])
                <form method="POST" action="{{ route('consult-overview.index') }}" class="w-full sm:min-w-[14rem] sm:flex-1" data-test="dashboard-consult-handoff-form">
                    @csrf
                    <input type="hidden" name="blood_test_ids[]" value="{{ $readiness['consultBloodTestId'] }}">
                    <input type="hidden" name="include_attention" value="1">
                    <input type="hidden" name="include_normal" value="1">
                    <input type="hidden" name="include_trends" value="1">
                    <input type="hidden" name="include_themes" value="1">
                    <input type="hidden" name="include_source_documents" value="1">
                    <flux:button type="submit" variant="primary" class="w-full" data-test="dashboard-consult-handoff-button">
                        {{ __('Consultlijst maken') }}
                    </flux:button>
                </form>

                <flux:button :href="route('consult-overview.index', $readiness['consultHandoffQuery'])" variant="outline" class="w-full sm:w-auto sm:shrink-0" data-test="dashboard-consult-customize-button">
                    {{ __('Selectie aanpassen') }}
                </flux:button>
            @else
                <flux:button :href="$nextStep['href']" variant="primary" class="w-full sm:w-auto sm:shrink-0" data-test="dashboard-next-step-button">
                    {{ __($nextStep['action']) }}
                </flux:button>
            @endif
        </div>
    </div>
</section>
