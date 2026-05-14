{{--
    Storefront hero. Light theme. Centered headline + CTAs + announcement
    banner, sitting over the Roddy Custom Hero interactive dots backdrop.
--}}
<section class="relative w-full overflow-hidden">

    {{-- Roddy Custom Hero animation with GSAP: dots backdrop styles --}}
    <style>
        .roddy-dots-container {
            font-size: clamp(8px, 0.9vw, 14px);
            display: flex;
            flex-flow: row wrap;
            justify-content: center;
            align-items: center;
            gap: 2em;
            position: absolute;
            inset: 0;
            pointer-events: none;
            /* Soft vignette so dots fade out on all four edges */
            -webkit-mask-image:
                linear-gradient(to right,  transparent 0%, #000 12%, #000 88%, transparent 100%),
                linear-gradient(to bottom, transparent 0%, #000 12%, #000 88%, transparent 100%);
            -webkit-mask-composite: source-in;
            mask-image:
                linear-gradient(to right,  transparent 0%, #000 12%, #000 88%, transparent 100%),
                linear-gradient(to bottom, transparent 0%, #000 12%, #000 88%, transparent 100%);
            mask-composite: intersect;
        }
        .roddy-dot {
            will-change: transform, background-color;
            transform-origin: center;
            background-color: rgba(113, 113, 122, 0.2);
            border-radius: 50%;
            width: 1em;
            height: 1em;
            position: relative;
            transform: translate(0);
        }
    </style>

    {{-- Roddy Custom Hero animation with GSAP: dots grid backdrop (JS populates the grid) --}}
    <div data-dots-container-init class="roddy-dots-container" aria-hidden="true">
        <div class="roddy-dot"></div>
    </div>

    {{-- Content over the dots (constrained to 1200px, dots stay full-width) --}}
    <div class="relative z-10 mx-auto max-w-[1200px] px-3 pt-10 pb-14 text-center sm:px-10 sm:pt-20 sm:pb-24 lg:px-14 lg:pt-24 lg:pb-32">

        @php
            // Shared heading classes used by both the visible h1 and the glowing overlay copy
            $headingClass = 'mx-auto my-0 max-w-[1200px] p-0 text-center text-[50px] font-extrabold leading-[1.1] tracking-normal text-zinc-900 sm:text-5xl md:text-6xl lg:text-[80px] lg:leading-[80px]';
        @endphp

        <div
            data-anim="hero-headline"
            x-data="{ x: -9999, y: -9999,
                track(e) {
                    const r = this.$el.getBoundingClientRect();
                    this.x = e.clientX - r.left;
                    this.y = e.clientY - r.top;
                }
            }"
            @mousemove="track($event)"
            class="relative"
        >
            {{-- Base (visible) heading --}}
            <h1 style="font-family: 'Urbanist', sans-serif;" class="{{ $headingClass }}">
                One Ecosystem<br>
                All your Digital<br class="sm:hidden">
                Solution And<br class="sm:hidden">
                Opportunities
            </h1>

            {{-- Glow overlay: bright green copy masked to a small circle that follows
                 the cursor. When the cursor leaves the heading, x/y stay at the last
                 position so the green letters at that spot remain visible. --}}
            <div
                aria-hidden="true"
                class="pointer-events-none absolute inset-0"
                :style="`mask-image: radial-gradient(circle 170px at ${x}px ${y}px, #000 0%, #000 80%, transparent 100%); -webkit-mask-image: radial-gradient(circle 170px at ${x}px ${y}px, #000 0%, #000 80%, transparent 100%);`"
            >
                <div style="font-family: 'Urbanist', sans-serif; color: #0044FF;" class="{{ $headingClass }}">
                    One Ecosystem<br>
                    All your Digital<br class="sm:hidden">
                    Solution And<br class="sm:hidden">
                    Opportunities
                </div>
            </div>
        </div>

        <p data-anim="hero-subtitle" class="mx-auto mt-5 max-w-xl text-lg leading-relaxed text-zinc-900">
            Buy gift cards, eSIMs, top-ups, and more. Fast delivery, great prices, 24/7 support.
        </p>

        {{-- CTAs --}}
        <div data-anim="hero-ctas" class="mt-8 flex flex-wrap items-center justify-center gap-4">

            {{-- Shop Gift Cards (primary) --}}
            <a href="#" class="group inline-flex items-center gap-2 rounded-xl bg-blue-600 px-5 py-3 text-base font-semibold text-white shadow-lg shadow-blue-600/20 transition-transform hover:-translate-y-0.5 hover:bg-blue-700">
                Shop Gift Cards
                <svg viewBox="0 0 24 24" class="h-5 w-5 transition-transform group-hover:translate-x-0.5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M3 8a3 3 0 0 1 3 -3h12a3 3 0 0 1 3 3v8a3 3 0 0 1 -3 3H6a3 3 0 0 1 -3 -3z"/>
                    <path d="m7 16 3 -3 3 3"/>
                    <path d="M8 13c-0.789 0 -2 -0.672 -2 -1.5S6.711 10 7.5 10c1.128 -0.02 2.077 1.17 2.5 3 0.423 -1.83 1.372 -3.02 2.5 -3 0.789 0 1.5 0.672 1.5 1.5S12.789 13 12 13H8z"/>
                </svg>
            </a>

            {{-- Explore eSIMs (glass button) --}}
            <a href="#" class="group inline-flex items-center gap-2 rounded-xl bg-white/60 backdrop-blur-md px-5 py-3 text-base font-semibold text-zinc-900 ring-1 ring-zinc-200/80 shadow-lg shadow-zinc-900/5 transition-all hover:-translate-y-0.5 hover:bg-white/80 hover:ring-zinc-300">
                Explore eSIMs
                <svg viewBox="0 0 24 24" class="h-5 w-5 transition-transform group-hover:translate-x-0.5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M6 3h8.5L19 7.5V20a1 1 0 0 1 -1 1H6a1 1 0 0 1 -1 -1V4a1 1 0 0 1 1 -1z"/>
                    <path d="M9 11h3v6"/>
                    <path d="M15 17v0.01"/>
                    <path d="M15 14v0.01"/>
                    <path d="M15 11v0.01"/>
                    <path d="M9 14v0.01"/>
                    <path d="M9 17v0.01"/>
                </svg>
            </a>

        </div>

        @php
            // Roddy Custom Hero animation with GSAP: unified expandable chip bar.
            // Each chip starts as a small circle showing only its PNG. The active
            // chip expands to reveal its title, subtitle, and arrow. The cycle
            // advances right to left, one chip every 6 seconds. Pauses on hover.
            $heroChips = [
                [
                    'image'    => 'hero gift.png',
                    'title'    => 'Shop Gift Cards Smartly',
                    'subtitle' => 'Shop more than 14k+ GiftCards Pay your bill and send gifts',
                ],
                [
                    'image'    => 'global coverage.png',
                    'title'    => 'Stay connected anywhere',
                    'subtitle' => '200+ countries supported with local rates.',
                ],
                [
                    'image'    => 'secured users.png',
                    'title'    => 'Trusted by thousands of users',
                    'subtitle' => 'Our users trust our Products and supports us',
                ],
                [
                    'image'    => 'compactible on all devices.png',
                    'title'    => 'Browse products Smartly',
                    'subtitle' => 'Compactible on Laptop, Ipad & Mobile',
                ],
                [
                    'image'    => 'seach products.png',
                    'title'    => 'Search Gift Cards, Esims, Appartments',
                    'subtitle' => 'Search and make your self comfortanle with our services',
                ],
            ];
        @endphp

        {{-- Floating expandable chip bar (single row, one chip expanded at a time) --}}
        <div data-anim="hero-banner" class="mx-auto mt-14 w-full max-w-5xl">
            <div
                x-data="{
                    total: {{ count($heroChips) }},
                    // Index of the chip currently expanded. Starts on the rightmost.
                    current: {{ count($heroChips) - 1 }},
                    paused: false,
                    wait(ms) {
                        return new Promise(r => {
                            const tick = () => {
                                if (this.paused) return setTimeout(tick, 100);
                                r();
                            };
                            setTimeout(tick, ms);
                        });
                    },
                    async cycle() {
                        // Ping-pong: walk left to the first chip, bounce back to the right,
                        // then bounce back to the left. Repeat forever.
                        // direction === -1 means moving leftward, +1 means moving rightward.
                        let direction = -1;
                        while (true) {
                            await this.wait(10000);
                            const next = this.current + direction;
                            if (next < 0 || next >= this.total) {
                                // Hit an edge: reverse direction and step the other way.
                                direction = -direction;
                                this.current = this.current + direction;
                            } else {
                                this.current = next;
                            }
                        }
                    }
                }"
                x-init="cycle()"
                @mouseenter="paused = true"
                @mouseleave="paused = false"
                class="flex flex-nowrap items-center justify-center gap-5"
                aria-live="polite"
            >
                @foreach ($heroChips as $i => $chip)
                    <a
                        href="#"
                        x-data="{ hovered: false }"
                        @mouseenter="hovered = true"
                        @mouseleave="hovered = false"
                        :class="[
                            current === {{ $i }} ? 'pr-4 sm:pr-5 lg:pr-8' : 'pr-2 sm:pr-2.5',
                            (current === {{ $i }} && hovered) ? '-translate-y-0.5' : '',
                            current === {{ $i }} ? 'max-lg:w-full' : 'max-lg:hidden'
                        ]"
                        class="group inline-flex h-[72px] shrink-0 items-center rounded-full bg-white pl-2 shadow-lg shadow-zinc-900/5 ring-1 ring-zinc-200 transition-all duration-500 ease-out sm:h-[88px] sm:pl-2.5"
                        aria-label="{{ $chip['title'] }}"
                    >
                        {{-- Icon (always visible) --}}
                        <span class="flex h-14 w-14 shrink-0 items-center justify-center sm:h-[68px] sm:w-[68px]">
                            <img src="{{ asset('assets/' . rawurlencode($chip['image'])) }}" alt="" class="h-14 w-14 object-contain sm:h-[68px] sm:w-[68px]" loading="lazy">
                        </span>

                        {{-- Title + subtitle (revealed when this chip is active) --}}
                        <span
                            :class="current === {{ $i }} ? 'flex-1 ml-3 opacity-100 lg:flex-none lg:max-w-[360px]' : 'max-w-0 ml-0 opacity-0'"
                            class="block min-w-0 overflow-hidden whitespace-nowrap text-left transition-all duration-500 ease-out"
                        >
                            <span class="block truncate text-[15px] font-semibold leading-tight text-zinc-900 transition-colors duration-200 group-hover:text-blue-600 sm:text-base">{{ $chip['title'] }}</span>
                            <span class="block truncate text-[13px] leading-tight text-zinc-500">{{ $chip['subtitle'] }}</span>
                        </span>

                        {{-- Arrow / Show-more button.
                             Default (chip active, not hovered): just the arrow icon.
                             On chip hover: a white pill rolls out with "Show more" + arrow. --}}
                        {{-- Show-more area: reserved width when the chip is open, so the
                             white pill can slide IN from the right on hover without
                             changing the chip's overall size. Bare arrow is always shown
                             at the right edge as the resting state. --}}
                        <span
                            :class="current === {{ $i }} ? 'w-[180px] ml-4 opacity-100' : 'w-0 ml-0 opacity-0'"
                            class="relative hidden h-14 shrink-0 items-center justify-end overflow-visible transition-all duration-500 ease-out lg:flex"
                        >
                            {{-- Resting state: bare arrow pinned to the right --}}
                            <span class="flex h-14 w-14 shrink-0 items-center justify-center text-zinc-800">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/>
                                </svg>
                            </span>

                            {{-- White "Show more" button slides in from the right on hover.
                                 Pops slightly past the chip's right edge (translate-x-3 = 12px)
                                 so the chip's gap accommodates it without overlapping neighbours. --}}
                            <span
                                :class="(current === {{ $i }} && hovered) ? 'translate-x-3 opacity-100' : 'translate-x-full opacity-0 pointer-events-none'"
                                class="absolute inset-0 z-10 inline-flex h-14 items-center justify-start rounded-full bg-white pl-5 pr-6 text-zinc-700 shadow-2xl shadow-zinc-900/25 transition-all duration-400 ease-out"
                            >
                                <svg class="mr-3 h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/>
                                </svg>
                                <span class="whitespace-nowrap text-base font-semibold">Show more</span>
                            </span>
                        </span>
                    </a>
                @endforeach
            </div>
        </div>

    </div>
</section>
