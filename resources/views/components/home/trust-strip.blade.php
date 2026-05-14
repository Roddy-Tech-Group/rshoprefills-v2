{{-- Trust signals strip. 4 columns on desktop, 2x2 on mobile.
     Each icon is loaded as a file so it renders in its original colours. --}}
<section data-reveal class="rounded-2xl bg-white p-5 ring-1 ring-zinc-200 sm:p-6" aria-label="Why shop with RshopRefills">
    <ul class="grid grid-cols-2 gap-5 sm:grid-cols-4 sm:gap-6">

        {{-- Best Prices --}}
        <li class="flex items-center gap-3">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50 ring-1 ring-blue-100">
                <img src="{{ asset('assets/' . rawurlencode('best prices.svg')) }}" alt="" class="h-6 w-6 object-contain" loading="lazy">
            </span>
            <div class="min-w-0 leading-tight">
                <p class="truncate text-base font-semibold text-zinc-900">Best Prices</p>
                <p class="truncate text-base text-zinc-500">Competitive rates</p>
            </div>
        </li>

        {{-- Wide Selection --}}
        <li class="flex items-center gap-3">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50 ring-1 ring-blue-100">
                <img src="{{ asset('assets/' . rawurlencode('wide selection.svg')) }}" alt="" class="h-6 w-6 object-contain" loading="lazy">
            </span>
            <div class="min-w-0 leading-tight">
                <p class="truncate text-base font-semibold text-zinc-900">Wide Selection</p>
                <p class="truncate text-base text-zinc-500">1000+ products</p>
            </div>
        </li>

        {{-- Trusted by Millions --}}
        <li class="flex items-center gap-3">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50 ring-1 ring-blue-100">
                <img src="{{ asset('assets/' . rawurlencode('trusted by millions.svg')) }}" alt="" class="h-6 w-6 object-contain" loading="lazy">
            </span>
            <div class="min-w-0 leading-tight">
                <p class="truncate text-base font-semibold text-zinc-900">Trusted by Thousands</p>
                <p class="truncate text-base text-zinc-500">Join our global community</p>
            </div>
        </li>

        {{-- Easy & Fast --}}
        <li class="flex items-center gap-3">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50 ring-1 ring-blue-100">
                <img src="{{ asset('assets/fast.png') }}" alt="" class="h-6 w-6 object-contain" loading="lazy">
            </span>
            <div class="min-w-0 leading-tight">
                <p class="truncate text-base font-semibold text-zinc-900">Easy & Fast</p>
                <p class="truncate text-base text-zinc-500">Simple 3-step checkout</p>
            </div>
        </li>

    </ul>
</section>
