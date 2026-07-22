<x-layouts::auth :title="__('Wachtwoord vergeten')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Wachtwoord vergeten')" :description="__('Vul je e-mailadres in om een link voor een nieuw wachtwoord te ontvangen.')" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('password.email') }}" class="flex flex-col gap-6">
            @csrf

            <!-- Email Address -->
            <flux:input
                name="email"
                :label="__('E-mailadres')"
                type="email"
                required
                autofocus
                placeholder="email@example.com"
            />

            <flux:button variant="primary" type="submit" class="w-full" data-test="email-password-reset-link-button">
                {{ __('Link voor nieuw wachtwoord mailen') }}
            </flux:button>
        </form>

        <div class="space-x-1 rtl:space-x-reverse text-center text-sm text-zinc-400">
            <span>{{ __('Of ga terug naar') }}</span>
            <flux:link :href="route('login')" wire:navigate>{{ __('inloggen') }}</flux:link>
        </div>
    </div>
</x-layouts::auth>
