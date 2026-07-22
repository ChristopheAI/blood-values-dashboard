<x-layouts::app :title="__('Contextnotities')">
    <section class="mx-auto flex w-full max-w-5xl flex-col gap-8">
        <header class="flex flex-col gap-2">
            <flux:heading size="xl">{{ __('Contextnotities') }}</flux:heading>
            <flux:text>{{ __('Noteer persoonlijke observaties rond bloedtesten en datums.') }}</flux:text>
        </header>

        <form method="POST" action="{{ route('context-notes.store') }}" class="space-y-4 rounded-lg border border-neutral-200 p-5 dark:border-neutral-700" data-test="context-note-form">
            @csrf

            <div class="grid gap-4 md:grid-cols-3">
                <flux:input name="note_date" type="date" :label="__('Datum')" required data-test="context-note-date-input" />

                <flux:select name="category" :label="__('Categorie')" required data-test="context-note-category-select">
                    @foreach ($categories as $category)
                        <option value="{{ $category->value }}">{{ $category->dutchLabel() }}</option>
                    @endforeach
                </flux:select>

                <flux:select name="blood_test_id" :label="__('Bloedtest')" data-test="context-note-blood-test-select">
                    <option value="">{{ __('Geen gekoppelde bloedtest') }}</option>
                    @foreach ($bloodTests as $bloodTest)
                        <option value="{{ $bloodTest->id }}">{{ $bloodTest->title ?: ($bloodTest->test_date ? \App\Support\Format::dutchDate($bloodTest->test_date) : __('Bloedtest zonder titel')) }}</option>
                    @endforeach
                </flux:select>
            </div>

            <flux:textarea name="body" :label="__('Observatie')" required data-test="context-note-body-input" />
            <flux:button type="submit" variant="primary" data-test="save-context-note-button">{{ __('Contextnotitie bewaren') }}</flux:button>
        </form>

        <section class="space-y-4">
            <flux:heading size="lg">{{ __('Bewaarde context') }}</flux:heading>

            <div class="space-y-3" data-test="context-note-list">
                @forelse ($contextNotes as $note)
                    <article class="rounded-lg border border-neutral-200 p-4 text-sm dark:border-neutral-700" data-test="context-note-row">
                        <div class="font-medium">
                            {{ \App\Support\Format::dutchDate($note->note_date) }} · {{ $note->category->dutchLabel() }}
                        </div>
                        <p class="mt-2 text-neutral-700 dark:text-neutral-300">{{ $note->body }}</p>

                        @if ($note->bloodTest)
                            <a href="{{ route('blood-tests.show', $note->bloodTest) }}" class="mt-2 inline-flex font-medium text-blue-700 underline dark:text-blue-300">
                                {{ $note->bloodTest->title ?: __('Gekoppelde bloedtest') }}
                            </a>
                        @endif

                        <details class="mt-4 rounded-md border border-neutral-200 p-3 dark:border-neutral-700" data-test="edit-context-note-form">
                            <summary class="cursor-pointer font-medium text-neutral-900 dark:text-white">{{ __('Contextnotitie aanpassen') }}</summary>
                            <form method="POST" action="{{ route('context-notes.update', $note) }}" class="mt-4 space-y-4">
                                @csrf
                                @method('PATCH')

                                <div class="grid gap-4 md:grid-cols-3">
                                    <flux:input name="note_date" type="date" :label="__('Datum')" :value="$note->note_date->toDateString()" required />

                                    <flux:select name="category" :label="__('Categorie')" required>
                                        @foreach ($categories as $category)
                                            <option value="{{ $category->value }}" @selected($note->category === $category)>{{ $category->dutchLabel() }}</option>
                                        @endforeach
                                    </flux:select>

                                    <flux:select name="blood_test_id" :label="__('Bloedtest')">
                                        <option value="">{{ __('Geen gekoppelde bloedtest') }}</option>
                                        @foreach ($bloodTests as $bloodTest)
                                            <option value="{{ $bloodTest->id }}" @selected($note->blood_test_id === $bloodTest->id)>{{ $bloodTest->title ?: ($bloodTest->test_date ? \App\Support\Format::dutchDate($bloodTest->test_date) : __('Bloedtest zonder titel')) }}</option>
                                        @endforeach
                                    </flux:select>
                                </div>

                                <flux:textarea name="body" :label="__('Observatie')" required>{{ $note->body }}</flux:textarea>
                                <flux:button type="submit" variant="outline" size="sm">{{ __('Wijzigingen bewaren') }}</flux:button>
                            </form>
                        </details>

                        <form method="POST" action="{{ route('context-notes.destroy', $note) }}" class="mt-3" onsubmit="return confirm('{{ __('Deze contextnotitie verwijderen?') }}');">
                            @csrf
                            @method('DELETE')

                            <flux:button type="submit" variant="danger" size="sm" data-test="delete-context-note-button">{{ __('Contextnotitie verwijderen') }}</flux:button>
                        </form>
                    </article>
                @empty
                    <div class="rounded-lg border border-dashed border-neutral-300 p-6 text-sm text-neutral-600 dark:border-neutral-700 dark:text-neutral-400">
                        {{ __('Nog geen contextnotities.') }}
                    </div>
                @endforelse
            </div>
        </section>
    </section>
</x-layouts::app>
