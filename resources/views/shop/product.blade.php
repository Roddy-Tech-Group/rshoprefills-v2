@php
    use App\Domain\Cart\Services\CartPricingService;
    use App\Models\CurrencyRate;
    use App\Models\Product;

    /**
     * @var \App\Models\Product $product  The country-specific representative Product for this brand.
     * @var string $brandKey  Raw Zendit brand key.
     */

    $brandName = Product::brandDisplayName($brandKey);
    $logoSrc   = Product::brandLogoUrl($brandKey, $product->logo_url);

    // This page is shared across catalog categories — gift cards and mobile
    // top-ups both render here. Derive the customer-facing noun + routes from
    // the product's category so a top-up never reads as a "gift card".
    $categorySlug = $product->category?->slug ?? 'gift-cards';
    $isTopup      = $categorySlug === 'mobile-airtime';
    $isBill       = $categorySlug === 'bill-payments';

    [$kindNoun, $kindTitle, $listingRoute, $detailRoute] = match ($categorySlug) {
        'mobile-airtime' => ['top-up', 'Mobile Top-up', 'shop.topups', 'shop.topup'],
        'bill-payments'  => ['bill payment', 'Bill Payment', 'shop.bills', 'shop.bill'],
        default          => ['gift card', 'Gift Card', 'shop.gift-cards', 'shop.brand'],
    };

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

    // Every country this brand has an active product in — i.e. the full set of
    // regions the card can be redeemed in. Drives the "redeemable in" chips.
    $brandCountries = Product::query()
        ->where('brand_key', $brandKey)
        ->where('is_active', true)
        ->whereNotNull('country_code')
        ->distinct()
        ->orderByRaw("country_code = '" . addslashes($product->country_code) . "' DESC")
        ->orderBy('country_code')
        ->pluck('country_code');

    // Primary redemption URL — a curated override (config/brand_urls.php), else
    // the first link inside Zendit's redemption instructions — so the customer
    // can jump straight to the brand to redeem right after buying.
    $redeemUrl = config("brand_urls.urls.{$brandKey}");
    if (! $redeemUrl && $product->redeem_instructions
        && preg_match('~https?://[^\s<>"\'\)]+~i', $product->redeem_instructions, $m)) {
        $redeemUrl = $m[0];
    }

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
            . ' - '
            . rtrim(rtrim(number_format((float) $rangeMax, 2), '0'), '.')
        : null;

    // All active currency rates (fiat + crypto) from the admin-managed `currency_rates`
    // table. `rate_per_usd` is the EXACT admin value — including USD (e.g. 1.04), which
    // acts as the platform spread/fee. It is applied as-is to compute the payable total.
    // A fiat code's flag derives from its first two letters (USD -> US, GBP -> GB,
    // EUR -> EU); a few non-country codes are mapped explicitly.
    $currencyFlagOverrides = ['XAF' => 'CM', 'XOF' => 'SN', 'XCD' => 'AG'];
    $currencyFlag = function (string $code) use ($currencyFlagOverrides) {
        $code = strtoupper($code);

        return Product::flagUrl($currencyFlagOverrides[$code] ?? substr($code, 0, 2));
    };

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
            // More decimals for tiny BTC-style rates, fewer for fiat.
            'decimals' => $r->rate_per_usd < 0.01 ? 8 : ($r->rate_per_usd < 1 ? 4 : 2),
            // Only crypto uses an icon image; fiat always falls through to its
            // country flag below (an icon_path on a fiat row is ignored).
            'icon'    => ($r->type === 'crypto' && $r->icon_path) ? asset('assets/' . $r->icon_path) : null,
            // Fiat currencies show a country flag instead of the letter circle.
            'flag'    => $r->type === 'fiat' ? $currencyFlag($r->code) : null,
        ])
        ->values();

    // Denominations are shown at their native face value, in the CARD'S OWN
    // currency (a UK card is GBP, a US card is USD). The face value is intrinsic
    // to the card and is never converted; only the price the customer pays is.
    $displayRate = 1.0;
    $displaySymbol = Product::currencySymbol($currency);
    $conv = fn ($v) => $displaySymbol . number_format((float) $v, 2);

    // A variable product's amount is entered in the VARIANT's own currency (e.g. a
    // utility top-up priced in XOF), not USD. These format the custom-amount UI in it.
    $customCurrency = $variable ? strtoupper((string) ($variable->currency ?: $currency)) : 'USD';
    $customSymbol   = $sym($customCurrency);
    $customMoney    = fn ($v) => $customSymbol . rtrim(rtrim(number_format((float) $v, 2), '0'), '.');

    // Makes bare URLs in Zendit's redemption/terms HTML clickable so customers can jump
    // straight to the brand's redeem page. Existing <a> tags are kept (and forced to
    // open in a new tab); only plain-text URLs between tags get wrapped.
    $linkify = function (?string $html): string {
        if (! $html) {
            return '';
        }
        $parts = preg_split('~(<a\b[^>]*>.*?</a>)~is', $html, -1, PREG_SPLIT_DELIM_CAPTURE);
        $out = '';
        foreach ($parts as $part) {
            if (preg_match('~^<a\b~i', $part)) {
                if (! preg_match('~\btarget=~i', $part)) {
                    $part = preg_replace('~^<a\b~i', '<a target="_blank" rel="noopener noreferrer"', $part);
                }
                $out .= $part;

                continue;
            }
            $out .= preg_replace(
                '~(https?://[^\s<>"\']+)~i',
                '<a href="$1" target="_blank" rel="noopener noreferrer">$1</a>',
                $part
            );
        }

        return $out;
    };

    // The Estimated price selector defaults to the country's currency
    // (config/country_currency.php) when that currency is configured in Rate
    // Management; otherwise USD.
    $countryCurrency = strtoupper(config('country_currency.map.' . strtoupper($product->country_code), 'USD'));
    $defaultCrypto = $cryptoRatesForJs->firstWhere('code', $countryCurrency)
        ? $countryCurrency
        : ($cryptoRatesForJs->firstWhere('code', 'USD') ? 'USD' : ($cryptoRatesForJs->first()['code'] ?? 'USDT'));

    // Pricing — the Estimated price is the actual payable amount: provider cost
    // + markup (CartPricingService), converted into the selected currency.
    $pricing = app(CartPricingService::class);
    $markup  = $pricing->markupDescriptor($product);

    // A variable amount is typed in the variant's own currency; this rate
    // converts that entered face amount to USD before markup is applied.
    $customRate = $variable
        ? (float) (CurrencyRate::where('code', $customCurrency)->value('rate_per_usd') ?: 1.0)
        : 1.0;

    // Compact variant payload for Alpine. `price_usd` is the marked-up USD price
    // for a FIXED denomination; variable variants are priced client-side from
    // the typed amount, so their price_usd is unused.
    $variantsForJs = $variants->map(function ($v) use ($pricing, $product) {
        $v->setRelation('product', $product);

        return [
            'id' => $v->id,
            'is_variable' => (bool) $v->is_variable,
            'face_value' => (float) ($v->face_value ?? $v->retail_price),
            'retail_price' => (float) $v->retail_price,
            'min' => (float) ($v->min_amount ?? 0),
            'max' => (float) ($v->max_amount ?? 0),
            'price_usd' => round((float) $pricing->calculatePricing($v, 1)['unit_price_snapshot'], 2),
        ];
    })->values();

    // Similar brands in same subcategory + same country.
    $similarIds = Product::query()
        ->where('is_active', true)
        ->where('brand_key', '!=', $brandKey)
        ->whereNotNull('brand_key')
        ->where('category_id', $product->category_id)
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

