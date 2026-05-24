@php
    use App\Domain\Cart\Services\CartPricingService;
    use App\Models\Product;

    /** @var \App\Models\Product $product  An `esims`-category Product = one coverage region. */

    $variants = $product->variants;
    $hasPlans = $variants->isNotEmpty();
    $pricing  = app(CartPricingService::class);

    // "United States Data eSIM" or "United States eSIM" -> "United States".
    $regionLabel = (string) str($product->name)->replaceLast(' Data eSIM', '')->replaceLast(' eSIM', '')->trim();
    $flag        = Product::flagUrl($product->country_code);

    // Compact plan payload for Alpine: each variant is a data plan. `price` is the
    // marked-up payable USD (provider cost + markup, via CartPricingService).
    $plans = $variants->map(function ($v) use ($pricing, $product) {
        $v->setRelation('product', $product);
        $meta = $v->metadata ?? [];

        return [
            'id'       => $v->id,
            'data'     => (string) ($meta['data_limit'] ?? 'Data plan'),
            'voice'    => $meta['voice_limit'] ?? null,
            'sms'      => $meta['sms_limit'] ?? null,
            'supports_voice' => (bool) ($meta['supports_voice'] ?? false),
            'validity' => (int) ($meta['validity_days'] ?? 0),
            'network'  => $meta['network'] ?? null,
            'topup'    => (bool) ($meta['supports_topup'] ?? false),
            'price'    => round((float) $pricing->calculatePricing($v, 1)['unit_price_snapshot'], 2),
        ];
    })->values();

    // Coverage — distinct ISO codes across the region's plans.
    $coverage = collect($variants)
        ->flatMap(fn ($v) => (array) ($v->metadata['coverage'] ?? []))
        ->map(fn ($c) => strtoupper((string) $c))
        ->filter(fn ($c) => strlen($c) === 2)
        ->unique()
        ->values();
@endphp

