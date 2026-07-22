<x-layouts::auth :title="__('E-mail verifiëren')">
    <div class="mt-4 flex flex-col gap-6">
        <flux:text class="text-center">
            {{ __('Verifieer je e-mailadres via de link die we je net hebben gemaild.') }}
        </flux:text>

        @if (session('status') == 'verification-link-sent')
            <flux:text class="text-center font-medium !dark:text-green-400 !text-green-600">
                {{ __('Er is een nieuwe verificatielink naar het opgegeven e-mailadres gestuurd.') }}
            </flux:text>
        @endif

        <div class="flex flex-col items-center justify-between space-y-3">
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <flux:button type="submit" variant="primary" class="w-full">
                    {{ __('Verificatie-e-mail opnieuw sturen') }}
                </flux:button>
            </form>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <flux:button variant="ghost" type="submit" class="text-sm cursor-pointer" data-test="logout-button">
                    {{ __('Uitloggen') }}
                </flux:button>
            </form>
        </div>
    </div>
</x-layouts::auth>
