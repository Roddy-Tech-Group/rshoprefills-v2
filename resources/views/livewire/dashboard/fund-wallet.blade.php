<?php

use App\Domain\Shared\Enums\Currency;
use App\Domain\Wallet\Services\WalletFundingService;
use App\Domain\Wallet\Services\WalletService;
use Livewire\Volt\Component;

/**
 * Fund Wallet — the action behind the dashboard wallet card's button.
 *
 * Collects an amount + currency and calls WalletFundingService::initializeFunding,
 * which creates the WalletFunding record and returns the gateway's hosted payment
 * link. We hand the customer off to that link — completing the payment and crediting
 * the wallet (gateway webhook) is backend / real-gateway work, intentionally not
 * done here.
 */
new class extends Component
{
    /** Funding form. */
    public string $currency = 'USD';

    public string $amount = '';

    /** Trigger style: 'full' (desktop wallet card) or 'compact' (mobile hero). */
    public string $variant = 'full';

    public function mount(string $currency = 'USD', string $variant = 'full'): void
    {
        $this->currency = Currency::tryFrom(strtoupper($currency))?->value ?? 'USD';
        $this->variant = $variant;
    }

    /** Selected currency, as the enum. */
    private function currencyEnum(): Currency
    {
        return Currency::tryFrom($this->currency) ?? Currency::USD;
    }

    /** Minimum funding amount for the selected currency. */
    public function minimum(): float
    {
        return $this->currencyEnum()->minimumFundingAmount();
    }

    /** Symbol for the selected currency. */
    public function symbol(): string
    {
        return $this->currencyEnum()->symbol();
    }

    /**
     * Initiate funding and hand off to the payment gateway's hosted page.
     */
    public function fund(WalletService $wallets, WalletFundingService $funding)
    {
        $this->validate([
            'currency' => ['required', 'in:'.implode(',', array_column(Currency::cases(), 'value'))],
            'amount' => ['required', 'numeric', 'gt:0'],
        ]);

        $currency = $this->currencyEnum();

        if ((float) $this->amount < $currency->minimumFundingAmount()) {
            $this->addError('amount', 'Minimum funding amount is '.$currency->symbol().number_format($currency->minimumFundingAmount(), 2).'.');

            return;
        }

        $wallet = $wallets->getOrCreateWallet(auth()->user(), $currency);

        try {
            $result = $funding->initializeFunding(
                user: auth()->user(),
                wallet: $wallet,
                amount: (float) $this->amount,
                currency: $currency,
            );

            // Dispatch event with the payment session payload for inline handling
            $this->dispatch('payment-session-initialized', [
                'session' => $result['payment_session']->toArray(),
                'reference' => $result['funding']->reference,
            ]);
        } catch (\Throwable $e) {
            $this->addError('amount', $e->getMessage());
        }
    }
}; ?>

<div
    x-data="{
        open: false,
        ccyOpen: false,
        paymentStatus: null,
        initPayment(session) {
            this.open = false;
            if (session.provider === 'flutterwave') {
                this.loadFlutterwave(session);
            }
        },
        loadFlutterwave(session) {
            const self = this;
            const runCheckout = () => {
                FlutterwaveCheckout({
                    ...session.payment_payload,
                    callback: function(data) {
                        console.log('FLW callback response', data);
                        self.paymentStatus = 'verifying';
                        
                        fetch(`/api/payment-sessions/${session.id}/verify`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                            },
                            body: JSON.stringify({
                                transaction_id: data.transaction_id || data.id
                            })
                        })
                        .then(res => res.json())
                        .then(resData => {
                            if (resData.status === 'confirmed') {
                                self.paymentStatus = 'confirmed';
                                setTimeout(() => {
                                    window.location.reload();
                                }, 1500);
                            } else {
                                self.paymentStatus = 'failed';
                            }
                        })
                        .catch(err => {
                            self.paymentStatus = 'failed';
                        });
                    },
                    onclose: function() {
                        console.log('Payment closed');
                    }
                });
            };

            if (typeof FlutterwaveCheckout !== 'undefined') {
                runCheckout();
                return;
            }

            const script = document.createElement('script');
            script.src = 'https://checkout.flutterwave.com/v3.js';
            script.onload = runCheckout;
            document.head.appendChild(script);
        }
    }"
    @payment-session-initialized.window="initPayment($event.detail.session)"
    class="shrink-0"
