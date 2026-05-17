@php
    use App\Domain\Cart\Services\CartPricingService;
    use App\Models\Category;
    use App\Models\Product;

    // eSIM storefront listing — one card per coverage region (a Product in the
    // `esims` category). Each region's detail page lists its data plans (variants).
    $search = trim((string) request()->query('q', ''));

    $esimCategory = Category::where('slug', 'esims')->first();

    $regions = Product::query()
        ->where('is_active', true)
        ->when($esimCategory, fn ($q) => $q->where('category_id', $esimCategory->id))
        ->when($search !== '', fn ($q) => $q->where('name', 'like', "%{$search}%"))
        ->with(['variants' => fn ($q) => $q->where('is_available', true)])
        ->orderBy('name')
        ->get();

    $pricing = app(CartPricingService::class);

    // "From" price for a region = its cheapest available plan, marked up (USD).
    // The markup engine is monotonic, so the lowest cost is also the lowest retail.
    $fromPrice = function (Product $region) use ($pricing): ?float {
        $cheapest = $region->variants->sortBy('cost_price')->first();
        if (! $cheapest) {
            return null;
        }
        $cheapest->setRelation('product', $region);

        return (float) $pricing->calculatePricing($cheapest, 1)['unit_price_snapshot'];
    };

    // "United States Data eSIM" -> "United States" for a cleaner card label.
    $shortName = fn (string $name) => (string) str($name)->replaceLast(' Data eSIM', '')->trim();
@endphp

