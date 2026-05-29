@php
    use App\Models\Product;
    use App\Models\Category;
    use App\Models\Subcategory;
    use Illuminate\Support\Facades\DB;

    // Bill payments listing — mirrors the gift-card / top-up listings. One card
    // per BILLER brand (Products in the `bill-payments` category, grouped by
    // brand_key); the detail page reuses the shared `shop.product` view.
    $billCategory = Category::where('slug', 'bill-payments')->first();

    $search   = (string) request()->query('q', '');
    $country  = strtoupper((string) ($region ?? 'US'));
    $currency = strtoupper((string) request()->query('currency', ''));
    $sub      = (string) request()->query('subcategory', '');
    $sort     = (string) request()->query('sort', 'popular');

    // Resolve a partial country-name search to ISO codes.
    $searchCountryCodes = [];
    if ($search !== '') {
        $needle = mb_strtolower(trim($search));
        foreach (config('countries.codes', []) as $cname => $code) {
            if (str_contains(mb_strtolower($cname), $needle)) {
                $searchCountryCodes[] = strtoupper($code);
            }
        }
        if (mb_strlen($needle) >= 1) {
            $searchCountryCodes[] = strtoupper($needle);
        }
        $searchCountryCodes = array_values(array_unique(array_filter($searchCountryCodes)));
    }

    // One card per biller brand_key. Hard-scoped to the bill-payments category;
    // `?->id ?? 0` shows the empty state if that category does not exist yet.
    $baseFilters = Product::query()
        ->where('is_active', true)
        ->whereNotNull('brand_key')
        ->where('category_id', $billCategory?->id ?? 0)
        ->when($search !== '', fn ($q) => $q->where(function ($qq) use ($search, $searchCountryCodes) {
            $qq->where('name', 'like', "%{$search}%")
                ->orWhere('slug', 'like', '%' . str()->slug($search) . '%')
                ->orWhere('brand_key', 'like', "%{$search}%")
                ->orWhere('country_code', 'like', strtoupper($search) . '%');
            if (! empty($searchCountryCodes)) {
                $qq->orWhereIn('country_code', $searchCountryCodes);
            }
        }))
        ->when($country !== '', fn ($q) => $q->where('country_code', $country))
        ->when($currency !== '', fn ($q) => $q->where('currency_code', $currency))
        // Precise subcategory filter: match products with a VARIANT in this subcategory.
        ->when($sub !== '', fn ($q) => $q->whereHas('variants', fn ($qq) => $qq->whereHas('subcategory', fn ($qqq) => $qqq->where('slug', $sub))));

    $representativeIds = (clone $baseFilters)
        ->select('brand_key', DB::raw("COALESCE(MIN(CASE WHEN country_code = 'US' THEN id END), MIN(id)) as id"))
        ->groupBy('brand_key')
        ->pluck('id');

    $productsQuery = Product::query()
        ->whereIn('id', $representativeIds)
        ->with(['variants:id,product_id,currency,retail_price,cost_price,face_value,min_amount,max_amount,is_variable,is_available']);

    if ($sort === 'name-asc') {
        $productsQuery->orderBy('name');
    } elseif ($sort === 'name-desc') {
        $productsQuery->orderByDesc('name');
    } else {
        $productsQuery->orderByDesc('is_popular')->orderByDesc('is_featured')->orderBy('name');
    }

    $products = $productsQuery->get();

    $subcategories = $billCategory
        ? Subcategory::where('category_id', $billCategory->id)->orderBy('name')->get(['name', 'slug'])
        : collect();

    $greenTint = 'filter: brightness(0) saturate(100%) invert(48%) sepia(79%) saturate(394%) hue-rotate(105deg) brightness(92%) contrast(87%);';

    $availableCountries = Product::query()
        ->where('category_id', $billCategory?->id ?? 0)
        ->where('is_active', true)
        ->distinct()
        ->pluck('country_code')
        ->filter()
        ->sort()
        ->values();

    $countryNameMap = array_flip(config('countries.codes', []));
    $countryOptions = $availableCountries
        ->map(fn ($c) => ['code' => $c, 'name' => $countryNameMap[$c] ?? $c])
        ->sortBy('name')
        ->values();
    $currentCountryName = $country ? ($countryNameMap[$country] ?? $country) : null;
    // US is the default region — USD, USA products only. Selecting a country
    // narrows to that region; the clear-X resets back to the US default.
    $countryFiltered = $country !== '' && $country !== 'US';

    $filterUrl = function (array $overrides) use ($search, $country, $currency, $sub, $sort) {
        $params = array_filter([
            'q'           => $search ?: null,
            'country'     => $country ?: null,
            'currency'    => $currency ?: null,
            'subcategory' => $sub ?: null,
            'sort'        => $sort !== 'popular' ? $sort : null,
        ], fn ($v) => $v !== null);
        foreach ($overrides as $k => $v) {
            if ($v === null) {
                unset($params[$k]);
            } else {
                $params[$k] = $v;
            }
        }
        return route('shop.bills', $params);
    };

    // Subcategory links for the shared sidebar — "All" first, then each subtype.
    $sidebarSubItems = array_merge(
        [['label' => 'All billers', 'url' => $filterUrl(['subcategory' => null]), 'active' => $sub === '']],
        $subcategories->map(fn ($s) => [
            'label' => $s->name,
            'url' => $filterUrl(['subcategory' => $s->slug]),
            'active' => $sub === $s->slug,
        ])->all()
    );
