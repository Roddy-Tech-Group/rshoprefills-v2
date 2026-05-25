@php
    use App\Domain\Cart\Services\CartPricingService;
    use App\Models\Category;
    use App\Models\CurrencyRate;
    use App\Models\Product;
    use Illuminate\Support\Facades\Cache;
    use Illuminate\Support\Str;

    /** @var \App\Models\Product $product  An `esims`-category Product = one coverage region. */

    $variants = $product->variants;
    $hasPlans = $variants->isNotEmpty();
    $pricing  = app(CartPricingService::class);

    // "United States Data eSIM" or "United States eSIM" -> "United States"
    // (Zendit names regions "… Data eSIM", Airalo names them "… eSIM").
    $regionLabel = (string) str($product->name)->replaceLast(' Data eSIM', '')->replaceLast(' eSIM', '')->trim();
    $flag        = Product::flagUrl($product->country_code);

    // Decorative tier badges, assigned by plan order (cheapest first). Cycles if a
    // region has more than four plans.
    $tiers = [
        ['name' => 'TRIP',       'badge' => 'bg-zinc-100 text-zinc-600'],
        ['name' => 'EXPLORER',   'badge' => 'bg-blue-100 text-blue-700'],
        ['name' => 'ADVENTURER', 'badge' => 'bg-violet-100 text-violet-700 dark:bg-violet-500/20 dark:text-violet-300'],
        ['name' => 'NOMAD',      'badge' => 'bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-300'],
    ];

    // Each variant is a plan. Two feeds populate these: Airalo gives a clean
    // `data_limit` ("1 GB") + reliable `validity_days` and Voice/SMS fields; Zendit
    // leaves those unreliable, so we fall back to its `raw_payload` (dataGB, etc.).
    $plans = $variants->values()->map(function ($v, $i) use ($pricing, $product, $tiers) {
        $v->setRelation('product', $product);
        $meta = $v->metadata ?? [];
        $raw  = $meta['raw_payload'] ?? [];

        // Data label — prefer the normalized field, else derive from Zendit's payload.
        $dl = trim((string) ($meta['data_limit'] ?? ''));
        if ($dl !== '' && strcasecmp($dl, 'Unknown') !== 0) {
            $dataLabel = $dl;
        } else {
            $gb        = (float) ($raw['dataGB'] ?? 0);
            $unlimited = (bool) ($raw['dataUnlimited'] ?? false);
            $dataLabel = $gb > 0
                ? rtrim(rtrim(number_format($gb, 2), '0'), '.').' GB'
                : ($unlimited ? 'Unlimited' : 'Data');
        }

        // Duration — Airalo's validity_days is reliable; Zendit's lives in raw_payload.
        $days = (int) ($meta['validity_days'] ?? 0);
        if ($days <= 0) {
            $days = (int) ($raw['durationDays'] ?? 0);
        }

        $tier = $tiers[$i % count($tiers)];

        return [
            'id'             => $v->id,
            'data'           => $dataLabel,
            'days'           => $days,
            'data_only'      => ($meta['plan_type'] ?? '') === 'data_only',
            'supports_voice' => (bool) ($meta['supports_voice'] ?? false),
            'supports_sms'   => (bool) ($meta['supports_sms'] ?? false),
            'voice'          => $meta['voice_limit'] ?? null,
            'sms'            => $meta['sms_limit'] ?? null,
            'tier'           => $tier['name'],
            'badge'          => $tier['badge'],
            'price'          => round((float) $pricing->calculatePricing($v, 1)['unit_price_snapshot'], 2),
        ];
    })->values();

    // Compact payload for Alpine (just what the price/points maths needs).
    $plansForJs = $plans->map(fn ($p) => ['id' => $p['id'], 'price' => $p['price']])->values();

    // Coverage — distinct ISO codes across the region's plans.
    $coverage = collect($variants)
        ->flatMap(fn ($v) => (array) ($v->metadata['coverage'] ?? []))
        ->map(fn ($c) => strtoupper((string) $c))
        ->filter(fn ($c) => strlen($c) === 2)
        ->unique()
        ->values();

    // Every eSIM region, for the "Select country or region" picker. Cached 10 min;
    // selecting one navigates to that region's page.
    $esimRegions = Cache::remember('esim-regions-dropdown', now()->addMinutes(10), function () {
        $cat = Category::where('slug', 'esims')->first();

        return Product::query()
            ->where('is_active', true)
            ->when($cat, fn ($q) => $q->where('category_id', $cat->id))
            ->orderBy('name')
            ->get(['slug', 'name', 'country_code'])
            ->map(fn ($r) => [
                'slug' => $r->slug,
                'name' => (string) str($r->name)->replaceLast(' Data eSIM', '')->replaceLast(' eSIM', '')->trim(),
                'flag' => Product::flagUrl($r->country_code),
            ])
            ->values();
    });

    // Estimated-price currency selector — admin-managed fiat + crypto rates. A fiat
    // code's flag derives from its first two letters; a few non-country codes map
    // explicitly. (Mirrors the gift-card product page.)
    $currencyFlagOverrides = ['XAF' => 'CM', 'XOF' => 'SN', 'XCD' => 'AG'];
    $currencyFlag = fn (string $code) => Product::flagUrl($currencyFlagOverrides[strtoupper($code)] ?? substr(strtoupper($code), 0, 2));

    $cryptoRatesForJs = CurrencyRate::active()
        ->orderBy('type')
        ->orderBy('sort_order')
        ->orderBy('code')
        ->get(['code', 'name', 'type', 'rate_per_usd', 'icon_path'])
        ->map(fn ($r) => [
            'code'     => $r->code,
            'name'     => $r->name,
            'type'     => $r->type,
            'perUsd'   => (float) $r->rate_per_usd,
            'decimals' => $r->rate_per_usd < 0.01 ? 8 : ($r->rate_per_usd < 1 ? 4 : 2),
            'icon'     => ($r->type === 'crypto' && $r->icon_path) ? asset('assets/'.$r->icon_path) : null,
            'flag'     => $r->type === 'fiat' ? $currencyFlag($r->code) : null,
        ])
        ->values();

    $countryCurrency = strtoupper(config('country_currency.map.'.strtoupper($product->country_code), 'USD'));
    $defaultCrypto = $cryptoRatesForJs->firstWhere('code', $countryCurrency)
        ? $countryCurrency
        : ($cryptoRatesForJs->firstWhere('code', 'USD') ? 'USD' : ($cryptoRatesForJs->first()['code'] ?? 'USDT'));