<x-layouts.app.header :title="'eSIMs | RshopRefills'">

    <section class="min-h-full bg-zinc-100">
        <div class="mx-auto w-full max-w-[1550px] px-4 py-8 sm:px-6 lg:px-8">

            {{-- Hero --}}
            <div class="flex flex-col items-center gap-6 rounded-3xl bg-white p-6 ring-1 ring-zinc-200 shadow-sm sm:flex-row sm:justify-between sm:p-8">
                <div class="min-w-0 text-center sm:text-left">
                    <h1 class="text-2xl font-bold tracking-tight text-zinc-900 sm:text-3xl">eSIMs</h1>
                    <p class="mt-1.5 max-w-lg text-sm text-zinc-600 sm:text-base">
                        Stay connected in 190+ countries. Instant activation, no physical SIM. Scan a QR code and you are online.
                    </p>

                    {{-- Region search --}}
                    <form method="GET" action="{{ route('shop.esims') }}" class="mt-4">
                        <div class="relative mx-auto max-w-sm sm:mx-0">
                            <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-zinc-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            <input
                                type="text"
                                name="q"
                                value="{{ $search }}"
                                placeholder="Search a country or region"
                                class="w-full rounded-xl border border-zinc-300 bg-white py-2.5 pl-10 pr-3 text-sm text-zinc-900 placeholder:text-zinc-500 outline-none transition-colors focus:border-blue-500 focus:ring-2 focus:ring-blue-500/15"
                            >
                        </div>
                    </form>
                </div>

                {{-- Connectivity pulse --}}
                <div class="relative flex h-28 w-28 shrink-0 items-center justify-center" aria-hidden="true">
                    <span class="signal-ring absolute inset-0 rounded-full border-2 border-blue-400/50"></span>
                    <span class="signal-ring absolute inset-0 rounded-full border-2 border-blue-400/50"></span>
                    <span class="signal-ring absolute inset-0 rounded-full border-2 border-blue-400/50"></span>
                    <span class="relative flex h-16 w-16 items-center justify-center rounded-full bg-blue-600 shadow-lg shadow-blue-600/30">
                        <svg class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9 9 0 100-18 9 9 0 000 18zm0 0c2.5 0 4.5-4.03 4.5-9S14.5 3 12 3 7.5 7.03 7.5 12s2 9 4.5 9zM3.6 9h16.8M3.6 15h16.8"/>
                        </svg>
                    </span>
                </div>
            </div>

            {{-- Region grid --}}
            @if ($regions->isNotEmpty())
                <ul class="mt-8 grid grid-cols-2 gap-x-4 gap-y-5 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
                    @foreach ($regions as $region)
                        @php
                            $planCount = $region->variants->count();
                            $fp        = $fromPrice($region);
                            $flag      = Product::flagUrl($region->country_code);
                        @endphp
                        <li>
                            <a
                                href="{{ route('shop.esim', $region->slug) }}"
                                wire:navigate
                                class="group block h-full focus:outline-none"
                                aria-label="{{ $shortName($region->name) }} eSIM"
                            >
                                <div class="flex h-full flex-col rounded-2xl bg-white p-5 ring-1 ring-zinc-200 shadow-sm transition-all duration-200 group-hover:-translate-y-1 group-hover:shadow-md group-hover:ring-zinc-300 group-focus-visible:ring-2 group-focus-visible:ring-blue-500/40">
                                    <div class="flex items-start justify-between gap-2">
                                        {{-- Flag, or a globe for regional/global eSIMs --}}
                                        <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-blue-50">
                                            @if ($flag)
                                                <img src="{{ $flag }}" alt="" class="h-7 w-10 rounded-[3px] object-cover ring-1 ring-zinc-200" loading="lazy">
                                            @else
                                                <svg class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9 9 0 100-18 9 9 0 000 18zm0 0c2.5 0 4.5-4.03 4.5-9S14.5 3 12 3 7.5 7.03 7.5 12s2 9 4.5 9zM3.6 9h16.8M3.6 15h16.8"/>
                                                </svg>
                                            @endif
                                        </span>
                                        {{-- Signal-strength glyph --}}
                                        <span class="flex items-end gap-0.5" aria-hidden="true">
                                            <span class="h-1.5 w-1 rounded-sm bg-blue-300"></span>
                                            <span class="h-2.5 w-1 rounded-sm bg-blue-400"></span>
                                            <span class="h-3.5 w-1 rounded-sm bg-blue-600"></span>
                                        </span>
                                    </div>

                                    <p class="mt-4 truncate text-sm font-bold text-zinc-900 transition-colors group-hover:text-blue-700">
                                        {{ $shortName($region->name) }}
                                    </p>

                                    <div class="mt-1 flex items-center justify-between gap-2">
                                        <span class="text-xs text-zinc-600">{{ $planCount }} {{ $planCount === 1 ? 'plan' : 'plans' }}</span>
                                        @if ($fp !== null)
                                            <span class="text-xs font-bold text-zinc-900">from ${{ number_format($fp, 2) }}</span>
                                        @endif
                                    </div>
                                </div>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @else
                {{-- Empty state --}}
                <div class="mt-8 rounded-3xl bg-white px-6 py-20 text-center ring-1 ring-zinc-200">
                    <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-blue-50 text-blue-600">
                        <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9 9 0 100-18 9 9 0 000 18zm0 0c2.5 0 4.5-4.03 4.5-9S14.5 3 12 3 7.5 7.03 7.5 12s2 9 4.5 9zM3.6 9h16.8M3.6 15h16.8"/>
                        </svg>
                    </span>
                    @if ($search !== '')
                        <p class="mt-4 text-base font-semibold text-zinc-900">No eSIM regions match "{{ $search }}"</p>
                        <p class="mt-1 text-sm text-zinc-600">Try a different country or region name.</p>
                        <a href="{{ route('shop.esims') }}" wire:navigate class="mt-4 inline-flex items-center gap-1.5 text-sm font-semibold text-blue-600 hover:text-blue-700">
                            Clear search
                        </a>
                    @else
                        <p class="mt-4 text-base font-semibold text-zinc-900">No eSIM coverage regions yet</p>
                        <p class="mt-1 text-sm text-zinc-600">The eSIM catalog has not been synced. Run the Zendit eSIM sync from the admin products page.</p>
                    @endif
                </div>
            @endif

        </div>
    </section>

</x-layouts.app.header>
