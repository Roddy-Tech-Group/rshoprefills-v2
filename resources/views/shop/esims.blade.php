@php
    /** @var \Illuminate\Support\Collection<int, array<string, mixed>> $catalog */
    $catalog = $catalog ?? collect();
@endphp

<x-layouts.app.header :title="'eSIMs | RshopRefills'">

    <div
        x-data="{
            locTab: @js($activeScope ?? 'popular'),
            locSearch: '',
            titles: { popular: 'Popular locations', local: 'Local eSIMs', regional: 'Regional eSIMs', global: 'Global eSIMs', all: 'All eSIMs' },
            crumbs: { popular: 'Popular', local: 'Local eSIMs', regional: 'Regional eSIMs', global: 'Global eSIMs', all: 'All eSIMs' },
            subtitles: {
                popular: 'Explore our most popular eSIMs. Packages start from the shown price.',
                local: 'Single-country eSIMs. Packages start from the shown price.',
                regional: 'Multi-country regional eSIMs. Packages start from the shown price.',
                global: 'Worldwide coverage eSIMs. Packages start from the shown price.',
                all: 'Every eSIM we offer. Packages start from the shown price.',
            },
            init() {
                // Deep link / back-forward: keep the active category and the URL in sync.
                const fromUrl = new URLSearchParams(window.location.search).get('scope');
                if (fromUrl && this.titles[fromUrl]) { this.locTab = fromUrl; }
                this.$watch('locTab', (v) => {
                    const url = new URL(window.location.href);
                    if (v === 'popular') { url.searchParams.delete('scope'); } else { url.searchParams.set('scope', v); }
                    window.history.replaceState({}, '', url);
                });
                window.addEventListener('popstate', () => {
                    const s = new URLSearchParams(window.location.search).get('scope') || 'popular';
                    if (this.titles[s]) { this.locTab = s; }
                });
            },
        }"
        class="mx-auto w-full max-w-[1320px] px-4 py-8 sm:px-6 lg:py-12"
    >

        {{-- ── Hero ───────────────────────────────────────────────────────────
             Photo-led variant: the "united states esim picture.jpg" sits as
             the background, with a pure-black overlay + side gradient so the
             copy stays readable on any frame. Same pattern as the home
             services-promo (Store front video 1.mp4). --}}
        <section class="relative overflow-hidden rounded-[40px] bg-zinc-950 px-6 py-8 text-white sm:px-10 sm:py-12">
            {{-- Background photo. Object-cover fills the section regardless of
                 aspect ratio; aria-hidden so screen readers skip it. --}}
            <img
                src="{{ asset('assets/'.rawurlencode('united states esim picture.jpg')) }}"
                alt=""
                aria-hidden="true"
                loading="eager"
                class="pointer-events-none absolute inset-0 h-full w-full object-cover"
            >
            <div class="pointer-events-none absolute inset-0 bg-black/70" aria-hidden="true"></div>
            <div class="pointer-events-none absolute inset-0 bg-gradient-to-r from-black/85 via-black/55 to-black/40" aria-hidden="true"></div>

            {{-- Floating bubbles. Seven circles drift slowly upward with varying
                 sizes, delays and speeds so the motion never repeats visibly.
                 pointer-events-none + aria-hidden so they don't interfere with
                 the page. Respects prefers-reduced-motion via the keyframe
                 disable below. --}}
            <style>
                @keyframes rshop-bubble-float {
                    0%   { transform: translate3d(0, 0, 0) scale(1);   opacity: 0; }
                    10%  { opacity: var(--bubble-opacity, 0.35); }
                    50%  { transform: translate3d(var(--bubble-drift, 20px), -55%, 0) scale(1.08); }
                    90%  { opacity: var(--bubble-opacity, 0.35); }
                    100% { transform: translate3d(0, -110%, 0) scale(0.95); opacity: 0; }
                }
                .rshop-bubble {
                    position: absolute;
                    bottom: -10%;
                    border-radius: 9999px;
                    background: radial-gradient(circle at 30% 30%, rgba(255,255,255,0.55) 0%, rgba(255,255,255,0.18) 40%, rgba(96,165,250,0.18) 75%, rgba(59,130,246,0.05) 100%);
                    box-shadow: inset 0 0 8px rgba(255,255,255,0.25), 0 0 12px rgba(96,165,250,0.15);
                    animation: rshop-bubble-float var(--bubble-duration, 14s) ease-in-out infinite;
                    animation-delay: var(--bubble-delay, 0s);
                    will-change: transform, opacity;
                }
                @media (prefers-reduced-motion: reduce) {
                    .rshop-bubble { animation: none; opacity: 0; }
                }
            </style>
            <div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
                <span class="rshop-bubble" style="left: 8%;  width: 90px;  height: 90px;  --bubble-duration: 16s; --bubble-delay: 0s;    --bubble-drift: 30px;  --bubble-opacity: 0.35;"></span>
                <span class="rshop-bubble" style="left: 22%; width: 50px;  height: 50px;  --bubble-duration: 12s; --bubble-delay: 2.5s;  --bubble-drift: -20px; --bubble-opacity: 0.30;"></span>
                <span class="rshop-bubble" style="left: 38%; width: 140px; height: 140px; --bubble-duration: 22s; --bubble-delay: 5s;    --bubble-drift: 45px;  --bubble-opacity: 0.22;"></span>
                <span class="rshop-bubble" style="left: 55%; width: 70px;  height: 70px;  --bubble-duration: 14s; --bubble-delay: 1s;    --bubble-drift: -35px; --bubble-opacity: 0.32;"></span>
                <span class="rshop-bubble" style="left: 70%; width: 100px; height: 100px; --bubble-duration: 18s; --bubble-delay: 7s;    --bubble-drift: 25px;  --bubble-opacity: 0.28;"></span>
                <span class="rshop-bubble" style="left: 82%; width: 40px;  height: 40px;  --bubble-duration: 10s; --bubble-delay: 3.5s;  --bubble-drift: -15px; --bubble-opacity: 0.38;"></span>
                <span class="rshop-bubble" style="left: 92%; width: 60px;  height: 60px;  --bubble-duration: 15s; --bubble-delay: 9s;    --bubble-drift: 20px;  --bubble-opacity: 0.30;"></span>
            </div>

            <div class="relative flex flex-col items-center gap-6 lg:flex-row lg:justify-between lg:gap-8">
                {{-- Desktop: both illustrations sit either side of the heading. --}}
                <img src="{{ asset('assets/'.rawurlencode('Esim stay connectd.png')) }}" alt="" class="hidden w-64 shrink-0 object-contain lg:block xl:w-80" loading="eager">

                <div class="max-w-xl text-center">
                    <h1 class="text-3xl font-bold tracking-tight text-white sm:text-4xl">Feel the freedom of unlimited data</h1>
                    <p class="mx-auto mt-3 max-w-lg text-sm leading-relaxed text-zinc-200 sm:text-base">
                        Go ahead and watch that video, listen to that song, download that app. Explore our eSIM data packages for an uninterrupted connection in 190+ countries.
                    </p>
                </div>

                {{-- Mobile: cycle between both illustrations every 5s with a fade. --}}
                <div
                    x-data="{
                        slides: [
                            '{{ asset('assets/'.rawurlencode('Esim 1.png')) }}',
                            '{{ asset('assets/'.rawurlencode('Esim stay connectd.png')) }}',
                        ],
                        idx: 0,
                        timer: null,
                        init() { this.timer = setInterval(() => { this.idx = (this.idx + 1) % this.slides.length; }, 5000); },
                        destroy() { clearInterval(this.timer); },
                    }"
                    class="relative h-56 w-56 shrink-0 sm:h-72 sm:w-72 lg:hidden"
                    aria-hidden="true"
                >
                    <template x-for="(src, i) in slides" :key="i">
                        <img
                            :src="src"
                            alt=""
                            x-show="idx === i"
                            x-transition:enter="transition-opacity duration-500 ease-out"
                            x-transition:enter-start="opacity-0"
                            x-transition:enter-end="opacity-100"
                            x-transition:leave="absolute inset-0 transition-opacity duration-500 ease-out"
                            x-transition:leave-start="opacity-100"
                            x-transition:leave-end="opacity-0"
                            class="h-full w-full object-contain"
                            loading="eager"
                        >
                    </template>
                </div>

                <img src="{{ asset('assets/'.rawurlencode('Esim 1.png')) }}" alt="" class="hidden shrink-0 object-contain lg:block lg:w-64 xl:w-80" loading="eager">
            </div>
        </section>

        {{-- ── Browse by location ───────────────────────────────────────────── --}}
        <section id="esim-locations" class="mt-10 scroll-mt-24">
            <x-esim.location-tabs />

            <div class="mt-6">
                <h2 class="text-[30px] font-bold tracking-tight text-zinc-900" x-text="titles[locTab]">Popular locations</h2>
                <p class="mt-1 text-[18px] text-zinc-600" x-text="subtitles[locTab]"></p>

                @if ($catalog->isNotEmpty())
                    <div class="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($catalog as $item)
                            <x-esim.location-card :item="$item" />
                        @endforeach
                    </div>
                @else
                    <div class="mt-6 rounded-[10px] bg-white px-6 py-16 text-center ring-1 ring-zinc-200">
                        <p class="text-base font-semibold text-zinc-900">No eSIMs available yet</p>
                        <p class="mt-1 text-sm text-zinc-600">We're adding coverage. Check back shortly.</p>
                    </div>
                @endif
            </div>
        </section>

    </div>

</x-layouts.app.header>
