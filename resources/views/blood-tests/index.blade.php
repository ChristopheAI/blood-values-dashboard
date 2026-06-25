<x-layouts::app :title="__('Bloedtesten')">
    <section class="mx-auto flex w-full max-w-5xl flex-col gap-8">
        <header class="flex flex-col gap-2">
            <flux:heading size="xl">{{ __('Bloedtesten') }}</flux:heading>
            <flux:text>{{ __('Upload een lab-PDF en kijk onzekere waarden na voordat ze verder gebruikt worden.') }}</flux:text>
        </header>

        @include('blood-tests._upload-dropzone')

        <div class="space-y-3">
            <flux:heading size="lg">{{ __('Recente bloedtesten') }}</flux:heading>

            @forelse (auth()->user()->bloodTests()->recentFirst()->get() as $bloodTest)
                <a href="{{ route('blood-tests.show', $bloodTest) }}" class="block rounded-lg border border-neutral-200 p-4 hover:bg-neutral-50 dark:border-neutral-700 dark:hover:bg-neutral-900" data-test="blood-test-link">
                    <div class="font-medium">{{ $bloodTest->title ?: __('Bloedtest zonder titel') }}</div>
                    <div class="text-sm text-neutral-600 dark:text-neutral-400">
                        {{ $bloodTest->test_date?->toDateString() ?? __('Nog geen datum') }}
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
