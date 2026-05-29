@php
    use App\Models\Category;
    use App\Models\Product;
    use Illuminate\Support\Facades\DB;

    $giftCardsCategory = Category::where('slug', 'gift-cards')->first();

    // Region lock — the homepage only showcases brands sold in the customer's
    // resolved region (ResolveRegion middleware shares $region).
    $region = strtoupper((string) ($region ?? 'US'));

    /*
     * Returns a collection of representative Products — one per brand_key — for a
     * home-page row. `$flag` optionally scopes to is_popular / is_featured.
     */
    $brandPicks = function (?string $flag = null, int $limit = 6) use ($giftCardsCategory, $region) {
        $base = Product::query()
            ->where('is_active', true)
            ->whereNotNull('brand_key')
            ->where('country_code', $region)
            ->when($giftCardsCategory, fn ($q) => $q->where('category_id', $giftCardsCategory->id));
        if ($flag) {
            $base->where($flag, true);
        }
        $ids = (clone $base)
            ->select('brand_key', DB::raw("COALESCE(MIN(CASE WHEN country_code = 'US' THEN id END), MIN(id)) as id"))
            ->groupBy('brand_key')
            ->limit($limit)
            ->pluck('id');

        return Product::whereIn('id', $ids)
            ->with('variants:id,product_id,retail_price,is_variable,is_available')
            ->orderBy('name')
            ->get();
    };

    // Same as brandPicks but scoped to a specific subcategory slug. Used by
    // the per-category rows (Gaming, Digital Apps, Clothing, Food, etc.).
    $brandPicksBySubcategory = function (string $subcategorySlug, int $limit = 5) use ($giftCardsCategory, $region) {
        $base = Product::query()
            ->where('is_active', true)
            ->whereNotNull('brand_key')
            ->where('country_code', $region)
            ->when($giftCardsCategory, fn ($q) => $q->where('category_id', $giftCardsCategory->id))
            ->whereHas('subcategory', fn ($q) => $q->where('slug', $subcategorySlug));

        $ids = (clone $base)
            ->select('brand_key', DB::raw("COALESCE(MIN(CASE WHEN country_code = 'US' THEN id END), MIN(id)) as id"))
            ->groupBy('brand_key')
            ->limit($limit)
            ->pluck('id');

        return Product::whereIn('id', $ids)
            ->with('variants:id,product_id,retail_price,is_variable,is_available')
            ->orderBy('name')
            ->get();
    };

    // "Popular Gift Cards" is a hand-curated brand list (config/popular_brands.php) —
    // the same list floats these brands to the top of the /gift-cards listing.
    $popularKeys = config('popular_brands.keys', []);
    $popularIdByKey = Product::query()
        ->where('is_active', true)
        ->where('country_code', $region)
        ->whereIn('brand_key', $popularKeys)
        ->select('brand_key', DB::raw('MIN(id) as id'))
        ->groupBy('brand_key')
        ->pluck('id');
    // Take the full curated popular list (was capped at 5, which dropped
    // Steam off some regions). With 7 brands and 5 grid columns, the row
    // wraps cleanly into 5 + 2 on desktop and stays a single horizontal
    // scroll on mobile.
    $popularBrands = Product::query()
        ->whereIn('id', $popularIdByKey)
        ->with('variants:id,product_id,retail_price,is_variable,is_available')
        ->get()
        ->sortBy(fn ($p) => array_search($p->brand_key, $popularKeys))
        ->take(count($popularKeys))
        ->values();

    $featuredBrands = $brandPicks('is_featured', 5);
    $browseBrands   = $brandPicks(null, 5);

    // Fallbacks so a row never renders empty (e.g. a small region, or no popular/featured flags).
    if ($featuredBrands->isEmpty()) { $featuredBrands = $browseBrands; }
    if ($popularBrands->isEmpty()) { $popularBrands = $browseBrands; }
@endphp

<x-layouts.app.header>

{{-- Hero is full-width so the dots backdrop spans the entire page --}}
<x-home.hero />