@endphp

<x-layouts.app.header :title="'Bill Payments | RshopRefills'">

    <section class="min-h-full bg-zinc-100">
        <div class="mx-auto w-full max-w-[1550px] px-4 py-8 sm:px-6 lg:px-8">

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-[220px_1fr] lg:gap-8">

                {{-- Shared category sidebar — same component on every storefront. --}}
                <x-shop.category-sidebar active="bill-payments" :sub-items="$sidebarSubItems" />

                {{-- Main column --}}
                <div>
                    {{-- Heading + search row --}}
                    <div class="mb-6 flex flex-col gap-3 sm:grid sm:grid-cols-3 sm:items-center sm:gap-4">
                        <div>
                            <h1 class="text-2xl font-bold tracking-tight text-zinc-900 sm:text-3xl">Bill Payments</h1>
                            <div class="mt-1.5 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm font-semibold text-zinc-700">
                                <span class="flex items-center gap-1.5">
                                    <img src="{{ asset('assets/' . rawurlencode('instant delivery.png')) }}" alt="" class="h-4 w-4 object-contain" style="{{ $greenTint }}" loading="lazy">
                                    Instant Delivery
                                </span>
                                <span class="flex items-center gap-1.5">
                                    <img src="{{ asset('assets/' . rawurlencode('Fair Redund Policy.png')) }}" alt="" class="h-4 w-4 object-contain" style="{{ $greenTint }}" loading="lazy">
                                    Clear Refund Policy
                                </span>
                            </div>
                        </div>

                        {{-- Shop by country picker --}}
                        <div
                            x-data="{ open: false, q: '' }"
                            @click.outside="open = false"
                            @keydown.escape="open = false"
                            class="relative w-full sm:w-72 sm:justify-self-center"
                        >
                            <div
                                :class="open ? 'border-zinc-900 ring-1 ring-zinc-900/10' : 'border-zinc-200 hover:border-zinc-400'"
                                class="flex w-full items-center gap-1 rounded-[10px] border bg-white py-2.5 pl-3.5 pr-2.5 text-sm text-zinc-900 transition-colors"
                            >
                                <button
                                    type="button"
                                    @click="open = !open; if (open) $nextTick(() => $refs.countryQ?.focus())"
                                    class="flex min-w-0 flex-1 items-center gap-2 text-left outline-none"
                                >
                                    @if ($currentCountryName && Product::flagUrl($country))
                                        <img src="{{ Product::flagUrl($country) }}" alt="" class="h-3.5 w-5 shrink-0 rounded-[2px] object-cover ring-1 ring-zinc-200" loading="lazy">
                                    @else
                                        <img src="{{ asset('assets/' . rawurlencode('global svg.svg')) }}" alt="" class="h-4 w-4 shrink-0" loading="lazy">
                                    @endif
                                    <span class="truncate {{ $currentCountryName ? 'font-medium text-zinc-900' : 'text-zinc-500' }}">{{ $currentCountryName ?? 'Shop by country' }}</span>
                                </button>

                                @if ($countryFiltered)
                                    <a
                                        href="{{ $filterUrl(['country' => 'US']) }}"
                                        wire:navigate
                                        aria-label="Clear country filter"
                                        class="flex h-5 w-5 shrink-0 items-center justify-center rounded-[10px] bg-zinc-200 transition-colors hover:bg-zinc-300"
                                    >
                                        <img src="{{ asset('assets/' . rawurlencode('x button.png')) }}" alt="" class="h-3.5 w-3.5 object-contain" loading="lazy">
                                    </a>
                                @endif

                                <button
                                    type="button"
                                    @click="open = !open"
                                    aria-label="Toggle country list"
                                    class="shrink-0 outline-none"
                                >
                                    <svg class="h-4 w-4 text-zinc-500 transition-transform" :class="open && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </button>
                            </div>

                            <div
                                x-show="open"
                                x-cloak
                                x-transition:enter="transition ease-out duration-150"
                                x-transition:enter-start="opacity-0 -translate-y-1"
                                x-transition:enter-end="opacity-100 translate-y-0"
                                class="absolute left-0 right-0 z-30 mt-1 overflow-hidden rounded-[10px] bg-white/80 shadow-xl shadow-zinc-900/10 ring-1 ring-zinc-200 backdrop-blur-xl"
                            >
                                <div class="border-b border-zinc-100 p-2">
                                    <input
                                        x-ref="countryQ"
                                        x-model="q"
                                        type="text"
                                        placeholder="Search countries"
                                        class="w-full rounded-[10px] border border-zinc-200 bg-white px-3 py-1.5 text-sm text-zinc-900 outline-none focus:border-zinc-900 focus:ring-1 focus:ring-zinc-900/10"
                                    >
                                </div>
                                <div class="max-h-72 overflow-y-auto p-1 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                                    @foreach ($countryOptions as $opt)
                                        <a
                                            href="{{ $filterUrl(['country' => $opt['code']]) }}"
                                            wire:navigate
                                            x-show="q === '' || '{{ strtolower(addslashes($opt['name'])) }}'.includes(q.toLowerCase())"
                                            class="flex items-center gap-2 rounded-[10px] px-3 py-2 text-sm font-medium transition-colors {{ $country === $opt['code'] ? 'bg-blue-50 text-blue-700' : 'text-zinc-700 hover:bg-zinc-100' }}"
                                        >
                                            @if (Product::flagUrl($opt['code']))
                                                <img src="{{ Product::flagUrl($opt['code']) }}" alt="" class="h-3.5 w-5 shrink-0 rounded-[2px] object-cover ring-1 ring-zinc-200" loading="lazy">
                                            @endif
                                            <span class="truncate">{{ $opt['name'] }}</span>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        {{-- Sort selector --}}
                        <div class="inline-flex shrink-0 items-center rounded-[10px] bg-zinc-100 p-1 sm:justify-self-end" role="tablist" aria-label="Sort billers">
                            @foreach ([
                                ['value' => 'popular',   'label' => 'Popularity'],
                                ['value' => 'name-asc',  'label' => 'A → Z'],
                                ['value' => 'name-desc', 'label' => 'Z → A'],
                            ] as $opt)
                                <a
                                    href="{{ $filterUrl(['sort' => $opt['value'] === 'popular' ? null : $opt['value']]) }}"
                                    wire:navigate
                                    role="tab"
                                    aria-selected="{{ $sort === $opt['value'] ? 'true' : 'false' }}"
                                    class="inline-flex items-center justify-center rounded-[10px] px-3 py-1.5 text-xs font-semibold transition-all {{ $sort === $opt['value'] ? 'bg-white text-zinc-900 shadow-sm ring-1 ring-zinc-200' : 'text-zinc-600 hover:bg-white/70 hover:text-zinc-900' }}"
                                >
                                    {{ $opt['label'] }}
                                </a>
                            @endforeach
                        </div>
                    </div>

                    {{-- Mobile/tablet subcategory pill row --}}
                    @if ($subcategories->isNotEmpty())
                        <div class="-mx-1 mb-6 hidden overflow-x-auto px-1 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden md:block lg:hidden">
                            <div class="flex w-max items-center gap-2">
                                <a
                                    href="{{ $filterUrl(['subcategory' => null]) }}"
                                    wire:navigate
                                    class="inline-flex shrink-0 items-center rounded-[10px] px-4 py-1.5 text-xs font-semibold transition-colors {{ $sub === '' ? 'bg-zinc-900 text-white' : 'bg-white text-zinc-700 ring-1 ring-zinc-200' }}"
                                >
                                    All billers
                                </a>
                                @foreach ($subcategories as $s)
                                    <a
                                        href="{{ $filterUrl(['subcategory' => $s->slug]) }}"
                                        wire:navigate
                                        class="inline-flex shrink-0 items-center rounded-[10px] px-4 py-1.5 text-xs font-semibold transition-colors {{ $sub === $s->slug ? 'bg-zinc-900 text-white' : 'bg-white text-zinc-700 ring-1 ring-zinc-200' }}"
                                    >
                                        {{ $s->name }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Biller grid. Same card treatment as the top-up listing — utilities
                         carry no logo, so cards render as solid-colour name tiles. --}}
                    <div>
                        @if ($products->isNotEmpty())
                            <ul class="grid grid-cols-2 gap-x-4 gap-y-6 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5">
                                @foreach ($products as $product)
                                    @php
                                        $variants   = $product->variants;
                                        $available  = $variants->where('is_available', true);
                                        $isOut      = $variants->isNotEmpty() && $available->isEmpty();
                                        $priceLabel = $product->priceRangeLabel();
                                        $logoSrc    = Product::brandLogoUrl($product->brand_key, $product->logo_url);
                                        $billerName = Product::brandDisplayName($product->brand_key);
                                        $tileColor  = Product::tileColor($product->brand_key);
                                    @endphp
                                    <li>
                                        <a
                                            href="{{ route('shop.bill', ['brandSlug' => Product::brandSlug($product->brand_key), 'country' => $product->country_code]) }}"
                                            wire:navigate
                                            class="card-3d-scene group block focus:outline-none"
                                            aria-label="{{ $billerName }}"
                                        >
                                            <div
                                                class="card-3d relative flex aspect-[16/10] items-center justify-center overflow-hidden rounded-[15px] shadow-sm ring-1 ring-zinc-200 group-hover:shadow-lg group-hover:ring-zinc-300 {{ $logoSrc ? 'bg-[#ffffff]' : '' }}"
                                                @if (! $logoSrc) style="background-color: {{ $tileColor }}" @endif
                                                x-data="cardTilt()"
                                                @mousemove="tilt($event)"
                                                @mouseleave="reset()"
                                            >
                                                @if ($logoSrc)
                                                    <img src="{{ $logoSrc }}" alt="{{ $billerName }}" class="h-full w-full object-cover" loading="lazy">
                                                @else
                                                    <span class="px-3 text-center text-lg font-extrabold leading-tight text-white">{{ $billerName }}</span>
                                                @endif

                                                @if ($isOut)
                                                    <span class="absolute bottom-2 right-2 inline-flex items-center rounded-[10px] bg-zinc-900/85 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-white backdrop-blur-sm">
                                                        Unavailable
                                                    </span>
                                                @elseif ($product->is_popular)
                                                    <span class="absolute left-2 top-2 inline-flex items-center rounded-[5px] bg-fuchsia-500 px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wide text-white">Popular</span>
                                                @elseif ($product->is_featured)
                                                    <span class="absolute left-2 top-2 inline-flex items-center rounded-[5px] bg-amber-500 px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wide text-white">Featured</span>
                                                @endif

                                                <span class="card-3d-glare pointer-events-none absolute inset-0" aria-hidden="true"></span>
                                            </div>

                                            {{-- Caption — biller name (only when a real logo is on the tile;
                                                 the styled tile already shows it) + amount range. --}}
                                            <div class="mt-2 px-0.5">
                                                @if ($logoSrc)
                                                    <p class="truncate text-[13px] font-bold leading-tight text-zinc-900 group-hover:text-blue-700">{{ $billerName }}</p>
                                                @endif
                                                @if ($priceLabel)
                                                    <p class="mt-0.5 truncate text-[14px] text-zinc-600">{{ $priceLabel }}</p>
                                                @endif
                                            </div>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>

                        @else
                            <div class="rounded-[10px] bg-white px-6 py-20 text-center ring-1 ring-zinc-200">
                                <img src="{{ asset('assets/' . rawurlencode('Empty state.png')) }}" alt="" class="mx-auto block h-44 w-auto object-contain" loading="lazy">
                                @if ($search !== '' || $countryFiltered || $sub !== '')
                                    <p class="mt-4 text-base font-semibold text-zinc-900">No billers match these filters</p>
                                    <p class="mt-1 text-sm text-zinc-600">Try clearing the search or pick a different country.</p>
                                    <a href="{{ route('shop.bills') }}" wire:navigate class="mt-4 inline-flex items-center gap-1.5 text-sm font-semibold text-blue-600 hover:text-blue-700">
                                        Clear all filters
                                    </a>
                                @else
                                    <p class="mt-4 text-base font-semibold text-zinc-900">Bill payments coming soon</p>
                                    <p class="mt-1 text-sm text-zinc-600">We're adding bill payment services for your region. Check back shortly.</p>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            </div>

        </div>
    </section>

</x-layouts.app.header>
