<x-layouts::app :title="__('Blood tests')">
    <section class="mx-auto flex w-full max-w-5xl flex-col gap-8">
        @include('blood-tests._upload-dropzone')

        <div class="space-y-3">
            <flux:heading size="lg">{{ __('Recent blood tests') }}</flux:heading>

            @forelse (auth()->user()->bloodTests()->latest()->get() as $bloodTest)
                <a href="{{ route('blood-tests.show', $bloodTest) }}" class="block rounded-lg border border-neutral-200 p-4 hover:bg-neutral-50 dark:border-neutral-700 dark:hover:bg-neutral-900" data-test="blood-test-link">
                    <div class="font-medium">{{ $bloodTest->title ?: __('Untitled blood test') }}</div>
                    <div class="text-sm text-neutral-600 dark:text-neutral-400">
                        {{ $bloodTest->test_date?->toDateString() ?? __('No date yet') }}
                        · {{ $bloodTest->lab_name ?: __('Unknown lab') }}
                        · {{ $bloodTest->status }}
                    </div>
                </a>
            @empty
                <div class="rounded-lg border border-dashed border-neutral-300 p-6 text-sm text-neutral-600 dark:border-neutral-700 dark:text-neutral-400">
                    {{ __('No blood tests yet. Upload a lab PDF to start the review flow.') }}
                </div>
            @endforelse
        </div>
    </section>
</x-layouts::app>
