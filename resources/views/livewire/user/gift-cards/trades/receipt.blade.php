<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\GiftCardTrade;
use App\Enums\TradeStatus;
use App\Domain\Shared\Enums\Currency;

new
#[Layout('components.layouts.dashboard')]
#[Title('Transaction Receipt')]
class extends Component {
    public GiftCardTrade $trade;

    public function mount(GiftCardTrade $trade)
    {
        if ($trade->user_id !== auth()->id()) {
            abort(403);
        }

        if ($trade->status !== TradeStatus::Paid) {
            abort(404, 'Receipt not available yet.');
        }

        $this->trade = $trade->load(['rate.brand', 'bankAccount']);
    }
    
    public function downloadReceipt()
    {
        $this->dispatch('print-receipt');
    }
}; ?>

<div class="mx-auto max-w-2xl space-y-6">
    <div class="flex items-center justify-between mb-4">
        <flux:button href="{{ route('dashboard.gift-cards.trades.show', $trade) }}" variant="subtle" icon="arrow-left">Back to Trade</flux:button>
        <flux:button wire:click="downloadReceipt" variant="primary" icon="printer">Print / Download</flux:button>
    </div>

    <div id="receipt-container" class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 shadow-xl overflow-hidden print:shadow-none print:border-none print:w-full print:absolute print:top-0 print:left-0 print:m-0 print:p-8">
        
        {{-- Receipt Header --}}
        <div class="bg-zinc-50 dark:bg-zinc-800/50 p-8 text-center border-b border-zinc-200 dark:border-zinc-800">
            <div class="inline-flex items-center justify-center p-3 bg-green-100 text-green-600 rounded-full mb-4 dark:bg-green-500/20 dark:text-green-400">
                <flux:icon.check-circle class="size-10" />
            </div>
            <h1 class="text-2xl font-bold text-zinc-900 dark:text-white uppercase tracking-wider">Transaction Successful</h1>
            <p class="text-zinc-500 dark:text-zinc-400 mt-2 text-sm">Your payout has been processed successfully.</p>
        </div>

        {{-- Receipt Amount --}}
        <div class="p-8 pb-4 text-center">
            <div class="text-xs font-semibold text-zinc-500 uppercase tracking-widest mb-1">Amount Paid</div>
            <div class="text-5xl font-black text-zinc-900 dark:text-white">
                {{ Currency::tryFrom($trade->payout_currency)?->symbol() ?? $trade->payout_currency . ' ' }}{{ number_format($trade->calculated_payout, 2) }}
            </div>
        </div>

        {{-- Divider --}}
        <div class="px-8 py-2">
            <div class="border-t-2 border-dashed border-zinc-200 dark:border-zinc-700"></div>
        </div>

        {{-- Receipt Details --}}
        <div class="p-8 pt-4 space-y-6">
            
            <div class="flex justify-between items-center">
                <span class="text-sm text-zinc-500 dark:text-zinc-400">Transaction Date</span>
                <span class="text-sm font-medium text-zinc-900 dark:text-white">{{ $trade->updated_at->format('M j, Y - g:i A') }}</span>
            </div>

            <div class="flex justify-between items-center">
                <span class="text-sm text-zinc-500 dark:text-zinc-400">Receipt Number</span>
                <span class="text-sm font-medium text-zinc-900 dark:text-white font-mono">{{ strtoupper(substr($trade->uuid, 0, 12)) }}</span>
            </div>

            <div class="flex justify-between items-center">
                <span class="text-sm text-zinc-500 dark:text-zinc-400">Transaction Type</span>
                <span class="text-sm font-medium text-zinc-900 dark:text-white">Gift Card Trade Payout</span>
            </div>

            <div class="flex justify-between items-center">
                <span class="text-sm text-zinc-500 dark:text-zinc-400">Asset Traded</span>
                <span class="text-sm font-medium text-zinc-900 dark:text-white">{{ $trade->rate->brand->name ?? 'Gift Card' }} (${{ number_format($trade->declared_value, 2) }})</span>
            </div>

            <div class="px-4 py-4 bg-zinc-50 dark:bg-zinc-800/50 rounded-xl mt-4">
                <h3 class="text-xs font-semibold text-zinc-500 uppercase tracking-widest mb-3">Destination Details</h3>
                
                @if($trade->payout_method === 'wallet')
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-zinc-500 dark:text-zinc-400">Payout Method</span>
                        <span class="text-sm font-medium text-zinc-900 dark:text-white flex items-center gap-1.5">
                            <flux:icon.wallet class="size-4 text-purple-500" /> Rshop Wallet
                        </span>
                    </div>
                @else
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-sm text-zinc-500 dark:text-zinc-400">Payout Method</span>
                        <span class="text-sm font-medium text-zinc-900 dark:text-white flex items-center gap-1.5">
                            <flux:icon.building-library class="size-4 text-blue-500" /> Bank Transfer
                        </span>
                    </div>
                    @if($trade->bankAccount)
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-sm text-zinc-500 dark:text-zinc-400">Bank Name</span>
                            <span class="text-sm font-medium text-zinc-900 dark:text-white">{{ $trade->bankAccount->bank_name }}</span>
                        </div>
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-sm text-zinc-500 dark:text-zinc-400">Account No.</span>
                            <span class="text-sm font-medium text-zinc-900 dark:text-white">***{{ substr($trade->bankAccount->account_number, -4) }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-zinc-500 dark:text-zinc-400">Account Name</span>
                            <span class="text-sm font-medium text-zinc-900 dark:text-white">{{ $trade->bankAccount->account_name }}</span>
                        </div>
                    @endif
                @endif
            </div>

        </div>

        {{-- Footer --}}
        <div class="bg-zinc-50 dark:bg-zinc-800/50 p-6 flex flex-col items-center justify-center border-t border-zinc-200 dark:border-zinc-800 rounded-b-2xl">
            <img src="{{ asset('assets/Rshoprefillslogo.webp') }}" alt="{{ config('app.name') }} Logo" class="h-8 object-contain">
        </div>

    </div>

    {{-- Print Script --}}
    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('print-receipt', () => {
                window.print();
            });
        });
    </script>
</div>
