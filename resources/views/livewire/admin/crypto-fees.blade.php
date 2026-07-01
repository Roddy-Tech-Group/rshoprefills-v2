{{-- FILE: resources/views/livewire/admin/crypto-fees.blade.php --}}
<?php

use App\Models\Setting;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new
#[Layout('components.layouts.admin')]
#[Title('Crypto Fee Settings')]
class extends Component {
    public array $networkFees = [];
    public float $serviceFeePct = 0.5;
    
    public array $savedKeys = [];

    public function mount()
    {
        $this->serviceFeePct = (float) Setting::get('crypto_service_fee_pct', 0.5);
        
        $networks = ['tron', 'ethereum', 'bnb', 'polygon', 'solana', 'bitcoin', 'litecoin'];
        foreach ($networks as $network) {
            $this->networkFees[$network] = (float) Setting::get("crypto_network_fee_{$network}", 1.00);
        }
    }

    public function save()
    {
        Setting::set(
            'crypto_service_fee_pct',
            $this->serviceFeePct,
            'number',
            'NOWPayments service fee percentage (added on top of the product price).'
        );
        $this->savedKeys['service_fee'] = now()->toTimeString();

        foreach ($this->networkFees as $network => $fee) {
            Setting::set(
                "crypto_network_fee_{$network}",
                (float) $fee,
                'number',
                "Estimated {$network} network fee in USD."
            );
        }
        
        $this->savedKeys['network_fees'] = now()->toTimeString();
    }
}; ?>

<div>
    <x-slot:heading>Crypto Fee Settings</x-slot:heading>
    
    <div class="mb-6 max-w-3xl">
        <p class="text-sm text-zinc-500 dark:text-zinc-400">
            Configure the fees that are passed onto the customer during crypto checkout. 
            The system uses `is_fee_paid_by_user` to force NOWPayments to charge the customer these fees on top of the product price.
            Network fees are advisory estimates shown before checkout; the final amount is determined by blockchain conditions.
        </p>
    </div>

    <form wire:submit="save" class="space-y-6 max-w-3xl">
        {{-- Service Fee Settings --}}
        <div class="rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-white/10 dark:bg-white/5">
            <div class="border-b border-zinc-200 bg-zinc-50/50 px-5 py-4 dark:border-white/10 dark:bg-white/5 rounded-t-xl">
                <h3 class="text-base font-semibold text-zinc-900 dark:text-white">NOWPayments Service Fee</h3>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                    The percentage commission NOWPayments takes per transaction (typically 0.5% for mono-currency).
                </p>
            </div>
            
            <div class="p-5">
                <flux:input wire:model="serviceFeePct" type="number" step="0.1" min="0" max="100" label="Service Fee (%)" />
                @if (isset($this->savedKeys['service_fee']))
                    <p class="mt-2 text-sm text-emerald-600 font-medium">Saved at {{ $this->savedKeys['service_fee'] }}</p>
                @endif
            </div>
        </div>

        {{-- Network Fee Estimates --}}
        <div class="rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-white/10 dark:bg-white/5">
            <div class="border-b border-zinc-200 bg-zinc-50/50 px-5 py-4 dark:border-white/10 dark:bg-white/5 rounded-t-xl">
                <h3 class="text-base font-semibold text-zinc-900 dark:text-white">Network Fee Estimates (USD)</h3>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                    Conservative estimates for the blockchain network fees shown to customers in the checkout breakdown.
                </p>
            </div>
            
            <div class="p-5 space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach ($networkFees as $network => $fee)
                        <flux:input wire:model="networkFees.{{ $network }}" type="number" step="0.01" min="0" label="{{ ucfirst($network) }} Network ($)" />
                    @endforeach
                </div>
                @if (isset($this->savedKeys['network_fees']))
                    <p class="mt-2 text-sm text-emerald-600 font-medium">Saved at {{ $this->savedKeys['network_fees'] }}</p>
                @endif
            </div>
        </div>

        <div class="flex justify-end">
            <flux:button type="submit" variant="primary">Save Changes</flux:button>
        </div>
    </form>
</div>
