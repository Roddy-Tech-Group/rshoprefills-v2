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

    $sym   = Product::currencySymbol($order->display_currency ?: 'USD');
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
                paymentStatus: null,
                copied: false,
                pollInterval: null,
                init() {
                    @if ($paymentSession->provider === 'nowpayments')
                        this.startPolling();
                    @endif
                },
                pay() {
                    @if ($paymentSession->provider === 'flutterwave')
                        this.loadFlutterwave();
                    @endif
                },
                loadFlutterwave() {
                    const self = this;
                    const payload = @json($paymentSession->payment_payload);
                    const runCheckout = () => {
                        FlutterwaveCheckout({
                            ...payload,
                            callback: function(data) {
                                console.log('FLW callback response', data);
                                self.paymentStatus = 'verifying';
                                
                                fetch('/api/payment-sessions/{{ $paymentSession->id }}/verify', {
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
                },
                startPolling() {
                    const self = this;
                    this.pollInterval = setInterval(() => {
                        fetch('/api/payment-sessions/{{ $paymentSession->id }}/status')
                            .then(res => res.json())
                            .then(data => {
                                if (data.status === 'confirmed') {
                                    clearInterval(self.pollInterval);
                                    self.paymentStatus = 'confirmed';
                                    setTimeout(() => {
                                        window.location.reload();
                                    }, 1500);
                                } else if (data.status === 'failed') {
                                    clearInterval(self.pollInterval);
                                    self.paymentStatus = 'failed';
                                }
                            });
                    }, 5000);
                },
                copyToClipboard(text) {
                    navigator.clipboard.writeText(text);
                    this.copied = true;
                    setTimeout(() => this.copied = false, 2000);
                }
            }"
            class="mt-6 rounded-[20px] bg-white p-6 shadow-sm shadow-zinc-900/5 ring-1 ring-zinc-100 sm:p-8"
        >
            @if ($paymentSession->provider === 'flutterwave')
                <div class="text-center">
                    <h2 class="text-lg font-bold text-zinc-900">Complete your payment</h2>
                    <p class="mt-1 text-sm text-zinc-600">Secure checkout using cards, bank transfer, or mobile money.</p>
                    
                    <div class="mt-6 flex flex-col items-center justify-center rounded-xl bg-zinc-50 py-4 px-6 ring-1 ring-zinc-100">
                        <span class="text-xs font-semibold uppercase tracking-wider text-zinc-500">Amount Due</span>
                        <span class="mt-1 text-3xl font-extrabold tabular-nums text-zinc-900">
                            {{ $money($order->total_amount) }}
                        </span>
                    </div>

                    <button
                        type="button"
                        @click="pay()"
                        class="mt-6 flex w-full items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-3.5 text-base font-semibold text-white transition-colors hover:bg-blue-700 active:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                    >
                        <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/>
                        </svg>
                        Pay Now
                    </button>
                </div>
            @elseif ($paymentSession->provider === 'nowpayments')
                <div>
                    <h2 class="text-lg font-bold text-zinc-900 text-center">Crypto Payment</h2>
                    <p class="mt-1 text-sm text-zinc-600 text-center">Please send the exact amount of cryptocurrency to the address below.</p>

                    <div class="mt-6 flex flex-col items-center gap-6 sm:flex-row sm:items-start">
                        <!-- QR Code -->
                        <div class="flex shrink-0 flex-col items-center rounded-xl bg-zinc-50 p-4 ring-1 ring-zinc-100">
                            <img 
                                src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data={{ urlencode($paymentSession->payment_payload['qr_payload'] ?? '') }}" 
                                alt="Payment QR Code" 
                                class="h-36 w-36 object-contain"
                            />
                            <span class="mt-2 text-[10px] font-bold uppercase tracking-wider text-zinc-500">Scan to pay</span>
                        </div>

                        <!-- Payment Details -->
                        <div class="flex-1 w-full space-y-4">
                            <div>
                                <span class="text-xs font-semibold text-zinc-500 uppercase tracking-wider block">Cryptocurrency</span>
                                <div class="mt-1 flex items-center gap-2">
                                    <span class="rounded-lg bg-blue-50 px-2.5 py-1 text-xs font-bold text-blue-700 uppercase">
                                        {{ $paymentSession->payment_payload['pay_currency'] ?? 'btc' }}
                                    </span>
                                    <span class="text-xs font-medium text-zinc-500 uppercase">Network: {{ $paymentSession->payment_payload['network'] ?? 'bitcoin' }}</span>
                                </div>
                            </div>

                            <div>
                                <span class="text-xs font-semibold text-zinc-500 uppercase tracking-wider block">Amount to Send</span>
                                <div class="mt-1 flex items-center gap-1.5">
                                    <span class="text-xl font-bold tabular-nums text-zinc-900">
                                        {{ $paymentSession->payment_payload['pay_amount'] ?? '0' }}
                                    </span>
                                    <span class="text-sm font-semibold text-zinc-500 uppercase">
                                        {{ $paymentSession->payment_payload['pay_currency'] ?? 'btc' }}
                                    </span>
                                </div>
                            </div>

                            <div>
                                <span class="text-xs font-semibold text-zinc-500 uppercase tracking-wider block">Deposit Address</span>
                                <div class="mt-1 flex items-stretch rounded-xl border border-zinc-200 bg-zinc-50 p-1.5 focus-within:border-blue-500">
                                    <input 
                                        type="text" 
                                        readonly 
                                        value="{{ $paymentSession->payment_payload['pay_address'] ?? '' }}" 
                                        class="w-full min-w-0 flex-1 border-0 bg-transparent px-2 text-xs font-medium tabular-nums text-zinc-800 outline-none"
                                    />
                                    <button 
                                        type="button" 
                                        @click="copyToClipboard('{{ $paymentSession->payment_payload['pay_address'] ?? '' }}')"
                                        class="flex items-center justify-center rounded-lg bg-white px-3 py-1.5 text-xs font-bold text-zinc-700 shadow-sm ring-1 ring-zinc-200 transition-colors hover:bg-zinc-50 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    >
                                        <span x-show="!copied">Copy</span>
                                        <span x-show="copied" x-cloak class="text-blue-600">Copied!</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Polling State -->
                    <div class="mt-6 flex items-center gap-3 rounded-xl bg-blue-50/50 p-4 ring-1 ring-blue-50">
                        <svg class="h-5 w-5 animate-spin text-blue-600" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-zinc-900">Awaiting block confirmation</p>
                            <p class="text-xs text-zinc-600">Required: {{ $paymentSession->payment_payload['confirmations_required'] ?? 2 }} confirmations. We check status every 5s.</p>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Verification processing overlay --}}
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
                            <p class="mt-4 text-base font-bold text-zinc-900">Verifying your payment...</p>
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
                            <p class="mt-4 text-base font-bold text-zinc-900">Payment Confirmed!</p>
                            <p class="mt-1 text-xs text-zinc-600">Refreshing your order status...</p>
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
