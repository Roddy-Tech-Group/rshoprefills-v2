@php
    use App\Domain\Cart\Services\CartPricingService;
    use App\Models\Category;
    use App\Models\CurrencyRate;
    use App\Models\Product;
    use Illuminate\Support\Facades\Cache;
    use Illuminate\Support\Str;

    /** @var \App\Models\Product $product  An `esims`-category Product = one coverage region. */

    // Variants are merged across suppliers for the country by EsimStoreController;
    // fall back to the product's own when rendered without that controller.
    $variants = $variants ?? $product->variants;
    $hasPlans = $variants->isNotEmpty();
    $pricing  = app(CartPricingService::class);

    // Region name + scope from the shared classifier (suppliers mislabel every
    // region "Global eSIM", so non-country products derive their name from the slug).
    $regionMeta  = \App\Http\Controllers\EsimStoreController::regionMeta($product->country_code, $product->slug, $product->name);
    $regionLabel = $regionMeta['name'];
    $cc          = $regionMeta['cc'];
    $isLocal     = strlen($cc) === 2 && $cc !== 'WW';
    $flag        = $isLocal ? Product::flagUrl($cc) : \App\Http\Controllers\EsimStoreController::globalFlag();
    $curScope    = ucfirst($regionMeta['scope']);
    // Key used to mark the active entry in the (country-deduped) region picker.
    $currentKey  = $isLocal ? $cc : 'slug:'.$product->slug;

    // Generic placeholder network names that aren't real carriers (Zendit reports
    // "eSIM"); only real operator names (Airalo: "Orange", "T-Mobile") are shown.
    $cleanNetwork = function ($n) {
        $n = trim((string) $n);

        return ($n === '' || in_array(strtolower($n), ['esim', 'multiple', 'unknown'], true)) ? null : $n;
    };

    // Splits "1 GB" / "120 mins" / "100 SMS" into figure + unit so the row can style
    // the number and the unit separately. Bare strings like "Unlimited" stay in 'num'.
    $splitUnit = function (?string $s): array {
        $s = trim((string) $s);
        if ($s === '') {
            return ['num' => '', 'unit' => ''];
        }
        if (preg_match('~^([\d.,/]+)\s*(.*)$~', $s, $m)) {
            return ['num' => trim($m[1]), 'unit' => trim($m[2])];
        }

        return ['num' => $s, 'unit' => ''];
    };

    // Each variant is a plan. Airalo provides clean data_limit + reliable
    // validity_days + Voice/SMS; Zendit leaves those unreliable so we fall back to
    // its raw_payload (dataGB / durationDays / dataUnlimited / shortNotes).
    $plans = $variants->values()->map(function ($v) use ($pricing, $product, $cleanNetwork, $splitUnit) {
        // Each merged variant already carries its own product (for correct markup);
        // only fall back to the page product when the relation isn't set.
        if (! $v->relationLoaded('product') || ! $v->product) {
            $v->setRelation('product', $product);
        }
        $meta = $v->metadata ?? [];
        $raw  = $meta['raw_payload'] ?? [];

        $dl          = trim((string) ($meta['data_limit'] ?? ''));
        $unlimited   = (bool) ($raw['dataUnlimited'] ?? false);
        $isUnlimited = $unlimited || str_contains(strtolower($dl), 'unlimited');

        if ($isUnlimited) {
            $dataLabel = 'Unlimited';
        } elseif ($dl !== '' && strcasecmp($dl, 'Unknown') !== 0) {
            $dataLabel = $dl;
        } else {
            $gb        = (float) ($raw['dataGB'] ?? 0);
            $dataLabel = $gb > 0 ? rtrim(rtrim(number_format($gb, 2), '0'), '.').' GB' : 'Data';
        }

        $days = (int) ($meta['validity_days'] ?? 0);
        if ($days <= 0) {
            $days = (int) ($raw['durationDays'] ?? 0);
        }

        // Voice/SMS: real numeric allowance only (prefer the raw package field). No
        // "Included" placeholder — a plan is voice only if it actually carries minutes.
        $voiceVal = $raw['voice'] ?? ($meta['voice_limit'] ?? null);
        $smsVal   = $raw['text'] ?? ($meta['sms_limit'] ?? null);
        $hasVoice = is_numeric($voiceVal) && (float) $voiceVal > 0;
        $hasSms   = is_numeric($smsVal) && (float) $smsVal > 0;

        $dataParts  = $splitUnit($dataLabel);
        $voiceLabel = $hasVoice ? $voiceVal.' mins' : null;
        $smsLabel   = $hasSms ? $smsVal.' SMS' : null;
        $voiceParts = $hasVoice ? $splitUnit($voiceLabel) : ['num' => '', 'unit' => ''];
        $smsParts   = $hasSms ? $splitUnit($smsLabel) : ['num' => '', 'unit' => ''];

        return [
            'id'           => $v->id,
            'data'         => $dataLabel,
            'data_num'     => $dataParts['num'],
            'data_unit'    => $dataParts['unit'],
            'days'         => $days,
            'is_unlimited' => $isUnlimited,
            'is_voice'     => $hasVoice || $hasSms,
            'voice'        => $voiceLabel,
            'voice_num'    => $voiceParts['num'],
            'voice_unit'   => $voiceParts['unit'],
            'sms'          => $smsLabel,
            'sms_num'      => $smsParts['num'],
            'sms_unit'     => $smsParts['unit'],
            'note'         => $isUnlimited ? trim((string) ($raw['shortNotes'] ?? '')) : '',
            'network'      => $cleanNetwork($meta['network'] ?? null),
            'top_up'       => (bool) ($meta['is_rechargeable'] ?? ($meta['supports_topup'] ?? false)),
            'cost'         => round((float) $v->cost_price, 2),
            'price'        => round((float) $pricing->calculatePricing($v, 1)['unit_price_snapshot'], 2),
        ];
    })->values();

    // Suppliers list the same offering twice (e.g. Airalo's "discover" and "discover+"
    // at the same retail price). Keep one per data+days+voice+sms — the cheapest.
    $plans = $plans
        ->sortBy(fn ($p) => sprintf('%012.2f|%012.2f', $p['price'], $p['cost']))
        ->unique(fn ($p) => ($p['is_voice'] ? 'v' : 'd').'|'.$p['days'].'|'.strtolower($p['data']).'|'.$p['voice'].'|'.$p['sms'])
        ->values();

    // Coverage list for multi-country (regional/global) eSIMs. Zendit stores ISO
    // codes in metadata.coverage; Airalo lists country names inside the package HTML.
    // All packages of a product share coverage, so the first one with data is enough.
    $isoToName = array_flip(config('countries.codes', []));
    $coverageList = collect();
    foreach ($variants as $v) {
        $meta = $v->metadata ?? [];
        foreach ((array) ($meta['coverage'] ?? []) as $c) {
            $code = strtoupper((string) $c);
            $coverageList->push($isoToName[$code] ?? $code);
        }
        if ($coverageList->isEmpty()) {
            $html = $meta['raw_payload']['qr_installation'] ?? ($meta['raw_payload']['manual_installation'] ?? '');
            if ($html && preg_match('~Coverage:\s*</b>\s*(.*?)</p>~is', $html, $cm)) {
                foreach (preg_split('~,|\sand\s~i', strip_tags($cm[1])) as $name) {
                    $name = trim($name, " .\t\n\r\0\x0B");
                    if ($name !== '') {
                        $coverageList->push($name);
                    }
                }
            }
        }
        if ($coverageList->isNotEmpty()) {
            break;
        }
    }
    $coverageList = $coverageList->unique()->sort()->values();
    $coverageCount = $coverageList->count();

    // Real carrier networks (e.g. T-Mobile 5G, Verizon 5G) captured from the supplier
    // per operator. Deduped by carrier name. We deliberately do NOT fall back to
    // metadata.network — that field holds Airalo's operator brand name ("Change",
    // "Change+", "Eurolink", "Merhaba"…), which is product marketing, not a carrier.
    $carrierNetworks = $variants
        ->flatMap(fn ($v) => (array) ($v->metadata['networks_detail'] ?? []))
        ->filter(fn ($n) => is_array($n) && ! empty($n['name']))
        ->unique(fn ($n) => $n['name'])
        ->values();

    $anyTopUp = $plans->contains(fn ($p) => $p['top_up']);

    // Region picker — one entry per country (deduped across suppliers); regional and
    // global eSIMs stay per-product. Selecting one opens that country's merged page.
    $esimRegions = Cache::remember('esim-regions-dropdown-v2', now()->addMinutes(10), function () {
        $cat = Category::where('slug', 'esims')->first();

        return Product::query()
            ->where('is_active', true)
            ->when($cat, fn ($q) => $q->where('category_id', $cat->id))
            ->orderBy('name')
            ->get(['slug', 'name', 'country_code'])
            ->groupBy(function ($r) {
                $cc = strtoupper((string) $r->country_code);

                return (strlen($cc) === 2 && $cc !== 'WW') ? $cc : 'slug:'.$r->slug;
            })
            ->map(function ($group) {
                $r = $group->first();
                $cc = strtoupper((string) $r->country_code);
                $isLocal = strlen($cc) === 2 && $cc !== 'WW';

                return [
                    'key'  => $isLocal ? $cc : 'slug:'.$r->slug,
                    'slug' => $r->slug,
                    'name' => \App\Http\Controllers\EsimStoreController::regionMeta($r->country_code, $r->slug, $r->name)['name'],
                    'flag' => $isLocal ? Product::flagUrl($cc) : \App\Http\Controllers\EsimStoreController::globalFlag(),
                ];
            })
            ->sortBy('name')
            ->values();
    });

    // Estimated-price currency selector (admin-managed fiat + crypto rates).
    $currencyFlagOverrides = ['XAF' => 'CM', 'XOF' => 'SN', 'XCD' => 'AG'];
    $currencyFlag = fn (string $code) => Product::flagUrl($currencyFlagOverrides[strtoupper($code)] ?? substr(strtoupper($code), 0, 2));

    // Only true glyph symbols. Currencies without one (XAF, AED, KES, crypto…) show
    // their code instead — e.g. "8.50 XAF" rather than an abbreviation.
    $currencySymbols = [
        'USD' => '$', 'EUR' => '€', 'GBP' => '£', 'NGN' => '₦', 'ZAR' => 'R', 'GHS' => '₵',
        'JPY' => '¥', 'CNY' => '¥', 'INR' => '₹', 'TRY' => '₺', 'KRW' => '₩', 'THB' => '฿',
        'PHP' => '₱', 'VND' => '₫', 'BRL' => 'R$', 'AUD' => 'A$', 'CAD' => 'CA$', 'MXN' => 'MX$',
        'HKD' => 'HK$', 'SGD' => 'S$', 'TWD' => 'NT$',
    ];

    $cryptoRatesForJs = CurrencyRate::active()
        ->orderBy('type')->orderBy('sort_order')->orderBy('code')
        ->get(['code', 'name', 'type', 'rate_per_usd', 'icon_path'])
        ->map(fn ($r) => [
            'code'     => $r->code,
            'name'     => $r->name,
            'type'     => $r->type,
            'perUsd'   => (float) $r->rate_per_usd,
            'decimals' => $r->rate_per_usd < 0.01 ? 8 : ($r->rate_per_usd < 1 ? 4 : 2),
            'icon'     => ($r->type === 'crypto' && $r->icon_path) ? asset('assets/'.$r->icon_path) : null,
            'flag'     => $r->type === 'fiat' ? $currencyFlag($r->code) : null,
            'symbol'   => $r->type === 'fiat' ? ($currencySymbols[strtoupper($r->code)] ?? null) : null,
        ])
        ->values();

    $countryCurrency = strtoupper(config('country_currency.map.'.$cc, 'USD'));
    $defaultCrypto = $cryptoRatesForJs->firstWhere('code', $countryCurrency)
        ? $countryCurrency
        : ($cryptoRatesForJs->firstWhere('code', 'USD') ? 'USD' : ($cryptoRatesForJs->first()['code'] ?? 'USDT'));

    $compat = config('esim_compatibility', ['ios' => [], 'android' => []]);

    // Regional + global eSIMs for the "Need broader coverage?" section under the
    // plan selector — sourced from the shared catalog summary (deduped, cached).
    $broaderCoverage = \App\Http\Controllers\EsimStoreController::catalogSummary()
        ->whereIn('scope', ['regional', 'global'])
        ->sortBy('from')
        ->take(6)
        ->values();
