<?php

use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Data and privacy')] class extends Component {}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading class="sr-only">{{ __('Data and privacy') }}</flux:heading>

    <x-pages::settings.layout :heading="__('Data and privacy')" :subheading="__('Export or delete your personal tracking records')">
        <div class="my-6 space-y-10">
            @if (session('status') === 'health-data-deleted')
                <div class="rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800 dark:border-green-800 dark:bg-green-950 dark:text-green-200">
                    {{ __('Your personal tracking records were deleted. Your account remains active.') }}
                </div>
            @endif

            <section class="space-y-4">
                <div>
                    <flux:heading>{{ __('Download my data') }}</flux:heading>
                    <flux:subheading>
                        {{ __('Export structured personal tracking data as JSON. Lab PDFs are listed by document metadata only.') }}
                    </flux:subheading>
                </div>

                <form method="POST" action="{{ route('data.export') }}">
                    @csrf

                    <flux:button type="submit" variant="primary" data-test="download-data-button">
                        {{ __('Download data') }}
                    </flux:button>
                </form>
            </section>

            <flux:separator />

            <section class="space-y-4">
                <div>
                    <flux:heading>{{ __('Delete all health data') }}</flux:heading>
                    <flux:subheading>
                        {{ __('This cannot be undone. It removes blood tests, confirmed values, context notes, pinned biomarkers, biomarker catalog records, and private lab PDFs while keeping your account.') }}
                    </flux:subheading>
                </div>

                <form method="POST" action="{{ route('data.destroy') }}" class="space-y-4">
                    @csrf
                    @method('DELETE')

                    <flux:input
                        name="confirmation"
                        :label="__('Type DELETE ALL to confirm')"
                        autocomplete="off"
                        data-test="delete-all-confirmation-input"
                    />

                    @error('confirmation')
                        <flux:text class="text-red-600 dark:text-red-400">{{ $message }}</flux:text>
                    @enderror

                    <flux:button type="submit" variant="danger" data-test="delete-all-health-data-button">
                        {{ __('Delete all health data') }}
                    </flux:button>
                </form>
            </section>
        </div>
    </x-pages::settings.layout>
</section>
