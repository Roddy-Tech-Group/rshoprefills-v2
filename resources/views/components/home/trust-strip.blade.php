{{-- Trust signals strip. 4 separate cards.
     Mobile: 2 columns x 2 rows. Desktop (sm+): 4 columns x 1 row.
     Each icon is loaded as a file so it renders in its original colours.
     The "Wide Selection" tile reads the live product-variant count from the
     catalog (cached 10 min) so the marketing number tracks the real DB. --}}
@php
    $catalogVariantCount = \Illuminate\Support\Facades\Cache::remember(
        'trust_strip.variant_count',
        now()->addMinutes(10),
        fn () => (int) \App\Models\ProductVariant::where('is_available', true)->count(),
    );
    // Round down to the nearest hundred so the public number reads cleanly
    // even as the catalog grows by tens at a time, then floor at 100 so the
    // tile never reads "0+" on an empty/seeded environment.
    $catalogVariantsRounded = max(100, intdiv($catalogVariantCount, 100) * 100);
@endphp
<section data-reveal aria-label="Why shop with RshopRefills">
    <ul class="grid grid-cols-2 gap-3 sm:grid-cols-4 sm:gap-5">

        {{-- Best Prices --}}
        <li class="flex min-h-[110px] items-center gap-3 rounded-[10px] bg-white p-4 shadow-sm shadow-zinc-900/5 ring-1 ring-zinc-300 transition-transform duration-200 hover:-translate-y-0.5 sm:min-h-0 sm:p-5">
            <img src="{{ asset('assets/' . rawurlencode('best prices.svg')) }}" alt="" class="h-7 w-7 shrink-0 object-contain" loading="lazy">
            <div class="min-w-0 leading-tight">
                <p class="text-base font-semibold text-zinc-900">Best Prices</p>
                <p class="text-sm text-zinc-600">Competitive rates</p>
            </div>
        </li>

        {{-- Wide Selection --}}
        <li class="flex min-h-[110px] items-center gap-3 rounded-[10px] bg-white p-4 shadow-sm shadow-zinc-900/5 ring-1 ring-zinc-300 transition-transform duration-200 hover:-translate-y-0.5 sm:min-h-0 sm:p-5">
            <img src="{{ asset('assets/' . rawurlencode('wide selection.svg')) }}" alt="" class="h-7 w-7 shrink-0 object-contain" loading="lazy">
            <div class="min-w-0 leading-tight">
                <p class="text-base font-semibold text-zinc-900">Wide Selection</p>
                <p class="text-sm text-zinc-600">{{ number_format($catalogVariantsRounded) }}+ products</p>
            </div>
        </li>

        {{-- Trusted by Thousands --}}
        <li class="flex min-h-[110px] items-center gap-3 rounded-[10px] bg-white p-4 shadow-sm shadow-zinc-900/5 ring-1 ring-zinc-300 transition-transform duration-200 hover:-translate-y-0.5 sm:min-h-0 sm:p-5">
            <img src="{{ asset('assets/' . rawurlencode('trusted by millions.svg')) }}" alt="" class="h-7 w-7 shrink-0 object-contain" loading="lazy">
            <div class="min-w-0 leading-tight">
                <p class="text-base font-semibold text-zinc-900">Trusted by Thousands</p>
                <p class="text-sm text-zinc-600">Join our global community</p>
            </div>
        </li>

        {{-- Easy & Fast --}}
        <li class="flex min-h-[110px] items-center gap-3 rounded-[10px] bg-white p-4 shadow-sm shadow-zinc-900/5 ring-1 ring-zinc-300 transition-transform duration-200 hover:-translate-y-0.5 sm:min-h-0 sm:p-5">
            <img src="{{ asset('assets/' . rawurlencode('Easy & Fast.webp')) }}" alt="" class="h-7 w-7 shrink-0 object-contain brightness-0 dark:invert" loading="lazy">
            <div class="min-w-0 leading-tight">
                <p class="text-base font-semibold text-zinc-900">Easy & Fast</p>
                <p class="text-sm text-zinc-600">Simple 3-step checkout</p>
            </div>
        </li>

    </ul>
</section>
