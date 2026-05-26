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

        {{-- ── Hero ─────────────────────────────────────────────────────────── --}}
        <section class="overflow-hidden rounded-3xl bg-blue-100 px-6 py-8 sm:px-10 sm:py-12">
            <div class="flex flex-col items-center gap-6 lg:flex-row lg:justify-between lg:gap-8">
                {{-- Desktop: both illustrations sit either side of the heading. --}}
                <img src="{{ asset('assets/'.rawurlencode('Esim stay connectd.png')) }}" alt="" class="hidden w-64 shrink-0 object-contain lg:block xl:w-80" loading="eager">

                <div class="max-w-xl text-center">
                    <h1 class="text-3xl font-bold tracking-tight text-zinc-900 sm:text-4xl">Feel the freedom of unlimited data</h1>
                    <p class="mx-auto mt-3 max-w-lg text-sm leading-relaxed text-zinc-600 sm:text-base">
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
                    <div class="mt-6 rounded-3xl bg-white px-6 py-16 text-center ring-1 ring-zinc-200">
                        <p class="text-base font-semibold text-zinc-900">No eSIMs available yet</p>
                        <p class="mt-1 text-sm text-zinc-600">We're adding coverage. Check back shortly.</p>
                    </div>
                @endif
            </div>
        </section>

    </div>

</x-layouts.app.header>
