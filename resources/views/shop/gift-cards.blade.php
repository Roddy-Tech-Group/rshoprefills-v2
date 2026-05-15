@php
    use App\Models\Product;
    use App\Models\Category;
    use App\Models\Subcategory;
    use Illuminate\Support\Facades\DB;

    $giftCardsCategory = Category::where('slug', 'gift-cards')->first();

    // URL-driven filters
    $search   = (string) request()->query('q', '');
    $country  = strtoupper((string) request()->query('country', ''));
    $currency = strtoupper((string) request()->query('currency', ''));
    $sub      = (string) request()->query('subcategory', '');
    $sort     = (string) request()->query('sort', 'popular');
    $perPage  = 25;

    // Resolve a partial country-name search ("camero", "United Sta") to ISO codes so the
    // user can type a country name in the global nav and get a matching catalog filter.
    $searchCountryCodes = [];
    if ($search !== '') {
        $needle = mb_strtolower(trim($search));
        foreach (config('countries.codes', []) as $cname => $code) {
            if (str_contains(mb_strtolower($cname), $needle)) {
                $searchCountryCodes[] = strtoupper($code);
            }
        }
        if (mb_strlen($needle) >= 1) {
            $searchCountryCodes[] = strtoupper($needle); // also catch raw "us" / "gb" inputs
        }
        $searchCountryCodes = array_values(array_unique(array_filter($searchCountryCodes)));
    }

    /*
     * One card per BRAND, not per (brand × country). Zendit syncs the catalog as one
     * Product row per country (e.g. Apple has 8 country variants), but customer-facing
     * the user just wants to see "Apple" once. They pick country+currency in the locale
     * modal which filters which brands appear, and they pick the denomination later on
     * the detail page.
     *
     * Implementation: filter the products table by the active filters, group by
     * brand_key, take the MIN(id) per group as the representative row to display.
     */
    $baseFilters = Product::query()
        ->where('is_active', true)
        ->whereNotNull('brand_key')
        ->when($giftCardsCategory, fn ($q) => $q->where('category_id', $giftCardsCategory->id))
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
        ->when($sub !== '', fn ($q) => $q->whereHas('subcategory', fn ($qq) => $qq->where('slug', $sub)));

    // Pluck one representative Product id per brand_key. With ~692 unique brands this is
    // a cheap single SELECT. Prefer the US variant as the representative (matches the
    // detail page, which defaults to US) so the card's price range reflects US pricing;
    // fall back to the lowest id when a brand has no US product.
    $representativeIds = (clone $baseFilters)
        ->select('brand_key', DB::raw("COALESCE(MIN(CASE WHEN country_code = 'US' THEN id END), MIN(id)) as id"))
        ->groupBy('brand_key')
        ->pluck('id');

    $productsQuery = Product::query()
        ->whereIn('id', $representativeIds)
        ->with(['variants:id,product_id,currency,retail_price,cost_price,face_value,min_amount,max_amount,is_variable,is_available']);

    $products = match ($sort) {
        'name-asc'   => $productsQuery->orderBy('name'),
        'name-desc'  => $productsQuery->orderByDesc('name'),
        default      => $productsQuery->orderByDesc('is_popular')->orderByDesc('is_featured')->orderBy('name'),
    };

    $products = $products->paginate($perPage)->withQueryString();

    $subcategories = $giftCardsCategory
        ? Subcategory::where('category_id', $giftCardsCategory->id)->orderBy('name')->get(['name', 'slug'])
        : collect();

    // Total = distinct BRANDS available in the catalog, not raw Product rows (since the listing now
    // shows one card per brand).
    $totalProducts = $giftCardsCategory
        ? Product::where('category_id', $giftCardsCategory->id)
            ->where('is_active', true)
            ->whereNotNull('brand_key')
            ->distinct()
            ->count('brand_key')
        : 0;

    // Distinct countries + currencies actually available in the synced gift-card catalog.
    $availableCountries = Product::query()
        ->when($giftCardsCategory, fn ($q) => $q->where('category_id', $giftCardsCategory->id))
        ->where('is_active', true)
        ->distinct()
        ->pluck('country_code')
        ->filter()
        ->sort()
        ->values();

    $availableCurrencies = Product::query()
        ->when($giftCardsCategory, fn ($q) => $q->where('category_id', $giftCardsCategory->id))
        ->where('is_active', true)
        ->distinct()
        ->pluck('currency_code')
        ->filter()
        ->sort()
        ->values();

    // Currency symbols. Falls back to the raw code for anything not in this table.
    $currencySymbols = [
        'USD' => '$',  'EUR' => '€',  'GBP' => '£',  'JPY' => '¥',  'CNY' => '¥',
        'AUD' => 'A$', 'CAD' => 'C$', 'NZD' => 'NZ$', 'CHF' => 'Fr', 'SEK' => 'kr',
        'NOK' => 'kr', 'DKK' => 'kr', 'PLN' => 'zł', 'CZK' => 'Kč', 'HUF' => 'Ft',
        'INR' => '₹',  'KRW' => '₩',  'THB' => '฿',  'IDR' => 'Rp', 'PHP' => '₱',
        'VND' => '₫',  'MYR' => 'RM', 'SGD' => 'S$', 'HKD' => 'HK$','TWD' => 'NT$',
        'BRL' => 'R$', 'MXN' => 'MX$','ARS' => 'AR$','CLP' => 'CL$','COP' => 'CO$',
        'PEN' => 'S/', 'NGN' => '₦',  'GHS' => '₵',  'KES' => 'KSh','UGX' => 'USh',
        'TZS' => 'TSh','ZAR' => 'R',  'EGP' => 'E£', 'MAD' => 'MAD','AED' => 'AED',
        'SAR' => 'SAR','TRY' => '₺',  'ILS' => '₪',  'PKR' => '₨',  'BDT' => '৳',
        'RUB' => '₽',  'UAH' => '₴',  'XAF' => 'FCFA','XOF' => 'CFA',
    ];
    $sym = fn (?string $code) => $code ? ($currencySymbols[strtoupper($code)] ?? $code) : '';

    // Helper to preserve other filters when toggling one
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
        return route('shop.gift-cards', $params);
    };

