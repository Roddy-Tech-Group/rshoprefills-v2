@php
    use App\Models\CurrencyRate;

    /**
     * Order details (left) is store-driven — it renders from the global Alpine cart
     * store, so quantity/remove are live. The payment panel (right) is a plain form
     * that POSTs to checkout.process; the backend resolves the cart server-side.
     */

    // Crypto coins for the crypto payment option — admin-managed currency rates.
    $cryptoCoins = CurrencyRate::query()->where('is_active', true)->where('type', 'crypto')
        ->orderBy('sort_order')->orderBy('code')
        ->get(['code', 'name', 'rate_per_usd', 'icon_path']);

    // Code-keyed map the Alpine total calculation reads from.
    $cryptoRatesForJs = $cryptoCoins->mapWithKeys(fn ($c) => [$c->code => [
        'code' => $c->code,
        'name' => $c->name,
        'perUsd' => (float) $c->rate_per_usd,
        'decimals' => $c->rate_per_usd < 0.01 ? 8 : ($c->rate_per_usd < 1 ? 4 : 2),
    ]]);

    // Mobile-money networks. Static for now.
    $momoNetworks = ['MTN', 'Orange'];

    // Crypto settlement networks. Static UI list — live per-coin availability and
    // minimum amounts come from the crypto gateway and are a backend follow-up.
    $cryptoNetworks = [
        ['key' => 'ethereum',  'label' => 'Ethereum'],
        ['key' => 'polygon',   'label' => 'Polygon'],
        ['key' => 'bsc',       'label' => 'BNB Smart Chain'],
        ['key' => 'arbitrum',  'label' => 'Arbitrum'],
        ['key' => 'optimism',  'label' => 'Optimism'],
        ['key' => 'avalanche', 'label' => 'Avalanche'],
        ['key' => 'tron',      'label' => 'Tron'],
        ['key' => 'solana',    'label' => 'Solana'],
        ['key' => 'ton',       'label' => 'Ton'],
    ];

    $methods = [
        ['key' => 'card',         'label' => 'Card',         'desc' => 'Visa, Mastercard'],
        ['key' => 'mobile_money', 'label' => 'Mobile Money', 'desc' => 'MTN, Orange'],
        ['key' => 'crypto',       'label' => 'Crypto',       'desc' => 'BTC, USDT, ETH and more'],
    ];

    $fieldClass = 'mt-1.5 w-full rounded-xl border border-zinc-200 bg-white px-3 py-2.5 text-base text-zinc-900 outline-none transition-colors focus:border-blue-500 focus:ring-2 focus:ring-blue-500/15';
@endphp

