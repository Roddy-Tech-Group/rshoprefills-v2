<x-layouts.app.header>

{{-- Hero is full-width so the dots backdrop spans the entire page --}}
<x-home.hero />

<div class="mx-auto w-full max-w-[1550px] px-4 py-6 sm:px-6 sm:py-8 lg:px-8 lg:py-10">

    {{-- Trust strip --}}
    <div>
        <x-home.trust-strip />
    </div>

    {{-- Category quick-nav --}}
    <div class="mt-8">
        <x-home.category-tiles />
    </div>

    {{-- Popular Gift Cards --}}
    <div class="mt-12">
        <x-home.brand-row
            title="Popular Gift Cards"
            subtitle="Top-rated gift cards across categories"
            view-all-href="#"
            :cols="5"
        >

            <x-home.brand-card name="Everything Apple" price-range="$10 - $500" card-class="bg-white">
                <img src="{{ asset('assets/apple.png') }}" alt="Everything Apple gift card" fetchpriority="high" class="h-full w-full object-cover">
            </x-home.brand-card>

            <x-home.brand-card name="Xbox" price-range="$10 - $500" card-class="bg-white">
                <img src="{{ asset('assets/x-box_300x190.png') }}" alt="Xbox gift card" class="h-full w-full object-cover" loading="lazy">
            </x-home.brand-card>

            <x-home.brand-card name="Amazon.com" price-range="$5 - $500" card-class="bg-white">
                <img src="{{ asset('assets/amazon.png') }}" alt="Amazon gift card" class="h-full w-full object-cover" loading="lazy">
            </x-home.brand-card>

            <x-home.brand-card name="Netflix" price-range="$5 - $200" card-class="bg-white">
                <img src="{{ asset('assets/netflix.webp') }}" alt="Netflix gift card" class="h-full w-full object-cover" loading="lazy">
            </x-home.brand-card>

            <x-home.brand-card name="Spotify" price-range="$5 - $100" card-class="bg-white">
                <img src="{{ asset('assets/spotify.webp') }}" alt="Spotify gift card" class="h-full w-full object-cover" loading="lazy">
            </x-home.brand-card>

        </x-home.brand-row>
    </div>

    {{-- Clothing & Accessories Gift Cards --}}
    <div class="mt-12">
        <x-home.brand-row
            title="Clothing & Accessories Gift Cards"
            subtitle="Designer brands and fashion essentials"
            view-all-href="#"
            :cols="6"
        >

            <x-home.brand-card name="Xbox" price-range="$10 - $500" card-class="bg-white">
                <img src="{{ asset('assets/x-box_300x190.png') }}" alt="Xbox gift card" class="h-full w-full object-cover" loading="lazy">
            </x-home.brand-card>

            <x-home.brand-card name="Versace" price-range="$50 - $1000" card-class="bg-white">
                <div class="flex flex-col items-center gap-1.5">
                    <svg viewBox="0 0 24 24" class="h-9 w-9 text-zinc-900" fill="none" stroke="currentColor" stroke-width="1" aria-hidden="true">
                        <circle cx="12" cy="12" r="3" fill="currentColor"/>
                        <path d="M12 3 L13.6 8.5 L19.5 8.5 L14.8 12 L16.4 17.5 L12 14 L7.6 17.5 L9.2 12 L4.5 8.5 L10.4 8.5 Z" stroke-linejoin="round"/>
                    </svg>
                    <span class="text-sm font-bold uppercase tracking-[0.25em] text-zinc-900">Versace</span>
                </div>
            </x-home.brand-card>

            <x-home.brand-card name="Tory Burch" price-range="$25 - $500" card-class="bg-white">
                <span class="text-base font-bold uppercase tracking-[0.2em] text-zinc-900">Tory Burch</span>
            </x-home.brand-card>

            <x-home.brand-card name="Tom Ford" price-range="$50 - $1000" card-class="bg-white">
                <span class="text-lg font-extrabold uppercase tracking-[0.2em] text-zinc-900">Tom Ford</span>
            </x-home.brand-card>

            <x-home.brand-card name="Steam" price-range="$5 - $500" card-class="bg-white">
                <img src="{{ asset('assets/steam.png') }}" alt="Steam gift card" class="h-full w-full object-cover" loading="lazy">
            </x-home.brand-card>

            <x-home.brand-card name="Shein" price-range="$10 - $200" card-class="bg-white">
                <span class="text-2xl font-extrabold uppercase tracking-[0.2em] text-zinc-900">Shein</span>
            </x-home.brand-card>

        </x-home.brand-row>
    </div>

    {{-- Digital Apps Gift Cards --}}
    <div class="mt-12">
        <x-home.brand-row
            title="Digital Apps Gift Cards"
            subtitle="Subscriptions, games, and digital services"
            view-all-href="#"
            :cols="6"
        >

            <x-home.brand-card name="Google Play" price-range="$5 - $500" card-class="bg-white">
                <img src="{{ asset('assets/googleplay.png') }}" alt="Google Play gift card" class="h-full w-full object-cover" loading="lazy">
            </x-home.brand-card>

            <x-home.brand-card name="PlayStation" price-range="$10 - $500" card-class="bg-white">
                <img src="{{ asset('assets/playstation.png') }}" alt="PlayStation gift card" class="h-full w-full object-cover" loading="lazy">
            </x-home.brand-card>

            <x-home.brand-card name="Hulu" price-range="$10 - $100" card-class="bg-white">
                <img src="{{ asset('assets/hulu.webp') }}" alt="Hulu gift card" class="h-full w-full object-cover" loading="lazy">
            </x-home.brand-card>

            <x-home.brand-card name="Nintendo eShop" price-range="$10 - $500" card-class="bg-white">
                <img src="{{ asset('assets/nintendo.webp') }}" alt="Nintendo eShop gift card" class="h-full w-full object-cover" loading="lazy">
            </x-home.brand-card>

            <x-home.brand-card name="iTunes" price-range="$10 - $500" card-class="bg-zinc-900">
                <div class="flex items-center gap-2.5">
                    <svg viewBox="0 0 24 24" class="h-9 w-9 text-white" fill="currentColor" aria-hidden="true">
                        <path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20zm-1 14a3 3 0 1 1 .001-6.001A3 3 0 0 1 11 16zm5-8a1 1 0 0 1-2 0V5l-4 1v7a1 1 0 0 1-2 0V5.5a.5.5 0 0 1 .4-.49l6-1.5A.5.5 0 0 1 16 4z"/>
                    </svg>
                    <span class="text-lg font-semibold text-white">iTunes</span>
                </div>
            </x-home.brand-card>

            <x-home.brand-card name="Twitch" price-range="$5 - $100" card-class="bg-white">
                <img src="{{ asset('assets/twitch.webp') }}" alt="Twitch gift card" class="h-full w-full object-cover" loading="lazy">
            </x-home.brand-card>

        </x-home.brand-row>
    </div>

    {{-- How it works --}}
    <div class="mt-12">
        <x-home.how-it-works />
    </div>

    {{-- eSIMs, flights and stays --}}
    <div class="mt-12">
        <x-home.explore-row />
    </div>

    {{-- Customer reviews --}}
    <div class="mt-12 mb-4">
        <x-home.customer-reviews />
    </div>

</div>

</x-layouts.app.header>
