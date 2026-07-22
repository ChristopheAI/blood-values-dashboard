<?php

use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Gegevens en privacy')] class extends Component {}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading class="sr-only">{{ __('Gegevens en privacy') }}</flux:heading>

    <x-pages::settings.layout :heading="__('Gegevens en privacy')" :subheading="__('Exporteer of verwijder je persoonlijke opvolggegevens.')">
        <div class="my-6 space-y-10">
            @if (session('status') === 'health-data-deleted')
                <div class="rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800 dark:border-green-800 dark:bg-green-950 dark:text-green-200">
                    {{ __('Je persoonlijke opvolggegevens zijn verwijderd. Je account blijft actief.') }}
                </div>
            @endif

            <section class="space-y-4">
                <div>
                    <flux:heading>{{ __('Mijn gegevens downloaden') }}</flux:heading>
                    <flux:subheading>
                        {{ __('Exporteer je gestructureerde opvolggegevens als JSON. Lab-PDF’s staan enkel als documentmetadata in de export.') }}
                    </flux:subheading>
                </div>

                <form method="POST" action="{{ route('data.export') }}">
                    @csrf

                    <flux:button type="submit" variant="primary" data-test="download-data-button">
                        {{ __('Gegevens downloaden') }}
                    </flux:button>
                </form>
            </section>

            <flux:separator />

            <section class="space-y-4">
                <div>
                    <flux:heading>{{ __('Alle gezondheidsgegevens verwijderen') }}</flux:heading>
                    <flux:subheading>
                        {{ __('Dit kan niet ongedaan worden gemaakt. Bloedtesten, bevestigde waarden, contextnotities, gevolgde biomarkers, biomarkercatalogus en private lab-PDF’s worden verwijderd; je account blijft bestaan.') }}
                    </flux:subheading>
                </div>

                <form method="POST" action="{{ route('data.destroy') }}" class="space-y-4">
                    @csrf
                    @method('DELETE')

                    <flux:input
                        name="confirmation"
                        :label="__('Typ DELETE ALL om te bevestigen')"
                        autocomplete="off"
                        data-test="delete-all-confirmation-input"
                    />

                    @error('confirmation')
                        <flux:text class="text-red-600 dark:text-red-400">{{ $message }}</flux:text>
                    @enderror

                    <flux:button type="submit" variant="danger" data-test="delete-all-health-data-button">
                        {{ __('Alle gezondheidsgegevens verwijderen') }}
                    </flux:button>
                </form>
            </section>
        </div>
    </x-pages::settings.layout>
</section>
