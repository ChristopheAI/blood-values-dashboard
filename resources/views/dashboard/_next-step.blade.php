<section class="rounded-lg border border-neutral-200 bg-white p-5 shadow-xs dark:border-neutral-700 dark:bg-neutral-900" data-test="dashboard-next-step">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="space-y-2">
            <flux:heading size="lg">{{ __('Volgende stap') }}</flux:heading>
            <div class="text-xl font-semibold text-neutral-900 dark:text-white">{{ __($nextStep['title']) }}</div>
            <flux:text>{{ __($nextStep['body']) }}</flux:text>
        </div>

        <flux:button :href="$nextStep['href']" variant="primary" class="shrink-0">
            {{ __($nextStep['action']) }}
        </flux:button>
    </div>
</section>