@endphp

<x-layouts.app.header :title="$regionLabel . ' eSIM | RshopRefills'">

    <div
        x-data="esimStore({
            plans: @js($plansForJs),
            cryptos: @js($cryptoRatesForJs),
            defaultCrypto: @js($defaultCrypto),
            checkoutUrl: '{{ route('shop.checkout') }}',
        })"
        x-init="init()"
        class="mx-auto w-full max-w-[1100px] px-4 py-8 sm:px-6 lg:py-12"
    >

        {{-- ── Heading ──────────────────────────────────────────────────────── --}}
        <div class="mb-6">
            <h1 class="text-2xl font-bold tracking-tight text-zinc-900 sm:text-3xl">eSIM data plans</h1>
            <p class="mt-1.5 text-sm text-zinc-600 sm:text-base">Stay connected in 190+ countries. Instant QR delivery, no physical SIM.</p>
        </div>

        {{-- ── Select country or region ─────────────────────────────────────── --}}
        <div class="max-w-md">
            <label class="mb-1.5 block text-sm font-bold text-zinc-900">Select country or region</label>
            <div
                x-data="{ open: false, search: '' }"
                @click.outside="open = false; search = ''"
                class="relative"
            >
                <button
                    type="button"
                    @click="open = ! open; if (open) $nextTick(() => $refs.regionSearch?.focus())"
                    :class="open ? 'border-blue-500 ring-2 ring-blue-500/15' : 'border-zinc-300 hover:border-zinc-400'"
                    class="flex h-[52px] w-full items-center gap-2.5 rounded-xl border bg-white px-3.5 text-base font-medium text-zinc-900 outline-none transition-colors"
                >
                    @if ($flag)
                        <img src="{{ $flag }}" alt="" class="h-4 w-6 shrink-0 rounded-[2px] object-cover ring-1 ring-zinc-200">
                    @endif
                    <span class="flex-1 truncate text-left">{{ $regionLabel }}</span>
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
                    class="absolute left-0 right-0 top-full z-30 mt-2 overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-xl shadow-zinc-900/10"
                    role="listbox"
                >
                    <div class="border-b border-zinc-100 p-2">
                        <div class="relative">
                            <svg class="pointer-events-none absolute left-2.5 top-1/2 h-4 w-4 -translate-y-1/2 text-zinc-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <input
                                x-ref="regionSearch"
                                x-model="search"
                                type="text"
                                placeholder="Search a country or region"
                                aria-label="Search a country or region"
                                class="w-full rounded-lg border border-zinc-200 bg-zinc-50 py-2 pl-8 pr-3 text-sm text-zinc-800 placeholder:text-zinc-500 outline-none transition-colors focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/15"
                            />
                        </div>
                    </div>

                    <div class="max-h-72 overflow-y-auto p-1">
                        @foreach ($esimRegions as $r)
                            <a
                                href="{{ route('shop.esim', $r['slug']) }}"
                                wire:navigate
                                data-name="{{ Str::lower($r['name']) }}"
                                x-show="search === '' || $el.dataset.name.includes(search.toLowerCase())"
                                @class([
                                    'flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-left text-base font-medium transition-colors',
                                    'bg-blue-50 text-blue-700' => $r['slug'] === $product->slug,
                                    'text-zinc-700 hover:bg-zinc-50' => $r['slug'] !== $product->slug,
                                ])
                            >
                                @if ($r['flag'])
                                    <img src="{{ $r['flag'] }}" alt="" class="h-4 w-6 shrink-0 rounded-[2px] object-cover ring-1 ring-zinc-200" loading="lazy">
                                @else
                                    <span class="h-4 w-6 shrink-0 rounded-[2px] bg-zinc-100 ring-1 ring-zinc-200"></span>
                                @endif
                                <span class="flex-1 truncate">{{ $r['name'] }}</span>
                                @if ($r['slug'] === $product->slug)
                                    <svg class="h-4 w-4 shrink-0 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                    </svg>
                                @endif
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        @if ($hasPlans)
            {{-- ── Select data plan ─────────────────────────────────────────── --}}
            <h2 class="mt-8 text-sm font-bold text-zinc-900">Select data plan</h2>
            <div class="mt-3 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($plans as $p)
                    <button
                        type="button"
                        @click="selectedId = {{ $p['id'] }}"
                        :class="selectedId === {{ $p['id'] }}
                            ? 'border-blue-600 ring-1 ring-blue-500/20'
                            : 'border-zinc-200 hover:border-zinc-300'"
                        class="flex flex-col rounded-2xl border-2 bg-white p-5 text-left transition-colors focus:outline-none"
                    >
                        <div class="flex flex-wrap items-center gap-1.5">
                            <span class="inline-flex w-max items-center rounded-[5px] px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide {{ $p['badge'] }}">{{ $p['tier'] }}</span>
                            @if ($p['supports_voice'])
                                {{-- Voice plans (Airalo) include a phone number. --}}
                                <span class="inline-flex items-center gap-1 rounded-[5px] bg-blue-100 px-2 py-1 text-[10px] font-bold uppercase tracking-wide text-blue-700">
                                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                    </svg>
                                    +Number
                                </span>
                            @endif
                        </div>

                        <p class="mt-4 text-xl font-bold text-zinc-900">
                            {{ $p['data'] }}@if ($p['days'] > 0) {{ $p['days'] }} Days @endif
                        </p>

                        <div class="my-3 h-px bg-zinc-100"></div>

                        <ul class="space-y-2 text-sm text-zinc-600">
                            <li class="flex items-center gap-2">
                                <svg class="h-4 w-4 shrink-0 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.288 15.038a5.25 5.25 0 017.424 0M5.106 11.856c3.807-3.808 9.98-3.808 13.788 0M1.924 8.674c5.565-5.565 14.587-5.565 20.152 0M12.53 18.22l-.53.53-.53-.53a.75.75 0 011.06 0z"/>
                                </svg>
                                {{ $p['data'] }} of data
                            </li>
                            <li class="flex items-center gap-2">
                                <svg class="h-4 w-4 shrink-0 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                @if ($p['days'] > 0)
                                    {{ $p['days'] }} day validity
                                @else
                                    Flexible validity
                                @endif
                            </li>
                            @if ($p['supports_voice'])
                                <li class="flex items-center gap-2">
                                    <svg class="h-4 w-4 shrink-0 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                    </svg>
                                    {{ $p['voice'] ?? 'Voice included' }}
                                </li>
                            @endif
                            @if ($p['supports_sms'])
                                <li class="flex items-center gap-2">
                                    <svg class="h-4 w-4 shrink-0 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0zM3 12c0 4.97 4.03 9 9 9a8.96 8.96 0 004.5-1.2L21 21l-1.2-4.5A8.96 8.96 0 0021 12"/>
                                    </svg>
                                    {{ $p['sms'] ?? 'SMS included' }}
                                </li>
                            @endif
                        </ul>

                        <div class="mt-4 space-y-0.5 text-xs text-zinc-500">
                            <p>Pin validity: 365 days</p>
                            <p>{{ $p['data_only'] ? 'Data only' : 'Voice, SMS & data' }}</p>
                        </div>

                        <p class="mt-4 text-right text-base font-bold tabular-nums text-zinc-900">${{ number_format($p['price'], 2) }}</p>
                    </button>
                @endforeach
            </div>

            {{-- ── Valid region ─────────────────────────────────────────────── --}}
            <div class="mt-8">
                <p class="text-sm font-bold text-zinc-900">Valid region:</p>
                <p class="mt-1 flex items-center gap-2 text-sm text-zinc-600">
                    @if ($flag)
                        <img src="{{ $flag }}" alt="" class="h-4 w-6 shrink-0 rounded-[2px] object-cover ring-1 ring-zinc-200">
                    @endif
                    {{ $regionLabel }}@if ($coverage->count() > 1) <span class="text-zinc-400">&middot;</span> usable in {{ $coverage->count() }} countries @endif
                </p>
            </div>

            {{-- ── Quantity + Estimated price ───────────────────────────────── --}}
            <div class="mt-8 grid grid-cols-1 gap-6 sm:grid-cols-[8rem_minmax(0,16rem)]">

                {{-- Quantity --}}
                <div>
                    <label class="mb-1.5 block text-sm font-bold text-zinc-900">Quantity</label>
                    <div
                        x-data="{ open: false }"
                        @click.outside="open = false"
                        class="relative"
                    >
                        <button
                            type="button"
                            @click="open = ! open"
                            :class="open ? 'border-blue-500 ring-2 ring-blue-500/15' : 'border-zinc-300 hover:border-zinc-400'"
                            class="flex h-[52px] w-full items-center gap-2 rounded-xl border bg-white px-3.5 text-base font-bold text-zinc-900 transition-colors"
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
                            class="absolute left-0 right-0 top-full z-20 mt-2 max-h-60 overflow-y-auto rounded-xl border border-zinc-200 bg-white p-1 shadow-xl shadow-zinc-900/10 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
                            role="listbox"
                        >
                            @for ($n = 1; $n <= 10; $n++)
                                <button
                                    type="button"
                                    @click="quantity = {{ $n }}; open = false"
                                    :class="quantity === {{ $n }} ? 'bg-blue-50 text-blue-700' : 'text-zinc-800 hover:bg-zinc-50'"
                                    class="flex w-full items-center justify-center rounded-lg px-2 py-2 text-center text-base font-medium tabular-nums transition-colors"
                                >
                                    {{ $n }}
                                </button>
                            @endfor
                        </div>
                    </div>
                </div>

                {{-- Estimated price --}}
                <div>
                    <label class="mb-1.5 block text-sm font-bold text-zinc-900">Estimated price</label>
                    <div
                        x-data="{ open: false }"
                        @click.outside="open = false"
                        class="relative"
                    >
                        <button
                            type="button"
                            @click="open = ! open"
                            :class="open ? 'border-blue-500 ring-2 ring-blue-500/15' : 'border-zinc-300 hover:border-zinc-400'"
                            class="flex h-[52px] w-full items-center gap-2 rounded-xl border bg-white px-3.5 text-base font-bold text-zinc-900 transition-colors"
                        >
                            <template x-if="cryptos[selectedCrypto]?.icon">
                                <img :src="cryptos[selectedCrypto].icon" :alt="selectedCrypto" class="h-5 w-5 shrink-0 rounded-full">
                            </template>
                            <template x-if="!cryptos[selectedCrypto]?.icon && cryptos[selectedCrypto]?.flag">
                                <img :src="cryptos[selectedCrypto].flag" :alt="selectedCrypto" class="h-5 w-5 shrink-0 rounded-full object-cover ring-1 ring-zinc-200">
                            </template>
                            <template x-if="!cryptos[selectedCrypto]?.icon && !cryptos[selectedCrypto]?.flag">
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
                            class="absolute left-0 top-full z-20 mt-2 max-h-80 w-60 overflow-y-auto rounded-xl border border-zinc-200 bg-white p-1 shadow-xl shadow-zinc-900/10 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
                            role="listbox"
                        >
                            <template x-for="(meta, code) in cryptos" :key="code">
                                <button
                                    type="button"
                                    @click="selectedCrypto = code; open = false"
                                    :class="selectedCrypto === code ? 'bg-blue-50 text-blue-700' : 'text-zinc-800 hover:bg-zinc-50'"
                                    class="flex w-full items-center gap-2 rounded-lg px-3 py-2.5 text-left text-sm font-medium transition-colors"
                                >
                                    <template x-if="meta.icon">
                                        <img :src="meta.icon" :alt="code" class="h-5 w-5 shrink-0 rounded-full">
                                    </template>
                                    <template x-if="!meta.icon && meta.flag">
                                        <img :src="meta.flag" :alt="code" class="h-5 w-5 shrink-0 rounded-full object-cover ring-1 ring-zinc-200">
                                    </template>
                                    <template x-if="!meta.icon && !meta.flag">
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

            {{-- ── Points you earn ──────────────────────────────────────────── --}}
            <p class="mt-6 flex items-center gap-1.5 text-sm font-semibold text-zinc-700">
                Points you earn
                <img src="{{ asset('assets/favicon.ico') }}" alt="coins" class="h-6 w-6 object-contain" loading="lazy">
                <span class="text-zinc-900" x-text="pointsEarned()">0</span>
            </p>

            {{-- ── Add to cart / Buy now ────────────────────────────────────── --}}
            <div class="mt-5 grid max-w-xl grid-cols-2 gap-3">
                {{-- Add to cart — morphs label -> spinner -> checkmark on success. --}}
                <button
                    type="button"
                    @click="addToCart()"
                    :disabled="! selectedId"
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
                    :disabled="! selectedId || $store.cart.loading"
                    class="flex h-[52px] items-center justify-center rounded-xl bg-blue-600 px-4 text-base font-semibold text-white transition-colors hover:bg-blue-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/40 disabled:cursor-not-allowed disabled:opacity-50 disabled:hover:bg-blue-600"
                >
                    Buy now
                </button>
            </div>

            {{-- ── Supporting info ──────────────────────────────────────────── --}}
            <div class="mt-12 grid grid-cols-1 gap-6 lg:grid-cols-2">
                {{-- Coverage --}}
                @if ($coverage->isNotEmpty())
                    <div class="rounded-2xl bg-white p-5 ring-1 ring-zinc-200 shadow-sm">
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
                <div class="rounded-2xl bg-white p-5 ring-1 ring-zinc-200 shadow-sm">
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
        @else
            {{-- No available plans --}}
            <div class="mt-8 rounded-2xl bg-zinc-50 px-4 py-10 text-center ring-1 ring-zinc-100">
                <p class="text-base font-semibold text-zinc-900">No data plans available</p>
                <p class="mt-1 text-sm text-zinc-600">This region has no plans in stock right now. Check back later.</p>
                <a href="{{ route('shop.esims') }}" wire:navigate class="mt-4 inline-flex items-center gap-1.5 text-sm font-semibold text-blue-600 hover:text-blue-700">
                    Browse other regions
                </a>
            </div>
        @endif
    </div>

    <script>
        window.esimStore = function ({ plans, cryptos, defaultCrypto, checkoutUrl }) {
            const cryptoMap = {};
            (cryptos || []).forEach((c) => { cryptoMap[c.code] = c; });

            return {
                plans: plans || [],
                checkoutUrl,
                selectedId: (plans && plans.length) ? plans[0].id : null,
                quantity: 1,
                cryptos: cryptoMap,
                selectedCrypto: cryptoMap[defaultCrypto] ? defaultCrypto : (cryptos[0]?.code ?? null),
                cartState: 'idle',
                _t: null,

                init() {},

                plan() {
                    return this.plans.find((p) => p.id === this.selectedId) || null;
                },
                unitPriceUsd() {
                    return this.plan()?.price || 0;
                },
                totalUsd() {
                    return this.unitPriceUsd() * (this.quantity || 1);
                },
                // 0.5 loyalty points per USD of the payable total, floored.
                pointsEarned() {
                    return Math.floor(this.totalUsd() * 0.5);
                },

                formatCrypto() {
                    return this.formatCryptoFor(this.selectedCrypto);
                },
                formatCryptoFor(code) {
                    const meta = this.cryptos[code];
                    if (! meta) return '0 ' + code;
                    return (this.totalUsd() * meta.perUsd).toFixed(meta.decimals) + ' ' + code;
                },

                async addToCart() {
                    if (this.cartState !== 'idle' || ! this.selectedId) {
                        return false;
                    }
                    this.cartState = 'loading';
                    const ok = await this.$store.cart.add(this.selectedId, this.quantity || 1);
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
                    const ok = await this.addToCart();
                    if (ok) {
                        window.location.href = this.checkoutUrl;
                    }
                },
            };
        };
    </script>

</x-layouts.app.header>