@endphp

<x-layouts.app.header>

    <section class="bg-white">
        <div class="mx-auto w-full max-w-[1550px] px-4 py-8 sm:px-6 lg:px-8">

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-[220px_1fr] lg:gap-8">

                {{-- Left sidebar (desktop only). Lists ONLY the gift-card sub-categories (real DB rows synced from Zendit subTypes).
                     The top-level product types (eSIMs, Flights, etc.) live in the storefront nav — don't duplicate.
                     `sticky top-4` keeps it in view while the product grid scrolls; max-height + overflow-y handles long lists. --}}
                {{-- Sidebar (desktop). The whole aside is sticky as ONE unit;
                     the inner nav handles its own scroll if the subcategory list outgrows the viewport. --}}
                <aside class="hidden self-start lg:sticky lg:top-4 lg:block">
                    <div class="max-h-[calc(100vh-2rem)] overflow-y-auto rounded-2xl bg-white p-3 ring-1 ring-zinc-200 [scrollbar-width:thin]">

                        {{-- Categories group --}}
                        <p class="px-3 pb-1 pt-1 text-[10px] font-bold uppercase tracking-[0.12em] text-zinc-500">Categories</p>
                        <nav class="space-y-0.5 text-sm" aria-label="Gift card categories">
                            <a
                                href="{{ route('shop.gift-cards') }}"
                                wire:navigate
                                class="flex items-center justify-between rounded-lg px-3 py-2 transition-colors {{ $sub === '' ? 'bg-zinc-100 font-bold text-zinc-900' : 'text-zinc-700 hover:bg-blue-100 hover:text-zinc-900' }}"
                            >
                                <span>All gift cards</span>
                                <span class="text-[11px] font-medium text-zinc-500">{{ number_format($totalProducts) }}</span>
                            </a>
                        </nav>

                        @if ($subcategories->isNotEmpty())
                            {{-- Subcategories group --}}
                            <p class="mt-4 px-3 pb-1 pt-1 text-[10px] font-bold uppercase tracking-[0.12em] text-zinc-500">Subcategories</p>
                            <nav class="space-y-0.5 text-sm" aria-label="Gift card subcategories">
                                @foreach ($subcategories as $s)
                                    <a
                                        href="{{ $filterUrl(['subcategory' => $s->slug]) }}"
                                        wire:navigate
                                        class="block rounded-lg px-3 py-2 transition-colors {{ $sub === $s->slug ? 'bg-zinc-100 font-bold text-zinc-900' : 'text-zinc-700 hover:bg-blue-100 hover:text-zinc-900' }}"
                                    >
                                        {{ $s->name }}
                                    </a>
                                @endforeach
                            </nav>
                        @endif

                    </div>
                </aside>

                {{-- Main column --}}
                <div>
                    {{-- Heading + search row --}}
                    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h1 class="text-2xl font-bold tracking-tight text-zinc-900 sm:text-3xl">Gift Cards</h1>
                            <p class="mt-0.5 text-sm text-zinc-600">{{ number_format($totalProducts) }} brands · delivered instantly</p>
                        </div>
                        <form method="GET" action="{{ route('shop.gift-cards') }}" class="flex w-full flex-wrap items-center gap-2 sm:w-auto">
                            <div class="relative w-full sm:w-72">
                                <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-zinc-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                                <input
                                    type="search"
                                    name="q"
                                    value="{{ $search }}"
                                    placeholder="Search brands"
                                    class="w-full rounded-xl border border-zinc-200 bg-white py-2.5 pl-10 pr-3 text-sm text-zinc-900 outline-none transition-colors focus:border-zinc-900 focus:ring-1 focus:ring-zinc-900/10"
                                />
                                {{-- Preserve sticky filters across form submits --}}
                                @if ($country)  <input type="hidden" name="country"     value="{{ $country }}">  @endif
                                @if ($currency) <input type="hidden" name="currency"    value="{{ $currency }}"> @endif
                                @if ($sub)      <input type="hidden" name="subcategory" value="{{ $sub }}">      @endif
                            </div>

                        </form>

                        {{-- Modern segmented sort selector. URL-driven so the choice survives reloads;
                             each pill is a real <a> that updates ?sort= while preserving other filters. --}}
                        <div class="inline-flex shrink-0 items-center rounded-xl bg-zinc-100 p-1" role="tablist" aria-label="Sort gift cards">
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
                                    class="inline-flex items-center justify-center rounded-lg px-3 py-1.5 text-xs font-semibold transition-all {{ $sort === $opt['value'] ? 'bg-white text-zinc-900 shadow-sm ring-1 ring-zinc-200' : 'text-zinc-600 hover:bg-white/70 hover:text-zinc-900' }}"
                                >
                                    {{ $opt['label'] }}
                                </a>
                            @endforeach
                        </div>
                    </div>

                    {{-- Mobile/tablet subcategory pill row (sidebar hidden below lg) --}}
                    @if ($subcategories->isNotEmpty())
                        <div class="-mx-1 mb-6 hidden overflow-x-auto px-1 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden md:block lg:hidden">
                            <div class="flex w-max items-center gap-2">
                                <a
                                    href="{{ $filterUrl(['subcategory' => null]) }}"
                                    wire:navigate
                                    class="inline-flex shrink-0 items-center rounded-full px-4 py-1.5 text-xs font-semibold transition-colors {{ $sub === '' ? 'bg-zinc-900 text-white' : 'bg-white text-zinc-700 ring-1 ring-zinc-200' }}"
                                >
                                    All gift cards
                                </a>
                                @foreach ($subcategories as $s)
                                    <a
                                        href="{{ $filterUrl(['subcategory' => $s->slug]) }}"
                                        wire:navigate
                                        class="inline-flex shrink-0 items-center rounded-full px-4 py-1.5 text-xs font-semibold transition-colors {{ $sub === $s->slug ? 'bg-zinc-900 text-white' : 'bg-white text-zinc-700 ring-1 ring-zinc-200' }}"
                                    >
                                        {{ $s->name }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Product grid. Skeleton overlay removed — it was rendering misaligned with the real
                         grid and flashing on every navigation. wire:navigate transitions are fast enough
                         without a synthetic loading state. --}}
                    <div>
                        @if ($products->isNotEmpty())
                            <ul class="grid grid-cols-2 gap-x-4 gap-y-6 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5">
                                @foreach ($products as $product)
                                    @php
                                        $variants   = $product->variants;
                                        $available  = $variants->where('is_available', true);
                                        $isOut      = $variants->isNotEmpty() && $available->isEmpty();
                                        // Min-to-max range across ALL denominations (fixed + variable), in the product's currency.
                                        $priceLabel = $product->priceRangeLabel();
                                        $logoSrc    = Product::brandLogoUrl($product->brand_key, $product->logo_url);
                                    @endphp
                                    <li>
                                        <a
                                            href="{{ route('shop.brand', ['brandSlug' => Product::brandSlug($product->brand_key)]) }}"
                                            wire:navigate
                                            class="group block focus:outline-none"
                                            aria-label="{{ Product::brandDisplayName($product->brand_key) }}"
                                        >
                                            {{-- White tile, logo fills the card edge-to-edge. Caption sits OUTSIDE on the page background. --}}
                                            <div class="relative flex aspect-[16/10] items-center justify-center overflow-hidden rounded-[15px] bg-white shadow-sm ring-1 ring-zinc-200 transition-all duration-200 group-hover:-translate-y-0.5 group-hover:shadow-md group-hover:ring-zinc-300">
                                                @if ($logoSrc)
                                                    <img src="{{ $logoSrc }}" alt="{{ Product::brandDisplayName($product->brand_key) }}" class="h-full w-full object-cover" loading="lazy">
                                                @else
                                                    <span class="text-2xl font-black tracking-tight text-zinc-700">
                                                        {{ str(Product::brandDisplayName($product->brand_key))->substr(0, 2)->upper() }}
                                                    </span>
                                                @endif

                                                @if ($isOut)
                                                    <span class="absolute bottom-2 right-2 inline-flex items-center rounded-md bg-zinc-900/85 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-white backdrop-blur-sm">
                                                        Out of stock
                                                    </span>
                                                @elseif ($product->is_popular)
                                                    <span class="absolute left-2 top-2 inline-flex items-center rounded-[5px] bg-fuchsia-500 px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wide text-white">Popular</span>
                                                @elseif ($product->is_featured)
                                                    <span class="absolute left-2 top-2 inline-flex items-center rounded-[5px] bg-amber-500 px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wide text-white">Featured</span>
                                                @endif
                                            </div>

                                            {{-- Caption — brand name + the real min-to-max denomination range. --}}
                                            <div class="mt-2 px-0.5">
                                                <p class="truncate text-[13px] font-bold leading-tight text-zinc-900 group-hover:text-blue-700">{{ Product::brandDisplayName($product->brand_key) }}</p>
                                                @if ($priceLabel)
                                                    <p class="mt-0.5 truncate text-[11px] text-zinc-600">{{ $priceLabel }}</p>
                                                @endif
                                            </div>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>

                            @if ($products->hasPages())
                                <div class="mt-10 border-t border-zinc-200 pt-6">
                                    {{ $products->onEachSide(1)->links('vendor.pagination.circles') }}
                                </div>
                            @endif

                        @else
                            <div class="rounded-3xl bg-white px-6 py-20 text-center ring-1 ring-zinc-200">
                                <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-blue-50 text-blue-600">
                                    <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                    </svg>
                                </span>
                                <p class="mt-4 text-base font-semibold text-zinc-900">No gift cards match these filters</p>
                                <p class="mt-1 text-sm text-zinc-600">Try clearing the search or pick a different category.</p>
                                @if ($search !== '' || $country !== '' || $sub !== '')
                                    <a href="{{ route('shop.gift-cards') }}" wire:navigate class="mt-4 inline-flex items-center gap-1.5 text-sm font-semibold text-blue-600 hover:text-blue-700">
                                        Clear all filters
                                    </a>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            </div>

        </div>
    </section>

</x-layouts.app.header>
