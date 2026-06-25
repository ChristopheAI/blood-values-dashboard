<section class="rounded-lg border border-neutral-200 bg-neutral-50 p-4 dark:border-neutral-700 dark:bg-neutral-900" data-test="dashboard-workstand-summary">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between" data-test="dashboard-dossier-status-line">
        <div class="text-sm font-medium text-neutral-900 dark:text-white">{{ __('Dossierstatus') }}</div>
        <div class="text-sm text-neutral-600 dark:text-neutral-300">{{ $statusLabel }}</div>
    </div>
</section>
