{{--
    Primary navigation row — logo · search · account/cart — plus a category
    shortcut bar that collapses on scroll. The row is a 3-column grid so the
    search bar is page-centred, aligned with the centred category bar below.
--}}
<div class="bg-white/70 backdrop-blur-md">

    {{-- Nav row --}}
    <div class="max-w-[1350px] mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-[1fr_auto_1fr] items-center h-[64px] gap-4">

            {{-- Logo (the source PNG is square with whitespace padding, so the
                 image is scaled up and clipped to its wordmark band) --}}
            <a
                href="{{ route('home') }}"
                wire:navigate
                class="col-start-1 justify-self-start -ml-3 relative flex items-center rounded-md group focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/40"
                aria-label="RshopRefills — Home"
            >
                <span class="flex items-center h-9 md:h-11 overflow-hidden">
                    <img
                        src="{{ asset('assets/Rshoprefillslogo.png') }}"
                        alt="RshopRefills"
                        class="h-[190px] md:h-[230px] w-auto max-w-none object-contain saturate-[1.25] group-hover:opacity-90 transition-opacity duration-200"
                    />
                </span>
                <span class="absolute left-1 top-full -mt-0.5 text-[10px] font-medium italic leading-none text-zinc-400" aria-hidden="true">Est. 2024</span>
            </a>

            {{-- Search (desktop) --}}
            <div
                role="search"
                @click="$refs.search.focus()"
                class="col-start-2 group hidden md:flex w-[420px] max-w-full items-center gap-3 cursor-text rounded-2xl border-2 border-zinc-400 bg-white px-4 py-2 transition-all duration-200 hover:border-zinc-500 focus-within:border-blue-500 focus-within:ring-2 focus-within:ring-blue-500/15"
            >
                <svg class="w-5 h-5 shrink-0 text-zinc-900" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <input
                    x-ref="search"
                    type="search"
                    placeholder="Search for brands or categories"
                    aria-label="Search for brands or categories"
                    autocomplete="off"
                    spellcheck="false"
                    class="flex-1 min-w-0 bg-transparent text-base text-zinc-800 placeholder:text-zinc-400 outline-none"
                />
            </div>

            {{-- Right actions --}}
            <div class="col-start-3 justify-self-end flex items-center gap-2">

                {{-- Mobile search trigger (icon-only on small screens) --}}
                <button
                    type="button"
                    class="md:hidden flex h-9 w-9 items-center justify-center rounded-md bg-zinc-100 text-zinc-900 transition-colors duration-150 hover:bg-zinc-200/70 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/50"
                    aria-label="Search"
                >
                    <svg class="h-[22px] w-[22px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </button>

                {{-- Account --}}
                @auth
                    <a
                        href="{{ route('settings.profile') }}"
                        wire:navigate
                        class="flex items-center justify-center w-9 h-9 md:w-10 md:h-10 rounded-md bg-zinc-100 text-zinc-700 hover:bg-zinc-200/70 transition-colors duration-150 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/50 select-none"
                        aria-label="Your account"
                    >
                        <span class="text-[12px] font-bold text-blue-600 leading-none">{{ auth()->user()->initials() }}</span>
                    </a>
                @else
                    <a
                        href="{{ route('login') }}"
                        wire:navigate
                        class="flex items-center justify-center w-9 h-9 md:w-10 md:h-10 rounded-md bg-zinc-100 text-zinc-900 hover:bg-zinc-200/70 transition-colors duration-150 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/50"
                        aria-label="Sign in"
                    >
                        <svg class="h-[22px] w-[22px] md:h-6 md:w-6" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M10.264 9.757a4.752 4.752 0 1 0 0 -9.504 4.752 4.752 0 0 0 0 9.504Zm0.19 9.804c0 0.569 0.172 1.097 0.467 1.535H1.484c-0.575 0 -1.036 -0.486 -0.94 -1.053 0.753 -4.424 4.55 -8.18 9.715 -8.18 2.82 0 5.231 1.118 6.963 2.853a2.498 2.498 0 0 0 -0.437 1.412v0.683h-3.582a2.75 2.75 0 0 0 -2.75 2.75Zm8.081 -1.25V16.13a0.75 0.75 0 0 1 1.28 -0.53l3.435 3.433a0.75 0.75 0 0 1 0 1.061l-3.434 3.434a0.75 0.75 0 0 1 -1.28 -0.53v-2.186h-5.333a1.25 1.25 0 0 1 0 -2.5h5.332Z"/>
                        </svg>
                    </a>
                @endauth

                {{-- Cart --}}
                <a
                    href="#"
                    class="relative flex items-center justify-center w-9 h-9 md:w-10 md:h-10 rounded-md bg-zinc-100 text-zinc-900 hover:bg-zinc-200/70 transition-colors duration-150 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/50"
                    aria-label="Shopping cart"
                >
                    <svg class="h-[22px] w-[22px] md:h-6 md:w-6" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <path d="M20.1 23.88a3.75 3.75 0 0 0 3.72 -4.22L22.24 7a1.1 1.1 0 0 0 0 -0.16l-1 -2.93a1.77 1.77 0 0 0 -1.64 -1.03h-1a0.75 0.75 0 0 0 -0.75 0.74 0.76 0.76 0 0 0 0.75 0.76h1a0.2 0.2 0 0 1 0.17 0.12l0.55 1.5a0.24 0.24 0 0 1 0 0.23 0.25 0.25 0 0 1 -0.21 0.11h-2.76a0.26 0.26 0 0 1 -0.25 -0.26v-1a5 5 0 0 0 -10 0v1a0.26 0.26 0 0 1 -0.25 0.26H4a0.27 0.27 0 0 1 -0.21 -0.12 0.24 0.24 0 0 1 0 -0.23l0.63 -1.5a0.26 0.26 0 0 1 0.19 -0.15h1a0.75 0.75 0 0 0 0.75 -0.76 0.74 0.74 0 0 0 -0.75 -0.74h-1a1.76 1.76 0 0 0 -1.55 1L1.81 6.83a0.65 0.65 0 0 0 -0.05 0.2L0.18 19.66a3.75 3.75 0 0 0 3.72 4.22ZM8.73 14.76a0.34 0.34 0 0 1 0.48 0l1.51 1.45a0.25 0.25 0 0 0 0.35 0l4.48 -4.47a0.33 0.33 0 0 1 0.48 0L17.26 13a0.33 0.33 0 0 1 0 0.48l-6.12 6.12a0.34 0.34 0 0 1 -0.48 0L7.5 16.48a0.34 0.34 0 0 1 0 -0.49Zm0.37 -9.64a3 3 0 1 1 6 0v1a0.26 0.26 0 0 1 -0.25 0.26h-5.5a0.26 0.26 0 0 1 -0.25 -0.26Z"/>
                    </svg>
                    @if(($cartCount ?? 0) > 0)
                        <span class="absolute -top-1 -right-1 inline-flex items-center justify-center min-w-[18px] h-[18px] px-1 rounded-full bg-blue-600 text-[10px] font-bold text-white leading-none">{{ $cartCount }}</span>
                    @endif
                </a>

            </div>

        </div>
    </div>

    {{-- Category shortcut bar — visible on all devices --}}
    <div>
        <nav aria-label="Product categories" class="max-w-[1350px] mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex h-[40px] gap-1 overflow-x-auto justify-start md:justify-center [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                @php
                    $catLinkClass = "group relative flex h-full shrink-0 items-center gap-2 px-3 text-sm font-medium transition-colors duration-150 whitespace-nowrap focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/30 after:content-[''] after:absolute after:bottom-0 after:left-1/2 after:-translate-x-1/2 after:h-0.5 after:w-[0.5cm] after:rounded-full";
                    $catSvgClass = "w-[25px] h-[25px] shrink-0 text-zinc-900";
                @endphp

                {{-- Gift Cards --}}
                <a href="#" @click.prevent="activeCategory = 'Gift Cards'" :class="activeCategory === 'Gift Cards' ? 'text-zinc-900 font-semibold after:bg-zinc-900' : 'text-zinc-500 hover:text-zinc-800 after:bg-transparent'" class="{{ $catLinkClass }}">
                    <svg viewBox="0 0 24 24" class="{{ $catSvgClass }}" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M3 8a3 3 0 0 1 3 -3h12a3 3 0 0 1 3 3v8a3 3 0 0 1 -3 3H6a3 3 0 0 1 -3 -3z"/>
                        <path d="m7 16 3 -3 3 3"/>
                        <path d="M8 13c-0.789 0 -2 -0.672 -2 -1.5S6.711 10 7.5 10c1.128 -0.02 2.077 1.17 2.5 3 0.423 -1.83 1.372 -3.02 2.5 -3 0.789 0 1.5 0.672 1.5 1.5S12.789 13 12 13H8z"/>
                    </svg>
                    Gift Cards
                </a>

                {{-- eSIMs --}}
                <a href="#" @click.prevent="activeCategory = 'eSIMs'" :class="activeCategory === 'eSIMs' ? 'text-zinc-900 font-semibold after:bg-zinc-900' : 'text-zinc-500 hover:text-zinc-800 after:bg-transparent'" class="{{ $catLinkClass }}">
                    <svg viewBox="0 0 24 24" class="{{ $catSvgClass }}" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M6 3h8.5L19 7.5V20a1 1 0 0 1 -1 1H6a1 1 0 0 1 -1 -1V4a1 1 0 0 1 1 -1z"/>
                        <path d="M9 11h3v6"/>
                        <path d="M15 17v0.01"/>
                        <path d="M15 14v0.01"/>
                        <path d="M15 11v0.01"/>
                        <path d="M9 14v0.01"/>
                        <path d="M9 17v0.01"/>
                    </svg>
                    eSIMs
                </a>

                {{-- Mobile top up --}}
                <a href="#" @click.prevent="activeCategory = 'Mobile top up'" :class="activeCategory === 'Mobile top up' ? 'text-zinc-900 font-semibold after:bg-zinc-900' : 'text-zinc-500 hover:text-zinc-800 after:bg-transparent'" class="{{ $catLinkClass }}">
                    <svg viewBox="0 0 14 14" class="{{ $catSvgClass }}" fill="none" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M10.5 0.5h-7c-0.55228 0 -1 0.447715 -1 1v11c0 0.5523 0.44772 1 1 1h7c0.5523 0 1 -0.4477 1 -1v-11c0 -0.552285 -0.4477 -1 -1 -1Z"/>
                        <path d="M6.5 11h1"/>
                    </svg>
                    Mobile top up
                </a>

                {{-- Bill payments --}}
                <a href="#" @click.prevent="activeCategory = 'Bill payments'" :class="activeCategory === 'Bill payments' ? 'text-zinc-900 font-semibold after:bg-zinc-900' : 'text-zinc-500 hover:text-zinc-800 after:bg-transparent'" class="{{ $catLinkClass }}">
                    <svg viewBox="0 0 24 24" class="{{ $catSvgClass }}" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M5 21V5a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2v16l-3 -2 -2 2 -2 -2 -2 2 -2 -2 -3 2"/>
                        <path d="M14.8 8A2 2 0 0 0 13 7h-2a2 2 0 1 0 0 4h2a2 2 0 1 1 0 4h-2a2 2 0 0 1 -1.8 -1"/>
                        <path d="M12 6v10"/>
                    </svg>
                    Bill payments
                </a>

                {{-- Flights --}}
                <a href="#" @click.prevent="activeCategory = 'Flights'" :class="activeCategory === 'Flights' ? 'text-zinc-900 font-semibold after:bg-zinc-900' : 'text-zinc-500 hover:text-zinc-800 after:bg-transparent'" class="{{ $catLinkClass }}">
                    <svg viewBox="0 0 24 24" class="{{ $catSvgClass }}" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="m14.5 6.5 3 -2.9a2.05 2.05 0 0 1 2.9 2.9l-2.9 3L20 17l-2.5 2.55L14 13l-3 3v3l-2 2 -1.5 -4.5L3 15l2 -2h3l3 -3 -6.5 -3.5L7 4l7.5 2.5z"/>
                    </svg>
                    Flights
                </a>

                {{-- Stays --}}
                <a href="#" @click.prevent="activeCategory = 'Stays'" :class="activeCategory === 'Stays' ? 'text-zinc-900 font-semibold after:bg-zinc-900' : 'text-zinc-500 hover:text-zinc-800 after:bg-transparent'" class="{{ $catLinkClass }}">
                    <svg viewBox="0 0 24 24" class="{{ $catSvgClass }}" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M5 9a2 2 0 1 0 4 0 2 2 0 1 0 -4 0"/>
                        <path d="M22 17v-3H2"/>
                        <path d="M2 8v9"/>
                        <path d="M12 14h10v-2a3 3 0 0 0 -3 -3h-7v5z"/>
                    </svg>
                    Stays
                </a>

            </div>
        </nav>
    </div>

</div>