<x-layouts.app.header :title="'Checkout | RshopRefills'">

    <div class="min-h-full bg-zinc-100">
    <div class="mx-auto w-full max-w-7xl px-4 py-6 sm:px-6 lg:px-8 lg:py-10">

        @if (session('checkout_status'))
            <div class="mt-4 rounded-xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800 ring-1 ring-emerald-200">
                {{ session('checkout_status') }}
            </div>
        @endif

        <div x-data="checkoutPage(@js($cryptoRatesForJs))">

            {{-- Loading — until the cart store's first fetch resolves --}}
            <div x-show="!$store.cart.hydrated" class="flex items-center justify-center rounded-[20px] bg-white py-24 shadow-sm shadow-zinc-900/5 ring-1 ring-zinc-100">
                <svg class="h-7 w-7 animate-spin text-blue-600" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
            </div>

            {{-- Empty cart --}}
            <div x-show="$store.cart.hydrated && $store.cart.count === 0" x-cloak class="rounded-[20px] bg-white px-6 py-20 text-center shadow-sm shadow-zinc-900/5 ring-1 ring-zinc-100">
                <img src="{{ asset('assets/' . rawurlencode('Empty cart.png')) }}" alt="" class="mx-auto h-40 w-auto object-contain animate-float" loading="lazy">
                <p class="mt-4 text-base font-semibold text-zinc-900">Your cart is empty</p>
                <p class="mt-1 text-sm text-zinc-600">Add a gift card before heading to checkout.</p>
                <a href="{{ route('shop.gift-cards') }}" wire:navigate class="mt-5 inline-flex items-center gap-1.5 rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-blue-700">
                    Browse gift cards
                </a>
            </div>

            {{-- Checkout --}}
            <div x-show="$store.cart.hydrated && $store.cart.count > 0" x-cloak class="grid grid-cols-1 gap-6 lg:grid-cols-2 lg:gap-6">

                {{-- ─── LEFT: Order details ─── --}}
                <section class="self-start rounded-[20px] bg-white p-5 shadow-sm shadow-zinc-900/5 ring-1 ring-zinc-100 sm:p-6">
                    <h2 class="text-lg font-bold text-zinc-900">Order details</h2>

                    <ul class="mt-4 divide-y divide-zinc-100">
                        <template x-for="item in $store.cart.items" :key="item.id">
                            <li class="flex items-start gap-3 py-4">
                                {{-- Brand tile — matches the catalog product card (16:10, edge-to-edge logo). --}}
                                <span class="flex aspect-[16/10] w-28 shrink-0 items-center justify-center overflow-hidden rounded-[10px] bg-white shadow-sm ring-1 ring-zinc-200 sm:w-32">
                                    <template x-if="item.logo">
                                        <img :src="item.logo" alt="" class="h-full w-full object-cover">
                                    </template>
                                    <template x-if="!item.logo">
                                        <span class="text-lg font-black uppercase text-zinc-700" x-text="item.name.substring(0,2).toUpperCase()"></span>
                                    </template>
                                </span>

                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-bold text-zinc-900" x-text="item.name"></p>
                                    <p class="mt-0.5 truncate text-xs text-zinc-500" x-text="item.country"></p>
                                    <p class="mt-0.5 text-xs text-zinc-600">
                                        <span x-show="item.face_label" x-text="item.face_label + ' card'"></span>
                                    </p>
                                    <p class="mt-1 inline-flex items-center gap-1 text-xs text-zinc-600">
                                        <span x-text="item.quantity"></span> x
                                        <img src="{{ asset('assets/favicon.ico') }}" alt="" class="h-3.5 w-3.5 object-contain">
                                        <span class="font-semibold text-zinc-900" x-text="Math.floor((item.line_total_usd || 0) * 0.5)"></span> Points
                                    </p>
                                </div>

                                <div class="flex shrink-0 flex-col items-end gap-2">
                                    {{-- Quantity dropdown --}}
                                    <div x-data="{ qtyOpen: false }" @click.outside="qtyOpen = false" class="relative">
                                        <button type="button" @click="qtyOpen = !qtyOpen" class="flex h-9 w-16 items-center justify-between rounded-lg border border-zinc-200 bg-white px-2.5 text-sm font-bold text-zinc-900 transition-colors hover:border-zinc-400">
                                            <span x-text="item.quantity"></span>
                                            <svg class="h-4 w-4 text-zinc-500" :class="qtyOpen && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                                        </button>
                                        <div x-show="qtyOpen" x-cloak x-transition.opacity class="absolute right-0 z-20 mt-1 max-h-52 w-16 overflow-y-auto rounded-lg bg-white/90 p-1 shadow-lg shadow-zinc-900/10 ring-1 ring-zinc-200 backdrop-blur-xl [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                                            <template x-for="n in 10" :key="n">
                                                <button type="button" @click="$store.cart.setQty(item.id, n); qtyOpen = false"
                                                    :class="n === item.quantity ? 'bg-blue-50 text-blue-700' : 'text-zinc-700 hover:bg-zinc-100'"
                                                    class="flex w-full items-center justify-center rounded-md px-2 py-1.5 text-sm font-medium tabular-nums transition-colors"
                                                    x-text="n"></button>
                                            </template>
                                        </div>
                                    </div>
                                    <button type="button" @click="$store.cart.remove(item.id)" class="text-xs font-semibold text-red-600 transition-colors hover:text-red-700">
                                        Remove
                                    </button>
                                </div>
                            </li>
                        </template>
                    </ul>

                    {{-- Region notice --}}
                    <div class="mt-3 flex items-start gap-2.5 rounded-[10px] bg-amber-50 px-4 py-3.5">
                        <svg class="mt-0.5 h-5 w-5 shrink-0 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
                        </svg>
                        <p class="text-sm text-amber-800">Gift cards are region-locked. Make sure to update the region of the device you want to redeem the gift card with. For more information visit our learning page.</p>
                    </div>

                    {{-- Points + total --}}
                    <div class="mt-4 space-y-2 border-t border-zinc-100 pt-4 text-sm">
                        <div class="flex items-center justify-between">
                            <span class="inline-flex items-center gap-1.5 text-zinc-600">
                                Points you earn
                                <img src="{{ asset('assets/favicon.ico') }}" alt="" class="h-4 w-4 object-contain">
                            </span>
                            <span x-data="valueFlip()" x-effect="points(); flash()" class="inline-block font-bold text-zinc-900" x-text="points()">0</span>
                        </div>
                        <div class="flex items-start justify-between border-t border-zinc-100 pt-2 text-base font-bold text-zinc-900">
                            <span>Total estimate</span>
                            <span class="text-right">
                                <span x-data="valueFlip()" x-effect="$store.cart.subtotal; flash()" class="block tabular-nums" x-text="$store.cart.pay($store.cart.subtotal)"></span>
                                <span x-show="$store.cart.showUsd" class="block text-xs font-medium tabular-nums text-zinc-500" x-text="'(' + $store.cart.usd($store.cart.subtotalUsd) + ' USD)'"></span>
                            </span>
                        </div>
                    </div>
                </section>

                {{-- ─── RIGHT: Select payment method ─── --}}
                <form method="POST" action="{{ route('checkout.process') }}" @submit="submitting = true" class="rounded-[20px] bg-white p-5 shadow-sm shadow-zinc-900/5 ring-1 ring-zinc-100 sm:p-6">
                    @csrf
                    <input type="hidden" name="payment_method" :value="method">
                    <input type="hidden" name="crypto_coin" :value="crypto">
                    <input type="hidden" name="crypto_network" :value="network">
                    <input type="hidden" name="currency" :value="$store.cart.currency">

                    <h2 class="text-lg font-bold text-zinc-900">Select payment method</h2>

                    {{-- Delivery email --}}
                    <div class="mt-4">
                        <label for="delivery_email" class="text-sm font-semibold text-zinc-900">Delivery email</label>
                        <p class="mt-0.5 text-xs text-zinc-600">Redemption codes are emailed here right after payment.</p>
                        <input id="delivery_email" name="delivery_email" type="email" required
                            value="{{ old('delivery_email', auth()->user()?->email) }}"
                            placeholder="you@example.com" class="{{ $fieldClass }}">
                        @error('delivery_email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    {{-- Method tabs --}}
                    <div class="mt-5 grid grid-cols-3 gap-2">
                        @foreach ($methods as $m)
                            <button type="button" @click="method = '{{ $m['key'] }}'"
                                :class="method === '{{ $m['key'] }}' ? 'border-blue-600 bg-blue-50 ring-1 ring-blue-500/20' : 'border-zinc-200 hover:border-zinc-300'"
                                class="rounded-xl border bg-white px-3 py-2.5 text-left transition duration-150 active:scale-[0.98]">
                                <span class="block text-sm font-bold text-zinc-900">{{ $m['label'] }}</span>
                                <span class="mt-0.5 block text-[11px] leading-tight text-zinc-500">{{ $m['desc'] }}</span>
                            </button>
                        @endforeach
                    </div>

                    {{-- Card --}}
                    <div x-show="method === 'card'" x-collapse class="mt-5 space-y-3">
                        <div>
                            <label for="card_name" class="text-sm font-semibold text-zinc-900">Name on card</label>
                            <input id="card_name" name="card_name" type="text" autocomplete="cc-name" placeholder="Full name" class="{{ $fieldClass }}">
                        </div>
                        <div>
                            <label for="card_number" class="text-sm font-semibold text-zinc-900">Card number</label>
                            <input id="card_number" name="card_number" type="text" inputmode="numeric" autocomplete="cc-number" placeholder="1234 1234 1234 1234" class="{{ $fieldClass }} tabular-nums">
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label for="card_expiry" class="text-sm font-semibold text-zinc-900">Expiry</label>
                                <input id="card_expiry" name="card_expiry" type="text" inputmode="numeric" autocomplete="cc-exp" placeholder="MM / YY" class="{{ $fieldClass }} tabular-nums">
                            </div>
                            <div>
                                <label for="card_cvc" class="text-sm font-semibold text-zinc-900">CVC</label>
                                <input id="card_cvc" name="card_cvc" type="text" inputmode="numeric" autocomplete="cc-csc" placeholder="123" class="{{ $fieldClass }} tabular-nums">
                            </div>
                        </div>
                    </div>

                    {{-- Mobile money --}}
                    <div x-show="method === 'mobile_money'" x-collapse x-cloak class="mt-5 space-y-3">
                        {{-- Network — custom glass dropdown (replaces the native <select>). --}}
                        <div>
                            <label class="text-sm font-semibold text-zinc-900">Network</label>
                            <div
                                x-data="{ open: false, value: @js($momoNetworks[0]) }"
                                @click.outside="open = false"
                                @keydown.escape="open = false"
                                class="relative mt-1.5"
                            >
                                <input type="hidden" name="momo_network" :value="value">
                                <button
                                    type="button"
                                    @click="open = !open"
                                    :class="open ? 'border-blue-500 ring-2 ring-blue-500/15' : 'border-zinc-200 hover:border-zinc-400'"
                                    class="flex w-full items-center justify-between gap-2 rounded-xl border bg-white px-3 py-2.5 text-base text-zinc-900 transition-colors"
                                >
                                    <span x-text="value"></span>
                                    <svg class="h-4 w-4 shrink-0 text-zinc-500 transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </button>
                                <div
                                    x-show="open"
                                    x-cloak
                                    x-transition:enter="transition ease-out duration-150"
                                    x-transition:enter-start="opacity-0 -translate-y-1"
                                    x-transition:enter-end="opacity-100 translate-y-0"
                                    x-transition:leave="transition ease-in duration-100"
                                    x-transition:leave-end="opacity-0"
                                    class="absolute left-0 right-0 top-full z-20 mt-1 overflow-hidden rounded-xl border border-zinc-200 bg-white/80 p-1 shadow-xl shadow-zinc-900/10 backdrop-blur-xl"
                                    role="listbox"
                                >
                                    @foreach ($momoNetworks as $net)
                                        <button
                                            type="button"
                                            @click="value = @js($net); open = false"
                                            :class="value === @js($net) ? 'bg-blue-50 text-blue-700' : 'text-zinc-800 hover:bg-zinc-200'"
                                            class="flex w-full items-center justify-between rounded-lg px-3 py-2.5 text-left text-base font-medium transition-colors"
                                        >
                                            {{ $net }}
                                            <svg x-show="value === @js($net)" class="h-4 w-4 shrink-0 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                                            </svg>
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        <div>
                            <label for="momo_phone" class="text-sm font-semibold text-zinc-900">Mobile money number</label>
                            <input id="momo_phone" name="momo_phone" type="tel" autocomplete="tel" placeholder="Phone number" class="{{ $fieldClass }}">
                        </div>
                    </div>

                    {{-- Crypto --}}
                    <div x-show="method === 'crypto'" x-collapse x-cloak class="mt-5">
                        <p class="text-sm font-semibold text-zinc-900">Currency</p>
                        <div class="mt-1.5 grid grid-cols-4 gap-2 sm:grid-cols-5">
                            @forelse ($cryptoCoins as $coin)
                                <button type="button" @click="crypto = '{{ $coin->code }}'"
                                    :class="crypto === '{{ $coin->code }}' ? 'border-blue-600 bg-blue-50 ring-1 ring-blue-500/20' : 'border-zinc-200 hover:border-zinc-300'"
                                    class="flex flex-col items-center gap-1 rounded-xl border bg-white px-2 py-2.5 transition-colors">
                                    @if ($coin->icon_path)
                                        <img src="{{ asset('assets/' . $coin->icon_path) }}" alt="" class="h-6 w-6 rounded-full">
                                    @else
                                        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-amber-500 text-[10px] font-black text-white">{{ substr($coin->code, 0, 1) }}</span>
                                    @endif
                                    <span class="text-xs font-bold text-zinc-900">{{ $coin->code }}</span>
                                </button>
                            @empty
                                <p class="col-span-full text-sm text-zinc-600">No crypto currencies are enabled.</p>
                            @endforelse
                        </div>

                        <p class="mt-4 text-sm font-semibold text-zinc-900">Network</p>
                        <div class="mt-1.5 grid grid-cols-2 gap-2 sm:grid-cols-3">
                            @foreach ($cryptoNetworks as $net)
                                <button type="button" @click="network = '{{ $net['key'] }}'"
                                    :class="network === '{{ $net['key'] }}' ? 'border-blue-600 bg-blue-50 text-blue-700 ring-1 ring-blue-500/20' : 'border-zinc-200 text-zinc-700 hover:border-zinc-300'"
                                    class="rounded-xl border bg-white px-3 py-2 text-sm font-semibold transition-colors">
                                    {{ $net['label'] }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    {{-- Coupon --}}
                    <div class="mt-5" x-data="{ open: false }">
                        <button type="button" @click="open = !open" class="text-sm font-semibold text-zinc-900 underline underline-offset-2 transition-colors hover:text-blue-700">
                            I have a coupon
                        </button>
                        <div x-show="open" x-collapse x-cloak class="mt-2">
                            <input name="coupon_code" type="text" placeholder="Coupon code" class="{{ $fieldClass }} !mt-0">
                        </div>
                    </div>

                    @error('payment_method') <p class="mt-3 text-xs text-red-600">{{ $message }}</p> @enderror

                    {{-- Total --}}
                    <div class="mt-5 flex items-center justify-between border-t border-zinc-100 pt-5">
                        <span class="text-base font-bold text-zinc-900">Total amount to pay</span>
                        <span x-data="valueFlip()" x-effect="totalLabel(); flash()" class="inline-block text-lg font-extrabold tabular-nums text-zinc-900" x-text="totalLabel()">0.00</span>
                    </div>

                    {{-- Crypto safety warning --}}
                    <div x-show="method === 'crypto'" x-cloak class="mt-3 flex items-start gap-2 rounded-xl bg-red-50 px-3.5 py-3">
                        <svg class="mt-0.5 h-4 w-4 shrink-0 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
                        </svg>
                        <p class="text-xs text-red-700">
                            Send the exact amount shown to the address provided on the next step. Sending a
                            <span class="font-bold">different coin</span> or on a
                            <span class="font-bold">different network</span> will result in
                            <span class="font-bold">loss of your funds</span>.
                        </p>
                    </div>

                    {{-- Continue --}}
                    @auth
                        <button type="submit" :disabled="!canContinue() || submitting"
                            class="mt-4 flex w-full items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-3.5 text-base font-bold text-white shadow-md transition-colors hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50 disabled:hover:bg-blue-600">
                            <template x-if="!submitting">
                                <span class="flex items-center gap-2">
                                    Continue to payment
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/>
                                    </svg>
                                </span>
                            </template>
                            <template x-if="submitting">
                                <span class="flex items-center gap-2">
                                    <svg class="h-5 w-5 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <circle class="opacity-30" cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3"/>
                                        <path class="opacity-90" fill="currentColor" d="M12 3a9 9 0 0 1 9 9h-3a6 6 0 0 0-6-6V3z"/>
                                    </svg>
                                    Processing
                                </span>
                            </template>
                        </button>
                    @else
                        <a href="{{ route('login') }}" wire:navigate class="mt-4 flex w-full items-center justify-center rounded-xl bg-blue-600 px-4 py-3.5 text-base font-bold text-white shadow-md transition-colors hover:bg-blue-700">
                            Sign in to pay
                        </a>
                        <p class="mt-2 text-center text-xs text-zinc-600">You need an account to complete a purchase.</p>
                    @endauth

                    <p class="mt-3 flex items-center justify-center gap-1.5 text-xs text-zinc-600">
                        <svg class="h-4 w-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Secure checkout. Codes delivered instantly.
                    </p>
                </form>

            </div>
        </div>
    </div>
    </div>

    <script>
        window.checkoutPage = function (cryptoRates) {
            return {
                method: 'card',
                crypto: '',
                network: '',
                submitting: false,
                cryptoRates: cryptoRates || {},

                coinMeta() {
                    return this.cryptoRates[this.crypto] || null;
                },

                // "Total amount to pay" — in the chosen coin for crypto, otherwise
                // the customer's display currency.
                totalLabel() {
                    const usd = Number(this.$store.cart.subtotalUsd || 0);
                    if (this.method === 'crypto') {
                        const coin = this.coinMeta();
                        if (! coin) {
                            return 'Select a coin';
                        }
                        return (usd * coin.perUsd).toFixed(coin.decimals) + ' ' + coin.code;
                    }
                    return this.$store.cart.pay(this.$store.cart.subtotal);
                },

                // Loyalty points: 0.5 per USD of the order, floored.
                points() {
                    return Math.floor(Number(this.$store.cart.subtotalUsd || 0) * 0.5);
                },

                canContinue() {
                    if (! this.$store.cart.hydrated || this.$store.cart.count === 0) {
                        return false;
                    }
                    if (this.method === 'crypto') {
                        return !! this.crypto && !! this.network;
                    }
                    return true;
                },
            };
        };
    </script>

</x-layouts.app.header>
