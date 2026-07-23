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
        class="flex min-h-64 flex-col items-center justify-center gap-4 rounded-xl border border-dashed p-6 text-center transition sm:p-8"
        data-density="compact"
        data-test="lab-pdf-dropzone"
    >
        <div class="flex w-full max-w-xl flex-col items-center gap-4">
            <span
                aria-hidden="true"
                class="grid size-12 place-items-center rounded-2xl bg-blue-50 text-blue-700 ring-1 ring-blue-100 dark:bg-blue-950 dark:text-blue-200 dark:ring-blue-900"
                data-test="upload-mark"
            >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" class="size-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 16V4m0 0L7.5 8.5M12 4l4.5 4.5M5 14v4.25A1.75 1.75 0 0 0 6.75 20h10.5A1.75 1.75 0 0 0 19 18.25V14" />
                </svg>
            </span>

            <div class="space-y-1">
                <p class="text-xl font-semibold text-neutral-900 dark:text-white sm:text-2xl">{{ __('Sleep je lab-PDF hierheen') }}</p>
                <p class="text-sm text-neutral-600 dark:text-neutral-400">{{ __('of kies hieronder een bestand') }}</p>
            </div>

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
                class="inline-flex h-10 cursor-pointer items-center rounded-lg bg-neutral-900 px-4 text-sm font-medium text-white disabled:cursor-not-allowed disabled:opacity-60 dark:bg-white dark:text-neutral-900"
                data-test="choose-pdf-button"
            >{{ __('PDF kiezen') }}</button>

            <span x-show="fileName" x-text="fileName" class="text-sm text-neutral-600 dark:text-neutral-400" data-test="selected-file-name"></span>

            <ul class="flex flex-wrap justify-center gap-2 text-xs text-neutral-700 dark:text-neutral-300" data-test="upload-trust-signals">
                <li class="rounded-full border border-neutral-200 bg-white px-3 py-1.5 dark:border-neutral-700 dark:bg-neutral-800">{{ __('Alleen PDF') }}</li>
                <li class="rounded-full border border-neutral-200 bg-white px-3 py-1.5 dark:border-neutral-700 dark:bg-neutral-800">{{ __('Geen externe verwerking') }}</li>
                <li class="rounded-full border border-neutral-200 bg-white px-3 py-1.5 dark:border-neutral-700 dark:bg-neutral-800">{{ __('Eerst bevestigen') }}</li>
            </ul>
        </div>

        @error('document')
            <flux:text class="mt-4 text-red-600 dark:text-red-400" data-test="upload-error">{{ $message }}</flux:text>
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

                let redirected = false;

                try {
                    const response = await fetch(form.action, {
                        method: 'POST',
                        body: new FormData(form),
                        headers: { 'Accept': 'application/x-ndjson', 'X-Intake-Stream': '1' },
                        // A validation failure (non-PDF, too large) answers with a
                        // 302 back to the form with the errors flashed. Without
                        // 'manual' the fetch would silently follow it to a 200 HTML
                        // page, slip past the guard below, and leave the progress
                        // panel stuck. 'manual' turns that 302 into an opaque,
                        // bodyless response so we fall through to a real GET
                        // navigation where the document validation error is shown
                        // again.
                        redirect: 'manual',
                    });

                    if (! response.ok || ! response.body) {
                        this.redirectToIndex(form);
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
                            redirected = this.handleProgressLine(line) || redirected;
                        }
                    }

                    if (buffer.trim() !== '') {
                        redirected = this.handleProgressLine(buffer) || redirected;
                    }
                } catch (error) {
                    this.redirectToIndex(form);
                    return;
                } finally {
                    if (! redirected) {
                        this.finishUpload();
                    }
                }
            },
            finishUpload() {
                this.isUploading = false;
                this.$refs.chooseButton.disabled = false;
            },
            redirectToIndex(form) {
                window.location.href = form.action;
            },
            handleProgressLine(line) {
                const trimmed = line.trim();

                if (trimmed === '') {
                    return false;
                }

                let payload;

                try {
                    payload = JSON.parse(trimmed);
                } catch (error) {
                    return false;
                }

                if (payload.stage && payload.state) {
                    this.progressStages[payload.stage] = payload.state;
                }

                if (payload.redirect) {
                    window.location.href = payload.redirect;

                    return true;
                }

                return false;
            },
        };
    };
</script>
