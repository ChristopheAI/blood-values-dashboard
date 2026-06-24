<x-layouts::app :title="__('Dashboard')">
    <section class="mx-auto flex w-full max-w-5xl flex-col gap-8">
        @include('dashboard._next-step', ['nextStep' => $dashboardOverview['nextStep']])

        @if ($recentBloodTests->isEmpty())
            @include('blood-tests._upload-dropzone')
        @endif

        @include('dashboard._blood-test-timeline', ['bloodTests' => $dashboardOverview['bloodTests']])

        @if ($latestUploadSummary)
            <section class="space-y-4" data-test="dashboard-latest-confirmed-values">
                <flux:heading size="lg">{{ __('Laatste bevestigde waarden') }}</flux:heading>
                @include('dashboard._blood-results-overview', ['summary' => $latestUploadSummary])
            </section>
        @elseif (! $recentBloodTests->isEmpty())
            <section class="rounded-lg border border-neutral-200 p-5 dark:border-neutral-700" data-test="dashboard-no-confirmed-values">
                <flux:heading size="lg">{{ __('Nog geen bevestigde waarden') }}</flux:heading>
                <flux:text>{{ __('Open een bloedtest om waarden te bevestigen voordat ze in dashboard, trends of consult verschijnen.') }}</flux:text>
            </section>
        @endif

        <div class="grid gap-4 md:grid-cols-3" data-test="dashboard-supporting-links">
            <section class="space-y-4 rounded-lg border border-neutral-200 p-4 dark:border-neutral-700" data-test="dashboard-pinned-biomarkers">
                <flux:heading size="lg">{{ __('Gevolgde biomarkers') }}</flux:heading>

                <div class="space-y-3">
                    @forelse ($pinnedBiomarkers as $pin)
                        <a href="{{ route('biomarkers.show', $pin->biomarker) }}" class="block text-sm font-medium text-blue-700 underline dark:text-blue-300">
                            {{ $pin->biomarker->name }}
                        </a>
                    @empty
                        <flux:text>{{ __('Nog geen gevolgde biomarkers.') }}</flux:text>
                    @endforelse
                </div>
            </section>

            <section class="space-y-4 rounded-lg border border-neutral-200 p-4 dark:border-neutral-700" data-test="dashboard-next-reminder">
                <flux:heading size="lg">{{ __('Volgende herinnering') }}</flux:heading>

                @if ($nextReminder)
                    <div class="space-y-2 text-sm">
                        <div class="font-medium">{{ $nextReminder->title }}</div>
                        <div class="text-neutral-600 dark:text-neutral-400">{{ $nextReminder->due_date->toDateString() }}</div>

                        @if ($nextReminder->note)
                            <p class="text-neutral-700 dark:text-neutral-300">{{ $nextReminder->note }}</p>
                        @endif
                    </div>
                @else
                    <flux:text>{{ __('Nog geen herinneringen.') }}</flux:text>
                @endif
            </section>

            <section class="space-y-4 rounded-lg border border-neutral-200 p-4 dark:border-neutral-700" data-test="dashboard-quick-actions">
                <flux:heading size="lg">{{ __('Snelle acties') }}</flux:heading>

                <div class="flex flex-col gap-2">
                    <flux:button :href="route('blood-tests.index')" variant="primary">{{ __('Lab-PDF uploaden') }}</flux:button>
                    <flux:button :href="route('context-notes.index')" variant="outline">{{ __('Contextnotitie toevoegen') }}</flux:button>
                    <flux:button :href="route('reminders.index')" variant="outline">{{ __('Herinnering toevoegen') }}</flux:button>
                    <flux:button :href="route('consult-overview.index')" variant="outline">{{ __('Consultlijst voorbereiden') }}</flux:button>
                </div>
            </section>
        </div>
    </section>
</x-layouts::app>
