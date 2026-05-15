@php
    use App\Models\CurrencyRate;
    use App\Models\Product;

    /**
     * @var \App\Models\Product $product  The country-specific representative Product for this brand.
     * @var string $brandKey  Raw Zendit brand key.
     */

    $brandName = Product::brandDisplayName($brandKey);
    $logoSrc   = Product::brandLogoUrl($brandKey, $product->logo_url);

    $variants    = $product->variants;
    $fixedDenoms = $variants->where('is_variable', false)->sortBy('retail_price')->values();
    $variable    = $variants->where('is_variable', true)->first();

    $currency = $product->currency_code ?: 'USD';

    $symbols = [
        'USD' => '$', 'EUR' => '€', 'GBP' => '£', 'NGN' => '₦', 'XAF' => 'FCFA', 'ZAR' => 'R',
        'KES' => 'KSh', 'GHS' => '₵', 'EGP' => 'E£', 'MAD' => 'DH', 'CAD' => 'CA$', 'AUD' => 'A$',
        'JPY' => '¥', 'CNY' => '¥', 'INR' => '₹', 'BRL' => 'R$', 'AED' => 'AED', 'SAR' => 'SAR',
        'TRY' => '₺', 'CHF' => 'Fr', 'MXN' => 'MX$', 'KRW' => '₩', 'SGD' => 'S$', 'HKD' => 'HK$',
        'TWD' => 'NT$', 'THB' => '฿', 'IDR' => 'Rp', 'PHP' => '₱', 'VND' => '₫', 'MYR' => 'RM',
    ];
    $sym = fn (string $code) => $symbols[strtoupper($code)] ?? ($code . ' ');

    $flag = function (?string $code) {
        if (! $code || strlen($code) !== 2) {
            return '';
        }
        $code = strtoupper($code);
        return mb_chr(0x1F1E6 + ord($code[0]) - ord('A')) . mb_chr(0x1F1E6 + ord($code[1]) - ord('A'));
    };

    $countryNames = array_flip(config('countries.codes', [])); // ISO → name
    $countryName  = $countryNames[strtoupper($product->country_code)] ?? $product->country_code;

    // Brand colour: config override → Zendit-synced colour → brand blue accent fallback.
    $brandCardColor = Product::brandColor($brandKey, $product->brand_color);
    $brandColor = $brandCardColor ?: '#2563eb';
    $hasStock   = $variants->isNotEmpty();
    $redemptionVideo = data_get($product->metadata, 'redemption_video');

    // Country-locked denomination range for the placeholder text ("$15 - 500").
    $availableVariants = $variants->where('is_available', true);
    $rangeMin = $availableVariants->pluck('retail_price')->filter()->min();
    $rangeMax = $availableVariants->pluck('retail_price')->filter()->max();
    if ((! $rangeMin || ! $rangeMax) && $variable) {
        $rangeMin = $variable->min_amount;
        $rangeMax = $variable->max_amount;
    }
    $rangeText = ($rangeMin !== null && $rangeMax !== null)
        ? $sym($currency) . rtrim(rtrim(number_format((float) $rangeMin, 2), '0'), '.')
            . ' – '
            . rtrim(rtrim(number_format((float) $rangeMax, 2), '0'), '.')
        : null;

    // Pull active currency rates from the admin-managed `currency_rates` table.
    // The "Estimated price" dropdown uses these to render per-currency totals.
    $cryptoRatesForJs = CurrencyRate::active()
        ->orderBy('type')
        ->orderBy('sort_order')
        ->orderBy('code')
        ->get(['code', 'name', 'type', 'rate_per_usd', 'icon_path'])
        ->map(fn ($r) => [
            'code'    => $r->code,
            'name'    => $r->name,
            'type'    => $r->type,
            'perUsd'  => (float) $r->rate_per_usd,
            // Match the precision of how the original table renders rates: more decimals for tiny
            // BTC-style rates, fewer for fiat. Just an inverse-magnitude heuristic.
            'decimals' => $r->rate_per_usd < 0.01 ? 8 : ($r->rate_per_usd < 1 ? 4 : 2),
            'icon'    => $r->icon_path ? asset('assets/' . $r->icon_path) : null,
        ])
        ->values();

    // Default crypto: USDT if present, else first active. The amount column in the picker
    // shows the estimated equivalent of the order total in that currency.
    $defaultCrypto = $cryptoRatesForJs->firstWhere('code', 'USDT')['code']
        ?? ($cryptoRatesForJs->first()['code'] ?? 'USDT');

    // Compact variant payload for Alpine.
    $variantsForJs = $variants->map(fn ($v) => [
        'id' => $v->id,
        'is_variable' => (bool) $v->is_variable,
        'face_value' => (float) ($v->face_value ?? $v->retail_price),
        'retail_price' => (float) $v->retail_price,
        'min' => (float) ($v->min_amount ?? 0),
        'max' => (float) ($v->max_amount ?? 0),
    ])->values();

    // Similar brands in same subcategory + same country.
    $similarIds = Product::query()
        ->where('is_active', true)
        ->where('brand_key', '!=', $brandKey)
        ->whereNotNull('brand_key')
        ->where('country_code', $product->country_code)
        ->when($product->subcategory_id, fn ($q) => $q->where('subcategory_id', $product->subcategory_id))
        ->select('brand_key', \Illuminate\Support\Facades\DB::raw('MIN(id) as id'))
        ->groupBy('brand_key')
        ->limit(6)
        ->pluck('id');

    $similar = Product::query()
        ->whereIn('id', $similarIds)
        ->orderByDesc('is_popular')
        ->orderByDesc('is_featured')
        ->orderBy('name')
        ->get(['id', 'name', 'slug', 'brand_key', 'country_code', 'logo_url', 'featured_image', 'brand_color']);
