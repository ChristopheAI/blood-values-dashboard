<x-layouts::app :title="__('Blood tests')">
    <section class="mx-auto flex w-full max-w-5xl flex-col gap-8">
        <form
            method="POST"
            action="{{ route('blood-tests.store') }}"
            enctype="multipart/form-data"
            class="space-y-5"
            data-test="blood-test-upload-form"
            onsubmit="document.querySelector('[data-test=&quot;intake-progress&quot;]').hidden = false; this.querySelector('[data-test=&quot;choose-pdf-button&quot;]').setAttribute('disabled', 'disabled');"
        >
            @csrf

            <section
                x-data="{
                    dragging: false,
                    fileName: '',
                    setFiles(files) {
                        if (files && files.length) {
                            $refs.input.files = files;
                            this.fileName = files[0].name;
                        }
                    },
                }"
                @dragover.prevent="dragging = true"
                @dragleave.prevent="dragging = false"
                @drop.prevent="dragging = false; setFiles($event.dataTransfer.files); if (fileName) $nextTick(() => $el.closest('form').requestSubmit())"
                :class="dragging
                    ? 'border-blue-400 bg-blue-50 dark:border-blue-500 dark:bg-neutral-800/80'
                    : 'border-neutral-300 bg-neutral-50 dark:border-neutral-700 dark:bg-neutral-900'"
                class="grid min-h-[22rem] place-items-center rounded-lg border border-dashed p-8 text-center transition"
                data-test="lab-pdf-dropzone"
            >
                <div class="flex w-full max-w-2xl flex-col items-center gap-4 rounded-lg border border-neutral-200 bg-white p-8 shadow-xs dark:border-neutral-700 dark:bg-neutral-800">
                    <span class="text-2xl font-semibold text-neutral-900 dark:text-white">{{ __('Sleep je lab-PDF hierheen') }}</span>
                    <span class="text-sm text-neutral-600 dark:text-neutral-400">{{ __('PDF only') }}</span>

                    <input
                        x-ref="input"
                        id="document"
                        name="document"
                        type="file"
                        accept="application/pdf"
                        required
                        class="sr-only"
                        data-test="lab-pdf-input"
                        @change="fileName = $event.target.files[0]?.name ?? ''; if (fileName) $nextTick(() => $el.form.requestSubmit())"
                    />

                    <button
                        type="button"
                        @click="$refs.input.click()"
                        class="inline-flex h-10 cursor-pointer items-center rounded-lg bg-neutral-900 px-4 text-sm font-medium text-white dark:bg-white dark:text-neutral-900"
                        data-test="choose-pdf-button"
                    >{{ __('Choose PDF') }}</button>

                    <span x-show="fileName" x-text="fileName" class="text-sm text-neutral-600 dark:text-neutral-400" data-test="selected-file-name"></span>
                </div>

                @error('document')
                    <flux:text class="mt-4 text-red-600 dark:text-red-400">{{ $message }}</flux:text>
                @enderror
            </section>

            <section hidden class="rounded-lg border border-neutral-200 p-4 dark:border-neutral-700" data-test="intake-progress">
                <div class="grid gap-3 text-sm sm:grid-cols-4">
                    <div class="rounded-md bg-neutral-100 p-3 dark:bg-neutral-900" data-test="intake-progress-stage-extract">{{ __('extract') }}</div>
                    <div class="rounded-md bg-neutral-100 p-3 dark:bg-neutral-900" data-test="intake-progress-stage-values">{{ __('waarden') }}</div>
                    <div class="rounded-md bg-neutral-100 p-3 dark:bg-neutral-900" data-test="intake-progress-stage-status">{{ __('status') }}</div>
                    <div class="rounded-md bg-neutral-100 p-3 dark:bg-neutral-900" data-test="intake-progress-stage-trend">{{ __('trend') }}</div>
                </div>
            </section>
        </form>

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
