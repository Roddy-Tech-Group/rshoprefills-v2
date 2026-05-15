{{--
    Customer reviews row. Trustpilot aggregate card pinned on the left,
    then a horizontal scroll of individual review cards.
--}}
<section data-reveal aria-label="What our customers say">

    <div class="mb-4 flex items-center justify-between gap-4">
        <h2 class="text-lg font-bold text-zinc-900 sm:text-xl">What our customers say</h2>
        <a
            href="#"
            aria-label="See all reviews"
            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-white text-zinc-700 ring-1 ring-zinc-200 shadow-sm transition-colors duration-150 hover:bg-zinc-50 hover:text-zinc-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/40"
        >
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M9 6l6 6-6 6"/>
            </svg>
        </a>
    </div>

    {{-- Horizontal scroll row. py-2 keeps the card ring/shadow from being clipped by overflow-x. --}}
    <div class="-mx-4 overflow-x-auto px-4 py-2 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden sm:mx-0 sm:px-0">
        <div class="flex w-max gap-4 sm:gap-5">

            {{-- Trustpilot aggregate card --}}
            <article class="flex w-72 shrink-0 flex-col rounded-2xl bg-white p-5 ring-1 ring-zinc-200 shadow-sm">

                <div class="flex items-start justify-between gap-2">
                    <div class="flex items-center gap-1.5">
                        <svg class="h-5 w-5 text-emerald-500" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M12 .587l3.668 7.568L24 9.423l-6 5.951L19.336 24 12 19.897 4.664 24 6 15.374 0 9.423l8.332-1.268z"/>
                        </svg>
                        <span class="text-base font-bold text-zinc-900">Trustpilot</span>
                    </div>
                    <p class="text-2xl font-bold leading-none text-zinc-900">
                        4.4<span class="text-base font-normal text-zinc-600"> / 5</span>
                    </p>
                </div>

                {{-- 5 green-square stars --}}
                <div class="mt-3 flex gap-0.5">
                    @for ($i = 0; $i < 5; $i++)
                        <span class="flex h-7 w-7 items-center justify-center bg-emerald-500">
                            <svg class="h-4 w-4 text-white" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path d="M12 .587l3.668 7.568L24 9.423l-6 5.951L19.336 24 12 19.897 4.664 24 6 15.374 0 9.423l8.332-1.268z"/>
                            </svg>
                        </span>
                    @endfor
                </div>

                <div class="mt-auto pt-6">
                    <p class="text-sm text-zinc-700">446+ reviews on Trustpilot</p>
                    <p class="mt-1 text-sm font-semibold text-zinc-900">Trusted since 2018</p>
                </div>
            </article>

            {{-- Individual review cards --}}
            @foreach ([
                ['HG', 'Harshit Garg', 'May 12, 2026', 'This is the best after trying many i can say that\'s the best, like within 5 minutes u get the pin code voucher without any error.. very fast service... and this is my TOP number 1'],
                ['J',  'Jay',          'May 12, 2026', 'I had some issues with my first purchase and the support team spent their valiant efforts on fixing these issues for me and I am truly grateful for the customer service.'],
                ['C',  'carlbooze',    'May 10, 2026', 'It were fast, felt secure and safe, no issues at all. Very well guide to send the crypto with copy paste adress and amount to send so its impossible to mess up. Thank you very much'],
                ['M',  'Micheal',      'May 2, 2026',  'Very easy to use and quick support'],
                ['GH', 'gfjg hgdh',    'May 1, 2026',  'Always the best site to purchase gift card fastest and trusted'],
                ['ZA', 'Zul Aliffi',   'Apr 28, 2026', 'I have tried many sites for local gift cards but this has been the smoothest delivery.'],
            ] as [$initials, $name, $date, $review])
                <article class="flex w-72 shrink-0 flex-col rounded-2xl bg-white p-5 ring-1 ring-zinc-200 shadow-sm">

                    {{-- Top row: avatar + name + Trustpilot mini-logo --}}
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex min-w-0 items-start gap-3">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-zinc-100 text-sm font-bold text-zinc-600">{{ $initials }}</span>
                            <div class="min-w-0 leading-tight">
                                <p class="truncate text-sm font-semibold text-zinc-900">{{ $name }}</p>
                                <p class="text-xs text-zinc-600">{{ $date }}</p>
                            </div>
                        </div>
                        <div class="flex shrink-0 items-center gap-1">
                            <svg class="h-3.5 w-3.5 text-emerald-500" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path d="M12 .587l3.668 7.568L24 9.423l-6 5.951L19.336 24 12 19.897 4.664 24 6 15.374 0 9.423l8.332-1.268z"/>
                            </svg>
                            <span class="text-[10px] font-bold text-zinc-900">Trustpilot</span>
                        </div>
                    </div>

                    {{-- 5 stars --}}
                    <div class="mt-3 flex gap-0.5">
                        @for ($i = 0; $i < 5; $i++)
                            <span class="flex h-4 w-4 items-center justify-center bg-emerald-500">
                                <svg class="h-3 w-3 text-white" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                    <path d="M12 .587l3.668 7.568L24 9.423l-6 5.951L19.336 24 12 19.897 4.664 24 6 15.374 0 9.423l8.332-1.268z"/>
                                </svg>
                            </span>
                        @endfor
                    </div>

                    {{-- Review text --}}
                    <p class="mt-3 text-sm leading-relaxed text-zinc-700">{{ $review }}</p>
                </article>
            @endforeach

        </div>
    </div>
</section>
