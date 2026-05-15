{{-- Trust signals strip. 4 separate cards.
     Mobile: 2 columns x 2 rows. Desktop (sm+): 4 columns x 1 row.
     Each icon is loaded as a file so it renders in its original colours. --}}
<section data-reveal aria-label="Why shop with RshopRefills">
    <ul class="grid grid-cols-2 gap-3 sm:grid-cols-4 sm:gap-5">

        {{-- Best Prices --}}
        <li class="flex min-h-[110px] items-center gap-3 rounded-2xl bg-white p-4 shadow-sm shadow-zinc-900/5 ring-1 ring-zinc-300 transition-transform duration-200 hover:-translate-y-0.5 sm:min-h-0 sm:p-5">
            <img src="{{ asset('assets/' . rawurlencode('best prices.svg')) }}" alt="" class="h-7 w-7 shrink-0 object-contain" loading="lazy">
            <div class="min-w-0 leading-tight">
                <p class="text-base font-semibold text-zinc-900">Best Prices</p>
                <p class="text-sm text-zinc-600">Competitive rates</p>
            </div>
        </li>

        {{-- Wide Selection --}}
        <li class="flex min-h-[110px] items-center gap-3 rounded-2xl bg-white p-4 shadow-sm shadow-zinc-900/5 ring-1 ring-zinc-300 transition-transform duration-200 hover:-translate-y-0.5 sm:min-h-0 sm:p-5">
            <img src="{{ asset('assets/' . rawurlencode('wide selection.svg')) }}" alt="" class="h-7 w-7 shrink-0 object-contain" loading="lazy">
            <div class="min-w-0 leading-tight">
                <p class="text-base font-semibold text-zinc-900">Wide Selection</p>
                <p class="text-sm text-zinc-600">14000+ products</p>
            </div>
        </li>

        {{-- Trusted by Thousands --}}
        <li class="flex min-h-[110px] items-center gap-3 rounded-2xl bg-white p-4 shadow-sm shadow-zinc-900/5 ring-1 ring-zinc-300 transition-transform duration-200 hover:-translate-y-0.5 sm:min-h-0 sm:p-5">
            <img src="{{ asset('assets/' . rawurlencode('trusted by millions.svg')) }}" alt="" class="h-7 w-7 shrink-0 object-contain" loading="lazy">
            <div class="min-w-0 leading-tight">
                <p class="text-base font-semibold text-zinc-900">Trusted by Thousands</p>
                <p class="text-sm text-zinc-600">Join our global community</p>
            </div>
        </li>

        {{-- Easy & Fast --}}
        <li class="flex min-h-[110px] items-center gap-3 rounded-2xl bg-white p-4 shadow-sm shadow-zinc-900/5 ring-1 ring-zinc-300 transition-transform duration-200 hover:-translate-y-0.5 sm:min-h-0 sm:p-5">
            <img src="{{ asset('assets/fast.png') }}" alt="" class="h-7 w-7 shrink-0 object-contain" loading="lazy">
            <div class="min-w-0 leading-tight">
                <p class="text-base font-semibold text-zinc-900">Easy & Fast</p>
                <p class="text-sm text-zinc-600">Simple 3-step checkout</p>
            </div>
        </li>

    </ul>
</section>
