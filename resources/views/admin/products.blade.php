@php
    use App\Models\Product;
    use App\Models\Category;

    $search = request()->query('q', '');
    $categorySlug = request()->query('category', 'all');
    $perPage = 25;

    // Pills: real categories from DB + an "All" sentinel that maps to no filter.
    $categoryPills = collect([['name' => 'All', 'slug' => 'all']])
        ->merge(Category::orderBy('sort_order')->get(['name', 'slug'])->toArray());

    // Search resolves against the product name AND country. We accept ISO-2 codes
    // ("US", "GB") directly, plus partial country names ("United Sta…", "Cameroon")
    // which we translate to ISO codes via the config map so they hit country_code.
    $matchedCountryCodes = [];
    if ($search !== '') {
        $needle = mb_strtolower(trim($search));
        foreach (config('countries.codes', []) as $name => $code) {
            if ($needle !== '' && str_contains(mb_strtolower($name), $needle)) {
                $matchedCountryCodes[] = strtoupper($code);
            }
        }
        // Allow direct ISO-2 / short-prefix matches (e.g. "us", "g" → countries starting with G).
        if (mb_strlen($needle) >= 1) {
            $matchedCountryCodes[] = strtoupper($needle);
        }
        $matchedCountryCodes = array_values(array_unique(array_filter($matchedCountryCodes)));
    }

    $products = Product::query()
        ->with(['category:id,name,slug', 'subcategory:id,name', 'variants:id,product_id,currency,retail_price,cost_price,face_value,min_amount,max_amount,is_variable,is_available'])
        ->when($search !== '', function ($q) use ($search, $matchedCountryCodes) {
            $q->where(function ($qq) use ($search, $matchedCountryCodes) {
                $qq->where('name', 'like', "%{$search}%")
                    ->orWhere('country_code', 'like', strtoupper($search) . '%');
                if (! empty($matchedCountryCodes)) {
                    $qq->orWhereIn('country_code', $matchedCountryCodes);
                }
            });
        })
        ->when($categorySlug !== 'all', fn ($q) => $q->whereHas('category', fn ($qq) => $qq->where('slug', $categorySlug)))
        ->latest('id')
        ->paginate($perPage)
        ->withQueryString();

    $stats = [
        'total'    => Product::count(),
        'active'   => Product::where('is_active', true)->count(),
        'featured' => Product::where('is_featured', true)->count(),
        'popular'  => Product::where('is_popular', true)->count(),
    ];
@endphp

