<x-layouts::app :title="__('Dashboard')">
    <section class="mx-auto flex w-full max-w-6xl flex-col gap-6">
        {{-- Gezondheid vóór boekhouding: het antwoord op "hoe sta ik ervoor?"
             (laatste test met aandachtswaarden) staat boven de procesbanner en
             de tellers, zodat een echte afwijkende waarde nooit wordt
             overschreeuwd door een routineuze review-stap. --}}
        @if (! $recentBloodTests->isEmpty())
            @include('dashboard._latest-blood-test', [
                'bloodTests' => $dashboardOverview['bloodTests'],
                'latestUploadSummary' => $latestUploadSummary,
            ])
        @endif

        @include('dashboard._next-step', [
            'nextStep' => $dashboardOverview['nextStep'],
            'readiness' => $dashboardOverview['readiness'],
        ])

        @include('dashboard._workstand-summary', ['workstand' => $dashboardOverview['workstand']])

        @if ($recentBloodTests->isEmpty())
            @include('blood-tests._upload-dropzone')
        @endif

        @include('dashboard._blood-test-timeline', ['bloodTests' => $dashboardOverview['bloodTests']])

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
                        <div class="text-neutral-600 dark:text-neutral-400">{{ \App\Support\Format::dutchDate($nextReminder->due_date) }}</div>

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
                    <flux:text class="text-sm text-neutral-600 dark:text-neutral-400">
                        {{ __('Consultlijst opent met je laatste bloedtest al voorgeselecteerd.') }}
                    </flux:text>
                </div>
            </section>
        </div>
    </section>
</x-layouts::app>