<x-layouts.app.header :title="$regionLabel . ' eSIM | RshopRefills'">

    <div class="mx-auto w-full max-w-7xl px-4 py-6 sm:px-6 lg:px-8 lg:py-10">

        <a href="{{ route('shop.esims') }}" wire:navigate class="inline-flex items-center gap-1.5 text-sm font-medium text-zinc-600 transition-colors hover:text-zinc-900">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
            </svg>
            All eSIMs
        </a>

        <div class="mt-5 grid grid-cols-1 gap-8 lg:grid-cols-2 lg:gap-10">

            {{-- LEFT: region visual + activation --}}
            <div>
                <div class="lg:sticky lg:top-[156px]">
                    {{-- Visual panel --}}
                    <div class="flex flex-col items-center rounded-[24px] bg-blue-950 px-6 py-12 text-center">
                        <div class="relative flex h-36 w-36 items-center justify-center" aria-hidden="true">
                            <span class="signal-ring absolute inset-0 rounded-full border-2 border-blue-400/40"></span>
                            <span class="signal-ring absolute inset-0 rounded-full border-2 border-blue-400/40"></span>
                            <span class="signal-ring absolute inset-0 rounded-full border-2 border-blue-400/40"></span>
                            <span class="relative flex h-20 w-20 items-center justify-center overflow-hidden rounded-full bg-white shadow-lg">
                                @if ($flag)
                                    <img src="{{ $flag }}" alt="{{ $regionLabel }}" class="h-12 w-16 rounded-[3px] object-cover">
                                @else
                                    <svg class="h-10 w-10 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9 9 0 100-18 9 9 0 000 18zm0 0c2.5 0 4.5-4.03 4.5-9S14.5 3 12 3 7.5 7.03 7.5 12s2 9 4.5 9zM3.6 9h16.8M3.6 15h16.8"/>
                                    </svg>
                                @endif
                            </span>
                        </div>
                        <h1 class="mt-6 text-2xl font-bold text-white">{{ $regionLabel }} eSIM</h1>
                        <p class="mt-2 max-w-sm text-sm leading-relaxed text-blue-100">
                            {{ $product->description ?: 'High-speed eSIM. Scan the QR code to activate, no physical SIM required.' }}
                        </p>
                    </div>

                    {{-- Coverage --}}
                    @if ($coverage->isNotEmpty())
                        <div class="mt-4 rounded-2xl bg-white p-5 ring-1 ring-zinc-200 shadow-sm">
                            <p class="text-sm font-bold text-zinc-900">Coverage</p>
                            <p class="mt-0.5 text-xs text-zinc-600">Usable across {{ $coverage->count() }} {{ $coverage->count() === 1 ? 'country' : 'countries' }}.</p>
                            <div class="mt-3 flex flex-wrap gap-1.5">
                                @foreach ($coverage->take(24) as $iso)
                                    @if (Product::flagUrl($iso))
                                        <img src="{{ Product::flagUrl($iso) }}" alt="{{ $iso }}" title="{{ $iso }}" class="h-4 w-6 rounded-[2px] object-cover ring-1 ring-zinc-200" loading="lazy">
                                    @endif
                                @endforeach
                                @if ($coverage->count() > 24)
                                    <span class="text-xs font-medium text-zinc-600">+{{ $coverage->count() - 24 }} more</span>
                                @endif
                            </div>
                        </div>
                    @endif

                    {{-- How to activate --}}
                    <div class="mt-4 rounded-2xl bg-white p-5 ring-1 ring-zinc-200 shadow-sm">
                        <p class="text-sm font-bold text-zinc-900">How to activate</p>
                        <ol class="mt-3 space-y-2.5">
                            @foreach ([
                                'Buy the plan and pay. Your eSIM QR code is emailed to you instantly.',
                                $product->redeem_instructions ?: 'On your phone, open Settings, then Cellular, then Add Cellular Plan, and scan the QR code.',
                                'Connect when you land. Data activates automatically on arrival.',
                            ] as $i => $step)
                                <li class="flex gap-3 text-sm text-zinc-700">
                                    <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-blue-600 text-[11px] font-bold text-white">{{ $i + 1 }}</span>
                                    <span class="leading-snug">{{ $step }}</span>
                                </li>
                            @endforeach
                        </ol>
                    </div>
                </div>
            </div>

            {{-- RIGHT: plan picker + buy panel --}}
            <div
                x-data="esimDetail({ plans: @js($plans), checkoutUrl: '{{ route('shop.checkout') }}' })"
                class="flex flex-col gap-5"
            >
                <div>
                    <h2 class="text-xl font-bold text-zinc-900">Choose a plan</h2>
                    <p class="mt-1 text-sm text-zinc-600">Pick the data amount and validity that fits your trip.</p>
                </div>

                @if ($hasPlans)
                    {{-- Plan cards --}}
                    <div class="flex flex-col gap-3">
                        @foreach ($plans as $p)
                            <button
                                type="button"
                                @click="selectedId = {{ $p['id'] }}"
                                :class="selectedId === {{ $p['id'] }}
                                    ? 'border-blue-600 bg-blue-50 ring-1 ring-blue-500/20'
                                    : 'border-zinc-200 bg-white hover:border-zinc-300'"
                                class="flex items-center justify-between gap-4 rounded-2xl border-2 px-4 py-3.5 text-left transition-colors"
                            >
                                <div class="flex items-center gap-3.5">
                                    {{-- Radio dot --}}
                                    <span
                                        :class="selectedId === {{ $p['id'] }} ? 'border-blue-600' : 'border-zinc-300'"
                                        class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full border-2 transition-colors"
                                    >
                                        <span x-show="selectedId === {{ $p['id'] }}" class="h-2.5 w-2.5 rounded-full bg-blue-600"></span>
                                    </span>
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2">
                                            <p class="text-base font-bold text-zinc-900">{{ $p['data'] }}</p>
                                            @if ($p['supports_voice'])
                                                <span class="inline-flex items-center gap-1 rounded-full bg-blue-100 px-2 py-0.5 text-[10px] font-semibold tracking-wide text-blue-700">
                                                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                                    </svg>
                                                    +Number
                                                </span>
                                            @endif
                                        </div>
                                        <p class="mt-0.5 text-xs text-zinc-600">
                                            @if ($p['validity'] > 0)
                                                {{ $p['validity'] }} {{ $p['validity'] === 1 ? 'day' : 'days' }}
                                            @else
                                                Flexible
                                            @endif
                                            @if ($p['supports_voice'])
                                                <span class="text-zinc-400">&middot;</span> {{ $p['voice'] ?? 'Voice' }}
                                                <span class="text-zinc-400">&middot;</span> {{ $p['sms'] ?? 'SMS' }}
                                            @endif
                                            @if ($p['network'] && $p['network'] !== 'Multiple')
                                                <span class="text-zinc-400">&middot;</span> {{ $p['network'] }}
                                            @endif
                                        </p>
                                    </div>
                                </div>
                                <div class="shrink-0 text-right">
                                    <p class="text-base font-bold tabular-nums text-zinc-900">${{ number_format($p['price'], 2) }}</p>
                                    @if ($p['topup'])
                                        <p class="mt-0.5 text-[10px] font-semibold uppercase tracking-wide text-emerald-600">Top-up ready</p>
                                    @endif
                                </div>
                            </button>
                        @endforeach
                    </div>

                    {{-- Points --}}
                    <p class="flex items-center gap-1.5 text-sm font-semibold text-zinc-700">
                        Points you earn
                        <img src="{{ asset('assets/favicon.ico') }}" alt="coins" class="h-6 w-6 object-contain" loading="lazy">
                        <span class="text-zinc-900" x-text="pointsEarned()">0</span>
                    </p>

                    {{-- Total + actions --}}
                    <div class="rounded-2xl bg-white p-5 ring-1 ring-zinc-200 shadow-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-semibold text-zinc-600">Total</span>
                            <span class="text-xl font-extrabold tabular-nums text-zinc-900" x-text="priceLabel()">$0.00</span>
                        </div>

                        <div class="mt-4 grid grid-cols-2 gap-3">
                            {{-- Add to cart — morphs to a spinner then a checkmark on success. --}}
                            <button
                                type="button"
                                @click="addToCart()"
                                :disabled="!selectedId"
                                :class="cartState === 'success'
                                    ? 'border-emerald-500 bg-emerald-500 text-white animate-cart-pop'
                                    : 'border-blue-600 bg-white text-blue-600 hover:bg-blue-600 hover:text-white'"
                                class="relative flex h-[52px] items-center justify-center rounded-xl border-2 px-4 text-base font-semibold transition-colors duration-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/40 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <span
                                    x-show="cartState === 'idle'"
                                    x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0"
                                    x-transition:leave="transition ease-in duration-100" x-transition:leave-end="opacity-0"
                                    class="absolute inset-0 flex items-center justify-center"
                                >Add to cart</span>
                                <span
                                    x-show="cartState === 'loading'" style="display:none;"
                                    x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0"
                                    x-transition:leave="transition ease-in duration-100" x-transition:leave-end="opacity-0"
                                    class="absolute inset-0 flex items-center justify-center"
                                >
                                    <svg class="h-5 w-5 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <circle class="opacity-30" cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3"/>
                                        <path class="opacity-90" fill="currentColor" d="M12 3a9 9 0 0 1 9 9h-3a6 6 0 0 0-6-6V3z"/>
                                    </svg>
                                </span>
                                <span
                                    x-show="cartState === 'success'" style="display:none;"
                                    x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-90" x-transition:enter-end="opacity-100 scale-100"
                                    class="absolute inset-0 flex items-center justify-center gap-2"
                                >
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M5 13l4 4L19 7"/>
                                    </svg>
                                    Added
                                </span>
                            </button>
                            <button
                                type="button"
                                @click="buyNow()"
                                :disabled="!selectedId"
                                class="flex h-[52px] items-center justify-center rounded-xl bg-blue-600 px-4 text-base font-semibold text-white transition-colors hover:bg-blue-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/40 disabled:cursor-not-allowed disabled:opacity-50 disabled:hover:bg-blue-600"
                            >
                                Buy now
                            </button>
                        </div>
                    </div>

                    {{-- Trust badges --}}
                    <div class="flex flex-wrap items-center gap-x-5 gap-y-2 text-sm font-semibold text-zinc-900">
                        <span class="flex items-center gap-1.5">
                            <svg class="h-5 w-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                            Instant QR delivery
                        </span>
                        <span class="flex items-center gap-1.5">
                            <svg class="h-5 w-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            No roaming fees
                        </span>
                    </div>
                @else
                    {{-- No available plans --}}
                    <div class="rounded-2xl bg-zinc-50 px-4 py-10 text-center ring-1 ring-zinc-100">
                        <p class="text-base font-semibold text-zinc-900">No data plans available</p>
                        <p class="mt-1 text-sm text-zinc-600">This region has no plans in stock right now. Check back later.</p>
                        <a href="{{ route('shop.esims') }}" wire:navigate class="mt-4 inline-flex items-center gap-1.5 text-sm font-semibold text-blue-600 hover:text-blue-700">
                            Browse other regions
                        </a>
                    </div>
                @endif
            </div>

        </div>
    </div>

    <script>
        window.esimDetail = function ({ plans, checkoutUrl }) {
            return {
                plans: plans || [],
                checkoutUrl,
                selectedId: (plans && plans.length) ? plans[0].id : null,
                cartState: 'idle',
                _t: null,

                plan() {
                    return this.plans.find((p) => p.id === this.selectedId) || null;
                },
                price() {
                    return this.plan()?.price || 0;
                },
                priceLabel() {
                    return '$' + Number(this.price()).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                },
                // 0.5 loyalty points per USD of the payable total, floored.
                pointsEarned() {
                    return Math.floor(this.price() * 0.5);
                },

                async addToCart() {
                    if (this.cartState !== 'idle' || ! this.selectedId) {
                        return false;
                    }
                    this.cartState = 'loading';
                    const ok = await this.$store.cart.add(this.selectedId, 1);
                    if (ok) {
                        this.cartState = 'success';
                        clearTimeout(this._t);
                        this._t = setTimeout(() => { this.cartState = 'idle'; }, 1600);
                    } else {
                        this.cartState = 'idle';
                    }
                    return ok;
                },

                async buyNow() {
                    if (! this.selectedId || this.cartState === 'loading') {
                        return;
                    }
                    const ok = await this.$store.cart.add(this.selectedId, 1);
                    if (ok) {
                        window.location.href = this.checkoutUrl;
                    }
                },
            };
        };
    </script>

</x-layouts.app.header>
