@php
    $statusLabel = fn (string $status): string => match ($status) {
        'uploaded' => __('Geüpload'),
        'reviewing' => __('Review nodig'),
        'confirmed' => __('Bevestigd'),
        default => __('Onbekend'),
    };
    $compareCandidates = $bloodTests->getCollection();
    $selectedFirstId = (int) old('first', $compareCandidates->last()?->id);
    $selectedSecondId = (int) old('second', $compareCandidates->first()?->id);
@endphp

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

        @if (session('compare_error'))
            <flux:text class="rounded-md border border-amber-200 bg-amber-50 p-3 text-sm font-medium text-amber-900 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-100" data-test="compare-selection-message">{{ session('compare_error') }}</flux:text>
        @endif

        @if ($bloodTests->count() >= 2)
            <form method="GET" action="{{ route('blood-tests.compare') }}" class="grid gap-4 rounded-lg border border-neutral-200 p-5 sm:grid-cols-[1fr_1fr_auto] sm:items-end dark:border-neutral-700" data-test="blood-test-compare-form">
                <flux:select name="first" :label="__('Eerste bloedtest')" required>
                    @foreach ($bloodTests as $bloodTest)
                        <option value="{{ $bloodTest->id }}" @selected($bloodTest->id === $selectedFirstId)>{{ $bloodTest->title ?: __('Bloedtest zonder titel') }} · {{ $bloodTest->test_date ? \App\Support\Format::dutchDate($bloodTest->test_date) : __('Geen datum') }}</option>
                    @endforeach
                </flux:select>

                <flux:select name="second" :label="__('Tweede bloedtest')" required>
                    @foreach ($bloodTests as $bloodTest)
                        <option value="{{ $bloodTest->id }}" @selected($bloodTest->id === $selectedSecondId)>{{ $bloodTest->title ?: __('Bloedtest zonder titel') }} · {{ $bloodTest->test_date ? \App\Support\Format::dutchDate($bloodTest->test_date) : __('Geen datum') }}</option>
                    @endforeach
                </flux:select>

                <flux:button type="submit" variant="outline" data-test="compare-blood-tests-button">{{ __('Bloedtesten vergelijken') }}</flux:button>

                @error('comparison')
                    <flux:text class="sm:col-span-3 text-sm text-red-600 dark:text-red-400" data-test="compare-blood-tests-error">{{ $message }}</flux:text>
                @enderror
            </form>
        @elseif ($bloodTests->isNotEmpty())
            <flux:text class="text-sm text-neutral-600 dark:text-neutral-400">{{ __('Upload nog een bloedtest om waarden naast elkaar te vergelijken.') }}</flux:text>
        @endif

        <div class="space-y-3">
            <flux:heading size="lg">{{ __('Recente bloedtesten') }}</flux:heading>

            @forelse ($bloodTests as $bloodTest)
                <a href="{{ route('blood-tests.show', $bloodTest) }}" class="block rounded-lg border border-neutral-200 p-4 hover:bg-neutral-50 dark:border-neutral-700 dark:hover:bg-neutral-900" data-test="blood-test-link">
                    <div class="font-medium">{{ $bloodTest->title ?: __('Bloedtest zonder titel') }}</div>
                    <div class="text-sm text-neutral-600 dark:text-neutral-400">
                        {{ $bloodTest->test_date ? \App\Support\Format::dutchDate($bloodTest->test_date) : __('Nog geen datum') }}
                        · {{ $bloodTest->lab_name ?: __('Onbekend labo') }}
                        · {{ $statusLabel($bloodTest->status) }}
                    </div>
                </a>
            @empty
                <div class="rounded-lg border border-dashed border-neutral-300 p-6 text-sm text-neutral-600 dark:border-neutral-700 dark:text-neutral-400">
                    {{ __('Nog geen bloedtesten. Upload een lab-PDF om de reviewflow te starten.') }}
                </div>
            @endforelse

            @if ($bloodTests->hasPages())
                <div data-test="blood-test-pagination">
                    {{ $bloodTests->links() }}
                </div>
            @endif
        </div>
    </section>
</x-layouts::app>
