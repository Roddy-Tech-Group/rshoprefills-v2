@php
    use App\Models\Product;

    /** @var \App\Models\Order $order */

    // Brand logos aren't snapshotted on the order item, so resolve them from the
    // catalog here (view layer — keeps the controller untouched). product_id is
    // stored as a string; the catalog keys on the integer id.
    $productIds   = $order->items->pluck('product_id')->filter()->map(fn ($id) => (int) $id)->all();
    $productsById = Product::whereIn('id', $productIds)->get()->keyBy('id');

    $status = $order->status;

    // Status-driven hero copy + colour. The order is `pending` straight after
    // checkout (payment gateway hand-off is the next backend step).
    $ui = match ($status->value) {
        'completed' => [
            'tone' => 'emerald', 'icon' => 'check',
            'title' => 'Your order is complete',
            'line'  => 'Your redemption codes are ready below and have been emailed to you.',
        ],
        'processing' => [
            'tone' => 'blue', 'icon' => 'clock',
            'title' => 'Payment received',
            'line'  => 'We are fulfilling your order. Codes land in your email the moment each card is ready.',
        ],
        'failed' => [
            'tone' => 'red', 'icon' => 'cross',
            'title' => 'This order could not be completed',
            'line'  => 'Your payment did not go through and no charge was taken. You can try checking out again.',
        ],
        'refunded' => [
            'tone' => 'zinc', 'icon' => 'clock',
            'title' => 'This order was refunded',
            'line'  => 'The amount has been returned to your original payment method.',
        ],
        'cancelled' => [
            'tone' => 'zinc', 'icon' => 'cross',
            'title' => 'This order was cancelled',
            'line'  => 'No payment was taken for this order.',
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
        'completed'  => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        'processing' => 'bg-blue-50 text-blue-700 ring-blue-200',
        'failed'     => 'bg-red-50 text-red-700 ring-red-200',
        'refunded', 'cancelled' => 'bg-zinc-100 text-zinc-600 ring-zinc-200',
        default      => 'bg-amber-50 text-amber-700 ring-amber-200',
    };

    // Customer-facing payment label only — the gateway/provider name is never shown.
    $methodLabels  = ['card' => 'Card', 'mobile_money' => 'Mobile Money', 'crypto' => 'Crypto', 'wallet' => 'Wallet'];
    $paymentMethod = $methodLabels[$order->metadata['payment_method'] ?? ''] ?? 'Card';
    $deliveryEmail = $order->metadata['delivery_email'] ?? auth()->user()?->email;

    $sym   = Product::currencySymbol($order->currency ?: 'USD');
    $money = fn ($v) => $sym . number_format((float) $v, 2);

    $points    = (int) floor((float) $order->total * 0.5);
    $isPending = in_array($status->value, ['pending', 'processing'], true);
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
                <dd class="mt-0.5 font-semibold text-zinc-900">{{ $order->created_at->format('M j, Y') }}</dd>
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
                    $product = $productsById->get((int) $item->product_id);
                    $logo    = $product ? Product::brandLogoUrl($product->brand_key, $product->logo_url) : null;
                @endphp
                <li class="flex items-start gap-3 py-4">
                    <span class="flex h-14 w-14 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-white ring-1 ring-zinc-200">
                        @if ($logo)
                            <img src="{{ $logo }}" alt="" class="h-full w-full object-cover" loading="lazy">
                        @else
                            <span class="text-[11px] font-black uppercase text-zinc-700">{{ str($item->product_name)->substr(0, 2)->upper() }}</span>
                        @endif
                    </span>

                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-bold text-zinc-900">{{ $item->product_name }}</p>
                        <p class="mt-0.5 text-xs text-zinc-600">
                            Qty {{ $item->quantity }} &middot; {{ $money($item->unit_price) }} each
                        </p>
                        @if (! empty($item->fulfillment_data))
                            <div class="mt-2 rounded-lg bg-zinc-50 px-3 py-2 ring-1 ring-zinc-200">
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-zinc-500">Redemption details</p>
                                @foreach ((array) $item->fulfillment_data as $value)
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

                    <span class="shrink-0 text-sm font-bold tabular-nums text-zinc-900">{{ $money($item->total_price) }}</span>
                </li>
            @endforeach
        </ul>

        {{-- Totals --}}
        <div class="mt-1 space-y-2 border-t border-zinc-100 pt-4 text-sm">
            <div class="flex items-center justify-between text-zinc-600">
                <span>Subtotal</span>
                <span class="tabular-nums">{{ $money($order->subtotal) }}</span>
            </div>
            @if ((float) $order->tax > 0)
                <div class="flex items-center justify-between text-zinc-600">
                    <span>Tax</span>
                    <span class="tabular-nums">{{ $money($order->tax) }}</span>
                </div>
            @endif
            <div class="flex items-center justify-between border-t border-zinc-100 pt-2 text-base font-bold text-zinc-900">
                <span>Total paid</span>
                <span class="tabular-nums">{{ $money($order->total) }}</span>
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
                    ['Codes are issued', 'Each gift card is generated and attached to your order.'],
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
