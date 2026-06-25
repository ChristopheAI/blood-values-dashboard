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
@endphp

<section
    @class([
        'rounded-lg border bg-white p-5 shadow-xs dark:bg-neutral-900',
        'border-amber-300 dark:border-amber-800' => $nextStep['kind'] === 'review',
        'border-neutral-200 dark:border-neutral-700' => $nextStep['kind'] !== 'review',
    ])
    data-test="dashboard-next-step"
>
    <div class="space-y-4">
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
            <div class="flex flex-wrap gap-2 text-xs" data-test="dashboard-consult-selection-pills">
                @foreach ($readiness['selectionPills'] as $pill)
                    <span class="rounded-md bg-neutral-100 px-2 py-1 font-medium text-neutral-800 dark:bg-neutral-800 dark:text-neutral-100">
                        {{ $pill }}
                    </span>
                @endforeach
            </div>
        @endif

        <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">
            @if ($readiness['showConsultPost'] && $readiness['consultBloodTestId'])
                <form method="POST" action="{{ route('consult-overview.index') }}" class="shrink-0" data-test="dashboard-consult-handoff-form">
                    @csrf
                    <input type="hidden" name="blood_test_ids[]" value="{{ $readiness['consultBloodTestId'] }}">
                    <input type="hidden" name="include_attention" value="1">
                    <input type="hidden" name="include_normal" value="1">
                    <input type="hidden" name="include_trends" value="1">
                    <input type="hidden" name="include_source_documents" value="1">
                    <flux:button type="submit" variant="primary" data-test="dashboard-consult-handoff-button">
                        {{ __('Consultlijst maken') }}
                    </flux:button>
                </form>

                <flux:button :href="route('consult-overview.index')" variant="outline" class="shrink-0" data-test="dashboard-consult-customize-button">
                    {{ __('Selectie aanpassen') }}
                </flux:button>
            @else
                <flux:button :href="$nextStep['href']" variant="primary" class="shrink-0" data-test="dashboard-next-step-button">
                    {{ __($nextStep['action']) }}
                </flux:button>
            @endif
        </div>
    </div>
</section>
