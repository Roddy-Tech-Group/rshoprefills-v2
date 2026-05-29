@php
    use App\Models\Category;
    use App\Models\Subcategory;
    use Illuminate\Support\Facades\Cache;
    use Illuminate\Support\Facades\DB;

    // Region-aware: only show subcategories that actually have active gift
    // cards in the visitor's resolved region. $region is shared by the
    // ResolveRegion middleware. Cached per-region for 15 minutes so each
    // region builds its tile set once, then serves cached on subsequent hits.
    $region = strtoupper((string) ($region ?? 'US'));
    $cacheKey = 'home.gift_card_subcategories.'.$region;

    $giftCardCategories = Cache::remember($cacheKey, now()->addMinutes(15), function () use ($region) {
        $cat = Category::where('slug', 'gift-cards')->first();
        if (! $cat) {
            return collect();
        }

        return Subcategory::query()
            ->where('subcategories.category_id', $cat->id)
            ->select('subcategories.id', 'subcategories.name', 'subcategories.slug', DB::raw('COUNT(products.id) AS product_count'))
            ->join('products', function ($join) use ($region) {
                $join->on('products.subcategory_id', '=', 'subcategories.id')
                    ->where('products.is_active', '=', 1)
                    ->where('products.country_code', '=', $region);
            })
            ->groupBy('subcategories.id', 'subcategories.name', 'subcategories.slug')
            ->having('product_count', '>', 0)
            ->orderByDesc('product_count')
            ->limit(8)
            ->get();
    });

    // Lightweight icon glyphs per slug fragment - SVG paths chosen so the
    // category reads at a glance without sourcing external artwork per cat.
    // Anything that doesn't match falls back to a generic gift box.
    $categoryIcon = function (string $slug) {
        return match (true) {
            str_contains($slug, 'gaming')        => 'M6.5 6.5h11M6.5 12h11M6.5 17.5h11M3 9.5 9 4l1.5 1.5L4.5 11 3 9.5zm0 5L9 20l1.5-1.5L4.5 13 3 14.5z',
            str_contains($slug, 'clothing')      => 'M16 4H8L4 7l3 3v10h10V10l3-3-4-3zm-4 2v2',
            str_contains($slug, 'travel')        => 'M2 12l7-2 5-7v9l8 2-8 4v6l-5-3-7 3v-12z',
            str_contains($slug, 'restaurant')    => 'M6 2v6a2 2 0 002 2v12m0-22v6m4-4v6a2 2 0 01-2 2m4-8v20',
            str_contains($slug, 'food')          => 'M12 2a6 6 0 016 6v0a3 3 0 11-6 0v-2a4 4 0 00-4 4v12a2 2 0 002 2h8',
            str_contains($slug, 'home')          => 'M3 12l9-9 9 9M5 10v10h14V10',
            str_contains($slug, 'digital')       => 'M4 6h16v10H4zm6 14h4M8 20h8',
            str_contains($slug, 'health')        => 'M12 2a8 8 0 00-8 8c0 6 8 12 8 12s8-6 8-12a8 8 0 00-8-8z',
            str_contains($slug, 'electronics')   => 'M4 4h16v12H4zm6 16h4M2 20h20',
            str_contains($slug, 'supermarket')   => 'M3 6h18l-2 9H5L3 6zm0 0L2 3H0m7 18a2 2 0 11-4 0 2 2 0 014 0zm14 0a2 2 0 11-4 0 2 2 0 014 0z',
            str_contains($slug, 'sport')         => 'M12 2a10 10 0 100 20 10 10 0 000-20zm0 0v20M2 12h20',
            str_contains($slug, 'auto')          => 'M4 16V11l2-5h12l2 5v5m-16 0h16m-16 0v3h3v-3m13 0v3h-3v-3M7 12h1m8 0h1',
            default                              => 'M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zM20 12v9a1 1 0 01-1 1H5a1 1 0 01-1-1v-9m16-4H4a1 1 0 00-1 1v3a1 1 0 001 1h16a1 1 0 001-1V9a1 1 0 00-1-1z',
        };
    };

    // Brand-style colour gradient per slug, mostly for visual variety.
    // Cycled by index so it's deterministic per position on the page.
    $palette = [
        'from-blue-500 to-indigo-600',
        'from-emerald-500 to-teal-600',
        'from-amber-500 to-orange-600',
        'from-pink-500 to-rose-600',
        'from-purple-500 to-fuchsia-600',
        'from-cyan-500 to-sky-600',
        'from-red-500 to-orange-500',
        'from-violet-500 to-purple-600',
    ];
@endphp

@if ($giftCardCategories->isNotEmpty())
    <section aria-label="Shop gift cards by category">
        <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
            <div>
                <h2 class="text-2xl font-bold tracking-tight text-zinc-900 sm:text-3xl dark:text-white">Shop by category</h2>
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Browse {{ $giftCardCategories->sum('product_count') }}+ gift card brands in your region across {{ $giftCardCategories->count() }} {{ \Illuminate\Support\Str::plural('category', $giftCardCategories->count()) }}.</p>
            </div>
            <a href="{{ route('shop.gift-cards') }}" wire:navigate class="inline-flex items-center gap-1.5 text-sm font-semibold text-blue-700 hover:text-blue-800 dark:text-blue-300 dark:hover:text-blue-200">
                All categories
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
            </a>
        </div>

        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
            @foreach ($giftCardCategories as $i => $sub)
                @php $gradient = $palette[$i % count($palette)]; @endphp
                <a
                    href="{{ route('shop.gift-cards', ['subcategory' => $sub->slug]) }}"
                    wire:navigate
                    class="group relative flex h-28 flex-col justify-between overflow-hidden rounded-[10px] bg-gradient-to-br {{ $gradient }} p-4 text-white shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-lg"
                >
                    {{-- Decorative oversized icon at bottom-right --}}
                    <svg class="pointer-events-none absolute -bottom-3 -right-3 h-20 w-20 opacity-20 transition-transform duration-300 group-hover:scale-110" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $categoryIcon($sub->slug) }}"/>
                    </svg>

                    <div class="relative">
                        <p class="text-[10px] font-bold uppercase tracking-wider opacity-80">Gift cards</p>
                    </div>
                    <div class="relative">
                        <p class="truncate text-base font-bold leading-tight">{{ $sub->name }}</p>
                        <p class="mt-0.5 text-xs opacity-80">{{ $sub->product_count }} brands</p>
                    </div>
                </a>
            @endforeach
        </div>
    </section>
@endif
