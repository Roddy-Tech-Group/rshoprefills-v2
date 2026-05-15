@php
    use App\Models\Category;
    use App\Models\Product;
    use Illuminate\Support\Facades\DB;

    $giftCardsCategory = Category::where('slug', 'gift-cards')->first();

    /*
     * Returns a collection of representative Products — one per brand_key — for a
     * home-page row. `$flag` optionally scopes to is_popular / is_featured.
     */
    $brandPicks = function (?string $flag = null, int $limit = 6) use ($giftCardsCategory) {
        $base = Product::query()
            ->where('is_active', true)
            ->whereNotNull('brand_key')
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

    // "Popular Gift Cards" is a hand-curated brand list — order matters.
    $popularKeys = ['Apple', 'Xbox', 'Steam', 'GooglePlay', 'Amazon', 'Playstation', 'Netflix'];
    $popularIdByKey = Product::query()
        ->where('is_active', true)
        ->whereIn('brand_key', $popularKeys)
        ->select('brand_key', DB::raw('MIN(id) as id'))
        ->groupBy('brand_key')
        ->pluck('id');
    $popularBrands = Product::query()
        ->whereIn('id', $popularIdByKey)
        ->with('variants:id,product_id,retail_price,is_variable,is_available')
        ->get()
        ->sortBy(fn ($p) => array_search($p->brand_key, $popularKeys))
        ->values();

    $featuredBrands = $brandPicks('is_featured', 6);
    $browseBrands   = $brandPicks(null, 6);

    // Fallbacks so a row never renders empty if the catalog has no popular/featured flags.
    if ($featuredBrands->isEmpty()) { $featuredBrands = $browseBrands; }
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

    {{-- Brand rows — all wired to the synced catalog (one card per brand_key). --}}
    @php
        $renderRow = [
            ['title' => 'Popular Gift Cards',  'subtitle' => 'Top-rated gift cards across categories', 'cols' => 7, 'brands' => $popularBrands],
            ['title' => 'Featured Gift Cards', 'subtitle' => 'Hand-picked brands worth checking out',    'cols' => 6, 'brands' => $featuredBrands],
            ['title' => 'Browse Gift Cards',   'subtitle' => 'More brands from our catalog',             'cols' => 6, 'brands' => $browseBrands],
        ];
    @endphp

    @foreach ($renderRow as $row)
        @if ($row['brands']->isNotEmpty())
            <div class="mt-12">
                <x-home.brand-row
                    :title="$row['title']"
                    :subtitle="$row['subtitle']"
                    :view-all-href="route('shop.gift-cards')"
                    :cols="$row['cols']"
                >
                    @foreach ($row['brands'] as $p)
                        @php
                            $logo  = Product::brandLogoUrl($p->brand_key, $p->logo_url);
                            $label = Product::brandDisplayName($p->brand_key);
                        @endphp
                        <x-home.brand-card
                            :name="$label"
                            :price-range="$p->priceRangeLabel()"
                            :href="route('shop.brand', ['brandSlug' => Product::brandSlug($p->brand_key)])"
                            card-class="bg-white"
                        >
                            @if ($logo)
                                <img src="{{ $logo }}" alt="{{ $label }} gift card" class="h-full w-full object-cover" loading="lazy">
                            @else
                                <span class="text-xl font-black uppercase tracking-tight text-zinc-700">{{ str($label)->substr(0, 2)->upper() }}</span>
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

    {{-- Customer reviews --}}
    <div class="mt-12 mb-4">
        <x-home.customer-reviews />
    </div>

</div>

</x-layouts.app.header>
