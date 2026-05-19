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

            $resource = new \App\Http\Resources\PaymentSessionResource($result['payment_session']);

            // Dispatch event with the payment session payload for inline handling
            $this->dispatch('payment-session-initialized',
                session: $resource->toArray(request()),
                reference: $result['funding']->reference,
            );
        } catch (\Throwable $e) {
            $this->addError('amount', $e->getMessage());
        }
    }
}; ?>

<div
    x-data="{
        open: false,
        ccyOpen: false,
        session: null,
        selectedMethod: null,
        cardDetails: {
            card_number: '',
            cvv: '',
            expiry_month: '',
            expiry_year: '',
            card_holder: ''
        },
        pinValue: '',
        otpValue: '',
        momoDetails: {
            phone_number: '',
            network: ''
        },
        cryptoDetails: {
            pay_currency: ''
        },
        paymentState: 'idle', // idle, select_method, card_input, action_pin, action_otp, action_3ds, awaiting_transfer, awaiting_confirmation, momo_input, processing, success, error
        errorMessage: '',
        actionMessage: '',
        bankDetails: null,
        pollInterval: null,

        initPayment(session) {
            this.session = session;
            this.paymentState = 'select_method';
            this.selectedMethod = null;
            this.errorMessage = '';
            this.pinValue = '';
            this.otpValue = '';
            this.resetCardDetails();
            if (this.pollInterval) {
                clearInterval(this.pollInterval);
            }
        },

        resetCardDetails() {
            this.cardDetails = {
                card_number: '',
                cvv: '',
                expiry_month: '',
                expiry_year: '',
                card_holder: ''
            };
            this.momoDetails = {
                phone_number: '',
                network: ''
            };
        },

        formatCardNumber(e) {
            let value = e.target.value.replace(/\D/g, '');
            let formatted = '';
            for (let i = 0; i < value.length; i++) {
                if (i > 0 && i % 4 === 0) {
                    formatted += ' ';
                }
                formatted += value[i];
            }
            this.cardDetails.card_number = formatted;
        },

        selectPaymentMethod(method) {
            this.selectedMethod = method;
            if (method.type === 'card') {
                this.paymentState = 'card_input';
            } else if (method.type === 'bank_transfer') {
                this.paySession('bank_transfer');
            } else if (method.type === 'mobile_money') {
                this.momoDetails.network = method.supported_networks ? method.supported_networks[0] : 'MTN';
                this.paymentState = 'momo_input';
            } else if (method.type === 'crypto') {
                this.cryptoDetails.pay_currency = method.coin || 'usdt';
                this.paySession('crypto', { pay_currency: this.cryptoDetails.pay_currency });
            } else if (method.type === 'apple_pay') {
                this.paymentState = 'processing';
                setTimeout(() => {
                    this.paymentState = 'success';
                    setTimeout(() => { window.location.reload(); }, 1500);
                }, 2000);
            }
        },

        async paySession(method, dataPayload = {}) {
            this.paymentState = 'processing';
            this.errorMessage = '';
            try {
                let body = {
                    method: method,
                    details: dataPayload
                };
                if (this.pinValue) {
                    body.pin = this.pinValue;
                }
                if (this.otpValue) {
                    body.otp = this.otpValue;
                    body.flw_ref = this.session.payment_payload?.flw_ref || '';
                }

                let response = await fetch(`/api/payment-sessions/${this.session.id}/pay`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    },
                    body: JSON.stringify(body)
                });

                let resData;
                try {
                    resData = await response.json();
                } catch (jsonErr) {
                    resData = { message: 'Server error (' + response.status + '): ' + response.statusText };
                }

                if (!response.ok) {
                    this.paymentState = 'error';
                    this.errorMessage = resData.message || 'Payment initiation failed.';
                    return;
                }

                this.handlePayResponse(resData.data || resData);
            } catch (err) {
                this.paymentState = 'error';
                this.errorMessage = 'Network connection failed. Please check your internet.';
            }
        },

        handlePayResponse(sessionData) {
            this.session = sessionData;
            const status = sessionData.status;

            if (status === 'confirmed') {
                this.paymentState = 'success';
                setTimeout(() => { window.location.reload(); }, 2000);
            } else if (status === 'failed') {
                this.paymentState = 'error';
                this.errorMessage = sessionData.payment_payload?.failure_reason || 'Transaction could not be completed.';
            } else if (status === 'awaiting_customer_action') {
                const action = sessionData.payment_payload?.action;
                if (action === 'pin') {
                    this.paymentState = 'action_pin';
                    this.pinValue = '';
                } else if (action === 'otp') {
                    this.paymentState = 'action_otp';
                    this.otpValue = '';
                    this.actionMessage = sessionData.payment_payload?.message || 'Verification code sent';
                } else if (action === 'redirect') {
                    this.paymentState = 'action_3ds';
                    this.startStatusPolling();
                }
            } else if (status === 'awaiting_transfer') {
                this.paymentState = 'awaiting_transfer';
                this.bankDetails = sessionData.payment_payload?.bank_details || sessionData.payment_payload || null;
                this.startStatusPolling();
            } else if (status === 'awaiting_confirmation') {
                this.paymentState = 'awaiting_confirmation';
                this.actionMessage = sessionData.payment_payload?.message || 'Please accept the billing prompt on your device.';
                this.startStatusPolling();
            }
        },

        startStatusPolling() {
            if (this.pollInterval) clearInterval(this.pollInterval);
            this.pollInterval = setInterval(async () => {
                try {
                    let res = await fetch(`/api/payment-sessions/${this.session.id}/status`);
                    let data = await res.json();
                    if (data.status === 'confirmed') {
                        clearInterval(this.pollInterval);
                        this.paymentState = 'success';
                        setTimeout(() => { window.location.reload(); }, 2000);
                    } else if (data.status === 'failed') {
                        clearInterval(this.pollInterval);
                        this.paymentState = 'error';
                        this.errorMessage = data.payment_payload?.failure_reason || 'Transaction failed.';
                    }
                } catch (e) {}
            }, 4500);
        },

        copyToClipboard(text) {
            navigator.clipboard.writeText(text);
            alert('Copied to clipboard!');
        },

        closeModal() {
            this.open = false;
            this.session = null;
            if (this.pollInterval) {
                clearInterval(this.pollInterval);
            }
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
        x-on:keydown.escape.window="closeModal()"
        class="fixed inset-0 z-[80] flex items-center justify-center p-4"
    >
        <div
            x-show="open"
            x-transition.opacity
            @click="closeModal()"
            class="absolute inset-0 bg-zinc-900/50 backdrop-blur-sm"
            aria-hidden="true"
        ></div>

        <div
            x-show="open"
            x-transition
            class="relative w-full max-w-md rounded-2xl bg-white p-6 text-left shadow-2xl shadow-zinc-900/25"
            role="dialog"
            aria-modal="true"
        >
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h2 class="text-lg font-bold text-zinc-900">Fund wallet</h2>
                    <p class="mt-0.5 text-xs text-zinc-600">Add money to pay instantly at checkout.</p>
                </div>
                <x-close-button @click="closeModal()" />
            </div>

            <!-- STEP 1: Enter amount & select currency -->
            <div x-show="!session">
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
                        class="absolute left-0 right-0 z-20 mt-1.5 max-h-60 overflow-y-auto rounded-xl border border-zinc-200 bg-white p-1 shadow-xl shadow-zinc-900/10"
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
            </div>

            <!-- STEP 2: Active Payment Session Details & Wizard -->
            <div x-show="session" class="mt-4">
                <!-- Select Method -->
                <div x-show="paymentState === 'select_method'">
                    <h3 class="text-sm font-bold text-zinc-900">Select Payment Method</h3>
                    <p class="text-xs text-zinc-500 mb-4">Choose how you want to fund your account.</p>

                    <div class="grid grid-cols-1 gap-3">
                        <template x-for="method in session?.available_methods" :key="method.type">
                            <button
                                type="button"
                                @click="selectPaymentMethod(method)"
                                class="flex items-center gap-3 w-full p-4 border border-zinc-200 rounded-xl hover:border-blue-500 hover:bg-blue-50/30 text-left transition duration-150"
                            >
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-zinc-100 text-zinc-900 font-bold text-xs uppercase" x-text="method.type.substring(0,2)"></span>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-semibold text-zinc-950" x-text="method.label"></p>
                                    <p class="text-xs text-zinc-500 truncate" x-text="method.description"></p>
                                </div>
                                <svg class="h-5 w-5 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                                </svg>
                            </button>
                        </template>
                    </div>
                </div>

                <!-- Card Details Form -->
                <div x-show="paymentState === 'card_input'">
                    <div class="flex items-center gap-2 mb-4">
                        <button type="button" @click="paymentState = 'select_method'" class="text-zinc-500 hover:text-zinc-800 text-xs flex items-center gap-1">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg> Back
                        </button>
                    </div>
                    <h3 class="text-sm font-bold text-zinc-900 mb-4">Enter Card Details</h3>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-zinc-700">Cardholder Name</label>
                            <input type="text" x-model="cardDetails.card_holder" placeholder="e.g. John Doe" class="w-full mt-1.5 rounded-xl border border-zinc-200 px-3 py-2.5 text-sm font-medium text-zinc-900">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-zinc-700">Card Number</label>
                            <input type="text" @input="formatCardNumber" x-model="cardDetails.card_number" maxlength="19" placeholder="0000 0000 0000 0000" class="w-full mt-1.5 rounded-xl border border-zinc-200 px-3 py-2.5 text-sm font-medium text-zinc-900">
                        </div>

                        <div class="grid grid-cols-3 gap-3">
                            <div class="col-span-2">
                                <label class="block text-xs font-semibold text-zinc-700">Expiry (MM/YY)</label>
                                <div class="flex gap-2">
                                    <input type="text" x-model="cardDetails.expiry_month" placeholder="MM" maxlength="2" class="w-full mt-1.5 rounded-xl border border-zinc-200 px-3 py-2.5 text-sm font-medium text-zinc-900 text-center">
                                    <input type="text" x-model="cardDetails.expiry_year" placeholder="YY" maxlength="2" class="w-full mt-1.5 rounded-xl border border-zinc-200 px-3 py-2.5 text-sm font-medium text-zinc-900 text-center">
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-zinc-700 text-center">CVV</label>
                                <input type="password" x-model="cardDetails.cvv" placeholder="123" maxlength="4" class="w-full mt-1.5 rounded-xl border border-zinc-200 px-3 py-2.5 text-sm font-medium text-zinc-900 text-center">
                            </div>
                        </div>

                        <button @click="paySession('card', cardDetails)" class="w-full rounded-xl bg-blue-600 py-3 text-sm font-semibold text-white hover:bg-blue-700 mt-2">
                            Pay <span x-text="session?.display_currency + ' ' + Number(session?.amount).toFixed(2)"></span>
                        </button>
                    </div>
                </div>

                <!-- Card Auth: PIN Challenge -->
                <div x-show="paymentState === 'action_pin'">
                    <h3 class="text-sm font-bold text-zinc-900 mb-2">Card PIN Required</h3>
                    <p class="text-xs text-zinc-600 mb-4">Enter your card 4-digit security PIN to authorize payment.</p>
                    <div class="space-y-4">
                        <input type="password" x-model="pinValue" maxlength="4" placeholder="••••" class="w-full rounded-xl border border-zinc-200 px-3 py-3 text-center text-lg font-bold tracking-widest text-zinc-900">
                        <button @click="paySession('card', cardDetails)" class="w-full rounded-xl bg-blue-600 py-3 text-sm font-semibold text-white hover:bg-blue-700">
                            Confirm PIN
                        </button>
                    </div>
                </div>

                <!-- Card Auth: OTP Challenge -->
                <div x-show="paymentState === 'action_otp'">
                    <h3 class="text-sm font-bold text-zinc-900 mb-2">OTP Verification</h3>
                    <p class="text-xs text-zinc-600 mb-4" x-text="actionMessage"></p>
                    <div class="space-y-4">
                        <input type="text" x-model="otpValue" placeholder="123456" class="w-full rounded-xl border border-zinc-200 px-3 py-3 text-center text-lg font-bold tracking-widest text-zinc-900">
                        <button @click="paySession('card', cardDetails)" class="w-full rounded-xl bg-blue-600 py-3 text-sm font-semibold text-white hover:bg-blue-700">
                            Verify OTP
                        </button>
                    </div>
                </div>

                <!-- Card Auth: 3D Secure Redirect -->
                <div x-show="paymentState === 'action_3ds'">
                    <h3 class="text-sm font-bold text-zinc-900 mb-2">Secure Verification</h3>
                    <p class="text-xs text-zinc-600 mb-4">Please complete the secure authentication inside the window below.</p>
                    
                    <div class="w-full border border-zinc-200 rounded-xl overflow-hidden bg-zinc-50" style="height: 350px;">
                        <iframe :src="session?.payment_payload?.redirect_url" class="w-full h-full border-0"></iframe>
                    </div>

                    <button @click="startStatusPolling()" class="w-full mt-4 rounded-xl bg-blue-600 py-3 text-sm font-semibold text-white hover:bg-blue-700">
                        I Have Completed Payment
                    </button>
                </div>

                <!-- Mobile Money input -->
                <div x-show="paymentState === 'momo_input'">
                    <div class="flex items-center gap-2 mb-4">
                        <button type="button" @click="paymentState = 'select_method'" class="text-zinc-500 hover:text-zinc-800 text-xs flex items-center gap-1">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg> Back
                        </button>
                    </div>
                    <h3 class="text-sm font-bold text-zinc-900 mb-4">Mobile Money Details</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-zinc-700">Phone Number</label>
                            <input type="text" x-model="momoDetails.phone_number" placeholder="e.g. 237670000000" class="w-full mt-1.5 rounded-xl border border-zinc-200 px-3 py-2.5 text-sm font-medium text-zinc-900">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-zinc-700">Network Network</label>
                            <select x-model="momoDetails.network" class="w-full mt-1.5 rounded-xl border border-zinc-200 px-3 py-2.5 text-sm font-medium text-zinc-900 bg-white">
                                <template x-for="net in selectedMethod?.supported_networks" :key="net">
                                    <option :value="net" x-text="net"></option>
                                </template>
                            </select>
                        </div>

                        <button @click="paySession('mobile_money', momoDetails)" class="w-full rounded-xl bg-blue-600 py-3 text-sm font-semibold text-white hover:bg-blue-700">
                            Pay <span x-text="session?.display_currency + ' ' + Number(session?.amount).toFixed(2)"></span>
                        </button>
                    </div>
                </div>

                <!-- Bank Transfer virtual accounts display -->
                <div x-show="paymentState === 'awaiting_transfer'">
                    <h3 class="text-sm font-bold text-zinc-900 mb-2">Virtual Bank Transfer</h3>
                    <p class="text-xs text-zinc-600 mb-4">Please make a transfer to the temporary virtual account below:</p>

                    <div class="bg-zinc-50 border border-zinc-200 rounded-xl p-4 space-y-3">
                        <div class="flex justify-between items-center text-xs">
                            <span class="text-zinc-500">Bank Name</span>
                            <span class="font-bold text-zinc-900" x-text="bankDetails?.bank_name"></span>
                        </div>
                        <div class="flex justify-between items-center text-xs">
                            <span class="text-zinc-500">Account Number</span>
                            <div class="flex items-center gap-1.5">
                                <span class="font-bold text-zinc-900 text-sm" x-text="bankDetails?.account_number"></span>
                                <button type="button" @click="copyToClipboard(bankDetails?.account_number)" class="text-blue-600 hover:text-blue-800 text-[10px] font-semibold">Copy</button>
                            </div>
                        </div>
                        <div class="flex justify-between items-center text-xs">
                            <span class="text-zinc-500">Account Name</span>
                            <span class="font-bold text-zinc-900" x-text="bankDetails?.account_name"></span>
                        </div>
                        <div class="flex justify-between items-center text-xs border-t border-zinc-200 pt-2">
                            <span class="text-zinc-500">Amount</span>
                            <span class="font-extrabold text-blue-700 text-sm" x-text="session?.currency + ' ' + Number(bankDetails?.amount || session?.amount).toFixed(2)"></span>
                        </div>
                    </div>

                    <div class="flex flex-col items-center mt-5 text-center">
                        <svg class="h-5 w-5 animate-spin text-blue-600" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <p class="text-xs font-semibold text-zinc-800 mt-2">Waiting for transfer...</p>
                        <p class="text-[10px] text-zinc-500 mt-1">This account expires in 30 minutes. Status updates automatically.</p>
                    </div>
                </div>

                <!-- Mobile money push prompt -->
                <div x-show="paymentState === 'awaiting_confirmation'">
                    <h3 class="text-sm font-bold text-zinc-900 mb-2">Authorize on Phone</h3>
                    <p class="text-xs text-zinc-600 mb-4" x-text="actionMessage"></p>

                    <div class="flex flex-col items-center py-6 text-center">
                        <span class="flex h-14 w-14 items-center justify-center rounded-full bg-blue-50 ring-8 ring-blue-100/50 mb-4">
                            <svg class="h-7 w-7 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                            </svg>
                        </span>
                        <svg class="h-6 w-6 animate-spin text-blue-600" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <p class="text-xs font-semibold text-zinc-800 mt-3">Verifying payment authorization...</p>
                    </div>
                </div>

                <!-- Processing state -->
                <div x-show="paymentState === 'processing'" class="flex flex-col items-center py-8 text-center">
                    <svg class="h-10 w-10 animate-spin text-blue-600" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <p class="mt-4 text-sm font-bold text-zinc-900">Processing transaction...</p>
                    <p class="mt-1 text-xs text-zinc-500">Please do not close this window.</p>
                </div>

                <!-- Success state -->
                <div x-show="paymentState === 'success'" class="flex flex-col items-center py-8 text-center">
                    <span class="flex h-12 w-12 items-center justify-center rounded-full bg-emerald-50 ring-8 ring-emerald-100">
                        <svg class="h-6 w-6 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                        </svg>
                    </span>
                    <h3 class="mt-4 text-base font-bold text-zinc-950">Deposit Complete!</h3>
                    <p class="mt-1.5 text-xs text-zinc-600">Your wallet balance is being updated now.</p>
                </div>

                <!-- Error state -->
                <div x-show="paymentState === 'error'" class="flex flex-col items-center py-6 text-center">
                    <span class="flex h-12 w-12 items-center justify-center rounded-full bg-red-50 ring-8 ring-red-100">
                        <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </span>
                    <h3 class="mt-4 text-sm font-bold text-zinc-900">Payment Failed</h3>
                    <p class="mt-1.5 text-xs text-red-600 px-4" x-text="errorMessage"></p>
                    
                    <button type="button" @click="paymentState = 'select_method'" class="mt-6 rounded-xl bg-zinc-100 px-5 py-2.5 text-xs font-semibold text-zinc-800 hover:bg-zinc-200">
                        Try Another Method
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
