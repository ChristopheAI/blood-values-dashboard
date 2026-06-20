<x-layouts::app :title="__('Blood tests')">
    <section class="mx-auto flex w-full max-w-5xl flex-col gap-8">
        <form
            method="POST"
            action="{{ route('blood-tests.store') }}"
            enctype="multipart/form-data"
            class="space-y-5"
            data-test="blood-test-upload-form"
            x-data="labPdfIntake()"
            @submit="submitUpload($event)"
        >
            @csrf

            <section
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
                        x-ref="chooseButton"
                        :disabled="isUploading"
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

            <section hidden x-bind:hidden="! progressVisible" class="rounded-lg border border-neutral-200 p-4 dark:border-neutral-700" data-test="intake-progress">
                <div class="grid gap-3 text-sm sm:grid-cols-4">
                    <div class="rounded-md p-3 font-medium" :class="stageClass('extract')" :data-state="progressStages.extract" data-test="intake-progress-stage-extract">{{ __('extract') }}</div>
                    <div class="rounded-md p-3 font-medium" :class="stageClass('values')" :data-state="progressStages.values" data-test="intake-progress-stage-values">{{ __('waarden') }}</div>
                    <div class="rounded-md p-3 font-medium" :class="stageClass('status')" :data-state="progressStages.status" data-test="intake-progress-stage-status">{{ __('status') }}</div>
                    <div class="rounded-md p-3 font-medium" :class="stageClass('trend')" :data-state="progressStages.trend" data-test="intake-progress-stage-trend">{{ __('trend') }}</div>
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

    <script>
        window.labPdfIntake = function () {
            return {
                dragging: false,
                fileName: '',
                isUploading: false,
                progressVisible: false,
                progressStages: {
                    extract: 'pending',
                    values: 'pending',
                    status: 'pending',
                    trend: 'pending',
                },
                setFiles(files) {
                    if (files && files.length) {
                        this.$refs.input.files = files;
                        this.fileName = files[0].name;
                    }
                },
                resetProgress() {
                    this.progressStages = {
                        extract: 'pending',
                        values: 'pending',
                        status: 'pending',
                        trend: 'pending',
                    };
                },
                stageClass(stage) {
                    return {
                        active: 'bg-blue-50 text-blue-800 dark:bg-blue-950 dark:text-blue-200',
                        done: 'bg-green-50 text-green-800 dark:bg-green-950 dark:text-green-200',
                        failed: 'bg-red-50 text-red-800 dark:bg-red-950 dark:text-red-200',
                        pending: 'bg-neutral-100 text-neutral-700 dark:bg-neutral-900 dark:text-neutral-300',
                    }[this.progressStages[stage] ?? 'pending'];
                },
                async submitUpload(event) {
                    if (this.isUploading) {
                        return;
                    }

                    event.preventDefault();

                    const form = event.target;
                    this.isUploading = true;
                    this.progressVisible = true;
                    this.resetProgress();
                    this.$refs.chooseButton.disabled = true;

                    try {
                        const response = await fetch(form.action, {
                            method: 'POST',
                            body: new FormData(form),
                            headers: { 'Accept': 'application/x-ndjson', 'X-Intake-Stream': '1' },
                        });

                        if (! response.ok || ! response.body) {
                            form.submit();
                            return;
                        }

                        const reader = response.body.getReader();
                        const decoder = new TextDecoder();
                        let buffer = '';

                        while (true) {
                            const { done, value } = await reader.read();

                            if (done) {
                                break;
                            }

                            buffer += decoder.decode(value, { stream: true });
                            const lines = buffer.split('\n');
                            buffer = lines.pop() ?? '';

                            for (const line of lines) {
                                this.handleProgressLine(line);
                            }
                        }

                        if (buffer.trim() !== '') {
                            this.handleProgressLine(buffer);
                        }
                    } catch (error) {
                        form.submit();
                    }
                },
                handleProgressLine(line) {
                    const trimmed = line.trim();

                    if (trimmed === '') {
                        return;
                    }

                    const payload = JSON.parse(trimmed);

                    if (payload.stage && payload.state) {
                        this.progressStages[payload.stage] = payload.state;
                    }

                    if (payload.redirect) {
                        window.location.href = payload.redirect;
                    }
                },
            };
        };
    </script>
</x-layouts::app>
