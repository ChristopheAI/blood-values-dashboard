<x-layouts::app :title="__('Bloedtesten')">
    @php
        $bloodTests = auth()->user()->bloodTests()->recentFirst()->get();
        $latestBloodTest = $bloodTests->first();
        $previousBloodTest = $bloodTests->skip(1)->first();
    @endphp

    <section class="mx-auto flex w-full max-w-5xl flex-col gap-8">
        <header class="flex flex-col gap-2">
            <flux:heading size="xl">{{ __('Bloedtesten') }}</flux:heading>
            <flux:text>{{ __('Upload een lab-PDF en kijk onzekere waarden na voordat ze verder gebruikt worden.') }}</flux:text>
        </header>

        @include('blood-tests._upload-dropzone')

        <div class="space-y-3">
            <flux:heading size="lg">{{ __('Recente bloedtesten') }}</flux:heading>

            @if (session('compare_error'))
                <div class="rounded-md border border-amber-200 bg-amber-50 p-3 text-sm font-medium text-amber-900 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-100" data-test="compare-selection-message">
                    {{ session('compare_error') }}
                </div>
            @endif

            @if ($bloodTests->count() >= 2)
                <form method="GET" action="{{ route('blood-tests.compare') }}" class="grid gap-3 rounded-lg border border-neutral-200 p-4 dark:border-neutral-700 sm:grid-cols-[1fr_1fr_auto] sm:items-end" data-test="compare-blood-tests-form">
                    <label class="space-y-1 text-sm">
                        <span class="font-medium text-neutral-700 dark:text-neutral-200">{{ __('Vorige bloedtest') }}</span>
                        <select name="first" class="w-full rounded-md border border-neutral-300 bg-white px-3 py-2 text-sm dark:border-neutral-700 dark:bg-neutral-950">
                            @foreach ($bloodTests as $bloodTest)
                                <option value="{{ $bloodTest->id }}" @selected($previousBloodTest?->is($bloodTest))>
                                    {{ $bloodTest->title ?: __('Bloedtest zonder titel') }}
                                    · {{ $bloodTest->test_date ? \App\Support\Format::dutchDate($bloodTest->test_date) : __('Geen datum') }}
                                </option>
                            @endforeach
                        </select>
                    </label>

                    <label class="space-y-1 text-sm">
                        <span class="font-medium text-neutral-700 dark:text-neutral-200">{{ __('Huidige bloedtest') }}</span>
                        <select name="second" class="w-full rounded-md border border-neutral-300 bg-white px-3 py-2 text-sm dark:border-neutral-700 dark:bg-neutral-950">
                            @foreach ($bloodTests as $bloodTest)
                                <option value="{{ $bloodTest->id }}" @selected($latestBloodTest?->is($bloodTest))>
                                    {{ $bloodTest->title ?: __('Bloedtest zonder titel') }}
                                    · {{ $bloodTest->test_date ? \App\Support\Format::dutchDate($bloodTest->test_date) : __('Geen datum') }}
                                </option>
                            @endforeach
                        </select>
                    </label>

                    <flux:button type="submit" variant="primary" data-test="compare-blood-tests-button">
                        {{ __('Vergelijken') }}
                    </flux:button>
                </form>
            @endif

            @forelse ($bloodTests as $bloodTest)
                <a href="{{ route('blood-tests.show', $bloodTest) }}" class="block rounded-lg border border-neutral-200 p-4 hover:bg-neutral-50 dark:border-neutral-700 dark:hover:bg-neutral-900" data-test="blood-test-link">
                    <div class="font-medium">{{ $bloodTest->title ?: __('Bloedtest zonder titel') }}</div>
                    <div class="text-sm text-neutral-600 dark:text-neutral-400">
                        {{ $bloodTest->test_date ? \App\Support\Format::dutchDate($bloodTest->test_date) : __('Nog geen datum') }}
                        · {{ $bloodTest->lab_name ?: __('Onbekend labo') }}
                        · {{ $bloodTest->status }}
                    </div>
                </a>
            @empty
                <div class="rounded-lg border border-dashed border-neutral-300 p-6 text-sm text-neutral-600 dark:border-neutral-700 dark:text-neutral-400">
                    {{ __('Nog geen bloedtesten. Upload een lab-PDF om de reviewflow te starten.') }}
                </div>
            @endforelse
        </div>
    </section>
</x-layouts::app>
