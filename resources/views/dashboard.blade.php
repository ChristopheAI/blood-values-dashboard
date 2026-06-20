<x-layouts::app :title="__('Dashboard')">
    <section class="mx-auto flex w-full max-w-6xl flex-col gap-8">
        @if ($recentBloodTests->isEmpty())
            @include('blood-tests._upload-dropzone')

            @if ($pinnedBiomarkers->isNotEmpty() || $nextReminder)
                <div class="grid gap-6 lg:grid-cols-2">
                    @if ($pinnedBiomarkers->isNotEmpty())
                        <section class="space-y-4 rounded-lg border border-neutral-200 p-5 dark:border-neutral-700" data-test="dashboard-pinned-biomarkers">
                            <flux:heading size="lg">{{ __('Pinned biomarkers') }}</flux:heading>

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
                            <flux:heading size="lg">{{ __('Next reminder') }}</flux:heading>

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
            <header class="flex flex-col gap-2">
                <flux:heading size="xl">{{ __('Personal overview') }}</flux:heading>
                <flux:text>{{ __('Review confirmed blood-test data, pinned markers, and context before a consult.') }}</flux:text>
            </header>

            <div class="grid gap-6 lg:grid-cols-4">
                <section class="space-y-4 rounded-lg border border-neutral-200 p-5 dark:border-neutral-700" data-test="dashboard-recent-blood-tests">
                    <flux:heading size="lg">{{ __('Recent blood tests') }}</flux:heading>

                    <div class="space-y-3">
                        @foreach ($recentBloodTests as $bloodTest)
                            <a href="{{ route('blood-tests.show', $bloodTest) }}" class="block text-sm font-medium text-blue-700 underline dark:text-blue-300">
                                {{ $bloodTest->title ?: __('Untitled blood test') }}
                            </a>
                        @endforeach
                    </div>
                </section>

                <section class="space-y-4 rounded-lg border border-neutral-200 p-5 dark:border-neutral-700" data-test="dashboard-pinned-biomarkers">
                    <flux:heading size="lg">{{ __('Pinned biomarkers') }}</flux:heading>

                    <div class="space-y-3">
                        @forelse ($pinnedBiomarkers as $pin)
                            <a href="{{ route('biomarkers.show', $pin->biomarker) }}" class="block text-sm font-medium text-blue-700 underline dark:text-blue-300">
                                {{ $pin->biomarker->name }}
                            </a>
                        @empty
                            <flux:text>{{ __('No pinned biomarkers yet.') }}</flux:text>
                        @endforelse
                    </div>
                </section>

                <section class="space-y-4 rounded-lg border border-neutral-200 p-5 dark:border-neutral-700" data-test="dashboard-next-reminder">
                    <flux:heading size="lg">{{ __('Next reminder') }}</flux:heading>

                    @if ($nextReminder)
                        <div class="space-y-2 text-sm">
                            <div class="font-medium">{{ $nextReminder->title }}</div>
                            <div class="text-neutral-600 dark:text-neutral-400">{{ $nextReminder->due_date->toDateString() }}</div>

                            @if ($nextReminder->note)
                                <p class="text-neutral-700 dark:text-neutral-300">{{ $nextReminder->note }}</p>
                            @endif
                        </div>
                    @else
                        <flux:text>{{ __('No reminders yet.') }}</flux:text>
                    @endif
                </section>

                <section class="space-y-4 rounded-lg border border-neutral-200 p-5 dark:border-neutral-700" data-test="dashboard-quick-actions">
                    <flux:heading size="lg">{{ __('Quick actions') }}</flux:heading>

                    <div class="flex flex-col gap-2">
                        <flux:button :href="route('blood-tests.index')" variant="primary">{{ __('Upload blood-test PDF') }}</flux:button>
                        <flux:button :href="route('context-notes.index')" variant="outline">{{ __('Add context note') }}</flux:button>
                        <flux:button :href="route('reminders.index')" variant="outline">{{ __('Add reminder') }}</flux:button>
                        <flux:button :href="route('consult-overview.index')" variant="outline">{{ __('Prepare consult overview') }}</flux:button>
                    </div>
                </section>
            </div>

            <section class="space-y-4 rounded-lg border border-neutral-200 p-5 dark:border-neutral-700" data-test="dashboard-attention-results">
                <flux:heading size="lg">{{ __('Values needing attention') }}</flux:heading>
                <flux:text>{{ __('Status is based on the reference range you entered.') }}</flux:text>

                <div class="overflow-hidden rounded-lg border border-neutral-200 dark:border-neutral-700">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-neutral-50 text-neutral-600 dark:bg-neutral-900 dark:text-neutral-300">
                            <tr>
                                <th class="p-3">{{ __('Date') }}</th>
                                <th class="p-3">{{ __('Biomarker') }}</th>
                                <th class="p-3">{{ __('Value') }}</th>
                                <th class="p-3">{{ __('Status') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($attentionResults as $result)
                                <tr class="border-t border-neutral-200 dark:border-neutral-700">
                                    <td class="p-3">{{ $result->bloodTest->test_date?->toDateString() ?? __('No date') }}</td>
                                    <td class="p-3">{{ $result->biomarker->name }}</td>
                                    <td class="p-3">{{ (float) $result->value }} {{ $result->unit }}</td>
                                    <td class="p-3">{{ $result->status }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="p-4 text-neutral-600 dark:text-neutral-400">{{ __('No confirmed low, high, or unknown values yet.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        @endif
    </section>
</x-layouts::app>
