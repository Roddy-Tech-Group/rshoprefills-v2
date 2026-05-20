@php
    use App\Models\Product;

    /** @var \App\Models\Order $order */

    $status = $order->order_status;

    // Status-driven hero copy + colour. The order is `pending` straight after
    // checkout while payment is verified, then moves through the fulfillment engine.
    $ui = match ($status->value) {
        'completed' => [
            'tone' => 'emerald', 'icon' => 'check',
            'title' => 'Your order is complete',
            'line'  => 'Your redemption codes are ready below and have been emailed to you.',
        ],
        'partially_completed' => [
            'tone' => 'amber', 'icon' => 'clock',
            'title' => 'Your order is partially complete',
            'line'  => 'Some items are ready below. The rest are still being processed and will be emailed shortly.',
        ],
        'processing' => [
            'tone' => 'blue', 'icon' => 'clock',
            'title' => 'Payment received',
            'line'  => 'We are fulfilling your order. Codes land in your email the moment each item is ready.',
        ],
        'failed' => [
            'tone' => 'red', 'icon' => 'cross',
            'title' => 'This order could not be completed',
            'line'  => 'Your payment did not go through and no charge was taken. You can try checking out again.',
        ],
        'cancelled' => [
            'tone' => 'zinc', 'icon' => 'cross',
            'title' => 'This order was cancelled',
            'line'  => 'No payment was taken for this order.',
        ],
        'requires_attention' => [
            'tone' => 'amber', 'icon' => 'clock',
            'title' => 'We are reviewing your order',
            'line'  => 'This order needs a quick review. Our team will update you by email shortly.',
        ],
        default => [
            'tone' => 'amber', 'icon' => 'clock',
            'title' => 'Thank you, your order is placed',
            'line'  => 'We are confirming your payment. Your redemption codes are emailed as soon as it clears.',
        ],
    };

    $tones = [
        'emerald' => ['bg' => 'bg-emerald-50', 'fg' => 'text-emerald-600', 'ring' => 'ring-emerald-100'],
        'blue'    => ['bg' => 'bg-blue-50',    'fg' => 'text-blue-600',    'ring' => 'ring-blue-100'],
        'amber'   => ['bg' => 'bg-amber-50',   'fg' => 'text-amber-600',   'ring' => 'ring-amber-100'],
        'red'     => ['bg' => 'bg-red-50',     'fg' => 'text-red-600',     'ring' => 'ring-red-100'],
        'zinc'    => ['bg' => 'bg-zinc-100',   'fg' => 'text-zinc-600',    'ring' => 'ring-zinc-200'],
    ];
    $tone = $tones[$ui['tone']];

    $statusBadge = match ($status->value) {
        'completed'           => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        'processing'          => 'bg-blue-50 text-blue-700 ring-blue-200',
        'failed'              => 'bg-red-50 text-red-700 ring-red-200',
        'cancelled'           => 'bg-zinc-100 text-zinc-600 ring-zinc-200',
        default               => 'bg-amber-50 text-amber-700 ring-amber-200',
    };

    // Customer-facing payment label only — the gateway/provider name is never shown.
    // The order stores wallet / flutterwave / crypto; card + mobile money both
    // settle through Flutterwave, so they surface as the generic "Card".
    $methodLabels  = ['wallet' => 'Wallet', 'crypto' => 'Crypto', 'flutterwave' => 'Card'];
    $paymentMethod = $methodLabels[$order->payment_method] ?? 'Card';
    $deliveryEmail = $order->metadata['delivery_email'] ?? auth()->user()?->email;

    // total_amount + line subtotals are stored in settlement_currency (USD per
    // CheckoutService); display_currency is a presentation hint that the backend's
    // FX pipeline doesn't yet convert against (exchange_rate_snapshot = 1.0 stub).
    // Render against settlement_currency so the symbol and number agree.
    $sym   = Product::currencySymbol($order->settlement_currency ?: 'USD');
    $money = fn ($v) => $sym . number_format((float) $v, 2);

    $points    = (int) floor((float) $order->total_amount * 0.5);
    $isPending = in_array($status->value, ['pending', 'processing'], true);

    $latestAttempt = $order->paymentAttempts->sortByDesc('created_at')->first();
    $paymentSession = $latestAttempt?->paymentSession;
    $sessionActive = $paymentSession && in_array($paymentSession->status->value ?? $paymentSession->status, ['pending', 'awaiting_payment']);
@endphp

<x-layouts.app.header :title="'Order ' . $order->order_number . ' | RshopRefills'">