@endphp

<x-layouts.app.header :title="$regionLabel . ' eSIM | RshopRefills'">

    {{-- translate="no": the page translator (Google) rewrites text nodes, which
         corrupts Alpine's reactive <template x-for> package list (it renders then
         vanishes). Excluding the interactive store keeps it stable; the rest of the
         site still translates. --}}
    <div
        x-data="esimStore({
            plans: @js($plans),
            cryptos: @js($cryptoRatesForJs),
            defaultCrypto: @js($defaultCrypto),
            rcoinConfig: @js([
                'cashback_percentage' => (float) \App\Models\Setting::get('cashback_percentage', 1.0),
                'usd_rate' => (float) \App\Models\Setting::get('rcoin_usd_rate', 0.005),
            ]),
            checkoutUrl: '{{ route('shop.checkout') }}',
        })"
        x-init="init()"
        translate="no"
        class="notranslate mx-auto w-full max-w-[1320px] px-4 pb-32 pt-6 sm:px-6 lg:pt-10"
    >

        {{-- ── Breadcrumb (aligned with the 800px cards) ────────────────────── --}}
        <nav class="mx-auto flex w-full max-w-[800px] flex-wrap items-center gap-1.5 text-sm text-zinc-500 dark:text-zinc-400" aria-label="Breadcrumb">
            <a href="{{ route('shop.esims') }}" wire:navigate class="font-medium transition-colors hover:text-zinc-900 dark:hover:text-white">eSIM Store</a>
            <span aria-hidden="true">&rsaquo;</span>
            <a href="{{ route('shop.esims', ['scope' => strtolower($curScope)]) }}" wire:navigate class="transition-colors hover:text-zinc-900 dark:hover:text-white">{{ $curScope }} eSIMs</a>
            <span aria-hidden="true">&rsaquo;</span>
            <span class="font-semibold text-zinc-900 dark:text-white">{{ $regionLabel }}</span>
        </nav>

        {{-- ── Country header (glass card, 900px centered) ──────────────────── --}}
        {{-- z-20: backdrop-blur creates a stacking context — without lifting it
             above the plan selector (also a blur context, later in DOM), the
             region picker dropdown gets clipped behind the plans card. --}}
        <div class="relative z-20 mx-auto mt-4 w-full max-w-[800px] rounded-3xl bg-white/60 p-6 ring-1 ring-white/60 shadow-xl shadow-zinc-900/5 backdrop-blur-xl sm:p-8 dark:bg-[#1d3252] dark:ring-zinc-800/60 dark:shadow-black/40">
            <div class="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
                <div class="min-w-0">
                    <div class="flex items-center gap-3">
                        @if ($flag)
                            <img src="{{ $flag }}" alt="" class="h-9 w-12 shrink-0 rounded-[4px] object-cover ring-1 ring-zinc-200 dark:ring-zinc-700">
                        @else
                            <span class="flex h-9 w-12 shrink-0 items-center justify-center rounded-[4px] bg-blue-50 ring-1 ring-zinc-200 dark:bg-blue-950/40 dark:ring-zinc-700">
                                <svg class="h-5 w-5 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9 9 0 100-18 9 9 0 000 18zm0 0c2.5 0 4.5-4.03 4.5-9S14.5 3 12 3 7.5 7.03 7.5 12s2 9 4.5 9zM3.6 9h16.8M3.6 15h16.8"/>
                                </svg>
                            </span>
                        @endif
                        <h1 class="truncate text-2xl font-bold tracking-tight text-zinc-900 sm:text-3xl dark:text-white">{{ $regionLabel }} eSIMs</h1>
                    </div>

                    <div class="my-5 h-px bg-zinc-100 dark:bg-zinc-700/60"></div>

                    {{-- Supported networks (real provider data) --}}
                    <div class="flex flex-wrap items-center gap-3">
                        @if ($coverageCount > 1)
                            <button type="button" @click="showNetworks = true" class="inline-flex items-center gap-2 text-sm font-semibold text-zinc-900 underline-offset-2 hover:underline dark:text-white">
                                <svg class="h-4 w-4 text-zinc-500 dark:text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9 9 0 100-18 9 9 0 000 18zm0 0c2.5 0 4.5-4.03 4.5-9S14.5 3 12 3 7.5 7.03 7.5 12s2 9 4.5 9zM3.6 9h16.8M3.6 15h16.8"/>
                                </svg>
                                {{ $coverageCount }} Countries and Networks
                            </button>
                        @elseif ($carrierNetworks->isNotEmpty())
                            <span class="inline-flex items-center gap-2 text-sm font-semibold text-zinc-900 dark:text-white">
                                <img src="{{ asset('assets/'.rawurlencode('Network Svg.svg')) }}" alt="" class="h-4 w-4 dark:invert" aria-hidden="true">
                                {{ $carrierNetworks->first()['name'] }}
                            </span>
                            <button type="button" @click="showNetworks = true" class="text-sm font-semibold text-blue-600 transition-colors hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300">Networks</button>
                        @endif
                    </div>

                    {{-- Check compatibility --}}
                    <div class="mt-4">
                        <button type="button" @click="showCompat = true" class="inline-flex items-center gap-2 rounded-full bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-blue-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/40">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3"/>
                            </svg>
                            Check compatibility
                        </button>
                    </div>

                    {{-- Reassurance notes --}}
                    <ul class="mt-5 space-y-2 text-sm text-zinc-600 dark:text-zinc-400">
                        @if ($anyTopUp)
                            <li class="flex items-center gap-2">
                                <svg class="h-4 w-4 shrink-0 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                                If you're running low, you can always&nbsp;<a href="{{ route('shop.topups') }}" wire:navigate class="font-semibold text-blue-600 underline-offset-2 transition-colors hover:text-blue-700 hover:underline dark:text-blue-400 dark:hover:text-blue-300">top up</a>
                            </li>
                        @endif
                        <li class="flex items-center gap-2">
                            <svg class="h-4 w-4 shrink-0 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                            The package starts when you connect to a supported network
                        </li>
                    </ul>
                </div>

                {{-- Region switcher --}}
                <div class="w-full shrink-0 sm:w-64">
                    <label class="mb-1.5 block text-xs font-semibold text-zinc-900 dark:text-zinc-200">Change country or region</label>
                    <div x-data="{ open: false, search: '' }" @click.outside="open = false; search = ''" class="relative">
                        <button
                            type="button"
                            @click="open = ! open; if (open) $nextTick(() => $refs.regionSearch?.focus())"
                            :class="open ? 'border-blue-500 ring-2 ring-blue-500/15' : 'border-zinc-300 hover:border-zinc-400 dark:border-zinc-700 dark:hover:border-zinc-600'"
                            class="flex h-[46px] w-full items-center gap-2 rounded-xl border bg-white px-3 text-sm font-medium text-zinc-900 outline-none transition-colors dark:bg-[#26416b] dark:text-white"
                        >
                            @if ($flag)
                                <img src="{{ $flag }}" alt="" class="h-3.5 w-5 shrink-0 rounded-[2px] object-cover ring-1 ring-zinc-200 dark:ring-zinc-700">
                            @endif
                            <span class="flex-1 truncate text-left">{{ $regionLabel }}</span>
                            <svg class="h-4 w-4 shrink-0 text-zinc-600 transition-transform duration-150 dark:text-zinc-400" :class="{ 'rotate-180': open }" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div
                            x-show="open"
                            x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
                            style="display:none;"
                            class="absolute right-0 left-0 top-full z-50 mt-2 overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-xl shadow-zinc-900/10 dark:border-zinc-700 dark:bg-[#1d3252] dark:shadow-black/40"
                            role="listbox"
                        >
                            <div class="border-b border-zinc-100 p-2 dark:border-zinc-700">
                                <input x-ref="regionSearch" x-model="search" type="text" placeholder="Search a country or region" aria-label="Search a country or region" class="w-full rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2 text-sm text-zinc-800 placeholder:text-zinc-500 outline-none transition-colors focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/15 dark:border-zinc-700 dark:bg-[#26416b] dark:text-zinc-100 dark:placeholder:text-zinc-500 dark:focus:bg-zinc-900">
                            </div>
                            <div class="max-h-72 overflow-y-auto p-1">
                                @foreach ($esimRegions as $r)
                                    <a
                                        href="{{ route('shop.esim', $r['slug']) }}"
                                        wire:navigate
                                        data-name="{{ Str::lower($r['name']) }}"
                                        x-show="search === '' || $el.dataset.name.includes(search.toLowerCase())"
                                        @class([
                                            'flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-left text-sm font-medium transition-colors',
                                            'bg-blue-50 text-blue-700 dark:bg-blue-600/15 dark:text-blue-300' => $r['key'] === $currentKey,
                                            'text-zinc-700 hover:bg-zinc-50 dark:text-zinc-300 dark:hover:bg-zinc-800' => $r['key'] !== $currentKey,
                                        ])
                                    >
                                        @if ($r['flag'])
                                            <img src="{{ $r['flag'] }}" alt="" class="h-3.5 w-5 shrink-0 rounded-[2px] object-cover ring-1 ring-zinc-200 dark:ring-zinc-700" loading="lazy">
                                        @else
                                            <span class="h-3.5 w-5 shrink-0 rounded-[2px] bg-zinc-100 ring-1 ring-zinc-200 dark:bg-[#26416b] dark:ring-zinc-700"></span>
                                        @endif
                                        <span class="flex-1 truncate">{{ $r['name'] }}</span>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if ($hasPlans)
            {{-- ── Plan selector (glass card, 900px centered; rows stay white) ──── --}}
            <div id="esim-packages" class="mx-auto mt-6 w-full max-w-[800px] scroll-mt-24 overflow-hidden rounded-3xl bg-white/60 ring-1 ring-white/60 shadow-xl shadow-zinc-900/5 backdrop-blur-xl dark:bg-[#1d3252] dark:ring-zinc-800/60 dark:shadow-black/40">
                {{-- Tabs: Data / Data·Calls·Texts --}}
                <div class="grid grid-cols-2 border-b border-zinc-200 dark:border-zinc-700/60">
                    <button
                        type="button"
                        x-show="tabHasPlans('data')"
                        @click="setTab('data')"
                        :class="activeTab === 'data' ? 'border-blue-600 text-zinc-900 dark:text-white' : 'border-transparent text-zinc-500 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white'"
                        class="border-b-2 px-4 py-4 text-sm font-bold transition-colors"
                    >Data</button>
                    <button
                        type="button"
                        x-show="tabHasPlans('voice')"
                        @click="setTab('voice')"
                        :class="activeTab === 'voice' ? 'border-blue-600 text-zinc-900 dark:text-white' : 'border-transparent text-zinc-500 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white'"
                        class="border-b-2 px-4 py-4 text-sm font-bold transition-colors"
                    >Data / Calls / Texts</button>
                </div>

                <div class="p-5 sm:p-6">
                    {{-- Standard / Unlimited toggle --}}
                    <div x-show="showModeToggle()" class="mb-5 grid grid-cols-2 gap-1 rounded-[10px] bg-zinc-100 p-1 dark:bg-[#26416b]" role="tablist">
                        <button type="button" @click="setMode('standard')" :class="dataMode === 'standard' ? 'bg-blue-600 text-white shadow-sm' : 'text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white'" class="rounded-[10px] px-4 py-2 text-sm font-semibold transition-colors">Standard</button>
                        <button type="button" @click="setMode('unlimited')" :class="dataMode === 'unlimited' ? 'bg-blue-600 text-white shadow-sm' : 'text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white'" class="rounded-[10px] px-4 py-2 text-sm font-semibold transition-colors">Unlimited</button>
                    </div>

                    <h2 class="text-lg font-bold text-zinc-900 dark:text-white">Choose your package</h2>

                    {{-- Duration-grouped package rows --}}
                    <div class="mt-4 space-y-6">
                        <template x-for="group in groups()" :key="group.days">
                            <div>
                                <p class="mb-2 text-sm font-bold text-zinc-900 dark:text-white" x-text="group.label"></p>
                                <div class="space-y-2.5">
                                    <template x-for="p in group.items" :key="p.id">
                                        <button
                                            type="button"
                                            @click="selectedId = p.id"
                                            :class="selectedId === p.id ? 'border-blue-600 ring-1 ring-blue-500/20' : 'border-transparent ring-1 ring-zinc-200 hover:ring-zinc-300 dark:ring-zinc-700 dark:hover:ring-zinc-600'"
                                            class="flex w-full items-center justify-between gap-4 rounded-[10px] border-2 bg-white px-4 py-3.5 text-left transition-all focus:outline-none dark:bg-[#1d3252]"
                                        >
                                            <div class="min-w-0">
                                                {{-- Figures (black, font-medium 500) sit next to their unit (zinc-800). --}}
                                                <p class="flex flex-wrap items-baseline gap-x-2 text-base">
                                                    <span>
                                                        <span class="font-medium text-black dark:text-white" x-text="p.data_num"></span>
                                                        <template x-if="p.data_unit">
                                                            <span class="text-zinc-800 dark:text-zinc-300" x-text="' ' + p.data_unit"></span>
                                                        </template>
                                                    </span>
                                                    <template x-if="p.voice">
                                                        <span class="text-sm">
                                                            <span class="text-zinc-300 dark:text-zinc-600">&middot;</span>
                                                            <span class="font-medium text-black dark:text-white" x-text="p.voice_num"></span>
                                                            <span class="text-zinc-800 dark:text-zinc-300" x-text="' ' + p.voice_unit"></span>
                                                        </span>
                                                    </template>
                                                    <template x-if="p.sms">
                                                        <span class="text-sm">
                                                            <span class="text-zinc-300 dark:text-zinc-600">&middot;</span>
                                                            <span class="font-medium text-black dark:text-white" x-text="p.sms_num"></span>
                                                            <span class="text-zinc-800 dark:text-zinc-300" x-text="' ' + p.sms_unit"></span>
                                                        </span>
                                                    </template>
                                                </p>
                                                <template x-if="p.note">
                                                    <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400" x-text="p.note"></p>
                                                </template>
                                            </div>
                                            <span class="shrink-0 text-base font-bold tabular-nums text-zinc-900 dark:text-white" x-text="rowPrice(p)"></span>
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </template>

                        <p x-show="groups().length === 0" class="rounded-[10px] bg-zinc-50 px-4 py-8 text-center text-sm text-zinc-600 ring-1 ring-zinc-100 dark:bg-[#1d3252] dark:text-zinc-400 dark:ring-zinc-700">No packages in this category right now.</p>
                    </div>

                    {{-- Need broader coverage — sits inside the same card, beneath the plans. --}}
                    @if ($broaderCoverage->isNotEmpty())
                        <div class="mt-8 border-t border-zinc-200 pt-6 dark:border-zinc-700/60">
                            <h2 class="text-lg font-bold text-zinc-900 dark:text-white">Need broader coverage?</h2>
                            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Explore our regional and global eSIMs. Packages start from the shown price and include coverage for the selected location.</p>
                            <div class="mt-4 space-y-3">
                                @foreach ($broaderCoverage as $b)
                                    <a href="{{ route('shop.esim', $b['slug']) }}" wire:navigate class="group flex items-center gap-4 rounded-[10px] bg-white px-4 py-3.5 ring-1 ring-zinc-200 shadow-sm transition-all hover:-translate-y-0.5 hover:shadow-md hover:ring-zinc-300 dark:bg-[#1d3252] dark:ring-zinc-700 dark:hover:ring-zinc-600 dark:hover:shadow-black/40">
                                        <span class="flex h-10 w-12 shrink-0 items-center justify-center overflow-hidden rounded-[5px] bg-blue-50 ring-1 ring-zinc-200 dark:bg-blue-950/40 dark:ring-zinc-700">
                                            @if ($b['flag'])
                                                <img src="{{ $b['flag'] }}" alt="" class="h-full w-full object-cover" loading="lazy">
                                            @else
                                                <svg class="h-5 w-5 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9 9 0 100-18 9 9 0 000 18zm0 0c2.5 0 4.5-4.03 4.5-9S14.5 3 12 3 7.5 7.03 7.5 12s2 9 4.5 9zM3.6 9h16.8M3.6 15h16.8"/></svg>
                                            @endif
                                        </span>
                                        <div class="min-w-0 flex-1">
                                            <p class="truncate text-base font-bold text-zinc-900 group-hover:text-blue-700 dark:text-white dark:group-hover:text-blue-300">{{ $b['name'] }}</p>
                                            <p class="text-sm font-bold tabular-nums text-zinc-900 dark:text-zinc-300">${{ number_format($b['from'], 2) }}</p>
                                        </div>
                                        <svg class="h-5 w-5 shrink-0 text-zinc-400 dark:text-zinc-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- ── Why choose us (1000px centered) ───────────────────────────── --}}
            <section class="mx-auto mt-8 w-full max-w-[1000px] rounded-3xl bg-blue-100 p-6 sm:p-10 dark:bg-[#1d3252]">
                <h2 class="text-center text-2xl font-bold tracking-tight text-zinc-900 sm:text-3xl dark:text-white">Why travelers choose RshopRefills eSIMs</h2>
                <div class="mt-8 grid grid-cols-2 gap-6 lg:grid-cols-4">
                    @foreach ([
                        ['t' => 'Local, regional & global coverage for 200+ destinations', 'd' => 'M12 21a9 9 0 100-18 9 9 0 000 18zm0 0c2.5 0 4.5-4.03 4.5-9S14.5 3 12 3 7.5 7.03 7.5 12s2 9 4.5 9zM3.6 9h16.8M3.6 15h16.8'],
                        ['t' => 'Flexible packages, including unlimited data options', 'd' => 'M3 7.5L7.5 3m0 0L12 7.5M7.5 3v13.5m13.5 0L16.5 21m0 0L12 16.5m4.5 4.5V7.5'],
                        ['t' => 'Pay in your currency or crypto, translated into 80+ languages', 'image' => 'total transactions.svg'],
                        ['t' => 'Easy install and set up to get connected in minutes', 'd' => 'M3.75 4.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75v3a.75.75 0 01-.75.75h-3a.75.75 0 01-.75-.75v-3zm12 0a.75.75 0 01.75-.75h3a.75.75 0 01.75.75v3a.75.75 0 01-.75.75h-3a.75.75 0 01-.75-.75v-3zm-12 12a.75.75 0 01.75-.75h3a.75.75 0 01.75.75v3a.75.75 0 01-.75.75h-3a.75.75 0 01-.75-.75v-3zM15 15.75h.008v.008H15v-.008zm0 3h.008v.008H15v-.008zm3-3h.008v.008H18v-.008zm0 3h.008v.008H18v-.008zm3-3h.008v.008H21v-.008zm0 3h.008v.008H21v-.008z'],
                    ] as $feature)
                        <div class="flex flex-col items-center text-center">
                            <span class="flex h-16 w-16 items-center justify-center rounded-full bg-white shadow-sm dark:bg-[#26416b]">
                                @if (! empty($feature['image']))
                                    <img src="{{ asset('assets/'.rawurlencode($feature['image'])) }}" alt="" class="h-7 w-7 object-contain dark:invert" aria-hidden="true">
                                @else
                                    <svg class="h-7 w-7 text-black dark:text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $feature['d'] }}"/></svg>
                                @endif
                            </span>
                            <p class="mt-3 text-sm font-semibold leading-snug text-zinc-700 dark:text-zinc-300">{{ $feature['t'] }}</p>
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- ── FAQ (glass card, 800px centered) ─────────────────────────── --}}
            <section class="mx-auto mt-8 w-full max-w-[800px] rounded-3xl bg-white/60 p-6 ring-1 ring-white/60 shadow-xl shadow-zinc-900/5 backdrop-blur-xl sm:p-8 dark:bg-[#1d3252] dark:ring-zinc-800/60 dark:shadow-black/40">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h2 class="text-lg font-bold text-zinc-900 dark:text-white">Frequently asked questions</h2>
                    <a href="{{ route('shop.help') }}" wire:navigate class="inline-flex items-center gap-1.5 rounded-full px-4 py-2 text-sm font-semibold text-zinc-900 ring-1 ring-zinc-300 transition-colors hover:bg-zinc-50 dark:text-white dark:ring-white dark:hover:bg-[#26416b]">Go to help center</a>
                </div>
                <div class="mt-5 space-y-2.5">
                    @foreach ([
                        ['q' => 'When should I install my eSIM?', 'a' => 'You can install your eSIM as soon as you receive the QR code by email. Most plans only start counting down once you arrive and connect to a supported network, so installing early is safe.'],
                        ['q' => 'How do I use my eSIM?', 'a' => 'Open your phone settings, add a cellular/mobile plan, and scan the QR code we email you. Turn on data roaming for the eSIM line and you are connected.'],
                        ['q' => 'Can I reuse my eSIM?', 'a' => 'If your plan supports top-ups you can add more data to the same eSIM. Otherwise, buy a fresh package whenever you travel again.'],
                        ['q' => 'What are renewals?', 'a' => 'Some packages can be renewed or topped up before they expire so you stay connected without installing a new eSIM.'],
                    ] as $faq)
                        <details class="group rounded-[10px] bg-zinc-50 px-4 ring-1 ring-zinc-100 dark:bg-[#1d3252] dark:ring-zinc-700/60">
                            <summary class="flex cursor-pointer items-center justify-between py-4 text-sm font-semibold text-zinc-900 marker:content-[''] dark:text-white">
                                {{ $faq['q'] }}
                                <svg class="h-5 w-5 shrink-0 text-zinc-500 transition-transform group-open:rotate-180 dark:text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                            </summary>
                            <p class="pb-4 text-sm leading-relaxed text-zinc-600 dark:text-zinc-300">{{ $faq['a'] }}</p>
                        </details>
                    @endforeach
                </div>
                <div class="mt-6 flex flex-wrap items-center justify-between gap-3 border-t border-zinc-100 pt-5 dark:border-zinc-700/60">
                    <div>
                        <p class="text-sm font-bold text-zinc-900 dark:text-white">Support</p>
                        <p class="text-sm text-zinc-600 dark:text-zinc-400">Need help? We offer 24/7, multi-language support.</p>
                    </div>
                    <a href="{{ route('shop.contact') }}" wire:navigate class="inline-flex items-center gap-1.5 rounded-full px-4 py-2 text-sm font-semibold text-zinc-900 ring-1 ring-zinc-300 transition-colors hover:bg-zinc-50 dark:text-white dark:ring-white dark:hover:bg-[#26416b]">Contact support</a>
                </div>
            </section>

            {{-- ── How to add your eSIM (three-step walkthrough) ─────────────── --}}
            @php
                $howSteps = [
                    [
                        'image' => 'esim-how-to-1.webp',
                        'title' => 'Open Settings &rarr; Mobile Service',
                        'body'  => 'Unlock your phone and tap Mobile Service (or Cellular on older iPhones). This is where every line on your device already lives.',
                    ],
                    [
                        'image' => 'esim-how-to-2.webp',
                        'title' => 'Tap Add eSIM and scan your QR code',
                        'body'  => 'We email the QR code seconds after checkout. Point your camera at it, or tap Enter Details Manually to paste the code instead.',
                    ],
                    [
                        'image' => 'esim-how-to-3.webp',
                        'title' => 'Turn it on, switch on Data Roaming',
                        'body'  => 'Flip Turn On This Line, then enable Data Roaming for the new line. The moment you land, your phone connects at local rates.',
                    ],
                ];
            @endphp
            {{-- Full-bleed bg: the negative-margin calc extends the section past the
                 parent's max-w-[1320px] + horizontal padding so the surface fills the
                 viewport, while the inner column stays constrained to 1450px. --}}
            <section class="mt-8 bg-gradient-to-r from-[#dfe5f1] via-[#dfe5f1] to-white py-10 sm:py-14 dark:from-[#1d3252] dark:via-[#1d3252] dark:to-[#34507a] [margin-left:calc(50%-50vw)] [margin-right:calc(50%-50vw)]">
                <div class="mx-auto w-full max-w-[1450px] px-4 sm:px-6">
                <div>
                    <h2 class="text-2xl font-bold tracking-tight text-zinc-900 sm:text-3xl dark:text-white">How to add your eSIM</h2>
                    <p class="mt-2 max-w-xl text-sm leading-relaxed text-zinc-600 sm:text-base dark:text-zinc-300">Three quick steps and you are roaming on local rates. No SIM tray, no queues at the airport kiosk.</p>
                </div>

                <ol class="mt-8 grid grid-cols-1 gap-8 lg:grid-cols-3">
                    @foreach ($howSteps as $i => $step)
                        <li class="flex flex-col">
                            <img
                                src="{{ asset('assets/'.$step['image']) }}"
                                alt=""
                                width="785"
                                height="900"
                                decoding="async"
                                loading="lazy"
                                class="aspect-[3/4] w-full object-contain [image-rendering:high-quality] [image-rendering:-webkit-optimize-contrast]"
                            >
                            <div class="mt-4 flex items-center gap-3">
                                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-blue-600 text-xs font-bold text-white">{{ $i + 1 }}</span>
                                <p class="text-base font-bold text-zinc-900 dark:text-white">{!! $step['title'] !!}</p>
                            </div>
                            <p class="mt-2 text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">{{ $step['body'] }}</p>
                        </li>
                    @endforeach
                </ol>

                <div class="mt-8 flex flex-col items-center gap-3 sm:flex-row sm:justify-center">
                    <a href="#esim-packages" class="inline-flex items-center gap-2 rounded-full bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-blue-700">
                        Choose your package
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                    </a>
                    <a href="{{ route('shop.help') }}" wire:navigate class="text-sm font-semibold text-zinc-700 underline-offset-2 transition-colors hover:text-blue-600 hover:underline dark:text-zinc-300 dark:hover:text-blue-300">Still stuck? Read the full guide</a>
                </div>
                </div>
            </section>

            {{-- ── Anonymous eSIM with crypto (no KYC) ──────────────────────── --}}
            <section class="mx-auto mt-12 grid w-full max-w-[1450px] grid-cols-1 items-start gap-10 lg:grid-cols-2 lg:gap-16">
                <div>
                    <span class="flex h-12 w-12 items-center justify-center rounded-full bg-blue-50 ring-1 ring-blue-100 dark:bg-blue-950/40 dark:ring-blue-900/40">
                        <svg class="h-6 w-6 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 01-1.043 3.296 3.745 3.745 0 01-3.296 1.043A3.745 3.745 0 0112 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 01-3.296-1.043 3.745 3.745 0 01-1.043-3.296A3.745 3.745 0 013 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 011.043-3.296 3.746 3.746 0 013.296-1.043A3.746 3.746 0 0112 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 013.296 1.043 3.746 3.746 0 011.043 3.296A3.745 3.745 0 0121 12z"/></svg>
                    </span>
                    <h2 class="mt-4 text-2xl font-bold tracking-tight text-zinc-900 sm:text-3xl dark:text-white">Anonymous eSIM with MOMO, Cards, Crypto and more. No KYC required.</h2>
                    <p class="mt-3 max-w-xl text-sm leading-relaxed text-zinc-600 sm:text-base dark:text-zinc-300">Unlike traditional SIM cards that ask for a passport and personal details, your RshopRefills eSIM can be bought with mobile money, cards, crypto and more. No identity verification, no personal data collected.</p>
                </div>

                <ul class="space-y-5">
                    @foreach ([
                        ['t' => 'No KYC required', 'b' => 'Buy instantly without ID verification or personal information.'],
                        ['t' => 'Pay your way', 'b' => 'Mobile money, cards, Apple Pay, bank transfer or crypto (Bitcoin, Ethereum, Tether, BNB, Solana, Litecoin). Your call.'],
                        ['t' => 'Instant delivery', 'b' => 'Receive your eSIM QR code in your inbox the moment your payment confirms.'],
                    ] as $row)
                        <li class="flex items-start gap-3">
                            <svg class="mt-0.5 h-5 w-5 shrink-0 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 12.75l2.25 2.25L15.75 9.75"/></svg>
                            <div>
                                <p class="text-base font-bold text-zinc-900 dark:text-white">{{ $row['t'] }}</p>
                                <p class="mt-1 text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">{{ $row['b'] }}</p>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </section>

            {{-- ── Payment methods we accept ──────────────────────────────── --}}
            @php
                // 'mono' => true → icon flattens to black in light mode, white in dark
                // mode (brightness-0 dark:invert). Used for the blue line-art icons.
                $payMethods = [
                    ['name' => 'Mobile Money', 'icon' => 'MOMO.svg',                'kind' => 'fiat',   'mono' => true],
                    ['name' => 'Card',         'icon' => 'credit card payment.png', 'kind' => 'fiat',   'mono' => true],
                    ['name' => 'Apple Pay',    'icon' => 'apply pay.png',           'kind' => 'fiat',   'mono' => true],
                    ['name' => 'Bank',         'icon' => 'Bank transfer.png',       'kind' => 'fiat',   'mono' => true],
                    ['name' => 'Bitcoin',      'icon' => 'BTC.svg',                 'kind' => 'crypto'],
                    ['name' => 'Ethereum',     'icon' => 'ETH.svg',                 'kind' => 'crypto'],
                    ['name' => 'Tether',       'icon' => 'USDT.svg',                'kind' => 'crypto'],
                    ['name' => 'Solana',       'icon' => 'SOLANA.svg',              'kind' => 'crypto'],
                    ['name' => 'BNB',          'icon' => 'BNB.png',                 'kind' => 'crypto'],
                    ['name' => 'Litecoin',     'icon' => 'LTC.png',                 'kind' => 'crypto'],
                ];
            @endphp
            <section class="mx-auto mt-12 w-full max-w-[1450px]">
                <h2 class="text-2xl font-bold tracking-tight text-zinc-900 sm:text-3xl dark:text-white">Payment methods we accept</h2>
                <p class="mt-2 max-w-xl text-sm leading-relaxed text-zinc-600 sm:text-base dark:text-zinc-300">Pay with crypto or your local card. Settle in seconds, anywhere in the world.</p>

                <div class="mt-6 grid grid-cols-3 gap-x-4 gap-y-5 sm:grid-cols-5 lg:grid-cols-10">
                    @foreach ($payMethods as $m)
                        <div class="flex flex-col items-center gap-2 text-center">
                            <img
                                src="{{ asset('assets/'.rawurlencode($m['icon'])) }}"
                                alt=""
                                @class([
                                    'h-10 w-10 shrink-0 rounded-[10px] object-contain',
                                    'brightness-0 dark:invert' => ! empty($m['mono']),
                                ])
                                loading="lazy"
                            >
                            <span class="text-xs font-semibold text-zinc-900 dark:text-white">{{ $m['name'] }}</span>
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- ── Sticky buy bar ───────────────────────────────────────────── --}}
            {{-- position:fixed + the parent's permanent pb-32 means the bar overlays
                 content without ever reflowing the page. The floating X chip in the
                 middle clears selectedId, which hides the bar via x-show. --}}
            <div x-show="selectedId" x-transition.opacity style="display:none;" class="fixed inset-x-0 bottom-0 z-40 border-t border-zinc-200 bg-white/95 backdrop-blur-md shadow-[0_-8px_24px_-12px_rgba(0,0,0,0.18)] dark:border-zinc-700/60 dark:bg-[#1d3252]/95">
                {{-- Centered close chip: deselects the current plan, which collapses
                     the bar. Sits half-out-of-bar so the X is visible without crowding
                     the buy controls. --}}
                <button
                    type="button"
                    @click="selectedId = null"
                    aria-label="Deselect plan"
                    class="absolute left-1/2 top-0 z-10 flex h-8 w-8 -translate-x-1/2 -translate-y-1/2 items-center justify-center rounded-full bg-white text-zinc-700 ring-1 ring-zinc-200 shadow-md transition-colors hover:bg-zinc-50 hover:text-zinc-900 dark:bg-[#26416b] dark:text-white dark:ring-zinc-700/60 dark:hover:bg-[#34507a]"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.25" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>

                <div class="mx-auto flex w-full max-w-[800px] flex-wrap items-center gap-3 px-4 py-3 sm:px-6">
                    <button type="button" @click="showDetails = true" class="inline-flex items-center gap-2 rounded-full px-4 py-2 text-sm font-semibold text-zinc-900 ring-1 ring-zinc-300 transition-colors hover:bg-zinc-50 dark:text-white dark:ring-white dark:hover:bg-[#26416b]">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
                        Package details
                    </button>

                    {{-- Currency selector --}}
                    <div x-data="{ open: false }" @click.outside="open = false" class="relative">
                        <button type="button" @click="open = ! open" class="flex items-center gap-1.5 rounded-full px-3 py-2 text-sm font-semibold text-zinc-700 ring-1 ring-zinc-300 transition-colors hover:bg-zinc-50 dark:text-white dark:ring-white dark:hover:bg-[#26416b]">
                            <template x-if="cryptos[selectedCrypto]?.icon"><img :src="cryptos[selectedCrypto].icon" :alt="selectedCrypto" class="h-4 w-4 rounded-full"></template>
                            <template x-if="!cryptos[selectedCrypto]?.icon && cryptos[selectedCrypto]?.flag"><img :src="cryptos[selectedCrypto].flag" :alt="selectedCrypto" class="h-4 w-4 rounded-full object-cover"></template>
                            <span x-text="selectedCrypto"></span>
                            <svg class="h-3.5 w-3.5 text-zinc-500 dark:text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="open" x-transition style="display:none;" class="absolute bottom-full left-0 z-20 mb-2 max-h-72 w-56 overflow-y-auto rounded-xl border border-zinc-200 bg-white p-1 shadow-xl shadow-zinc-900/10 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden dark:border-zinc-700 dark:bg-[#1d3252] dark:shadow-black/40" role="listbox">
                            <template x-for="(meta, code) in cryptos" :key="code">
                                <button type="button" @click="selectedCrypto = code; open = false" :class="selectedCrypto === code ? 'bg-blue-50 text-blue-700 dark:bg-blue-600/15 dark:text-blue-300' : 'text-zinc-800 hover:bg-zinc-50 dark:text-zinc-200 dark:hover:bg-[#26416b]'" class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm font-medium transition-colors">
                                    <template x-if="meta.icon"><img :src="meta.icon" :alt="code" class="h-5 w-5 shrink-0 rounded-full"></template>
                                    <template x-if="!meta.icon && meta.flag"><img :src="meta.flag" :alt="code" class="h-5 w-5 shrink-0 rounded-full object-cover ring-1 ring-zinc-200"></template>
                                    <template x-if="!meta.icon && !meta.flag"><span class="inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full text-[10px] font-black text-white" :class="meta.type === 'crypto' ? 'bg-amber-500' : 'bg-emerald-500'" x-text="code.charAt(0)"></span></template>
                                    <span class="flex-1 truncate" x-text="code"></span>
                                    <svg x-show="selectedCrypto === code" class="h-4 w-4 shrink-0 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                                </button>
                            </template>
                        </div>
                    </div>

                    <div class="ml-auto flex items-center gap-4">
                        <div class="text-right">
                            <p class="text-[11px] font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Total</p>
                            <p class="text-lg font-extrabold tabular-nums text-zinc-900 dark:text-white" x-text="totalLabel()">0.00</p>
                        </div>
                        <span class="hidden items-center gap-1 text-xs font-semibold text-zinc-600 sm:flex dark:text-zinc-400">
                            <img src="{{ asset('assets/favicon.ico') }}" alt="" class="h-5 w-5 object-contain">
                            <span x-text="pointsEarned()">0</span>
                        </span>
                        <button
                            type="button"
                            @click="addToCart()"
                            :disabled="! selectedId"
                            :class="cartState === 'success' ? 'border-emerald-500 bg-emerald-500 text-white' : 'border-blue-600 bg-white text-blue-600 hover:bg-blue-600 hover:text-white dark:bg-[#1d3252] dark:text-blue-300 dark:hover:text-white'"
                            class="hidden h-11 items-center justify-center rounded-full border-2 px-5 text-sm font-semibold transition-colors disabled:opacity-50 sm:flex"
                        >
                            <span x-show="cartState !== 'success'">Add to cart</span>
                            <span x-show="cartState === 'success'" style="display:none;">Added</span>
                        </button>
                        <button
                            type="button"
                            @click="buyNow()"
                            :disabled="! selectedId || $store.cart.loading"
                            class="flex h-11 items-center justify-center rounded-full bg-blue-600 px-6 text-sm font-semibold text-white transition-colors hover:bg-blue-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/40 disabled:opacity-50 disabled:hover:bg-blue-600"
                        >
                            Buy now
                        </button>
                    </div>
                </div>
            </div>
        @else
            <div class="mt-8 rounded-2xl bg-zinc-50 px-4 py-10 text-center ring-1 ring-zinc-100 dark:bg-[#1d3252] dark:ring-zinc-700/60">
                <p class="text-base font-semibold text-zinc-900 dark:text-white">No data plans available</p>
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">This region has no plans in stock right now. Check back later.</p>
                <a href="{{ route('shop.esims') }}" wire:navigate class="mt-4 inline-flex items-center gap-1.5 text-sm font-semibold text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300">Browse other regions</a>
            </div>
        @endif

        {{-- ════ Modals ═══════════════════════════════════════════════════════ --}}

        {{-- Networks modal --}}
        <div x-show="showNetworks" style="display:none;" class="fixed inset-0 z-[70] flex items-end justify-center p-0 sm:items-center sm:p-4" role="dialog" aria-modal="true" aria-labelledby="networks-title">
            <div x-show="showNetworks" @click="showNetworks = false" x-transition.opacity class="absolute inset-0 bg-zinc-900/40 dark:bg-black/60"></div>
            <div x-show="showNetworks" x-transition class="relative w-full max-w-lg rounded-t-3xl bg-white p-6 shadow-2xl sm:rounded-3xl dark:bg-[#1d3252] dark:ring-1 dark:ring-zinc-700/60">
                <div class="flex items-start justify-between gap-4">
                    <h2 id="networks-title" class="text-lg font-bold text-zinc-900 dark:text-white">{{ $coverageCount > 1 ? 'Countries & Networks' : 'Networks' }}</h2>
                    <button type="button" @click="showNetworks = false" aria-label="Close" class="flex h-9 w-9 items-center justify-center rounded-full bg-zinc-100 text-zinc-600 transition-colors hover:bg-zinc-200 dark:bg-[#26416b] dark:text-zinc-200 dark:hover:bg-[#34507a]"><svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>
                </div>
                @if ($coverageCount > 1)
                    <p class="mt-3 text-sm leading-relaxed text-zinc-600 dark:text-zinc-300">This eSIM works across {{ $coverageCount }} countries and connects to a local network automatically in each.</p>
                    <div class="mt-4 max-h-72 overflow-y-auto pr-1">
                        <ul class="grid grid-cols-2 gap-x-4 gap-y-1.5 text-sm text-zinc-700 sm:grid-cols-3 dark:text-zinc-300">
                            @foreach ($coverageList as $c)
                                <li class="truncate">{{ $c }}</li>
                            @endforeach
                        </ul>
                    </div>
                @else
                    <p class="mt-3 text-sm leading-relaxed text-zinc-600 dark:text-zinc-300">This eSIM may switch between the following networks based on availability and speed. You can adjust this in your device settings.</p>
                    <ul class="mt-4 space-y-2.5">
                        @forelse ($carrierNetworks as $net)
                            <li class="flex items-center gap-2.5 text-sm font-semibold text-zinc-900 dark:text-white">
                                <img src="{{ asset('assets/'.rawurlencode('Network Svg.svg')) }}" alt="" class="h-4 w-4 dark:invert" aria-hidden="true">
                                {{ $net['name'] }}
                                @if (! empty($net['speed']))
                                    <span class="rounded-[5px] bg-zinc-100 px-1.5 py-0.5 text-[10px] font-bold text-zinc-600 dark:bg-[#26416b] dark:text-zinc-200">{{ $net['speed'] }}</span>
                                @endif
                            </li>
                        @empty
                            <li class="text-sm text-zinc-600 dark:text-zinc-300">Your device connects automatically to the best available local network in {{ $regionLabel }}.</li>
                        @endforelse
                    </ul>
                @endif
            </div>
        </div>

        {{-- Check compatibility modal --}}
        <div x-show="showCompat" style="display:none;" class="fixed inset-0 z-[70] flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="compat-title">
            <div x-show="showCompat" @click="showCompat = false" x-transition.opacity class="absolute inset-0 bg-zinc-900/40 dark:bg-black/60"></div>
            <div x-show="showCompat" x-transition class="relative flex max-h-[80vh] w-full max-w-md flex-col overflow-hidden rounded-3xl bg-white shadow-2xl dark:bg-[#1d3252] dark:ring-1 dark:ring-zinc-700/60">
                <div class="flex items-start justify-between gap-4 p-5 pb-3">
                    <h2 id="compat-title" class="text-lg font-bold text-zinc-900 dark:text-white">Check compatibility</h2>
                    <button type="button" @click="showCompat = false" aria-label="Close" class="flex h-9 w-9 items-center justify-center rounded-full bg-zinc-100 text-zinc-600 transition-colors hover:bg-zinc-200 dark:bg-[#26416b] dark:text-zinc-200 dark:hover:bg-[#34507a]"><svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>
                </div>
                <div class="px-6 text-sm leading-relaxed text-zinc-600 dark:text-zinc-300">
                    <p>To use an eSIM, a device must meet the following conditions:</p>
                    <ul class="mt-2 list-disc space-y-1 pl-5">
                        <li>The device supports eSIMs.</li>
                        <li>The device is not carrier or network-locked.</li>
                        <li>The device is not jailbroken (iOS) or rooted (Android).</li>
                    </ul>
                    <p class="mt-2">Use the list to check if your device is eSIM-compatible. Some regional models may differ, so confirm with your manufacturer if unsure.</p>
                </div>

                <div class="mt-4 grid grid-cols-2 border-b border-zinc-200 px-6 dark:border-zinc-700/60">
                    <button type="button" @click="compatTab = 'ios'; compatSearch = ''" :class="compatTab === 'ios' ? 'border-blue-600 text-zinc-900 dark:text-white' : 'border-transparent text-zinc-500 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white'" class="border-b-2 py-3 text-sm font-bold transition-colors">iOS</button>
                    <button type="button" @click="compatTab = 'android'; compatSearch = ''" :class="compatTab === 'android' ? 'border-blue-600 text-zinc-900 dark:text-white' : 'border-transparent text-zinc-500 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white'" class="border-b-2 py-3 text-sm font-bold transition-colors">Android</button>
                </div>

                <div class="p-4">
                    <div class="relative">
                        <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-zinc-500 dark:text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <input x-model="compatSearch" type="text" placeholder="Search your device" aria-label="Search your device" class="w-full rounded-[10px] border border-zinc-300 bg-white py-2.5 pl-9 pr-3 text-sm text-zinc-900 placeholder:text-zinc-500 outline-none transition-colors focus:border-blue-500 focus:ring-2 focus:ring-blue-500/15 dark:border-zinc-700 dark:bg-[#26416b] dark:text-white dark:placeholder:text-zinc-400">
                    </div>
                </div>

                <div class="mx-4 mb-4 min-h-0 flex-1 overflow-y-auto rounded-[10px] p-3 ring-1 ring-zinc-200 dark:ring-zinc-700/60">
                    {{-- iOS --}}
                    <div x-show="compatTab === 'ios'" class="space-y-0.5">
                        @foreach ($compat['ios'] as $device)
                            <p data-name="{{ Str::lower($device) }}" x-show="compatSearch === '' || $el.dataset.name.includes(compatSearch.toLowerCase())" class="border-b border-zinc-100 py-2 text-sm text-zinc-800 dark:border-zinc-700/40 dark:text-zinc-200">{{ $device }}</p>
                        @endforeach
                    </div>
                    {{-- Android (grouped by brand) --}}
                    <div x-show="compatTab === 'android'" style="display:none;" class="space-y-4">
                        @foreach ($compat['android'] as $brand => $models)
                            <div
                                data-brand="{{ Str::lower($brand) }}"
                                data-models="{{ Str::lower(implode('|', $models)) }}"
                                x-show="compatSearch === '' || $el.dataset.brand.includes(compatSearch.toLowerCase()) || $el.dataset.models.includes(compatSearch.toLowerCase())"
                            >
                                <p class="sticky top-0 bg-white py-1.5 text-sm font-bold text-zinc-900 dark:bg-[#1d3252] dark:text-white">{{ $brand }}</p>
                                <div class="space-y-0.5">
                                    @foreach ($models as $model)
                                        <p data-name="{{ Str::lower($model) }}" data-brand="{{ Str::lower($brand) }}" x-show="compatSearch === '' || $el.dataset.brand.includes(compatSearch.toLowerCase()) || $el.dataset.name.includes(compatSearch.toLowerCase())" class="border-b border-zinc-100 py-2 text-sm text-zinc-800 dark:border-zinc-700/40 dark:text-zinc-200">{{ $model }}</p>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- Package details modal --}}
        <div x-show="showDetails" style="display:none;" class="fixed inset-0 z-[70] flex items-end justify-center p-0 sm:items-center sm:p-4" role="dialog" aria-modal="true" aria-labelledby="details-title">
            <div x-show="showDetails" @click="showDetails = false" x-transition.opacity class="absolute inset-0 bg-zinc-900/40 dark:bg-black/60"></div>
            <div x-show="showDetails" x-transition class="relative w-full max-w-lg rounded-t-3xl bg-white p-6 shadow-2xl sm:rounded-3xl dark:bg-[#1d3252] dark:ring-1 dark:ring-zinc-700/60">
                <div class="flex items-start justify-between gap-4">
                    <h2 id="details-title" class="flex items-center gap-2.5 text-lg font-bold text-zinc-900 dark:text-white">
                        @if ($flag)
                            <img src="{{ $flag }}" alt="" class="h-5 w-7 rounded-[2px] object-cover ring-1 ring-zinc-200 dark:ring-zinc-700">
                        @endif
                        {{ $regionLabel }}
                    </h2>
                    <button type="button" @click="showDetails = false" aria-label="Close" class="flex h-9 w-9 items-center justify-center rounded-full bg-zinc-100 text-zinc-600 transition-colors hover:bg-zinc-200 dark:bg-[#26416b] dark:text-zinc-200 dark:hover:bg-[#34507a]"><svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>
                </div>
                <div class="mt-5">
                    <p class="text-sm font-bold text-zinc-900 dark:text-white">Package</p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <span class="inline-flex flex-col rounded-[10px] bg-zinc-50 px-3 py-2 ring-1 ring-zinc-100 dark:bg-[#26416b] dark:ring-zinc-700/60">
                            <span class="text-[11px] font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Coverage</span>
                            <span class="text-sm font-semibold text-zinc-900 dark:text-white">{{ $regionLabel }}</span>
                        </span>
                        <span class="inline-flex flex-col rounded-[10px] bg-zinc-50 px-3 py-2 ring-1 ring-zinc-100 dark:bg-[#26416b] dark:ring-zinc-700/60">
                            <span class="text-[11px] font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Data</span>
                            <span class="text-sm font-semibold text-zinc-900 dark:text-white" x-text="plan()?.data || '-'"></span>
                        </span>
                        <span class="inline-flex flex-col rounded-[10px] bg-zinc-50 px-3 py-2 ring-1 ring-zinc-100 dark:bg-[#26416b] dark:ring-zinc-700/60">
                            <span class="text-[11px] font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Calls</span>
                            <span class="text-sm font-semibold text-zinc-900 dark:text-white" x-text="plan()?.voice || '-'"></span>
                        </span>
                        <span class="inline-flex flex-col rounded-[10px] bg-zinc-50 px-3 py-2 ring-1 ring-zinc-100 dark:bg-[#26416b] dark:ring-zinc-700/60">
                            <span class="text-[11px] font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Texts</span>
                            <span class="text-sm font-semibold text-zinc-900 dark:text-white" x-text="plan()?.sms || '-'"></span>
                        </span>
                        <span class="inline-flex flex-col rounded-[10px] bg-zinc-50 px-3 py-2 ring-1 ring-zinc-100 dark:bg-[#26416b] dark:ring-zinc-700/60">
                            <span class="text-[11px] font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Validity</span>
                            <span class="text-sm font-semibold text-zinc-900 dark:text-white" x-text="(plan()?.days > 0 ? plan().days + ' days' : 'Flexible')"></span>
                        </span>
                    </div>
                    <p class="mt-5 text-xs leading-relaxed text-zinc-500 dark:text-zinc-400">To complete your order, confirm your device is eSIM-compatible and network-unlocked.</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        window.esimStore = function ({ plans, cryptos, defaultCrypto, checkoutUrl, rcoinConfig }) {
            const cryptoMap = {};
            (cryptos || []).forEach((c) => { cryptoMap[c.code] = c; });

            return {
                rcoinConfig,
                plans: plans || [],
                checkoutUrl,
                cryptos: cryptoMap,
                selectedCrypto: cryptoMap[defaultCrypto] ? defaultCrypto : (cryptos[0]?.code ?? 'USD'),

                activeTab: 'data',     // 'data' | 'voice'
                dataMode: 'standard',  // 'standard' | 'unlimited'
                selectedId: null,

                cartState: 'idle',
                _t: null,

                showNetworks: false,
                showCompat: false,
                showDetails: false,
                compatTab: 'ios',
                compatSearch: '',

                locTab: 'popular',
                locSearch: '',

                init() {
                    if (! this.tabHasPlans('data') && this.tabHasPlans('voice')) {
                        this.activeTab = 'voice';
                    }
                    this.normalizeMode();
                    // No auto-selection — the buyer chooses a package by tapping it.
                },

                // ---- filtering ----
                inTab(p) { return this.activeTab === 'voice' ? p.is_voice : ! p.is_voice; },
                tabHasPlans(tab) { return this.plans.some((p) => (tab === 'voice' ? p.is_voice : ! p.is_voice)); },
                currentTabPlans() { return this.plans.filter((p) => this.inTab(p)); },
                modeHas(mode) { return this.currentTabPlans().some((p) => (mode === 'unlimited' ? p.is_unlimited : ! p.is_unlimited)); },
                // Only show the Standard/Unlimited toggle when the CURRENT tab actually
                // has both kinds — so it appears on Data but not on Data/Calls/Texts
                // (which has no unlimited tier).
                showModeToggle() { return this.modeHas('standard') && this.modeHas('unlimited'); },
                normalizeMode() {
                    if (! this.modeHas(this.dataMode)) {
                        this.dataMode = this.modeHas('standard') ? 'standard' : 'unlimited';
                    }
                },
                visiblePlans() {
                    return this.currentTabPlans().filter((p) => (this.showModeToggle()
                        ? (this.dataMode === 'unlimited' ? p.is_unlimited : ! p.is_unlimited)
                        : true));
                },
                groups() {
                    const map = {};
                    this.visiblePlans().forEach((p) => { (map[p.days] = map[p.days] || []).push(p); });
                    return Object.keys(map).map(Number).sort((a, b) => a - b).map((days) => ({
                        days,
                        label: days === 0 ? 'Flexible' : (days === 1 ? '1 day' : days + ' days'),
                        items: map[days].slice().sort((a, b) => a.price - b.price),
                    }));
                },
                setTab(tab) { this.activeTab = tab; this.normalizeMode(); this.selectedId = null; },
                setMode(mode) { this.dataMode = mode; this.selectedId = null; },

                // ---- selected plan + pricing ----
                plan() { return this.plans.find((p) => p.id === this.selectedId) || null; },
                unitPriceUsd() { return this.plan()?.price || 0; },
                totalUsd() { return this.unitPriceUsd(); },
                // Calculated from backend settings.
                pointsEarned() {
                    const cashbackUsd = this.totalUsd() * (this.rcoinConfig.cashback_percentage / 100);
                    return Math.floor(cashbackUsd / this.rcoinConfig.usd_rate);
                },

                money(usd) {
                    const meta = this.cryptos[this.selectedCrypto];
                    if (! meta) { return '$' + Number(usd).toFixed(2); }
                    const val = (Number(usd) * meta.perUsd).toFixed(meta.decimals);
                    // Glyph symbol only ("$8.50"); otherwise the code ("8.50 XAF").
                    return meta.symbol ? (meta.symbol + val) : (val + ' ' + this.selectedCrypto);
                },
                rowPrice(p) { return this.money(p.price); },
                totalLabel() { return this.money(this.totalUsd()); },

                // ---- cart ----
                async addToCart() {
                    if (this.cartState !== 'idle' || ! this.selectedId) { return false; }
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
                    if (! this.selectedId) { return; }
                    const ok = await this.$store.cart.add(this.selectedId, 1);
                    if (ok) { window.location.href = this.checkoutUrl; }
                },
            };
        };
    </script>

</x-layouts.app.header>
