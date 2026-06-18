<section class="mx-auto flex w-full max-w-5xl flex-col gap-8">
    <header class="flex flex-col gap-2">
        <flux:heading size="xl">{{ $bloodTest->title ?: __('Blood test review') }}</flux:heading>
        <flux:text>
            {{ $bloodTest->test_date?->toDateString() ?? __('No date yet') }}
            · {{ $bloodTest->lab_name ?: __('Unknown lab') }}
            · {{ $bloodTest->status }}
        </flux:text>
    </header>

    <div class="grid gap-6 lg:grid-cols-[1fr_1.2fr]">
        <section class="space-y-4 rounded-lg border border-neutral-200 p-5 dark:border-neutral-700">
            <flux:heading size="lg">{{ __('Source document') }}</flux:heading>

            @forelse ($bloodTest->documents as $document)
                <a class="inline-flex text-sm font-medium text-blue-700 underline dark:text-blue-300" href="{{ route('blood-test-documents.download', $document) }}">
                    {{ $document->original_filename }}
                </a>
            @empty
                <flux:text>{{ __('No source document is attached.') }}</flux:text>
            @endforelse
        </section>

        <form wire:submit="confirmResult" class="space-y-4 rounded-lg border border-neutral-200 p-5 dark:border-neutral-700">
            <flux:heading size="lg">{{ __('Confirm a biomarker value') }}</flux:heading>

            <flux:select wire:model="resultForm.biomarker_id" :label="__('Existing biomarker')">
                <option value="">{{ __('Create new') }}</option>
                @foreach ($biomarkers as $biomarker)
                    <option value="{{ $biomarker->id }}">{{ $biomarker->name }}</option>
                @endforeach
            </flux:select>

            <flux:input wire:model="resultForm.name" :label="__('Biomarker name')" />
            <flux:input wire:model="resultForm.value" :label="__('Value')" inputmode="decimal" />
            <flux:input wire:model="resultForm.unit" :label="__('Unit')" />

            <div class="grid gap-4 md:grid-cols-3">
                <flux:input wire:model="resultForm.reference_min" :label="__('Range min')" inputmode="decimal" />
                <flux:input wire:model="resultForm.reference_max" :label="__('Range max')" inputmode="decimal" />
                <flux:input wire:model="resultForm.reference_unit" :label="__('Range unit')" />
            </div>

            <flux:textarea wire:model="resultForm.note" :label="__('Note')" />
            <flux:button type="submit" variant="primary">{{ __('Confirm value') }}</flux:button>
        </form>
    </div>

    <section class="space-y-4">
        <flux:heading size="lg">{{ __('Confirmed values') }}</flux:heading>

        <div class="overflow-hidden rounded-lg border border-neutral-200 dark:border-neutral-700">
            <table class="w-full text-left text-sm">
                <thead class="bg-neutral-50 text-neutral-600 dark:bg-neutral-900 dark:text-neutral-300">
                    <tr>
                        <th class="p-3">{{ __('Biomarker') }}</th>
                        <th class="p-3">{{ __('Value') }}</th>
                        <th class="p-3">{{ __('Status') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($bloodTest->results as $result)
                        <tr class="border-t border-neutral-200 dark:border-neutral-700">
                            <td class="p-3">{{ $result->biomarker->name }}</td>
                            <td class="p-3">{{ (float) $result->value }} {{ $result->unit }}</td>
                            <td class="p-3">{{ $result->status }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="p-4 text-neutral-600 dark:text-neutral-400">{{ __('No confirmed values yet.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</section>