@endphp

<x-layouts.app.header :title="$brandName . ' Gift Card | RshopRefills'">

    <div
        x-data="brandDetail({
            currencySymbol: @js($sym($currency)),
            variants: @js($variantsForJs),
            rangeText: @js($rangeText),
            cryptos: @js($cryptoRatesForJs),
            defaultCrypto: @js($defaultCrypto),
        })"
        x-init="init()"
        style="--brand: {{ $brandColor }};"
        class="mx-auto w-full max-w-7xl px-4 py-6 sm:px-6 lg:px-8 lg:py-10"
    >

        {{-- Breadcrumb --}}
        <nav class="flex items-center gap-2 text-sm text-zinc-600" aria-label="Breadcrumb">
            <a href="{{ route('shop.gift-cards') }}" wire:navigate class="transition-colors hover:text-zinc-900">Gift Cards</a>
            @if ($product->subcategory)
                <span aria-hidden="true">/</span>
                <a href="{{ route('shop.gift-cards', ['subcategory' => $product->subcategory->slug]) }}" wire:navigate class="transition-colors hover:text-zinc-900">{{ $product->subcategory->name }}</a>
            @endif
            <span aria-hidden="true">/</span>
            <span class="font-medium text-zinc-900">{{ $brandName }}</span>
        </nav>

        {{-- Two-column hero + buy panel --}}
        <div class="mt-6 grid grid-cols-1 gap-8 lg:mt-10 lg:grid-cols-2 lg:gap-12">

            {{-- LEFT: hero plate. The outer div is a normal grid cell (stretches to full row
                 height); the sticky wrapper lives INSIDE it so position:sticky always has room
                 to travel as the taller right column scrolls. --}}
            <div>
                <div class="lg:sticky lg:top-6">
                    <div class="mx-auto flex aspect-square w-full max-w-md items-center justify-center rounded-[24px] bg-zinc-100 p-6 sm:p-8">
                        <div class="relative flex aspect-[8/5] w-full items-center justify-center overflow-hidden rounded-xl bg-white shadow-[0_10px_28px_-8px_rgba(0,0,0,0.25)]">
                            @if ($logoSrc)
                                <img src="{{ $logoSrc }}" alt="{{ $brandName }} gift card" class="h-full w-full object-cover" loading="eager">
                            @else
                                <span class="text-3xl font-black uppercase tracking-tight text-zinc-700">
                                    {{ str($brandName)->substr(0, 2)->upper() }}
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- RIGHT: buy panel --}}
            <div class="flex flex-col gap-5">

                {{-- Heading --}}
                <div>
                    <h1 class="text-[24px] font-bold leading-tight text-zinc-900">{{ $brandName }} gift card</h1>

                    @if ($product->description)
                        <div class="mt-3 text-base leading-relaxed text-zinc-600 [&>p]:mb-2 [&_a]:text-blue-600 [&_a]:underline">{!! $product->description !!}</div>
                    @else
                        <p class="mt-3 text-base leading-relaxed text-zinc-600">
                            Buy {{ $brandName }} gift cards with Bitcoin, USDT, USDC and other Crypto. Instant delivery and a fair refund policy.
                        </p>
                    @endif
                </div>

                {{-- Trust badges row (Instant delivery + Fair refund policy) --}}
                <div class="flex flex-wrap items-center gap-x-6 gap-y-2 text-sm font-semibold text-zinc-900">
                    <span class="flex items-center gap-1.5">
                        <svg class="h-5 w-5 text-emerald-500" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M13 2L3 14h7l-1 8 10-12h-7l1-8z"/>
                        </svg>
                        Instant delivery
                    </span>
                    <span class="flex items-center gap-1.5">
                        <svg class="h-5 w-5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <circle cx="12" cy="12" r="9" stroke-linecap="round"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 7v10M9 9.5a2.5 2.5 0 015 0c0 1-.5 1.5-2 2.5s-2 1.5-2 2.5a2.5 2.5 0 005 0"/>
                        </svg>
                        Fair refund policy
                    </span>
                </div>

                @if ($hasStock)
                    {{-- 3-col row: Amount / Quantity / Estimated price --}}
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-[1fr_auto_auto]">

                        {{-- Amount field — plain typeable input with $ prefix. Suggested denominations
                             render as small clickable chips below so users can one-tap a common value. --}}
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold text-zinc-900">Amount</label>
                            <div
                                x-data="{ open: false, locked: false }"
                                @mouseenter="open = true"
                                @mouseleave="if (! locked) open = false"
                                @click.outside="open = false; locked = false"
                                class="relative"
                            >
                                <button
                                    type="button"
                                    @click="open = ! open; locked = open"
                                    :class="open ? 'border-[color:var(--brand)] ring-2 ring-blue-500/15' : 'border-zinc-200 hover:border-zinc-400'"
                                    class="flex h-[50px] w-full items-center gap-2 rounded-xl border bg-white px-3 text-base font-bold text-zinc-900 transition-colors"
                                >
                                    <span class="font-semibold text-zinc-600">{{ $sym($currency) }}</span>
                                    <span class="flex-1 truncate text-left tabular-nums" :class="amount ? 'text-zinc-900' : 'font-medium text-zinc-500'" x-text="amount ? amount : 'Select amount'">Select amount</span>
                                    <svg class="h-4 w-4 shrink-0 text-zinc-600 transition-transform duration-150" :class="{ 'rotate-180': open }" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>

                                <div
                                    x-show="open"
                                    x-transition:enter="transition ease-out duration-150"
                                    x-transition:enter-start="opacity-0 -translate-y-1"
                                    x-transition:enter-end="opacity-100 translate-y-0"
                                    style="display:none;"
                                    class="absolute left-0 right-0 top-full z-20 max-h-[27rem] overflow-y-auto rounded-xl border border-zinc-200 bg-white p-1 shadow-xl shadow-zinc-900/10 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
                                    role="listbox"
                                >
                                    @foreach ($fixedDenoms as $i => $v)
                                        @php($val = (float) $v->retail_price)
                                        <button
                                            type="button"
                                            @click="amount = {{ $val }}; customMode = false; open = false"
                                            :class="(! customMode && Number(amount) === {{ $val }}) ? 'bg-blue-50 text-blue-700' : 'text-zinc-800 hover:bg-zinc-200'"
                                            class="flex w-full items-center justify-between rounded-lg px-3 py-2.5 text-left text-base font-medium tabular-nums transition-colors"
                                        >
                                            <span class="font-bold">{{ $sym($currency) }}{{ rtrim(rtrim(number_format($val, 2), '0'), '.') }}</span>
                                            @if ($i === $fixedDenoms->count() - 1 && $fixedDenoms->count() > 1)
                                                <span class="text-xs font-medium text-zinc-500">Popular</span>
                                            @endif
                                        </button>
                                    @endforeach
                                    @if ($variable)
                                        <button
                                            type="button"
                                            @click="customMode = true; amount = ''; open = false"
                                            :class="customMode ? 'bg-blue-50 text-blue-700' : 'text-zinc-800 hover:bg-zinc-200'"
                                            class="flex w-full items-center justify-between rounded-lg {{ $fixedDenoms->isNotEmpty() ? 'border-t border-zinc-100' : '' }} px-3 py-2.5 text-left text-base font-medium transition-colors"
                                        >
                                            <span class="font-bold">Custom amount</span>
                                            <span class="text-xs text-zinc-500">{{ $sym($currency) }}{{ number_format((float) $variable->min_amount, 0) }}–{{ number_format((float) $variable->max_amount, 0) }}</span>
                                        </button>
                                    @endif
                                </div>
                            </div>

                            @if ($variable)
                                {{-- Custom-amount input, revealed only when "Custom amount" is chosen. --}}
                                <div x-show="customMode" x-transition class="mt-2">
                                    <div class="relative">
                                        <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-base font-semibold text-zinc-600">{{ $sym($currency) }}</span>
                                        <input
                                            type="number"
                                            x-model="amount"
                                            min="{{ (float) $variable->min_amount }}"
                                            max="{{ (float) $variable->max_amount }}"
                                            step="any"
                                            placeholder="0.00"
                                            class="w-full rounded-xl border border-zinc-200 bg-white py-2.5 pl-10 pr-3 text-base font-bold tabular-nums text-zinc-900 outline-none transition-colors focus:border-[color:var(--brand)] focus:ring-2 focus:ring-blue-500/15"
                                        />
                                    </div>
                                </div>
                            @endif

                            @if ($rangeMin !== null && $rangeMax !== null)
                                <p class="mt-1.5 text-xs text-zinc-600">
                                    Between {{ $sym($currency) }}{{ rtrim(rtrim(number_format((float) $rangeMin, 2), '0'), '.') }}
                                    and {{ $sym($currency) }}{{ rtrim(rtrim(number_format((float) $rangeMax, 2), '0'), '.') }}
                                </p>
                            @endif
                        </div>

                        {{-- Quantity — custom dropdown so it visually matches the Amount + Estimated price fields. --}}
                        <div class="sm:min-w-[6rem]">
                            <label class="mb-1.5 block text-xs font-semibold text-zinc-900">Quantity</label>
                            <div
                                x-data="{ open: false, locked: false }"
                                @mouseenter="open = true"
                                @mouseleave="if (! locked) open = false"
                                @click.outside="open = false; locked = false"
                                class="relative"
                            >
                                <button
                                    type="button"
                                    @click="open = ! open; locked = open"
                                    :class="open ? 'border-[color:var(--brand)] ring-2 ring-blue-500/15' : 'border-zinc-200 hover:border-zinc-400'"
                                    class="flex h-[50px] w-full items-center gap-2 rounded-xl border bg-white px-3 text-base font-bold text-zinc-900 transition-colors"
                                >
                                    <span class="flex-1 text-left tabular-nums" x-text="quantity">1</span>
                                    <svg class="h-4 w-4 shrink-0 text-zinc-600 transition-transform duration-150" :class="{ 'rotate-180': open }" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>

                                <div
                                    x-show="open"
                                    x-transition:enter="transition ease-out duration-150"
                                    x-transition:enter-start="opacity-0 -translate-y-1"
                                    x-transition:enter-end="opacity-100 translate-y-0"
                                    style="display:none;"
                                    class="absolute left-0 right-0 top-full z-20 max-h-60 overflow-y-auto rounded-xl border border-zinc-200 bg-white p-1 shadow-xl shadow-zinc-900/10 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
                                    role="listbox"
                                >
                                    @for ($n = 1; $n <= 10; $n++)
                                        <button
                                            type="button"
                                            @click="quantity = {{ $n }}; open = false"
                                            :class="quantity === {{ $n }} ? 'bg-blue-50 text-blue-700' : 'text-zinc-800 hover:bg-zinc-200'"
                                            class="flex w-full items-center rounded-lg px-3 py-2 text-left text-base font-medium tabular-nums transition-colors"
                                        >
                                            {{ $n }}
                                        </button>
                                    @endfor
                                </div>
                            </div>
                        </div>

                        {{-- Estimated price — crypto currency selector. Each option shows the equivalent
                             in that crypto using a placeholder rate (rate table lives in Alpine state). --}}
                        <div class="sm:min-w-[9rem]">
                            <label class="mb-1.5 block text-xs font-semibold text-zinc-900">Estimated price</label>
                            <div
                                x-data="{ open: false, locked: false }"
                                @mouseenter="open = true"
                                @mouseleave="if (! locked) open = false"
                                @click.outside="open = false; locked = false"
                                class="relative"
                            >
                                <button
                                    type="button"
                                    @click="open = ! open; locked = open"
                                    :class="open ? 'border-[color:var(--brand)] ring-2 ring-blue-500/15' : 'border-zinc-200 hover:border-zinc-400'"
                                    class="flex h-[50px] w-full items-center gap-2 rounded-xl border bg-white px-3 text-base font-bold text-zinc-900 transition-colors"
                                >
                                    <template x-if="cryptos[selectedCrypto]?.icon">
                                        <img :src="cryptos[selectedCrypto].icon" :alt="selectedCrypto" class="h-5 w-5 shrink-0 rounded-full">
                                    </template>
                                    <template x-if="!cryptos[selectedCrypto]?.icon">
                                        <span class="inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full text-[10px] font-black text-white" :class="cryptos[selectedCrypto]?.type === 'crypto' ? 'bg-amber-500' : 'bg-emerald-500'" x-text="selectedCrypto.charAt(0)"></span>
                                    </template>
                                    <span class="flex-1 truncate text-left tabular-nums" x-text="formatCrypto()">0.00 USDT</span>
                                    <svg class="h-4 w-4 shrink-0 text-zinc-600 transition-transform duration-150" :class="{ 'rotate-180': open }" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>

                                <div
                                    x-show="open"
                                    x-transition:enter="transition ease-out duration-150"
                                    x-transition:enter-start="opacity-0 -translate-y-1"
                                    x-transition:enter-end="opacity-100 translate-y-0"
                                    style="display:none;"
                                    class="absolute right-0 top-full z-20 w-56 overflow-hidden rounded-xl border border-zinc-200 bg-white p-1 shadow-xl shadow-zinc-900/10"
                                    role="listbox"
                                >
                                    <template x-for="(meta, code) in cryptos" :key="code">
                                        <button
                                            type="button"
                                            @click="selectedCrypto = code; open = false"
                                            :class="selectedCrypto === code ? 'bg-blue-50 text-blue-700' : 'text-zinc-800 hover:bg-zinc-200'"
                                            class="flex w-full items-center gap-2 rounded-lg px-3 py-2.5 text-left text-sm font-medium transition-colors"
                                        >
                                            <template x-if="meta.icon">
                                                <img :src="meta.icon" :alt="code" class="h-5 w-5 shrink-0 rounded-full">
                                            </template>
                                            <template x-if="!meta.icon">
                                                <span class="inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full text-[10px] font-black text-white" :class="meta.type === 'crypto' ? 'bg-amber-500' : 'bg-emerald-500'" x-text="code.charAt(0)"></span>
                                            </template>
                                            <span class="flex-1 truncate text-left tabular-nums" x-text="formatCryptoFor(code)"></span>
                                            <svg x-show="selectedCrypto === code" class="h-4 w-4 shrink-0 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                                            </svg>
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Points you earn — 0.5 coins per $1 spent, rounded to nearest whole coin. --}}
                    <p class="flex items-center gap-1.5 text-sm font-semibold text-zinc-700">
                        Points you earn
                        <svg class="h-4 w-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        <span class="text-zinc-900" x-text="pointsEarned()">0</span>
                    </p>

                    {{-- Add to cart + Buy now — brand blue (outline + filled). --}}
                    <div class="grid grid-cols-2 gap-3">
                        <button
                            type="button"
                            class="rounded-lg border-2 border-blue-600 bg-white px-4 py-3 text-base font-semibold text-blue-600 transition-colors hover:bg-blue-600 hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/40"
                        >
                            Add to cart
                        </button>
                        <button
                            type="button"
                            class="rounded-lg bg-blue-600 px-4 py-3 text-base font-semibold text-white transition-colors hover:bg-blue-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/40"
                        >
                            Buy now
                        </button>
                    </div>

                    {{-- Country availability + "find your country" link to reopen the locale modal. --}}
                    <div>
                        <p class="flex items-center gap-2 text-sm text-zinc-700">
                            <span class="text-base leading-none" aria-hidden="true">{{ $flag($product->country_code) }}</span>
                            <span>May only be redeemable in {{ $countryName }}</span>
                        </p>
                        <p class="mt-0.5 text-sm text-zinc-600">
                            Not in {{ $countryName }}?
                            <button type="button" @click="$dispatch('open-locale-modal'); localeModalOpen = true" class="font-semibold text-zinc-900 underline underline-offset-2 transition-colors hover:text-blue-700">
                                Find your country
                            </button>
                        </p>
                    </div>
                @else
                    <div class="rounded-2xl bg-zinc-50 px-4 py-8 text-center ring-1 ring-zinc-100">
                        <p class="text-base font-semibold text-zinc-900">Out of stock</p>
                        <p class="mt-1 text-sm text-zinc-600">This card has no denominations available right now. Check back later.</p>
                    </div>
                @endif

                {{-- Accordion sections: How to redeem / Terms / FAQ — INSIDE the right column so the
                     gift card stays alone on the left. No dividers between items. --}}
                <section class="mt-6">
            @if ($product->redeem_instructions)
                <details class="group" open>
                    <summary class="flex cursor-pointer items-center justify-between py-5 text-lg font-bold text-zinc-900 marker:content-['']">
                        How to redeem
                        <svg class="h-5 w-5 text-zinc-600 transition-transform group-open:rotate-45" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/>
                        </svg>
                    </summary>
                    <div class="pb-5 text-base leading-relaxed text-zinc-700 [&>p]:mb-3 [&>ol]:list-decimal [&>ol]:pl-5 [&>ul]:list-disc [&>ul]:pl-5 [&>ol>li]:mb-1.5 [&>ul>li]:mb-1.5 [&_a]:text-blue-600 [&_a]:underline [&_a]:hover:text-blue-700">
                        {!! $product->redeem_instructions !!}
                    </div>
                </details>
            @endif

            @if ($product->terms_and_conditions)
                <details class="group">
                    <summary class="flex cursor-pointer items-center justify-between py-5 text-lg font-bold text-zinc-900 marker:content-['']">
                        Terms and conditions
                        <svg class="h-5 w-5 text-zinc-600 transition-transform group-open:rotate-45" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/>
                        </svg>
                    </summary>
                    <div class="pb-5 text-sm leading-relaxed text-zinc-700 [&>p]:mb-3 [&>ol]:list-decimal [&>ol]:pl-5 [&>ul]:list-disc [&>ul]:pl-5 [&_a]:text-blue-600 [&_a]:underline">
                        {!! $product->terms_and_conditions !!}
                    </div>
                </details>
            @endif

            @if ($redemptionVideo)
                <details class="group">
                    <summary class="flex cursor-pointer items-center justify-between py-5 text-lg font-bold text-zinc-900 marker:content-['']">
                        Watch a walkthrough
                        <svg class="h-5 w-5 text-zinc-600 transition-transform group-open:rotate-45" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/>
                        </svg>
                    </summary>
                    <div class="pb-5">
                        <div class="overflow-hidden rounded-2xl bg-zinc-900 ring-1 ring-zinc-100">
                            <video controls class="aspect-video w-full">
                                <source src="{{ $redemptionVideo }}">
                                Your browser doesn't support embedded video.
                            </video>
                        </div>
                    </div>
                </details>
            @endif

            <details class="group">
                <summary class="flex cursor-pointer items-center justify-between py-5 text-lg font-bold text-zinc-900 marker:content-['']">
                    Frequently asked questions
                    <svg class="h-5 w-5 text-zinc-600 transition-transform group-open:rotate-45" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/>
                    </svg>
                </summary>
                <div class="pb-5 text-base leading-relaxed text-zinc-700">
                    <p class="mb-3"><strong class="text-zinc-900">How fast will I receive my gift card?</strong><br>Delivery is instant — the redemption code lands in your delivery email within seconds of confirmed payment.</p>
                    <p class="mb-3"><strong class="text-zinc-900">Can I redeem this {{ $brandName }} gift card in my country?</strong><br>This card is only redeemable in {{ $countryName }}. To shop a different country's catalog, switch country in the locale picker at the top of the page.</p>
                    <p><strong class="text-zinc-900">What happens if the code doesn't work?</strong><br>Our fair-refund policy covers any code that fails to redeem due to a delivery issue on our end. Contact support with your order ID.</p>
                </div>
            </details>
                </section>
            </div>{{-- /right column --}}
        </div>{{-- /grid --}}

        {{-- Similar brands --}}
        @if ($similar->isNotEmpty())
            <section class="mt-12">
                <div class="mb-4 flex items-baseline justify-between">
                    <h2 class="text-lg font-bold text-zinc-900">Similar in {{ $countryName }}</h2>
                    <a href="{{ route('shop.gift-cards', array_filter(['country' => $product->country_code, 'subcategory' => $product->subcategory?->slug])) }}" wire:navigate class="text-sm font-semibold text-blue-600 transition-colors hover:text-blue-700">
                        View all →
                    </a>
                </div>

                <div class="grid grid-cols-3 gap-3 sm:grid-cols-4 lg:grid-cols-6">
                    @foreach ($similar as $s)
                        @php($sLogo = Product::brandLogoUrl($s->brand_key, $s->logo_url))
                        <a href="{{ route('shop.brand', ['brandSlug' => Product::brandSlug($s->brand_key), 'country' => $product->country_code]) }}" wire:navigate class="group block">
                            <div class="relative flex aspect-[16/10] items-center justify-center overflow-hidden rounded-[15px] bg-white shadow-sm ring-1 ring-zinc-200 transition-all duration-200 group-hover:-translate-y-0.5 group-hover:shadow-md group-hover:ring-zinc-300">
                                @if ($sLogo)
                                    <img src="{{ $sLogo }}" alt="" class="max-h-[68%] max-w-[78%] object-contain" loading="lazy">
                                @else
                                    <span class="text-xl font-black uppercase text-zinc-700">{{ str(Product::brandDisplayName($s->brand_key))->substr(0, 2)->upper() }}</span>
                                @endif
                            </div>
                            <p class="mt-2 truncate text-[13px] font-bold text-zinc-900 group-hover:text-blue-700">{{ Product::brandDisplayName($s->brand_key) }}</p>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif
    </div>

    <script>
        window.brandDetail = function ({ currencySymbol, variants, rangeText, cryptos, defaultCrypto }) {
            // `cryptos` arrives as an array of {code, name, type, perUsd, decimals, icon} from
            // the admin-managed currency_rates table. Reshape into a code-keyed object so the
            // existing render code (cryptos[selectedCrypto].icon etc.) still works.
            const cryptoMap = {};
            (cryptos || []).forEach((c) => { cryptoMap[c.code] = c; });

            return {
                currencySymbol,
                variants,
                rangeText,
                amount: '',
                customMode: false,
                quantity: 1,
                cryptos: cryptoMap,
                selectedCrypto: cryptoMap[defaultCrypto] ? defaultCrypto : (cryptos[0]?.code ?? null),

                init() {
                    // Empty by default so the placeholder hint stays visible until the user types
                    // or taps a chip.
                },

                selectedAmount() {
                    return Number(this.amount || 0);
                },

                totalUsd() {
                    return this.selectedAmount() * (this.quantity || 1);
                },

                formatTotal() {
                    return this.currencySymbol + this.totalUsd().toFixed(2);
                },

                // 0.5 coins per $1 of the order total, floored.
                pointsEarned() {
                    return Math.floor(this.totalUsd() * 0.5);
                },

                // Estimated equivalent in the currently selected crypto.
                formatCrypto() {
                    return this.formatCryptoFor(this.selectedCrypto);
                },

                formatCryptoFor(code) {
                    const meta = this.cryptos[code];
                    if (! meta) return '0 ' + code;
                    const value = this.totalUsd() * meta.perUsd;
                    return value.toFixed(meta.decimals) + ' ' + code;
                },
            };
        };
    </script>

</x-layouts.app.header>
