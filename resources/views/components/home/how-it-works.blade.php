{{--
    How it works. 3-step buying process: Pick a product, Pay with crypto, Receive instantly.
    Mobile: horizontal scroll carousel. Desktop (sm+): 3 cards side-by-side.
--}}
<section data-reveal aria-label="How it works">

    <h2 class="mb-5 text-xl font-bold text-zinc-900 sm:text-2xl">How it works</h2>

    {{-- Mobile: horizontal scroll. Desktop: 3-col grid. Mobile py-2 prevents the card ring/shadow from being clipped. --}}
    <div class="-mx-4 overflow-x-auto px-4 py-2 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden sm:mx-0 sm:overflow-visible sm:px-0 sm:py-0">
        <div class="flex w-max gap-4 sm:grid sm:w-full sm:grid-cols-3 sm:gap-5">

        {{-- Step 1 — Pick a product --}}
        <article class="w-72 shrink-0 overflow-hidden rounded-2xl bg-zinc-100 ring-1 ring-zinc-200 shadow-sm sm:w-auto">
            <div class="flex aspect-[4/3] items-center justify-center overflow-hidden bg-zinc-100">
                <img
                    src="{{ asset('assets/' . rawurlencode('Pick a product first process.png')) }}"
                    alt=""
                    class="h-full w-full object-cover"
                    loading="lazy"
                >
            </div>
            <div class="bg-zinc-100 p-5">
                <h3 class="text-base font-semibold text-zinc-900">1. Pick a product or service</h3>
                <p class="mt-1.5 text-sm leading-relaxed text-zinc-600">
                    Choose from 14000+ gift cards, eSIMs, flights, stays and mobile top-ups made ready for you to simplify your shopping experience.
                </p>
            </div>
        </article>

        {{-- Step 2 — Pay with crypto --}}
        <article class="w-72 shrink-0 overflow-hidden rounded-2xl bg-zinc-100 ring-1 ring-zinc-200 shadow-sm sm:w-auto">
            <div class="flex aspect-[4/3] items-center justify-center overflow-hidden bg-zinc-100">
                <img
                    src="{{ asset('assets/' . rawurlencode('pay with crypto momo +.png')) }}"
                    alt=""
                    class="h-full w-full object-cover"
                    loading="lazy"
                >
            </div>
            <div class="bg-zinc-100 p-5">
                <h3 class="text-base font-semibold text-zinc-900">2. Pay with Cards, Crypto, MoMo etc</h3>
                <p class="mt-1.5 text-sm leading-relaxed text-zinc-600">
                    Access 14,000+ digital products including gift cards, eSIMs, flights, hotel stays, mobile top-ups, and more, all in one seamless platform.
                </p>
            </div>
        </article>

        {{-- Step 3 — Receive instantly --}}
        <article class="w-72 shrink-0 overflow-hidden rounded-2xl bg-zinc-100 ring-1 ring-zinc-200 shadow-sm sm:w-auto">
            <div class="flex aspect-[4/3] items-center justify-center overflow-hidden bg-zinc-100">
                <img
                    src="{{ asset('assets/' . rawurlencode('step 3.png')) }}"
                    alt=""
                    class="h-full w-full object-cover"
                    loading="lazy"
                >
            </div>
            <div class="bg-zinc-100 p-5">
                <h3 class="text-base font-semibold text-zinc-900">3. Receive instantly</h3>
                <p class="mt-1.5 text-sm leading-relaxed text-zinc-600">
                    Your product arrives in seconds, to your email address and your clients dashboard if you are signed up with us ready to use.
                </p>
            </div>
        </article>

        </div>
    </div>
</section>
