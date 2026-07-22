<?php

use App\Concerns\PasswordValidationRules;
use App\Domain\Privacy\DeleteAllHealthData;
use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

new class extends Component {
    use PasswordValidationRules;

    public string $password = '';

    /**
     * Delete the currently authenticated user.
     */
    public function deleteUser(Logout $logout, DeleteAllHealthData $deleteAllHealthData): void
    {
        $this->validate([
            'password' => $this->currentPasswordRules(),
        ]);

        $user = Auth::user();

        $deleteAllHealthData($user);

        if (config('session.driver') === 'database') {
            DB::connection(config('session.connection'))
                ->table(config('session.table'))
                ->where('user_id', $user->id)
                ->delete();
        }

        tap($user, $logout(...))->delete();

        $this->redirect('/', navigate: true);
    }
}; ?>

<flux:modal name="confirm-user-deletion" :show="$errors->isNotEmpty()" focusable class="max-w-lg">
    <form method="POST" wire:submit="deleteUser" class="space-y-6">
        <div>
            <flux:heading size="lg">{{ __('Weet je zeker dat je je account wilt verwijderen?') }}</flux:heading>

            <flux:subheading>
                {{ __('Je account, gezondheidsgegevens en bronbestanden worden definitief verwijderd. Vul je wachtwoord in om te bevestigen.') }}
            </flux:subheading>
        </div>

        <flux:input wire:model="password" :label="__('Wachtwoord')" type="password" viewable />

        <div class="flex justify-end space-x-2 rtl:space-x-reverse">
            <flux:modal.close>
                <flux:button variant="filled">{{ __('Annuleren') }}</flux:button>
            </flux:modal.close>

            <flux:button variant="danger" type="submit" data-test="confirm-delete-user-button">
                {{ __('Account verwijderen') }}
            </flux:button>
        </div>
    </form>
</flux:modal>
