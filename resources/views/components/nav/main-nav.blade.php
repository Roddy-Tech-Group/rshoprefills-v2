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
                        fetchpriority="high"
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
                    class="md:hidden flex h-9 w-9 items-center justify-center rounded-md bg-zinc-100 text-zinc-900 transition-colors duration-150 hover:bg-blue-600 hover:text-white/70 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/50"
                    aria-label="Search"
                >
                    <svg class="h-[22px] w-[22px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </button>

                {{-- Wallet — authed users go straight to the dashboard, guests are prompted to log in --}}
                <a
                    href="{{ auth()->check() ? route('dashboard') : route('login') }}"
                    wire:navigate
                    class="hidden md:inline-flex h-10 items-center gap-2 rounded-md bg-zinc-100 px-4 text-sm font-semibold text-zinc-900 transition-colors duration-150 hover:bg-blue-600 hover:text-white/70 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/50"
                >
                    Wallet
                </a>

                {{-- Account --}}
                @auth
                    {{-- Authenticated: hover/click opens the account dropdown.
                         Hover toggles unless the user clicked the trigger — then it stays open until they click outside. --}}
                    <div
                        x-data="{ open: false, locked: false }"
                        @mouseenter="if (!locked) open = true"
                        @mouseleave="if (!locked) open = false"
                        @click.outside="open = false; locked = false"
                        @keydown.escape.window="open = false; locked = false"
                        class="relative"
                    >
                        <button
                            type="button"
                            @click="locked = !locked; open = locked"
                            :aria-expanded="open.toString()"
                            aria-haspopup="menu"
                            class="flex items-center justify-center w-9 h-9 md:w-10 md:h-10 rounded-md bg-zinc-100 text-zinc-900 hover:bg-blue-600 hover:text-white/70 transition-colors duration-150 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/50"
                            aria-label="Your account"
                        >
                            <img src="{{ asset('assets/' . rawurlencode('new user svg.svg')) }}" alt="" class="h-[22px] w-[22px] md:h-6 md:w-6" loading="lazy">
                        </button>

                        <div
                            x-show="open"
                            x-transition:enter="transition ease-out duration-150"
                            x-transition:enter-start="opacity-0 -translate-y-1"
                            x-transition:enter-end="opacity-100 translate-y-0"
                            x-transition:leave="transition ease-in duration-100"
                            x-transition:leave-start="opacity-100 translate-y-0"
                            x-transition:leave-end="opacity-0 -translate-y-1"
                            style="display:none;"
                            class="absolute right-0 top-full z-50 mt-2 w-60 overflow-hidden rounded-xl bg-white/85 backdrop-blur-md shadow-xl shadow-zinc-900/10 ring-1 ring-zinc-200"
                            role="menu"
                        >
                            <div class="p-1.5">
                                <a href="{{ route('settings.profile') }}" wire:navigate role="menuitem" class="group flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-zinc-800 transition-colors hover:bg-blue-600 hover:text-white">
                                    <svg class="h-5 w-5 shrink-0 text-zinc-700 transition-colors group-hover:text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
                                    </svg>
                                    Account
                                </a>
                                <a href="#" role="menuitem" class="group flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-zinc-800 transition-colors hover:bg-blue-600 hover:text-white">
                                    <svg class="h-5 w-5 shrink-0 text-zinc-700 transition-colors group-hover:text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    My orders
                                </a>
                                <a href="#" role="menuitem" class="group flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-zinc-800 transition-colors hover:bg-blue-600 hover:text-white">
                                    <svg class="h-5 w-5 shrink-0 text-zinc-700 transition-colors group-hover:text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 11.25v8.25a1.5 1.5 0 01-1.5 1.5H5.25a1.5 1.5 0 01-1.5-1.5v-8.25M12 4.875A2.625 2.625 0 109.375 7.5H12m0-2.625V7.5m0-2.625A2.625 2.625 0 1114.625 7.5H12m0 0V21m-8.625-9.75h18c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125h-18c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/>
                                    </svg>
                                    Redeem
                                </a>
                                <a href="{{ route('settings.profile') }}" wire:navigate role="menuitem" class="group flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-zinc-800 transition-colors hover:bg-blue-600 hover:text-white">
                                    <svg class="h-5 w-5 shrink-0 text-zinc-700 transition-colors group-hover:text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a6.759 6.759 0 010 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 010-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                    Settings
                                </a>
                                <a href="#" role="menuitem" class="group flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-zinc-800 transition-colors hover:bg-blue-600 hover:text-white">
                                    <svg class="h-5 w-5 shrink-0 text-zinc-700 transition-colors group-hover:text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 01-1.043 3.296 3.745 3.745 0 01-3.296 1.043A3.745 3.745 0 0112 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 01-3.296-1.043 3.745 3.745 0 01-1.043-3.296A3.745 3.745 0 013 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 011.043-3.296 3.746 3.746 0 013.296-1.043A3.746 3.746 0 0112 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 013.296 1.043 3.746 3.746 0 011.043 3.296A3.745 3.745 0 0121 12z"/>
                                    </svg>
                                    Limits
                                </a>
                            </div>

                            <div class="border-t border-zinc-100 p-1.5">
                                <form method="POST" action="{{ route('logout') }}" class="w-full">
                                    @csrf
                                    <button type="submit" class="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-left text-sm font-medium text-red-600 transition-colors hover:bg-red-50">
                                        <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75"/>
                                        </svg>
                                        Logout
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @else
                    {{-- Guest: hover/click opens a Login + Sign up dropdown.
                         Hover toggles unless the user clicked the trigger — then it stays open until they click outside. --}}
                    <div
                        x-data="{ open: false, locked: false }"
                        @mouseenter="if (!locked) open = true"
                        @mouseleave="if (!locked) open = false"
                        @click.outside="open = false; locked = false"
                        @keydown.escape.window="open = false; locked = false"
                        class="relative"
                    >
                        <button
                            type="button"
                            @click="locked = !locked; open = locked"
                            :aria-expanded="open.toString()"
                            aria-haspopup="menu"
                            class="flex items-center justify-center w-9 h-9 md:w-10 md:h-10 rounded-md bg-zinc-100 text-zinc-900 hover:bg-blue-600 hover:text-white/70 transition-colors duration-150 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/50"
                            aria-label="Sign in"
                        >
                            <img src="{{ asset('assets/' . rawurlencode('new user svg.svg')) }}" alt="" class="h-[22px] w-[22px] md:h-6 md:w-6" loading="lazy">
                        </button>

                        <div
                            x-show="open"
                            x-transition:enter="transition ease-out duration-150"
                            x-transition:enter-start="opacity-0 -translate-y-1"
                            x-transition:enter-end="opacity-100 translate-y-0"
                            x-transition:leave="transition ease-in duration-100"
                            x-transition:leave-start="opacity-100 translate-y-0"
                            x-transition:leave-end="opacity-0 -translate-y-1"
                            style="display:none;"
                            class="absolute right-0 top-full z-50 mt-2 w-48 overflow-hidden rounded-xl bg-white/85 backdrop-blur-md shadow-xl shadow-zinc-900/10 ring-1 ring-zinc-200"
                            role="menu"
                        >
                            <a
                                href="{{ route('login') }}"
                                wire:navigate
                                role="menuitem"
                                class="flex items-center gap-3 px-4 py-3 text-base font-medium text-zinc-800 transition-colors hover:bg-blue-600 hover:text-white"
                            >
                                <img src="{{ asset('assets/' . rawurlencode('Login.svg')) }}" alt="" class="h-5 w-5 shrink-0" loading="lazy">
                                Login
                            </a>
                            <a
                                href="{{ route('register') }}"
                                wire:navigate
                                role="menuitem"
                                class="flex items-center gap-3 border-t border-zinc-100 px-4 py-3 text-base font-medium text-zinc-800 transition-colors hover:bg-blue-600 hover:text-white"
                            >
                                <img src="{{ asset('assets/' . rawurlencode('create an account.svg')) }}" alt="" class="h-5 w-5 shrink-0" loading="lazy">
                                Create Account
                            </a>
                        </div>
                    </div>
                @endauth

                {{-- Cart (click/hover opens an empty-cart dropdown until cart state ships).
                     Hover toggles unless the user clicked the cart icon — then it stays open until they click outside.
                     Backend hook: dispatch a Livewire event named "cart-added" whenever a product is added
                     to auto-open this popup. Example: $this->dispatch('cart-added'); --}}
                <div
                    x-data="{ open: false, locked: false }"
                    @mouseenter="if (!locked) open = true"
                    @mouseleave="if (!locked) open = false"
                    @click.outside="open = false; locked = false"
                    @keydown.escape.window="open = false; locked = false"
                    @cart-added.window="open = true; setTimeout(() => { if (!locked) open = false }, 3500)"
                    class="relative"
                >
                    <button
                        type="button"
                        @click="locked = !locked; open = locked"
                        :aria-expanded="open.toString()"
                        aria-haspopup="menu"
                        class="relative flex items-center justify-center w-9 h-9 md:w-10 md:h-10 rounded-md bg-zinc-100 text-zinc-900 hover:bg-blue-600 hover:text-white/70 transition-colors duration-150 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/50"
                        aria-label="Shopping cart"
                    >
                        <img src="{{ asset('assets/' . rawurlencode('new cart.svg')) }}" alt="" class="h-[22px] w-[22px] md:h-6 md:w-6" loading="lazy">
                        @if(($cartCount ?? 0) > 0)
                            <span class="absolute -top-1 -right-1 inline-flex items-center justify-center min-w-[18px] h-[18px] px-1 rounded-full bg-blue-600 text-[10px] font-bold text-white leading-none">{{ $cartCount }}</span>
                        @endif
                    </button>

                    {{-- Cart popup (empty state) — glassmorphism, illustration only, no CTA --}}
                    <div
                        x-show="open"
                        x-transition:enter="transition ease-out duration-150"
                        x-transition:enter-start="opacity-0 -translate-y-1"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-100"
                        x-transition:leave-start="opacity-100 translate-y-0"
                        x-transition:leave-end="opacity-0 -translate-y-1"
                        style="display:none;"
                        class="absolute right-0 top-full z-50 mt-2 w-[320px] overflow-hidden rounded-2xl bg-white/85 backdrop-blur-md shadow-xl shadow-zinc-900/15 ring-1 ring-zinc-200"
                        role="menu"
                    >
                        @if(($cartCount ?? 0) === 0)
                            {{-- Empty state. Pure-CSS floating motion on the illustration (see .animate-float in app.css). --}}
                            <div class="flex flex-col items-center px-6 py-7 text-center">
                                <h3 class="text-xl font-bold text-zinc-900">Your cart is empty</h3>
                                <img
                                    src="{{ asset('assets/' . rawurlencode('Empty cart.png')) }}"
                                    alt=""
                                    class="mt-4 h-40 w-auto object-contain animate-float"
                                    loading="lazy"
                                >
                                <p class="mt-3 text-sm text-zinc-500">Your Cards needs items</p>
                            </div>
                        @else
                            {{-- Populated state — backend will provide $cartItems (collection) and $cartSubtotal (decimal).
                                 The illustration is intentionally NOT rendered here so it only shows on the empty state. --}}
                            <div class="px-5 py-5">
                                <div class="mb-4 flex items-center justify-between">
                                    <h3 class="text-lg font-bold text-zinc-900">Your cart</h3>
                                    <span class="text-sm text-zinc-500">{{ $cartCount }} {{ \Illuminate\Support\Str::plural('item', $cartCount) }}</span>
                                </div>

                                {{-- Item list — backend loops $cartItems and renders brand/quantity/price rows --}}
                                <ul class="max-h-64 space-y-3 overflow-y-auto">
                                    {{-- @foreach($cartItems as $item) ...row markup... @endforeach --}}
                                </ul>

                                <div class="mt-4 flex items-center justify-between border-t border-zinc-200 pt-4">
                                    <span class="text-sm font-medium text-zinc-700">Subtotal</span>
                                    <span class="text-base font-bold text-zinc-900">${{ number_format($cartSubtotal ?? 0, 2) }}</span>
                                </div>

                                <a
                                    href="#"
                                    wire:navigate
                                    class="mt-4 inline-flex w-full items-center justify-center rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-blue-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/40"
                                >
                                    Checkout
                                </a>
                            </div>
                        @endif
                    </div>
                </div>

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
