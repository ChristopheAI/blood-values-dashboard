<x-layouts::app :title="__('Dashboard')">
    <section class="mx-auto flex w-full max-w-5xl flex-col gap-8">
        @if ($recentBloodTests->isEmpty())
            @include('blood-tests._upload-dropzone')

            @if ($pinnedBiomarkers->isNotEmpty() || $nextReminder)
                <div class="grid gap-6 lg:grid-cols-2">
                    @if ($pinnedBiomarkers->isNotEmpty())
                        <section class="space-y-4 rounded-lg border border-neutral-200 p-5 dark:border-neutral-700" data-test="dashboard-pinned-biomarkers">
                            <flux:heading size="lg">{{ __('Gevolgde biomarkers') }}</flux:heading>

                            <div class="space-y-3">
                                @foreach ($pinnedBiomarkers as $pin)
                                    <a href="{{ route('biomarkers.show', $pin->biomarker) }}" class="block text-sm font-medium text-blue-700 underline dark:text-blue-300">
                                        {{ $pin->biomarker->name }}
                                    </a>
                                @endforeach
                            </div>
                        </section>
                    @endif

                    @if ($nextReminder)
                        <section class="space-y-4 rounded-lg border border-neutral-200 p-5 dark:border-neutral-700" data-test="dashboard-next-reminder">
                            <flux:heading size="lg">{{ __('Volgende herinnering') }}</flux:heading>

                            <div class="space-y-2 text-sm">
                                <div class="font-medium">{{ $nextReminder->title }}</div>
                                <div class="text-neutral-600 dark:text-neutral-400">{{ $nextReminder->due_date->toDateString() }}</div>

                                @if ($nextReminder->note)
                                    <p class="text-neutral-700 dark:text-neutral-300">{{ $nextReminder->note }}</p>
                                @endif
                            </div>
                        </section>
                    @endif
                </div>
            @endif
        @else
            @if ($latestUploadSummary)
                @include('dashboard._blood-results-overview', ['summary' => $latestUploadSummary])
            @else
                <header class="flex flex-col gap-2">
                    <flux:heading size="xl">{{ __('Dashboard') }}</flux:heading>
                    <flux:text>{{ __('Upload een lab-PDF of bevestig waarden om je bloedwaardenoverzicht op te bouwen.') }}</flux:text>
                </header>
            @endif

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4" data-test="dashboard-supporting-links">
                <section class="space-y-4 rounded-lg border border-neutral-200 p-4 dark:border-neutral-700" data-test="dashboard-recent-blood-tests">
                    <flux:heading size="lg">{{ __('Recente bloedtesten') }}</flux:heading>

                    <div class="space-y-3">
                        @foreach ($recentBloodTests as $bloodTest)
                            <a href="{{ route('blood-tests.show', $bloodTest) }}" class="block text-sm font-medium text-blue-700 underline dark:text-blue-300">
                                {{ $bloodTest->title ?: __('Bloedtest zonder titel') }}
                            </a>
                        @endforeach
                    </div>
                </section>

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
        @endif
    </section>
</x-layouts::app>
