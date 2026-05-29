<?php

use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component {
    public string $password = '';

    /**
     * Delete the currently authenticated user.
     */
    public function deleteUser(Logout $logout): void
    {
        $this->validate([
            'password' => ['required', 'string', 'current_password'],
        ]);

        tap(Auth::user(), $logout(...))->delete();

        $this->redirect('/', navigate: true);
    }
}; ?>

<section class="flex items-center justify-between gap-3">
    <div class="min-w-0">
        <p class="text-sm font-semibold text-black">{{ __('Delete account') }}</p>
        <p class="mt-0.5 text-xs text-zinc-600">{{ __('Permanent and cannot be undone') }}</p>
    </div>

    <flux:modal.trigger name="confirm-user-deletion">
        <button
            type="button"
            x-data=""
            x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
            class="shrink-0 text-sm font-medium text-red-600 transition-colors hover:text-red-700 hover:underline focus:outline-none focus-visible:underline"
        >
            {{ __('Delete account') }}
        </button>
    </flux:modal.trigger>

    <flux:modal name="confirm-user-deletion" :show="$errors->isNotEmpty()" focusable class="max-w-lg">
        <form wire:submit="deleteUser" class="space-y-5">
            <div>
                <flux:heading size="lg">{{ __('Delete your account?') }}</flux:heading>
                <flux:subheading>
                    {{ __('Everything will be permanently removed: orders, wallet balance, and transaction history. Enter your password to confirm.') }}
                </flux:subheading>
            </div>

            <flux:input wire:model="password" id="password" label="{{ __('Password') }}" type="password" name="password" />

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>

                <flux:button variant="danger" type="submit">{{ __('Delete account') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</section>