>
    {{-- Trigger. `full` = desktop wallet-card button; `compact` = mobile hero "Top Up". --}}
    @if ($variant === 'compact')
        <button
            type="button"
            @click="open = true"
            class="inline-flex shrink-0 items-center gap-1.5 rounded-xl bg-white px-4 py-2.5 text-sm font-semibold text-blue-700 transition-colors active:bg-blue-100"
        >
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            Top Up
        </button>
    @else
        <button
            type="button"
            @click="open = true"
            class="w-full rounded-xl bg-white px-3 py-2.5 text-sm font-semibold text-blue-700 transition-colors hover:bg-blue-100"
        >
            Fund Wallet
        </button>
    @endif

    {{-- Fund modal --}}
    <div
        x-show="open"
        x-cloak
        x-on:keydown.escape.window="open = false"
        class="fixed inset-0 z-[80] flex items-center justify-center p-4"
    >
        <div
            x-show="open"
            x-transition.opacity
            @click="open = false"
            class="absolute inset-0 bg-zinc-900/50 backdrop-blur-sm"
            aria-hidden="true"
        ></div>

        <div
            x-show="open"
            x-transition
            class="relative w-full max-w-sm rounded-2xl bg-white p-6 text-left shadow-2xl shadow-zinc-900/25"
            role="dialog"
            aria-modal="true"
        >
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h2 class="text-lg font-bold text-zinc-900">Fund wallet</h2>
                    <p class="mt-0.5 text-xs text-zinc-600">Add money to pay instantly at checkout.</p>
                </div>
                <x-close-button @click="open = false" />
            </div>

            {{-- Currency — custom dropdown selector. --}}
            <label class="mt-5 block text-xs font-semibold text-zinc-700">Currency</label>
            <div class="relative mt-1.5" @click.outside="ccyOpen = false">
                <button
                    type="button"
                    @click="ccyOpen = ! ccyOpen"
                    :aria-expanded="ccyOpen.toString()"
                    class="flex w-full items-center justify-between gap-2 rounded-xl border border-zinc-200 bg-white px-3 py-2.5 text-sm font-medium text-zinc-900 outline-none transition-colors hover:border-zinc-300 focus:outline-none focus-visible:border-blue-500 focus-visible:ring-2 focus-visible:ring-blue-500/15"
                >
                    <span>{{ $currency }} &middot; {{ \App\Domain\Shared\Enums\Currency::tryFrom($currency)?->label() }}</span>
                    <svg class="h-4 w-4 shrink-0 text-zinc-500 transition-transform duration-150" :class="ccyOpen && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>

                <div
                    x-show="ccyOpen"
                    x-cloak
                    x-transition:enter="transition ease-out duration-150"
                    x-transition:enter-start="opacity-0 -translate-y-1"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-100"
                    x-transition:leave-start="opacity-100 translate-y-0"
                    x-transition:leave-end="opacity-0 -translate-y-1"
                    class="absolute left-0 right-0 z-20 mt-1.5 overflow-hidden rounded-xl border border-zinc-200 bg-white p-1 shadow-xl shadow-zinc-900/10"
                    role="listbox"
                >
                    @foreach (\App\Domain\Shared\Enums\Currency::cases() as $c)
                        <button
                            type="button"
                            wire:click="$set('currency', '{{ $c->value }}')"
                            @click="ccyOpen = false"
                            role="option"
                            aria-selected="{{ $currency === $c->value ? 'true' : 'false' }}"
                            class="flex w-full items-center justify-between gap-2 rounded-lg px-3 py-2 text-left text-sm font-medium transition-colors {{ $currency === $c->value ? 'bg-blue-50 text-blue-700' : 'text-zinc-700 hover:bg-zinc-100' }}"
                        >
                            <span>{{ $c->value }} &middot; {{ $c->label() }}</span>
                            @if ($currency === $c->value)
                                <svg class="h-4 w-4 shrink-0 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                                </svg>
                            @endif
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Amount --}}
            <label class="mt-4 block text-xs font-semibold text-zinc-700">Amount</label>
            <div class="mt-1.5 flex items-stretch overflow-hidden rounded-xl border border-zinc-200 bg-white transition-colors focus-within:border-blue-500 focus-within:ring-2 focus-within:ring-blue-500/15">
                <span class="flex shrink-0 items-center border-r border-zinc-200 bg-zinc-50 px-3 text-sm font-semibold text-zinc-600">{{ $this->symbol() }}</span>
                <input
                    type="number"
                    step="any"
                    min="0"
                    wire:model="amount"
                    wire:keydown.enter="fund"
                    placeholder="0.00"
                    class="w-full min-w-0 flex-1 border-0 bg-transparent px-3 py-2.5 text-sm font-medium text-zinc-900 outline-none"
                >
            </div>
            <p class="mt-1.5 text-[11px] text-zinc-500">Minimum {{ $this->symbol() }}{{ number_format($this->minimum(), 2) }}.</p>

            @error('amount') <p class="mt-1 text-[11px] font-medium text-red-600">{{ $message }}</p> @enderror
            @error('currency') <p class="mt-1 text-[11px] font-medium text-red-600">{{ $message }}</p> @enderror

            <button
                type="button"
                wire:click="fund"
                wire:target="fund"
                wire:loading.attr="disabled"
                class="mt-5 flex w-full items-center justify-center gap-2 rounded-[15px] bg-blue-600 px-4 py-3 text-sm font-semibold text-white transition-colors hover:bg-blue-700 disabled:opacity-60"
            >
                <svg wire:loading wire:target="fund" class="h-4 w-4 shrink-0 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                <span wire:loading.remove wire:target="fund">Continue to payment</span>
                <span wire:loading wire:target="fund">Starting...</span>
            </button>

            <p class="mt-3 text-center text-[11px] text-zinc-500">Complete payment securely using Flutterwave.</p>
        </div>
    </div>

    {{-- Inline Payment processing overlay --}}
    <div
        x-show="paymentStatus"
        x-cloak
        class="fixed inset-0 z-[90] flex items-center justify-center p-4 bg-zinc-900/60 backdrop-blur-sm"
    >
        <div class="w-full max-w-sm rounded-2xl bg-white p-6 text-center shadow-2xl">
            <template x-if="paymentStatus === 'verifying'">
                <div class="flex flex-col items-center py-6">
                    <svg class="h-10 w-10 animate-spin text-blue-600" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <p class="mt-4 text-base font-bold text-zinc-900">Verifying your deposit...</p>
                    <p class="mt-1 text-xs text-zinc-600">Please do not close this window.</p>
                </div>
            </template>
            <template x-if="paymentStatus === 'confirmed'">
                <div class="flex flex-col items-center py-6">
                    <span class="flex h-12 w-12 items-center justify-center rounded-full bg-emerald-50 ring-8 ring-emerald-100">
                        <svg class="h-6 w-6 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                        </svg>
                    </span>
                    <p class="mt-4 text-base font-bold text-zinc-900">Wallet Funded Successfully!</p>
                    <p class="mt-1 text-xs text-zinc-600">Refreshing your balance...</p>
                </div>
            </template>
            <template x-if="paymentStatus === 'failed'">
                <div class="flex flex-col items-center py-6">
                    <span class="flex h-12 w-12 items-center justify-center rounded-full bg-red-50 ring-8 ring-red-100">
                        <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </span>
                    <p class="mt-4 text-base font-bold text-zinc-900">Verification Failed</p>
                    <p class="mt-1 text-xs text-zinc-600">We couldn't confirm the transaction. Please try again or contact support.</p>
                    <button @click="paymentStatus = null" class="mt-4 rounded-xl bg-zinc-100 px-4 py-2 text-xs font-semibold text-zinc-800 hover:bg-zinc-200">
                        Close
                    </button>
                </div>
            </template>
        </div>
    </div>
</div>
