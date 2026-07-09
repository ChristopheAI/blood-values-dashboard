<x-layouts::app :title="__('Herinneringen')">
    <section class="mx-auto flex w-full max-w-5xl flex-col gap-8">
        <header class="flex flex-col gap-2">
            <flux:heading size="xl">{{ __('Herinneringen') }}</flux:heading>
            <flux:text>{{ __('Plan opvolgmomenten voor je persoonlijke opvolging.') }}</flux:text>
        </header>

        <form method="POST" action="{{ route('reminders.store') }}" class="space-y-4 rounded-lg border border-neutral-200 p-5 dark:border-neutral-700" data-test="reminder-form">
            @csrf

            <div class="grid gap-4 md:grid-cols-[180px_1fr]">
                <flux:input name="due_date" type="date" :label="__('Datum')" required data-test="reminder-due-date-input" />
                <flux:input name="title" :label="__('Titel')" required data-test="reminder-title-input" />
            </div>

            <flux:textarea name="note" :label="__('Notitie')" data-test="reminder-note-input" />
            <flux:button type="submit" variant="primary" data-test="save-reminder-button">{{ __('Herinnering bewaren') }}</flux:button>
        </form>

        <section class="space-y-4">
            <flux:heading size="lg">{{ __('Open herinneringen') }}</flux:heading>

            <div class="space-y-3" data-test="open-reminder-list">
                @forelse ($openReminders as $reminder)
                    <article class="rounded-lg border border-neutral-200 p-4 text-sm dark:border-neutral-700" data-test="open-reminder-row">
                        <div class="font-medium">{{ \App\Support\Format::dutchDate($reminder->due_date) }} · {{ $reminder->title }}</div>

                        @if ($reminder->note)
                            <p class="mt-2 text-neutral-700 dark:text-neutral-300">{{ $reminder->note }}</p>
                        @endif

                        <div class="mt-4 grid gap-3 lg:grid-cols-[1fr_auto_auto] lg:items-end">
                            <form method="POST" action="{{ route('reminders.update', $reminder) }}" class="grid gap-3 md:grid-cols-[160px_1fr]">
                                @csrf
                                @method('PATCH')

                                <flux:input name="due_date" type="date" :label="__('Datum')" :value="$reminder->due_date->toDateString()" required />
                                <flux:input name="title" :label="__('Titel')" :value="$reminder->title" required />
                                <div class="md:col-span-2">
                                    <flux:textarea name="note" :label="__('Notitie')">{{ $reminder->note }}</flux:textarea>
                                </div>

                                <div class="md:col-span-2">
                                    <flux:button type="submit" variant="outline" size="sm" data-test="update-reminder-button">
                                        {{ __('Herinnering bijwerken') }}
                                    </flux:button>
                                </div>
                            </form>

                            <form method="POST" action="{{ route('reminders.update', $reminder) }}">
                                @csrf
                                @method('PATCH')

                                <input type="hidden" name="due_date" value="{{ $reminder->due_date->toDateString() }}">
                                <input type="hidden" name="title" value="{{ $reminder->title }}">
                                <input type="hidden" name="note" value="{{ $reminder->note }}">
                                <input type="hidden" name="completed" value="1">

                                <flux:button type="submit" variant="primary" size="sm" data-test="complete-reminder-button">
                                    {{ __('Als afgewerkt markeren') }}
                                </flux:button>
                            </form>

                            <form method="POST" action="{{ route('reminders.destroy', $reminder) }}" onsubmit="return confirm('{{ __('Deze herinnering verwijderen?') }}');">
                                @csrf
                                @method('DELETE')

                                <flux:button type="submit" variant="danger" size="sm" data-test="delete-reminder-button">
                                    {{ __('Herinnering verwijderen') }}
                                </flux:button>
                            </form>
                        </div>
                    </article>
                @empty
                    <div class="rounded-lg border border-dashed border-neutral-300 p-6 text-sm text-neutral-600 dark:border-neutral-700 dark:text-neutral-400">
                        {{ __('Nog geen herinneringen.') }}
                    </div>
                @endforelse
            </div>
        </section>

        <section class="space-y-4">
            <flux:heading size="lg">{{ __('Afgewerkte herinneringen') }}</flux:heading>

            <div class="space-y-3" data-test="completed-reminder-list">
                @forelse ($completedReminders as $reminder)
                    <article class="rounded-lg border border-neutral-200 p-4 text-sm dark:border-neutral-700" data-test="completed-reminder-row">
                        <div class="font-medium">{{ \App\Support\Format::dutchDate($reminder->due_date) }} · {{ $reminder->title }}</div>

                        @if ($reminder->note)
                            <p class="mt-2 text-neutral-700 dark:text-neutral-300">{{ $reminder->note }}</p>
                        @endif
                    </article>
                @empty
                    <div class="rounded-lg border border-dashed border-neutral-300 p-6 text-sm text-neutral-600 dark:border-neutral-700 dark:text-neutral-400">
                        {{ __('Nog geen afgewerkte herinneringen.') }}
                    </div>
                @endforelse
            </div>
        </section>
    </section>
</x-layouts::app>