<div class="min-h-full bg-zinc-100">
<div class="mx-auto w-full max-w-3xl px-4 py-6 sm:px-6 lg:py-10">

    {{-- Status hero --}}
    <section class="rounded-[20px] bg-white p-6 text-center shadow-sm shadow-zinc-900/5 ring-1 ring-zinc-100 sm:p-8">
        <span class="mx-auto flex h-16 w-16 items-center justify-center rounded-full {{ $tone['bg'] }} ring-8 {{ $tone['ring'] }}">
            <svg class="h-8 w-8 {{ $tone['fg'] }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.25" aria-hidden="true">
                @if ($ui['icon'] === 'check')
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                @elseif ($ui['icon'] === 'cross')
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                @else
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2m6-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                @endif
            </svg>
        </span>

        <h1 class="mt-5 text-2xl font-bold text-zinc-900">{{ $ui['title'] }}</h1>
        <p class="mx-auto mt-2 max-w-md text-sm leading-relaxed text-zinc-600">{{ $ui['line'] }}</p>

        <div class="mt-4 inline-flex items-center gap-2 rounded-full bg-zinc-50 px-4 py-1.5 ring-1 ring-zinc-200">
            <span class="text-xs font-medium text-zinc-500">Order</span>
            <span class="text-sm font-bold tabular-nums text-zinc-900">{{ $order->order_number }}</span>
        </div>

        @if ($deliveryEmail)
            <p class="mt-3 text-sm text-zinc-600">
                A confirmation has been sent to <span class="font-semibold text-zinc-900">{{ $deliveryEmail }}</span>
            </p>
        @endif
    </section>

    {{-- Embedded Payment Orchestration Component --}}
    @if ($sessionActive)
        <section
            x-data="{
                session: @js(new \App\Http\Resources\PaymentSessionResource($paymentSession)),
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
                paymentState: 'select_method', // select_method, card_input, action_pin, action_otp, action_3ds, awaiting_transfer, awaiting_confirmation, momo_input, processing, success, error
                errorMessage: '',
                actionMessage: '',
                bankDetails: null,
                pollInterval: null,

                init() {
                    this.resetCardDetails();
                    if (this.session.status === 'awaiting_transfer') {
                        this.paymentState = 'awaiting_transfer';
                        this.bankDetails = this.session.payment_payload?.bank_details || this.session.payment_payload || null;
                        this.startStatusPolling();
                    } else if (this.session.status === 'awaiting_confirmation') {
                        this.paymentState = 'awaiting_confirmation';
                        this.actionMessage = this.session.payment_payload?.message || 'Please authorize payment on your mobile money device.';
                        this.startStatusPolling();
                    } else if (this.session.status === 'awaiting_customer_action') {
                        const action = this.session.payment_payload?.action;
                        if (action === 'pin') {
                            this.paymentState = 'action_pin';
                        } else if (action === 'otp') {
                            this.paymentState = 'action_otp';
                            this.actionMessage = this.session.payment_payload?.message || 'Enter verification code';
                        } else if (action === 'redirect') {
                            this.paymentState = 'action_3ds';
                            this.startStatusPolling();
                        }
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
                }
            }"
            class="mt-6 rounded-[20px] bg-white p-6 shadow-sm shadow-zinc-900/5 ring-1 ring-zinc-100 sm:p-8"
        >
            <!-- Select Method -->
            <div x-show="paymentState === 'select_method'">
                <h3 class="text-base font-bold text-zinc-900 text-center">Complete Your Payment</h3>
                <p class="text-xs text-zinc-500 text-center mb-6">Choose your preferred payment method below to complete order #{{ $order->order_number }}.</p>

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
                
                <div class="w-full border border-zinc-200 rounded-xl overflow-hidden bg-zinc-50" style="height: 380px;">
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

            <!-- Bank Transfer virtual accounts display or Crypto invoice -->
            <div x-show="paymentState === 'awaiting_transfer'">
                <!-- If Bank Transfer details -->
                <template x-if="selectedMethod?.type === 'bank_transfer' || session?.payment_payload?.bank_details">
                    <div>
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
                    </div>
                </template>

                <!-- If Crypto details -->
                <template x-if="selectedMethod?.type === 'crypto' || session?.payment_payload?.qr_payload">
                    <div>
                        <h3 class="text-sm font-bold text-zinc-900 text-center mb-2">Crypto Payment Details</h3>
                        <p class="text-xs text-zinc-600 text-center mb-4">Send the exact amount of cryptocurrency shown to the address below:</p>

                        <div class="flex flex-col items-center gap-4 sm:flex-row sm:items-start bg-zinc-50 p-4 border border-zinc-200 rounded-xl">
                            <!-- QR Code -->
                            <div class="flex shrink-0 flex-col items-center rounded-lg bg-white p-2 border border-zinc-150">
                                <img 
                                    :src="'https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=' + encodeURIComponent(session?.payment_payload?.qr_payload || '')" 
                                    alt="Payment QR Code" 
                                    class="h-28 w-28 object-contain"
                                />
                                <span class="mt-1 text-[9px] font-bold uppercase tracking-wider text-zinc-400">Scan to pay</span>
                            </div>

                            <!-- Details -->
                            <div class="flex-1 w-full space-y-2 text-xs">
                                <div>
                                    <span class="text-[10px] font-semibold text-zinc-400 uppercase tracking-wider block">Cryptocurrency</span>
                                    <div class="flex items-center gap-1.5">
                                        <span class="rounded bg-blue-50 px-2 py-0.5 font-bold text-blue-700 uppercase" x-text="session?.payment_payload?.pay_currency || 'btc'"></span>
                                        <span class="text-[10px] font-medium text-zinc-500 uppercase" x-text="'Network: ' + (session?.payment_payload?.network || 'bitcoin')"></span>
                                    </div>
                                </div>
                                <div>
                                    <span class="text-[10px] font-semibold text-zinc-400 uppercase tracking-wider block">Amount to Send</span>
                                    <span class="font-bold text-zinc-900 text-sm" x-text="session?.payment_payload?.pay_amount"></span>
                                    <span class="font-bold text-zinc-500 uppercase" x-text="session?.payment_payload?.pay_currency"></span>
                                </div>
                                <div>
                                    <span class="text-[10px] font-semibold text-zinc-400 uppercase tracking-wider block">Deposit Address</span>
                                    <div class="mt-1 flex items-center gap-1">
                                        <input type="text" readonly :value="session?.payment_payload?.pay_address" class="w-full bg-zinc-100 px-2 py-1 rounded text-[10px] text-zinc-800 font-mono select-all outline-none">
                                        <button type="button" @click="copyToClipboard(session?.payment_payload?.pay_address)" class="text-blue-600 hover:text-blue-800 text-[10px] font-semibold shrink-0">Copy</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

                <div class="flex flex-col items-center mt-5 text-center">
                    <svg class="h-5 w-5 animate-spin text-blue-600" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <p class="text-xs font-semibold text-zinc-800 mt-2">Waiting for transfer...</p>
                    <p class="text-[10px] text-zinc-500 mt-1">Status updates automatically. This temporary reference expires in 30 minutes.</p>
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
                <h3 class="mt-4 text-base font-bold text-zinc-950">Payment Complete!</h3>
                <p class="mt-1.5 text-xs text-zinc-600">Your order status is refreshing now.</p>
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
        </section>
    @endif

    {{-- Order summary --}}
    <section class="mt-6 rounded-[20px] bg-white p-5 shadow-sm shadow-zinc-900/5 ring-1 ring-zinc-100 sm:p-6">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-bold text-zinc-900">Order summary</h2>
            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-bold ring-1 {{ $statusBadge }}">
                {{ $status->label() }}
            </span>
        </div>

        {{-- Meta rows --}}
        <dl class="mt-4 grid grid-cols-2 gap-x-4 gap-y-3 text-sm sm:grid-cols-3">
            <div>
                <dt class="text-xs font-medium text-zinc-500">Date placed</dt>
                <dd class="mt-0.5 font-semibold text-zinc-900">{{ ($order->placed_at ?? $order->created_at)->format('M j, Y') }}</dd>
            </div>
            <div>
                <dt class="text-xs font-medium text-zinc-500">Payment method</dt>
                <dd class="mt-0.5 font-semibold text-zinc-900">{{ $paymentMethod }}</dd>
            </div>
            <div>
                <dt class="text-xs font-medium text-zinc-500">Items</dt>
                <dd class="mt-0.5 font-semibold text-zinc-900">{{ $order->items->sum('quantity') }}</dd>
            </div>
        </dl>

        {{-- Line items --}}
        <ul class="mt-5 divide-y divide-zinc-100 border-t border-zinc-100">
            @foreach ($order->items as $item)
                @php
                    // product_snapshot is the catalog Product captured at order time.
                    $snap     = $item->product_snapshot ?? [];
                    $brandKey = $snap['brand_key'] ?? null;
                    $name     = $brandKey ? Product::brandDisplayName($brandKey) : ($snap['name'] ?? 'Item');
                    $logo     = Product::brandLogoUrl($brandKey, $snap['logo_url'] ?? null);
                @endphp
                <li class="flex items-start gap-3 py-4">
                    {{-- Brand tile — matches the catalog / cart product card (16:10, edge-to-edge logo). --}}
                    <span class="flex aspect-[16/10] w-24 shrink-0 items-center justify-center overflow-hidden rounded-[15px] bg-white shadow-sm ring-1 ring-zinc-200 sm:w-28">
                        @if ($logo)
                            <img src="{{ $logo }}" alt="" class="h-full w-full object-cover" loading="lazy">
                        @else
                            <span class="text-lg font-black uppercase text-zinc-700">{{ str($name)->substr(0, 2)->upper() }}</span>
                        @endif
                    </span>

                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-bold text-zinc-900">{{ $name }}</p>
                        <p class="mt-0.5 text-xs text-zinc-600">
                            Qty {{ $item->quantity }} &middot; {{ $money($item->display_amount) }} each
                        </p>
                        @if (! empty($item->fulfillment_payload))
                            <div class="mt-2 rounded-lg bg-zinc-50 px-3 py-2 ring-1 ring-zinc-200">
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-zinc-500">Redemption details</p>
                                @foreach ((array) $item->fulfillment_payload as $value)
                                    @if (is_scalar($value))
                                        <p class="mt-1 text-sm font-bold tabular-nums text-zinc-900">{{ $value }}</p>
                                    @endif
                                @endforeach
                            </div>
                        @else
                            <p class="mt-1.5 inline-flex items-center gap-1 text-xs font-medium text-zinc-500">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2m6-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Code emailed once payment clears
                            </p>
                        @endif
                    </div>

                    <span class="shrink-0 text-sm font-bold tabular-nums text-zinc-900">{{ $money($item->subtotal_amount) }}</span>
                </li>
            @endforeach
        </ul>

        {{-- Totals --}}
        <div class="mt-1 space-y-2 border-t border-zinc-100 pt-4 text-sm">
            <div class="flex items-center justify-between text-base font-bold text-zinc-900">
                <span>Total paid</span>
                <span class="tabular-nums">{{ $money($order->total_amount) }}</span>
            </div>
            <div class="flex items-center justify-between pt-1 text-zinc-600">
                <span class="inline-flex items-center gap-1.5">
                    Points earned
                    <img src="{{ asset('assets/favicon.ico') }}" alt="" class="h-4 w-4 object-contain">
                </span>
                <span class="font-bold tabular-nums text-zinc-900">{{ number_format($points) }}</span>
            </div>
        </div>
    </section>

    {{-- What happens next — only while the order is still being settled --}}
    @if ($isPending)
        <section class="mt-6 rounded-[20px] bg-white p-5 shadow-sm shadow-zinc-900/5 ring-1 ring-zinc-100 sm:p-6">
            <h2 class="text-lg font-bold text-zinc-900">What happens next</h2>
            <ol class="mt-4 space-y-4">
                @foreach ([
                    ['Confirm payment', 'We verify your payment with the provider. This is usually instant.'],
                    ['Codes are issued', 'Each item is fulfilled and its redemption code attached to your order.'],
                    ['Delivery', 'Codes arrive at ' . ($deliveryEmail ?: 'your email') . ' and stay available on this page.'],
                ] as $i => $step)
                    <li class="flex gap-3">
                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-blue-50 text-xs font-bold text-blue-700 ring-1 ring-blue-100">{{ $i + 1 }}</span>
                        <div>
                            <p class="text-sm font-semibold text-zinc-900">{{ $step[0] }}</p>
                            <p class="mt-0.5 text-sm text-zinc-600">{{ $step[1] }}</p>
                        </div>
                    </li>
                @endforeach
            </ol>
        </section>
    @endif

    {{-- Region notice --}}
    <div class="mt-6 flex items-start gap-2.5 rounded-[10px] bg-amber-50 px-4 py-3.5">
        <svg class="mt-0.5 h-5 w-5 shrink-0 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
        </svg>
        <p class="text-sm text-amber-800">Gift cards are region-locked. Make sure to update the region of the device you want to redeem the gift card with. For more information visit our learning page.</p>
    </div>

    {{-- Actions --}}
    <div class="mt-6 grid grid-cols-1 gap-3 sm:grid-cols-2">
        <a href="{{ route('dashboard') }}" wire:navigate
            class="flex items-center justify-center rounded-xl border-2 border-blue-600 bg-white px-4 py-3 text-base font-semibold text-blue-600 transition-colors hover:bg-blue-600 hover:text-white">
            Go to dashboard
        </a>
        <a href="{{ route('shop.gift-cards') }}" wire:navigate
            class="flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-3 text-base font-semibold text-white transition-colors hover:bg-blue-700">
            Continue shopping
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/>
            </svg>
        </a>
    </div>

    <p class="mt-5 flex items-center justify-center gap-1.5 text-center text-xs text-zinc-600">
        <svg class="h-4 w-4 shrink-0 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        Need help with this order? Contact support with reference {{ $order->order_number }}.
    </p>

</div>
</div>

</x-layouts.app.header>