<x-layouts.admin>
    <x-slot:heading>All Products</x-slot:heading>
    <x-slot:subheading>{{ number_format($stats['total']) }} products synced. Manage status, featured and popular flags across the catalog.</x-slot:subheading>

    <div class="flex flex-1 flex-col gap-6">

        {{-- KPI strip — quiet, scannable. Tints sit on the dot indicator, not the whole pill. --}}
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            @foreach ([
                ['label' => 'Total',    'value' => $stats['total'],    'dot' => 'bg-blue-500'],
                ['label' => 'Active',   'value' => $stats['active'],   'dot' => 'bg-emerald-500'],
                ['label' => 'Featured', 'value' => $stats['featured'], 'dot' => 'bg-amber-500'],
                ['label' => 'Popular',  'value' => $stats['popular'],  'dot' => 'bg-fuchsia-500'],
            ] as $stat)
                <div class="rounded-2xl bg-white p-4 shadow-sm shadow-zinc-900/[0.04] ring-1 ring-zinc-100">
                    <p class="flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-wider text-zinc-600">
                        <span class="inline-block h-1.5 w-1.5 rounded-full {{ $stat['dot'] }}"></span>
                        {{ $stat['label'] }}
                    </p>
                    <p class="mt-2 text-3xl font-bold tracking-tight text-zinc-900">{{ number_format($stat['value']) }}</p>
                </div>
            @endforeach
        </div>

        {{-- Search + Add row --}}
        <form method="GET" action="{{ route('admin.products') }}" class="flex flex-col items-stretch gap-3 sm:flex-row sm:items-center">
            <div class="relative flex-1">
                <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-zinc-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input
                    type="search"
                    name="q"
                    value="{{ $search }}"
                    placeholder="Search products by name or country (e.g. Cameroon, US)"
                    class="w-full rounded-xl border border-zinc-200 bg-white py-2.5 pl-10 pr-3 text-sm text-zinc-900 outline-none transition-colors focus:border-blue-500 focus:ring-2 focus:ring-blue-500/15"
                />
                @if ($categorySlug !== 'all')
                    <input type="hidden" name="category" value="{{ $categorySlug }}">
                @endif
            </div>
            <button type="button" class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-blue-700">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/>
                </svg>
                Add product
            </button>
        </form>

        {{-- Category filter pills, server-routed via query string. Hidden on mobile to remove the horizontal-slide bar
             (filtering on mobile can be added later as a dropdown if you want it back). --}}
        <div class="-mx-1 hidden overflow-x-auto px-1 py-1 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden md:block">
            <div class="flex w-max items-center gap-2">
                @foreach ($categoryPills as $pill)
                    @php
                        $isActive = $categorySlug === $pill['slug'];
                        $href = route('admin.products', array_filter([
                            'category' => $pill['slug'] === 'all' ? null : $pill['slug'],
                            'q' => $search ?: null,
                        ]));
                    @endphp
                    <a
                        href="{{ $href }}"
                        wire:navigate
                        class="inline-flex items-center rounded-full px-5 py-2 text-sm font-semibold ring-1 transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/40 {{ $isActive ? 'bg-zinc-900 text-white ring-zinc-900' : 'bg-white text-zinc-800 ring-zinc-200 hover:bg-zinc-50' }}"
                    >
                        {{ $pill['name'] }}
                    </a>
                @endforeach
            </div>
        </div>

        {{-- Product table.
             Skeleton rows are layered under the real table and shown during wire:navigate
             page transitions (livewire:navigating fires on link click, ends on navigated).
             This relies on the global `wire:navigate` flow that's already in the project. --}}
        <div
            x-data="{ navigating: false }"
            x-on:livewire:navigate.window="navigating = true"
            x-on:livewire:navigated.window="navigating = false"
            class="relative overflow-hidden rounded-[20px] bg-white shadow-sm shadow-zinc-900/5 ring-1 ring-zinc-100"
        >
            {{-- Skeleton overlay shown while navigating between paginator pages or filtered URLs. --}}
            <div x-show="navigating" x-cloak class="absolute inset-0 z-10 flex flex-col bg-white" aria-hidden="true">
                <div class="grid grid-cols-7 gap-3 bg-zinc-50 px-5 py-3 text-[11px] font-semibold uppercase tracking-wider text-zinc-600">
                    <span>Product</span><span>Category</span><span>Country</span><span>Variants</span><span>Price range</span><span>Status</span><span class="text-right">Actions</span>
                </div>
                <div class="skeleton-stagger divide-y divide-zinc-100">
                    @for ($i = 0; $i < 8; $i++)
                        <div class="grid grid-cols-7 items-center gap-3 px-5 py-3.5" style="--i: {{ $i }}">
                            <span class="flex items-center gap-3">
                                <x-skeleton shape="rect" w="h-9" h="w-9" rounded="rounded-lg" />
                                <span class="flex flex-col gap-1.5">
                                    <x-skeleton shape="line" class="h-3 w-32" />
                                    <x-skeleton shape="line" class="h-2.5 w-20" />
                                </span>
                            </span>
                            <x-skeleton shape="line" class="h-3 w-20" />
                            <x-skeleton shape="line" class="h-3 w-8" />
                            <x-skeleton shape="line" class="h-3 w-12" />
                            <x-skeleton shape="line" class="h-3 w-24" />
                            <x-skeleton shape="line" class="h-4 w-16 rounded-[5px]" />
                            <x-skeleton shape="line" class="h-3 w-10 justify-self-end" />
                        </div>
                    @endfor
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="border-b border-zinc-100 bg-zinc-50/60">
                        <tr class="text-left text-[10px] font-semibold uppercase tracking-[0.1em] text-zinc-600">
                            <th class="px-5 py-3 font-semibold">Product</th>
                            <th class="px-5 py-3 font-semibold">Category</th>
                            <th class="px-5 py-3 font-semibold">Country</th>
                            <th class="px-5 py-3 font-semibold">Variants</th>
                            <th class="px-5 py-3 font-semibold">Price range</th>
                            <th class="px-5 py-3 font-semibold">Status</th>
                            <th class="px-5 py-3 text-right font-semibold">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100/80">
                        @forelse ($products as $product)
                            @php
                                $variants = $product->variants;
                                $available = $variants->where('is_available', true)->count();
                                $prices = $variants->pluck('retail_price')->filter()->all();
                                $minPrice = ! empty($prices) ? min($prices) : null;
                                $maxPrice = ! empty($prices) ? max($prices) : null;
                                $currency = $product->currency_code ?: 'USD';
                            @endphp
                            <tr class="transition-colors duration-150 hover:bg-blue-50/40">
                                <td class="px-5 py-3.5">
                                    <div class="flex items-center gap-3">
                                        @if ($product->logo_url)
                                            <img src="{{ $product->logo_url }}" alt="" class="h-10 w-10 shrink-0 rounded-xl object-contain bg-white ring-1 ring-zinc-100" loading="lazy">
                                        @else
                                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-xs font-bold uppercase tracking-tight text-blue-700 ring-1 ring-blue-100">
                                                {{ str($product->name)->substr(0, 2)->upper() }}
                                            </span>
                                        @endif
                                        <div class="min-w-0 leading-tight">
                                            <p class="truncate text-sm font-semibold text-zinc-900">{{ $product->name }}</p>
                                            <p class="mt-0.5 truncate text-[11px] text-zinc-600">{{ $product->subcategory?->name ?? '—' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-3.5 text-sm text-zinc-700">{{ $product->category?->name ?? '—' }}</td>
                                <td class="px-5 py-3.5">
                                    <span class="inline-flex items-center rounded-md bg-zinc-100 px-2 py-0.5 font-mono text-[11px] font-semibold tracking-wider text-zinc-700">{{ $product->country_code ?? '—' }}</span>
                                </td>
                                <td class="px-5 py-3.5 text-sm text-zinc-700">
                                    <span class="font-semibold text-zinc-900 tabular-nums">{{ $variants->count() }}</span>
                                    <span class="text-zinc-600">/ {{ $available }} live</span>
                                </td>
                                <td class="px-5 py-3.5 whitespace-nowrap text-sm">
                                    @if ($minPrice !== null)
                                        <span class="font-semibold tabular-nums text-zinc-900">{{ $currency }} {{ number_format($minPrice, 2) }}</span>
                                        @if ($maxPrice !== null && $maxPrice > $minPrice)
                                            <span class="text-zinc-600">–</span>
                                            <span class="font-semibold tabular-nums text-zinc-700">{{ number_format($maxPrice, 2) }}</span>
                                        @endif
                                    @else
                                        <span class="text-zinc-600">—</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3.5">
                                    <div class="flex flex-wrap gap-1">
                                        @if ($product->is_active)
                                            <span class="rounded-[5px] bg-emerald-500 px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-white">Active</span>
                                        @else
                                            <span class="rounded-[5px] bg-zinc-400 px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-white">Inactive</span>
                                        @endif
                                        @if ($product->is_featured)
                                            <span class="rounded-[5px] bg-amber-500 px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-white">Featured</span>
                                        @endif
                                        @if ($product->is_popular)
                                            <span class="rounded-[5px] bg-fuchsia-500 px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-white">Popular</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-5 py-3.5 text-right">
                                    <a href="#" class="inline-flex items-center gap-1 text-xs font-semibold text-blue-600 transition-colors hover:text-blue-700">
                                        Edit
                                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
                                        </svg>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-5 py-20 text-center">
                                    <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-blue-50 text-blue-600">
                                        <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                        </svg>
                                    </span>
                                    <p class="mt-4 text-base font-semibold text-zinc-900">No products match these filters</p>
                                    <p class="mt-1 text-sm text-zinc-600">Try clearing the search or picking a different category.</p>
                                    @if ($search !== '' || $categorySlug !== 'all')
                                        <a href="{{ route('admin.products') }}" wire:navigate class="mt-4 inline-flex items-center gap-1.5 text-sm font-semibold text-blue-600 hover:text-blue-700">
                                            Clear all filters
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($products->hasPages())
                <div class="border-t border-zinc-100 px-5 py-3">
                    {{ $products->onEachSide(1)->links('vendor.pagination.circles') }}
                </div>
            @endif
        </div>

    </div>
</x-layouts.admin>
