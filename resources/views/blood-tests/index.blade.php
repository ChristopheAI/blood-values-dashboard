<x-layouts::app :title="__('Blood tests')">
    <section class="mx-auto flex w-full max-w-4xl flex-col gap-8">
        <header class="flex flex-col gap-2">
            <flux:heading size="xl">{{ __('Blood tests') }}</flux:heading>
            <flux:text>{{ __('Upload the original lab PDF first, then confirm the structured values yourself.') }}</flux:text>
        </header>

        <form method="POST" action="{{ route('blood-tests.store') }}" enctype="multipart/form-data" class="space-y-5 rounded-lg border border-neutral-200 p-6 dark:border-neutral-700">
            @csrf

            <flux:input name="document" type="file" :label="__('Lab PDF')" accept="application/pdf" required />
            <flux:input name="test_date" type="date" :label="__('Test date')" />
            <flux:input name="lab_name" :label="__('Lab or source')" />
            <flux:input name="title" :label="__('Title')" />

            @error('document')
                <flux:text class="text-red-600 dark:text-red-400">{{ $message }}</flux:text>
            @enderror

            <flux:button type="submit" variant="primary">{{ __('Upload PDF') }}</flux:button>
        </form>

        <div class="space-y-3">
            <flux:heading size="lg">{{ __('Recent blood tests') }}</flux:heading>

            @forelse (auth()->user()->bloodTests()->latest()->get() as $bloodTest)
                <a href="{{ route('blood-tests.show', $bloodTest) }}" class="block rounded-lg border border-neutral-200 p-4 hover:bg-neutral-50 dark:border-neutral-700 dark:hover:bg-neutral-900">
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