<x-layouts.app.header :title="$brandName . ' ' . $kindTitle . ' | RshopRefills'">

    <div
        @php
            $dialCode = config('dial_codes.codes.'.strtoupper((string) $product->country_code), '+1');
        @endphp
        x-data="brandDetail({
            variants: @js($variantsForJs),
            rangeText: @js($rangeText),
            cryptos: @js($cryptoRatesForJs),
            defaultCrypto: @js($defaultCrypto),
            markup: @js($markup),
            customRate: @js($customRate),
            customMin: @js($variable ? (float) $variable->min_amount : 0),
            customMax: @js($variable ? (float) $variable->max_amount : 0),
            requiresRecipientPhone: @js($isTopup),
            defaultDialCode: @js($dialCode),
            requiresAccountId: @js($isBill),
        })"
        x-init="init()"
        style="--brand: {{ $brandColor }};"
        class="mx-auto w-full max-w-7xl px-4 py-6 sm:px-6 lg:px-8 lg:py-10"
    >

        {{-- Two-column hero + buy panel --}}
        <div class="mt-6 grid grid-cols-1 gap-8 lg:mt-10 lg:grid-cols-2 lg:gap-8">

            {{-- LEFT: hero plate. The outer div is a normal grid cell (stretches to full row
                 height); the sticky wrapper lives INSIDE it so position:sticky always has room
                 to travel as the taller right column scrolls. --}}
            <div>
                {{-- top offset clears the sticky header (top-bar 36 + nav 64 + category bar 40)
                     so the card parks below it instead of sliding up behind the nav. --}}
                <div class="lg:sticky lg:top-[156px]">
                    <div class="mx-auto lg:mr-0 flex w-full max-w-lg items-center justify-center rounded-[24px] bg-[#e8e8f7] p-10 sm:p-14 dark:bg-[#1d3252]">
                        <div class="relative flex aspect-[8/5] w-4/5 items-center justify-center overflow-hidden rounded-[10px] bg-[#ffffff] shadow-[0_10px_28px_-8px_rgba(0,0,0,0.25)]">
                            @if ($logoSrc)
                                <img src="{{ $logoSrc }}" alt="{{ $brandName }} {{ $kindNoun }}" class="h-full w-full object-cover" loading="eager">
                            @else
                                {{-- No logo (e.g. mobile-airtime operators) — a branded name tile. --}}
                                <div class="flex h-full w-full items-center justify-center" style="background-color: {{ Product::tileColor($brandKey) }}">
                                    <span class="px-4 text-center text-2xl font-extrabold leading-tight text-white">{{ $brandName }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- RIGHT: buy panel --}}
            <div class="flex flex-col gap-5">

                {{-- Heading --}}
                <div>
                    <h1 class="text-[24px] font-bold leading-tight text-zinc-900">{{ $brandName }} {{ $kindNoun }}</h1>

                    @if ($product->description)
                        <div class="mt-3 text-base leading-relaxed text-zinc-600 [&>p]:mb-2 [&_a]:text-blue-600 [&_a]:underline">{!! $product->description !!}</div>
                    @elseif ($isTopup)
                        <p class="mt-3 text-base leading-relaxed text-zinc-600">
                            Send airtime to any {{ $brandName }} number. Instant delivery and a fair refund policy.
                        </p>
                    @elseif ($isBill)
                        <p class="mt-3 text-base leading-relaxed text-zinc-600">
                            Pay your {{ $brandName }} bill instantly. Fast delivery and a fair refund policy.
                        </p>
                    @else
                        <p class="mt-3 text-base leading-relaxed text-zinc-600">
                            Buy {{ $brandName }} gift cards with Bitcoin, USDT, USDC and other Crypto. Instant delivery and a fair refund policy.
                        </p>
                    @endif
                </div>

                {{-- Trust badges — each is a small emerald-tinted circle + label,
                     mirroring the reference. Mobile keeps them on one scrollable row;
                     sm+ wraps naturally. --}}
                @php
                    $redeemLabel = $isTopup
                        ? 'Credited straight to the number'
                        : ($isBill ? 'Paid straight to the account' : 'Online redeemable');

                    $badges = [
                        ['label' => 'Instant delivery', 'd' => 'M13 10V3L4 14h7v7l9-11h-7z'],
                        ['label' => $redeemLabel,       'd' => 'M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
                        ['label' => 'Fair refund policy', 'd' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
                    ];
                @endphp
                <div class="flex items-center gap-x-6 gap-y-3 overflow-x-auto text-sm font-medium text-zinc-700 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden sm:flex-wrap sm:overflow-visible dark:text-zinc-200">
                    @foreach ($badges as $badge)
                        <span class="flex shrink-0 items-center gap-2">
                            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-[10px] bg-emerald-50 text-emerald-600 ring-1 ring-emerald-100 dark:bg-emerald-500/15 dark:ring-emerald-500/30">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $badge['d'] }}"/>
                                </svg>
                            </span>
                            {{ $badge['label'] }}
                        </span>
                    @endforeach
                </div>

                {{-- Category type pill — small chip below the trust badges so the
                     buyer instantly knows what kind of product this is (matches the
                     "Credits" tag in the reference). --}}
                @php
                    $kindPillLabel = $isTopup ? 'Credits' : ($isBill ? 'Bill Payment' : 'Gift Card');
                @endphp
                <div>
                    <span class="inline-flex items-center rounded-[5px] bg-blue-600 px-3 py-1 text-xs font-bold uppercase tracking-wide text-white">{{ $kindPillLabel }}</span>
                </div>

                @if ($hasStock)
                    {{-- Amount / Quantity / Estimated price. Mobile: Amount on its own full
                         row, Quantity + Estimated price share a 2-col row. sm+: one row. --}}
                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-[1fr_auto_auto]">

                        {{-- Amount field — plain typeable input with $ prefix. Suggested denominations
                             render as small clickable chips below so users can one-tap a common value. --}}
                        <div class="col-span-2 sm:col-span-1">
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
                                    class="flex h-[50px] w-full items-center gap-2 rounded-[10px] border bg-white px-3 text-base font-bold text-zinc-900 transition-colors"
                                >
                                    <span class="font-semibold text-zinc-600">{{ $variable ? $customSymbol : $displaySymbol }}</span>
                                    <span
                                        x-data="valueFlip()"
                                        x-effect="selectedVariantId; customMode; flash()"
                                        class="flex-1 truncate text-left tabular-nums"
                                        :class="amount ? 'text-zinc-900' : 'font-medium text-zinc-500'"
                                        x-text="amount ? amount : 'Select amount'"
                                    >Select amount</span>
                                    {{-- Clear the selected denomination. @click.stop so it doesn't toggle the dropdown. --}}
                                    <span
                                        x-show="selectedVariantId || amount"
                                        @click.stop="amount = ''; selectedVariantId = null; customMode = false; open = false"
                                        @keydown.enter.stop="amount = ''; selectedVariantId = null; customMode = false; open = false"
                                        role="button"
                                        tabindex="0"
                                        aria-label="Clear selected amount"
                                        class="flex h-5 w-5 shrink-0 items-center justify-center rounded-[10px] bg-zinc-200 transition-colors hover:bg-zinc-300"
                                    >
                                        <img src="{{ asset('assets/' . rawurlencode('x button.png')) }}" alt="" class="h-3.5 w-3.5 object-contain" loading="lazy">
                                    </span>
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
                                    class="absolute left-0 right-0 top-full z-20 max-h-[27rem] overflow-y-auto rounded-[10px] border border-zinc-200 bg-white/80 backdrop-blur-xl p-1 shadow-xl shadow-zinc-900/10 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
                                    role="listbox"
                                >
                                    @foreach ($fixedDenoms as $i => $v)
                                        @php
                                            $val  = (float) $v->retail_price;
                                            $disp = round($val * $displayRate, 2); // value in the display currency
                                        @endphp
                                        <button
                                            type="button"
                                            @click="amount = {{ $disp }}; selectedVariantId = {{ $v->id }}; customMode = false; open = false"
                                            :class="(! customMode && Number(amount) === {{ $disp }}) ? 'bg-blue-50 text-blue-700' : 'text-zinc-800 hover:bg-zinc-200'"
                                            class="flex w-full items-center justify-between rounded-[10px] px-3 py-2.5 text-left text-base font-medium tabular-nums transition-colors"
                                        >
                                            <span class="font-bold">{{ $conv($val) }}</span>
                                            @if ($i === $fixedDenoms->count() - 1 && $fixedDenoms->count() > 1)
                                                <span class="inline-flex items-center rounded-[10px] bg-white/60 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-blue-700 ring-1 ring-blue-300/50 shadow-[0_0_10px_rgba(37,99,235,0.45)] backdrop-blur-md dark:bg-blue-950 dark:ring-blue-400/50">Popular</span>
                                            @endif
                                        </button>
                                    @endforeach
                                    @if ($variable)
                                        <button
                                            type="button"
                                            @click="customMode = true; selectedVariantId = {{ $variable->id }}; amount = ''; open = false"
                                            :class="customMode ? 'bg-blue-50 text-blue-700' : 'text-zinc-800 hover:bg-zinc-200'"
                                            class="flex w-full items-center justify-between rounded-[10px] {{ $fixedDenoms->isNotEmpty() ? 'border-t border-zinc-100' : '' }} px-3 py-2.5 text-left text-base font-medium transition-colors"
                                        >
                                            <span class="font-bold">Custom amount</span>
                                            <span class="text-xs text-zinc-500">{{ $customMoney($variable->min_amount) }} - {{ $customMoney($variable->max_amount) }}</span>
                                        </button>
                                    @endif
                                </div>
                            </div>

                            @if ($variable)
                                {{-- Custom-amount input. The amount is entered in the variant's own
                                     currency ({{ $customCurrency }} for this product). A value outside the
                                     min-max range is rejected: the error shows and the buy buttons
                                     disable via canAddToCart(). --}}
                                <div x-show="customMode" x-transition class="mt-2">
                                    <div class="relative">
                                        <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-base font-semibold text-zinc-600">{{ $customSymbol }}</span>
                                        <input
                                            type="number"
                                            inputmode="decimal"
                                            x-model="amount"
                                            min="{{ (float) $variable->min_amount }}"
                                            max="{{ (float) $variable->max_amount }}"
                                            step="any"
                                            placeholder="{{ rtrim(rtrim(number_format((float) $variable->min_amount, 2), '0'), '.') }} - {{ rtrim(rtrim(number_format((float) $variable->max_amount, 2), '0'), '.') }}"
                                            class="w-full rounded-[10px] border bg-white py-2.5 pl-10 pr-3 text-base font-bold tabular-nums text-zinc-900 outline-none transition-colors focus:ring-2 focus:ring-blue-500/15"
                                            :class="(amount !== '' && (Number(amount) < customMin || Number(amount) > customMax)) ? 'border-red-400 focus:border-red-500' : 'border-zinc-200 focus:border-[color:var(--brand)]'"
                                        />
                                    </div>
                                    <p
                                        x-show="amount !== '' && (Number(amount) < customMin || Number(amount) > customMax)"
                                        x-cloak
                                        class="mt-1.5 text-xs font-medium text-red-600"
                                    >
                                        Enter an amount between {{ $customMoney($variable->min_amount) }} and {{ $customMoney($variable->max_amount) }}.
                                    </p>
                                </div>
                            @endif

                            @if ($rangeMin !== null && $rangeMax !== null)
                                <p class="mt-1.5 text-xs text-zinc-600">
                                    Between {{ $variable ? $customMoney($rangeMin) : $conv($rangeMin) }} and {{ $variable ? $customMoney($rangeMax) : $conv($rangeMax) }}
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
                                    class="flex h-[50px] w-full items-center gap-2 rounded-[10px] border bg-white px-3 text-base font-bold text-zinc-900 transition-colors"
                                >
                                    <span x-data="valueFlip()" x-effect="quantity; flash()" class="flex-1 text-left tabular-nums" x-text="quantity">1</span>
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
                                    class="absolute left-0 right-0 top-full z-20 max-h-60 overflow-y-auto rounded-[10px] border border-zinc-200 bg-white/80 backdrop-blur-xl p-1 shadow-xl shadow-zinc-900/10 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
                                    role="listbox"
                                >
                                    @for ($n = 1; $n <= 10; $n++)
                                        <button
                                            type="button"
                                            @click="quantity = {{ $n }}; open = false"
                                            :class="quantity === {{ $n }} ? 'bg-blue-50 text-blue-700' : 'text-zinc-800 hover:bg-zinc-200'"
                                            class="flex w-full items-center justify-center rounded-[10px] px-2 py-2 text-center text-base font-medium tabular-nums transition-colors"
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
                                    class="flex h-[50px] w-full items-center gap-2 rounded-[10px] border bg-white px-3 text-base font-bold text-zinc-900 transition-colors"
                                >
                                    <template x-if="cryptos[selectedCrypto]?.icon">
                                        <img :src="cryptos[selectedCrypto].icon" :alt="selectedCrypto" class="h-5 w-5 shrink-0 rounded-[10px]">
                                    </template>
                                    <template x-if="!cryptos[selectedCrypto]?.icon && cryptos[selectedCrypto]?.flag">
                                        <img :src="cryptos[selectedCrypto].flag" :alt="selectedCrypto" class="h-5 w-5 shrink-0 rounded-[10px] object-cover ring-1 ring-zinc-200">
                                    </template>
                                    <template x-if="!cryptos[selectedCrypto]?.icon && !cryptos[selectedCrypto]?.flag">
                                        <span class="inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-[10px] text-[10px] font-black text-white" :class="cryptos[selectedCrypto]?.type === 'crypto' ? 'bg-amber-500' : 'bg-emerald-500'" x-text="selectedCrypto.charAt(0)"></span>
                                    </template>
                                    <span x-data="valueFlip()" x-effect="selectedVariantId; quantity; selectedCrypto; flash()" class="flex-1 truncate text-left tabular-nums" x-text="formatCrypto()">0.00 USDT</span>
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
                                    class="absolute right-0 top-full z-20 max-h-80 w-56 overflow-y-auto rounded-[10px] border border-zinc-200 bg-white/80 backdrop-blur-xl p-1 shadow-xl shadow-zinc-900/10 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
                                    role="listbox"
                                >
                                    <template x-for="(meta, code) in cryptos" :key="code">
                                        <button
                                            type="button"
                                            @click="selectedCrypto = code; open = false"
                                            :class="selectedCrypto === code ? 'bg-blue-50 text-blue-700' : 'text-zinc-800 hover:bg-zinc-200'"
                                            class="flex w-full items-center gap-2 rounded-[10px] px-3 py-2.5 text-left text-sm font-medium transition-colors"
                                        >
                                            <template x-if="meta.icon">
                                                <img :src="meta.icon" :alt="code" class="h-5 w-5 shrink-0 rounded-[10px]">
                                            </template>
                                            <template x-if="!meta.icon && meta.flag">
                                                <img :src="meta.flag" :alt="code" class="h-5 w-5 shrink-0 rounded-[10px] object-cover ring-1 ring-zinc-200">
                                            </template>
                                            <template x-if="!meta.icon && !meta.flag">
                                                <span class="inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-[10px] text-[10px] font-black text-white" :class="meta.type === 'crypto' ? 'bg-amber-500' : 'bg-emerald-500'" x-text="code.charAt(0)"></span>
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

                    {{-- Account ID — bill payments only. Captures the meter
                         number / customer ID the biller expects. Mirrors the
                         top-up phone pattern: validated client-side, blocks
                         Add to cart until non-empty, persisted to cart→order
                         metadata for the Zendit billing.accountId field. --}}
                    @if ($isBill)
                        <div>
                            <label class="mb-1.5 flex items-center gap-1.5 text-xs font-semibold text-zinc-900 dark:text-zinc-100">
                                {{ $brandName }} account / meter number
                            </label>
                            <div
                                :class="accountIdValid()
                                    ? 'border-emerald-500 ring-2 ring-emerald-500/15'
                                    : (accountId.length > 0 ? 'border-red-300' : 'border-zinc-200')"
                                class="flex h-[52px] w-full items-center gap-2 rounded-[10px] border bg-white px-3 transition-colors focus-within:border-blue-500 focus-within:ring-2 focus-within:ring-blue-500/15 dark:bg-[#26416b]"
                            >
                                <input
                                    type="text"
                                    inputmode="numeric"
                                    autocomplete="off"
                                    x-model="accountId"
                                    placeholder="e.g. 04220098765"
                                    class="min-w-0 flex-1 bg-transparent text-base font-medium tracking-wider text-zinc-900 outline-none placeholder:text-zinc-400 dark:text-white"
                                >
                                <svg
                                    x-show="accountIdValid()"
                                    x-cloak
                                    class="h-5 w-5 shrink-0 text-emerald-600"
                                    fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"
                                    aria-hidden="true"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                                </svg>
                            </div>
                            <p
                                x-show="accountId.length > 0 && ! accountIdValid()"
                                x-cloak
                                class="mt-1 text-[11px] font-medium text-red-600"
                            >Enter a valid account or meter number.</p>
                        </div>
                    @endif

                    {{-- Recipient phone — top-ups only. The buyer enters the
                         number to credit before adding to cart; canAddToCart()
                         enforces a valid number. Country flag + dial code are
                         locked to the product's country_code via dial_codes.php. --}}
                    @if ($isTopup)
                        <div>
                            <label class="mb-1.5 flex items-center gap-1.5 text-xs font-semibold text-zinc-900 dark:text-zinc-100">
                                @if (Product::flagUrl($product->country_code))
                                    <img src="{{ Product::flagUrl($product->country_code) }}" alt="" class="h-3.5 w-5 rounded-[2px] object-cover ring-1 ring-zinc-200" loading="lazy">
                                @endif
                                {{ $countryName }} phone number to refill
                            </label>
                            <div
                                :class="recipientPhoneValid()
                                    ? 'border-emerald-500 ring-2 ring-emerald-500/15'
                                    : (recipientPhone.length > 0 ? 'border-red-300' : 'border-zinc-200')"
                                class="flex h-[52px] w-full items-center gap-2 rounded-[10px] border bg-white px-3 transition-colors focus-within:border-blue-500 focus-within:ring-2 focus-within:ring-blue-500/15 dark:bg-[#26416b]"
                            >
                                <span class="shrink-0 text-sm font-semibold text-zinc-600 dark:text-zinc-200" x-text="recipientDialCode">{{ $dialCode }}</span>
                                <input
                                    type="tel"
                                    inputmode="tel"
                                    autocomplete="tel-national"
                                    x-model="recipientPhone"
                                    placeholder="555 123 4567"
                                    class="min-w-0 flex-1 bg-transparent text-base font-medium text-zinc-900 outline-none placeholder:text-zinc-400 dark:text-white"
                                >
                                <svg
                                    x-show="recipientPhoneValid()"
                                    x-cloak
                                    class="h-5 w-5 shrink-0 text-emerald-600"
                                    fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"
                                    aria-hidden="true"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                                </svg>
                            </div>
                            <p
                                x-show="recipientPhone.length > 0 && ! recipientPhoneValid()"
                                x-cloak
                                class="mt-1 text-[11px] font-medium text-red-600"
                            >Enter a valid {{ $countryName }} mobile number.</p>
                        </div>
                    @endif

                    {{-- Points you earn — 0.5 coins per $1 spent, floored. The coin icon is the site favicon. --}}
                    <p class="flex items-center gap-1.5 text-sm font-semibold text-zinc-700">
                        Points you earn
                        <img src="{{ asset('assets/favicon.ico') }}" alt="coins" class="h-6 w-6 object-contain" loading="lazy">
                        <span x-data="valueFlip()" x-effect="selectedVariantId; quantity; flash()" class="inline-block text-zinc-900" x-text="pointsEarned()">0</span>
                    </p>

                    {{-- Add to cart + Buy now — brand blue (outline + filled).
                         addToCart() pushes the selected variant into the global cart store,
                         which drops the nav cart popup open. Buy now also routes to checkout. --}}
                    <div class="grid grid-cols-2 gap-3">
                        {{-- Add to cart — morphs label -> spinner -> checkmark with a success bounce.
                             Re-clicks during the spinner/success cue are ignored by addToCart(). --}}
                        <button
                            type="button"
                            @click="addToCart()"
                            :disabled="! canAddToCart()"
                            :class="cartState === 'success'
                                ? 'border-emerald-500 bg-emerald-500 text-white animate-cart-pop'
                                : 'border-blue-600 bg-white text-blue-600 hover:bg-blue-600 hover:text-white'"
                            class="relative flex h-[52px] items-center justify-center rounded-[10px] border-2 px-4 text-base font-semibold transition-colors duration-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/40 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            <span
                                x-show="cartState === 'idle'"
                                x-transition:enter="transition ease-out duration-150"
                                x-transition:enter-start="opacity-0"
                                x-transition:leave="transition ease-in duration-100"
                                x-transition:leave-end="opacity-0"
                                class="absolute inset-0 flex items-center justify-center"
                            >Add to cart</span>

                            <span
                                x-show="cartState === 'loading'"
                                style="display:none;"
                                x-transition:enter="transition ease-out duration-150"
                                x-transition:enter-start="opacity-0"
                                x-transition:leave="transition ease-in duration-100"
                                x-transition:leave-end="opacity-0"
                                class="absolute inset-0 flex items-center justify-center"
                            >
                                <svg class="h-5 w-5 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <circle class="opacity-30" cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3"/>
                                    <path class="opacity-90" fill="currentColor" d="M12 3a9 9 0 0 1 9 9h-3a6 6 0 0 0-6-6V3z"/>
                                </svg>
                            </span>

                            <span
                                x-show="cartState === 'success'"
                                style="display:none;"
                                x-transition:enter="transition ease-out duration-200"
                                x-transition:enter-start="opacity-0 scale-90"
                                x-transition:enter-end="opacity-100 scale-100"
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
                            :disabled="! canAddToCart() || $store.cart.loading"
                            class="flex h-[52px] items-center justify-center rounded-[10px] bg-blue-600 px-4 text-base font-semibold text-white transition-colors hover:bg-blue-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/40 disabled:cursor-not-allowed disabled:opacity-50 disabled:hover:bg-blue-600"
                        >
                            Buy now
                        </button>
                    </div>

                    {{-- Country availability — the redeemable country for this
                         variant, plus a link to switch country via the locale modal. --}}
                    <div>
                        <p class="flex items-center gap-2 text-sm text-zinc-700">
                            @if (Product::flagUrl($product->country_code))
                                <img src="{{ Product::flagUrl($product->country_code) }}" alt="" class="h-4 w-6 shrink-0 rounded-[2px] object-cover ring-1 ring-zinc-200" loading="lazy">
                            @endif
                            <span>{{ $isTopup ? 'Works for ' . $countryName . ' mobile numbers' : ($isBill ? 'Available for billers in ' . $countryName : 'May only be redeemable in ' . $countryName) }}</span>
                        </p>
                        <p class="mt-0.5 text-sm text-zinc-600">
                            Not in {{ $countryName }}?
                            <button type="button" @click="$dispatch('open-locale-modal'); localeModalOpen = true" class="font-semibold text-zinc-900 underline underline-offset-2 transition-colors hover:text-blue-700">
                                Find your country
                            </button>
                        </p>
                    </div>
                @else
                    <div class="rounded-[10px] bg-zinc-50 px-4 py-8 text-center ring-1 ring-zinc-100">
                        <p class="text-base font-semibold text-zinc-900">Out of stock</p>
                        <p class="mt-1 text-sm text-zinc-600">{{ $isTopup ? 'This network has no top-up amounts available right now.' : ($isBill ? 'This biller has no payment amounts available right now.' : 'This card has no denominations available right now.') }} Check back later.</p>
                    </div>
                @endif

                {{-- Accordion sections: How to redeem / Terms / FAQ — INSIDE the right column so the
                     gift card stays alone on the left. No dividers between items. --}}
                <section class="mt-6">
            @if ($redeemUrl)
                {{-- Jump straight to the brand's redemption page in a new tab. --}}
                <a
                    href="{{ $redeemUrl }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="mb-2 inline-flex items-center gap-2 rounded-[10px] bg-blue-600 px-4 py-3 text-sm font-semibold text-white transition-colors hover:bg-blue-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/40"
                >
                    Redeem at {{ $brandName }}
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/>
                    </svg>
                </a>
            @endif

            @if ($product->redeem_instructions)
                <details class="group" open>
                    <summary class="flex cursor-pointer items-center justify-between py-5 text-lg font-bold text-zinc-900 marker:content-['']">
                        {{ $categorySlug === 'gift-cards' ? 'How to redeem' : 'How it works' }}
                        <svg class="h-5 w-5 text-zinc-600 transition-transform group-open:rotate-45" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/>
                        </svg>
                    </summary>
                    <div class="pb-5 text-base leading-relaxed text-zinc-700 [&>p]:mb-3 [&>ol]:list-decimal [&>ol]:pl-5 [&>ul]:list-disc [&>ul]:pl-5 [&>ol>li]:mb-1.5 [&>ul>li]:mb-1.5 [&_a]:text-blue-600 [&_a]:underline [&_a]:hover:text-blue-700">
                        {!! $linkify($product->redeem_instructions) !!}
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
                        {!! $linkify($product->terms_and_conditions) !!}
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
                        <div class="overflow-hidden rounded-[10px] bg-zinc-900 ring-1 ring-zinc-100">
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
                    @if ($isTopup)
                        <p class="mb-3"><strong class="text-zinc-900">How fast will the top-up arrive?</strong><br>Delivery is instant — the airtime is credited to the phone number within seconds of confirmed payment.</p>
                        <p class="mb-3"><strong class="text-zinc-900">Which numbers can I top up on {{ $brandName }}?</strong><br>This top-up works for {{ $brandName }} mobile numbers in {{ $countryName }}. To top up a different network or country, switch country in the locale picker at the top of the page.</p>
                        <p><strong class="text-zinc-900">What happens if the top-up fails?</strong><br>Our fair-refund policy covers any top-up that fails to deliver due to an issue on our end. Contact support with your order ID.</p>
                    @elseif ($isBill)
                        <p class="mb-3"><strong class="text-zinc-900">How fast is the bill paid?</strong><br>Payment is instant — the amount is applied to the account within seconds of confirmed payment.</p>
                        <p class="mb-3"><strong class="text-zinc-900">What do I need to pay a {{ $brandName }} bill?</strong><br>You need the account or meter number the bill is registered to. Enter it at checkout so the payment reaches the right account.</p>
                        <p><strong class="text-zinc-900">What happens if the payment fails?</strong><br>Our fair-refund policy covers any bill payment that fails to apply due to an issue on our end. Contact support with your order ID.</p>
                    @else
                        <p class="mb-3"><strong class="text-zinc-900">How fast will I receive my gift card?</strong><br>Delivery is instant — the redemption code lands in your delivery email within seconds of confirmed payment.</p>
                        <p class="mb-3"><strong class="text-zinc-900">Can I redeem this {{ $brandName }} gift card in my country?</strong><br>This card is only redeemable in {{ $countryName }}. To shop a different country's catalog, switch country in the locale picker at the top of the page.</p>
                        <p><strong class="text-zinc-900">What happens if the code doesn't work?</strong><br>Our fair-refund policy covers any code that fails to redeem due to a delivery issue on our end. Contact support with your order ID.</p>
                    @endif
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
                    <a href="{{ route($listingRoute, array_filter(['country' => $product->country_code, 'subcategory' => $product->subcategory?->slug])) }}" wire:navigate class="text-sm font-semibold text-blue-600 transition-colors hover:text-blue-700">
                        View all →
                    </a>
                </div>

                {{-- Horizontal carousel on mobile (scrollbar hidden); a static grid from sm up. --}}
                <div class="flex gap-3 overflow-x-auto pb-1 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden sm:grid sm:grid-cols-4 sm:overflow-visible sm:pb-0 lg:grid-cols-6">
                    @foreach ($similar as $s)
                        @php($sLogo = Product::brandLogoUrl($s->brand_key, $s->logo_url))
                        <a href="{{ route($detailRoute, ['brandSlug' => Product::brandSlug($s->brand_key), 'country' => $product->country_code]) }}" wire:navigate class="card-3d-scene group block w-36 shrink-0 sm:w-auto">
                            <div
                                class="card-3d relative flex aspect-[16/10] items-center justify-center overflow-hidden rounded-[15px] bg-[#ffffff] shadow-sm ring-1 ring-zinc-200 group-hover:shadow-lg group-hover:ring-zinc-300"
                                x-data="cardTilt()"
                                @mousemove="tilt($event)"
                                @mouseleave="reset()"
                            >
                                @if ($sLogo)
                                    <img src="{{ $sLogo }}" alt="" class="h-full w-full object-cover" loading="lazy">
                                @else
                                    <span class="text-xl font-black uppercase text-[#3f3f46]">{{ str(Product::brandDisplayName($s->brand_key))->substr(0, 2)->upper() }}</span>
                                @endif
                                <span class="card-3d-glare pointer-events-none absolute inset-0" aria-hidden="true"></span>
                            </div>
                            <p class="mt-2 truncate text-[13px] font-bold text-zinc-900 group-hover:text-blue-700">{{ Product::brandDisplayName($s->brand_key) }}</p>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif
    </div>

    <script>
        window.brandDetail = function ({ variants, rangeText, cryptos, defaultCrypto, markup, customRate, customMin, customMax, requiresRecipientPhone, defaultDialCode, requiresAccountId }) {
            // `cryptos` is an array of {code, name, type, perUsd, decimals, icon} from the
            // admin currency_rates table. Reshape into a code-keyed object.
            const cryptoMap = {};
            (cryptos || []).forEach((c) => { cryptoMap[c.code] = c; });

            return {
                variants,
                rangeText,
                markup,               // { type, value, min_margin_percent } from CartPricingService
                customRate,           // variant-currency → USD divisor for a variable amount
                amount: '',           // selected face value, in the card's own currency
                customMode: false,
                customMin,            // min/max for a custom amount, in the card's currency
                customMax,
                selectedVariantId: null,
                quantity: 1,
                cryptos: cryptoMap,
                selectedCrypto: cryptoMap[defaultCrypto] ? defaultCrypto : (cryptos[0]?.code ?? null),

                // Add-to-cart micro-interaction: idle -> loading -> success -> idle.
                cartState: 'idle',
                _cartTimer: null,

                // Mobile top-up: the buyer enters the phone number to credit on
                // the product page. The form below is only rendered when
                // requiresRecipientPhone is true; the digits live here.
                requiresRecipientPhone: !! requiresRecipientPhone,
                recipientPhone: '',                          // digits the buyer typed (no country code)
                recipientDialCode: defaultDialCode || '',    // e.g. '+1', shown as a prefix on the input

                // Bill payment: account / meter number the biller will credit.
                requiresAccountId: !! requiresAccountId,
                accountId: '',

                init() {
                    // Empty by default so the placeholder hint stays visible.
                },

                // Validation: digits + spaces + optional dashes, 6-15 digits when
                // stripped. Server re-validates with the same regex but more strict.
                recipientPhoneValid() {
                    if (! this.requiresRecipientPhone) { return true; }
                    const digits = (this.recipientPhone || '').replace(/[^0-9]/g, '');
                    return digits.length >= 6 && digits.length <= 15;
                },

                // E.164-ish string we ship to the server. Strips the user-visible
                // separators and prefixes the dial code, e.g. '+17575551234'.
                fullRecipientPhone() {
                    const digits = (this.recipientPhone || '').replace(/[^0-9]/g, '');
                    if (! digits) { return ''; }
                    const dial = (this.recipientDialCode || '').replace(/[^0-9+]/g, '');
                    return dial && ! digits.startsWith(dial.replace('+', ''))
                        ? dial + digits
                        : '+' + digits;
                },

                // Bill payment account-id validation. Most billers accept 6-20
                // characters of digits + optional separators. We don't restrict
                // hyphens/spaces since some operators format their meter IDs.
                accountIdValid() {
                    if (! this.requiresAccountId) { return true; }
                    const cleaned = (this.accountId || '').replace(/[^0-9A-Za-z]/g, '');
                    return cleaned.length >= 4 && cleaned.length <= 30;
                },

                canAddToCart() {
                    if (! this.selectedVariantId) {
                        return false;
                    }
                    if (this.customMode) {
                        // A custom amount must fall within the variable variant's range.
                        const value = Number(this.amount);
                        if (value < this.customMin || value > this.customMax) { return false; }
                    }
                    if (this.requiresRecipientPhone && ! this.recipientPhoneValid()) {
                        return false;
                    }
                    if (this.requiresAccountId && ! this.accountIdValid()) {
                        return false;
                    }
                    return true;
                },

                async addToCart() {
                    // Ignore re-clicks while the spinner/success cue is still playing.
                    if (this.cartState !== 'idle') {
                        return false;
                    }
                    if (! this.canAddToCart()) {
                        return false;
                    }
                    // Variable variants pass the requested face value back in USD (the cart works in USD).
                    const requested = this.customMode
                        ? Number(this.amount) / (this.customRate || 1)
                        : null;

                    // Per-item context: recipient phone (top-ups) or account
                    // ID (bill payments). Persisted to cart→order metadata
                    // and consumed by the fulfilment provider.
                    let metadata = null;
                    if (this.requiresRecipientPhone) {
                        metadata = { recipient_phone: this.fullRecipientPhone() };
                    } else if (this.requiresAccountId) {
                        metadata = { account_id: (this.accountId || '').trim() };
                    }

                    this.cartState = 'loading';
                    const ok = await this.$store.cart.add(this.selectedVariantId, this.quantity || 1, requested, metadata);

                    if (ok) {
                        // Hold the success cue briefly, then settle back to idle.
                        this.cartState = 'success';
                        clearTimeout(this._cartTimer);
                        this._cartTimer = setTimeout(() => { this.cartState = 'idle'; }, 1600);
                    } else {
                        this.cartState = 'idle';
                    }
                    return ok;
                },

                async buyNow() {
                    const ok = await this.addToCart();
                    if (ok) {
                        window.location.href = '{{ route('shop.checkout') }}';
                    }
                },

                // Payable USD price for one unit of the selected variant. Fixed
                // denominations use the precomputed price_usd (face_value + markup).
                // Variable amounts apply markup to the entered face value, matching
                // the backend's CartPricingService logic.
                unitPriceUsd() {
                    if (! this.selectedVariantId) {
                        return 0;
                    }
                    const variant = this.variants.find((v) => v.id === this.selectedVariantId);
                    if (! variant) {
                        return 0;
                    }
                    if (variant.is_variable) {
                        // The entered amount is the face value in the card's currency.
                        // Convert to USD, then apply markup (same as backend).
                        const faceUsd = Number(this.amount || 0) / (this.customRate || 1);
                        return this.applyMarkup(faceUsd);
                    }
                    return variant.price_usd || 0;
                },

                // Mirror of CartPricingService::resolveRetailPrice — apply the
                // markup, then clamp to the min-margin floor.
                applyMarkup(costUsd) {
                    if (costUsd <= 0) {
                        return 0;
                    }
                    const retail = this.markup.type === 'fixed'
                        ? costUsd + Number(this.markup.value)
                        : costUsd * (1 + Number(this.markup.value) / 100);
                    const floor = costUsd * (1 + Number(this.markup.min_margin_percent || 0) / 100);
                    return Math.max(retail, floor);
                },

                // Payable USD total for the whole order.
                totalUsd() {
                    return this.unitPriceUsd() * (this.quantity || 1);
                },

                // 0.5 coins per $1 (USD) of the payable total, floored.
                pointsEarned() {
                    return Math.floor(this.totalUsd() * 0.5);
                },

                // Estimated equivalent in the currently selected currency (fiat or crypto).
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

    {{-- Region notice lives on the checkout + order pages (amber strip), so we don't
         interrupt browsing with a modal here. Customers see the warning at the point
         it matters most — right before paying. --}}

</x-layouts.app.header>