<div class="mx-auto w-full max-w-[1550px] px-4 py-6 sm:px-6 sm:py-8 lg:px-8 lg:py-10">

    {{-- Trust strip --}}
    <div>
        <x-home.trust-strip />
    </div>

    {{-- Category quick-nav --}}
    <div class="mt-8">
        <x-home.category-tiles />
    </div>

    {{-- Brand rows - all wired to the synced catalog (one card per brand_key).
         Each subcategory row's `href` deep-links to the filtered gift-cards
         page so "View all" goes straight to that category's full brand list. --}}
    @php
        // 5 columns on desktop (lg) for larger, less cramped cards. Each row's
        // brand list is capped at 5 above so the desktop row stays clean.
        $renderRow = [
            ['title' => 'Popular Gift Cards',      'subtitle' => 'Top-rated gift cards across categories',         'cols' => 5, 'carousel' => true, 'brands' => $popularBrands],
            ['title' => 'Gaming & Entertainment',  'subtitle' => 'Game credit, console codes and streaming passes', 'cols' => 5, 'carousel' => true, 'brands' => $brandPicksBySubcategory('gaming-entertainment', 20), 'href' => route('shop.gift-cards', ['subcategory' => 'gaming-entertainment'])],
            ['title' => 'Digital Apps',            'subtitle' => 'App store credit and subscription gift cards',    'cols' => 5, 'carousel' => true, 'brands' => $brandPicksBySubcategory('digital-apps', 20),         'href' => route('shop.gift-cards', ['subcategory' => 'digital-apps'])],
            ['title' => 'Clothing & Accessories',  'subtitle' => 'Fashion and lifestyle gift cards',                'cols' => 5, 'carousel' => true, 'brands' => $brandPicksBySubcategory('clothing-accessories', 20), 'href' => route('shop.gift-cards', ['subcategory' => 'clothing-accessories'])],
            ['title' => 'Food & Beverage',         'subtitle' => 'Cafes, restaurants and grocery gift cards',       'cols' => 5, 'carousel' => true, 'brands' => $brandPicksBySubcategory('food-beverage', 20),        'href' => route('shop.gift-cards', ['subcategory' => 'food-beverage'])],
        ];
    @endphp

    @foreach ($renderRow as $row)
        @if ($row['brands']->isNotEmpty())
            <div class="mt-12">
                <x-home.brand-row
                    :title="$row['title']"
                    :subtitle="$row['subtitle']"
                    :view-all-href="$row['href'] ?? route('shop.gift-cards')"
                    :cols="$row['cols']"
                    :carousel="$row['carousel'] ?? false"
                >
                    @foreach ($row['brands'] as $p)
                        @php
                            $logo       = Product::brandLogoUrl($p->brand_key, $p->logo_url);
                            $label      = Product::brandDisplayName($p->brand_key);
                            $brandColor = Product::brandColor($p->brand_key, $p->brand_color);
                        @endphp
                        <x-home.brand-card
                            :name="$label"
                            :price-range="$p->priceRangeLabel()"
                            :href="route('shop.brand', ['brandSlug' => Product::brandSlug($p->brand_key)])"
                            :card-class="$logo ? 'bg-[#ffffff]' : ($brandColor ? '' : 'bg-zinc-100')"
                            :style="! $logo && $brandColor ? 'background-color: ' . $brandColor . ';' : false"
                        >
                            @if ($logo)
                                <img src="{{ $logo }}" alt="{{ $label }} gift card" class="h-full w-full object-cover" loading="lazy">
                            @else
                                <span class="px-3 text-center text-xl font-black uppercase leading-tight tracking-tight sm:text-2xl {{ $brandColor ? 'text-white' : 'text-zinc-700' }}">{{ $label }}</span>
                            @endif
                        </x-home.brand-card>
                    @endforeach
                </x-home.brand-row>
            </div>
        @endif
    @endforeach

    {{-- How it works --}}
    <div class="mt-12">
        <x-home.how-it-works />
    </div>

    {{-- eSIMs, flights and stays --}}
    <div class="mt-12">
        <x-home.explore-row />
    </div>

    {{-- Services promo with ambient video bg + referral CTA --}}
    <div class="mt-12">
        <x-home.services-promo />
    </div>

</div>

{{-- Customer reviews — full-bleed so the scroll row spans the entire page --}}
<div class="mt-12 mb-10">
    <x-home.customer-reviews />
</div>

</x-layouts.app.header>
