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
                <span class="flex items-center h-10 md:h-12">
                    <img
                        src="{{ asset('assets/Rshoprefillslogo.png') }}"
                        alt="RshopRefills"
                        fetchpriority="high"
                        class="h-full w-auto object-contain transition-opacity duration-200 group-hover:opacity-90"
                    />
                </span>
                <span class="absolute left-1 top-full -mt-0.5 text-[10px] font-medium italic leading-none text-zinc-600" aria-hidden="true">Est. 2024</span>
            </a>

            {{-- Live search (desktop). Hits /api/search/brands?q=… on every keystroke (debounced)
                 and renders a dropdown of matching brands. Pressing Enter navigates to
                 /gift-cards?q= for the full results page. --}}
            <div
                x-data="navBrandSearch()"
                @click.outside="open = false"
                @keydown.escape.window="open = false"
                class="col-start-2 hidden md:block w-[480px] max-w-full relative"
            >
                <form
                    role="search"
                    method="GET"
                    action="{{ route('shop.gift-cards') }}"
                    @click="$refs.search.focus()"
                    :class="open ? 'border-blue-500 ring-2 ring-blue-500/15' : 'border-zinc-400 hover:border-zinc-500'"
                    class="group flex items-center gap-3 cursor-text rounded-2xl border-2 bg-white px-4 py-2 transition-all duration-200"
                >
                    <button type="submit" class="shrink-0 text-zinc-900 transition-colors hover:text-blue-600 focus:outline-none" aria-label="Search">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </button>
                    <input
                        x-ref="search"
                        x-model="query"
                        @input="onInput()"
                        @focus="if (query.length >= 2) open = true"
                        name="q"
                        type="text"
                        placeholder="Search brands, categories, countries"
                        aria-label="Search brands, categories, countries"
                        autocomplete="off"
                        spellcheck="false"
                        class="flex-1 min-w-0 bg-transparent text-base text-zinc-800 placeholder:text-zinc-600 outline-none"
                    />
                    <button type="button" x-show="query.length > 0" @click="clear()" class="shrink-0 text-blue-600 transition-colors hover:text-blue-700 focus:outline-none" aria-label="Clear">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </form>

                {{-- Results dropdown --}}
                <div
                    x-show="open"
                    x-transition:enter="transition ease-out duration-150"
                    x-transition:enter-start="opacity-0 -translate-y-1"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    style="display:none;"
                    class="absolute left-0 right-0 top-full z-30 mt-2 overflow-hidden rounded-2xl border border-zinc-200 bg-white/80 shadow-2xl shadow-zinc-900/15 backdrop-blur-xl"
                >
                    {{-- Loading state --}}
                    <div x-show="loading && results.length === 0" class="px-5 py-6 text-center text-sm text-zinc-600">
                        <svg class="mx-auto h-5 w-5 animate-spin text-zinc-400" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>
                        <p class="mt-2">Searching…</p>
                    </div>

                    {{-- Results list --}}
                    <ul x-show="results.length > 0" class="max-h-[60vh] divide-y divide-zinc-100/80 overflow-y-auto [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                        <template x-for="r in results" :key="r.slug">
                            <li>
                                <a
                                    :href="'/gift-cards/' + r.slug"
                                    wire:navigate
                                    @click="open = false"
                                    class="flex items-center gap-3 px-4 py-2.5 transition-colors hover:bg-zinc-100"
                                >
                                    <template x-if="r.logo">
                                        <span class="flex aspect-[16/10] w-20 shrink-0 items-center justify-center overflow-hidden rounded-[5px] bg-white shadow-sm ring-1 ring-zinc-200">
                                            <img :src="r.logo" :alt="r.name" class="h-full w-full object-cover">
                                        </span>
                                    </template>
                                    <template x-if="!r.logo">
                                        <span class="flex aspect-[16/10] w-20 shrink-0 items-center justify-center rounded-[5px] bg-white text-sm font-black uppercase text-zinc-700 shadow-sm ring-1 ring-zinc-200" x-text="r.name.substring(0, 2).toUpperCase()"></span>
                                    </template>
                                    <span class="flex-1 truncate text-sm font-semibold text-zinc-900" x-text="r.name"></span>
                                </a>
                            </li>
                        </template>
                    </ul>

                    {{-- No results --}}
                    <div x-show="!loading && results.length === 0 && query.length >= 2" class="px-5 py-6 text-center text-sm text-zinc-600">
                        No brands match "<span class="font-semibold text-zinc-900" x-text="query"></span>"
                    </div>

                    {{-- Show all results footer --}}
                    <a
                        x-show="results.length > 0"
                        :href="'{{ route('shop.gift-cards') }}?q=' + encodeURIComponent(query)"
                        wire:navigate
                        @click="open = false"
                        class="block border-t border-zinc-100 px-5 py-3 text-center text-sm font-semibold text-blue-600 transition-colors hover:bg-zinc-100/70 hover:text-blue-700"
                    >
                        Show all results
                    </a>
                </div>
            </div>

            {{-- Right actions --}}
            <div class="col-start-3 justify-self-end flex items-center gap-2">

                {{-- Mobile search trigger — navigates to /gift-cards (which has its own
                     search input that lives in the page sidebar). On the customer flow,
                     /gift-cards is the unified catalog so a tap on this is equivalent to
                     "open the searchable catalog." --}}
                <a
                    href="{{ route('shop.gift-cards') }}"
                    wire:navigate
                    class="md:hidden flex h-9 w-9 items-center justify-center rounded-md bg-zinc-100 text-zinc-900 transition-colors duration-150 hover:bg-blue-600 hover:text-white/70 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/50"
                    aria-label="Search"
                >
                    <svg class="h-[22px] w-[22px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </a>

                {{-- Wallet — authed users see their balance, guests see "Wallet" and get prompted to log in.
                     Symbol resolution: prefers App\Domain\Shared\Enums\Currency::symbol() when the case exists,
                     otherwise falls back to the raw code so any backend-added currency (KES, ZAR, EUR, etc.)
                     renders gracefully without a frontend change. --}}
                @php
                    use App\Domain\Shared\Enums\Currency;

                    $walletBalance = auth()->check() ? (float) (auth()->user()->wallet?->balance ?? 0) : null;
                    $walletCurrency = auth()->check() ? (auth()->user()->wallet?->currency ?? 'USD') : null;
                    $currencyCase = $walletCurrency ? Currency::tryFrom($walletCurrency) : null;
                    $currencySymbol = $currencyCase?->symbol() ?? ($walletCurrency ? $walletCurrency.' ' : '');

                    // Gender-aware default avatar for authed users. Resolved at render time so the right
                    // portrait shows up everywhere the user's avatar is rendered until they upload one.
                    $authUser = auth()->user();
                    $accountAvatar = $authUser?->avatar_url ?: asset('assets/' . rawurlencode(match (strtolower($authUser?->gender ?? '')) {
                        'female', 'f' => 'New Female Account Avatar.png',
                        default       => 'New male account avatar.png',
                    }));
                @endphp
                <a
                    href="{{ auth()->check() ? route('dashboard') : route('login') }}"
                    wire:navigate
                    class="hidden md:inline-flex h-10 items-center gap-2 rounded-md bg-zinc-200 px-4 text-sm font-semibold text-zinc-900 transition-colors duration-150 hover:bg-zinc-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/50"
                    aria-label="{{ auth()->check() ? 'Wallet balance' : 'Wallet — sign in to view balance' }}"
                >
                    <img src="{{ asset('assets/' . rawurlencode('transactions.svg')) }}" alt="" class="h-4 w-4 shrink-0" loading="lazy">
                    @auth
                        {{ $currencySymbol }}{{ number_format($walletBalance, 2) }}
                    @else
                        Wallet
                    @endauth
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
                            class="flex items-center justify-center w-9 h-9 md:w-10 md:h-10 overflow-hidden rounded-full bg-zinc-100 transition-all duration-150 ring-1 ring-zinc-200 hover:ring-2 hover:ring-blue-400 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/50"
                            aria-label="Your account"
                        >
                            <img src="{{ $accountAvatar }}" alt="{{ $authUser?->name ?? 'Account' }}" class="h-full w-full object-cover" loading="lazy">
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
                                <a href="{{ route('dashboard') }}" wire:navigate role="menuitem" class="group flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-zinc-800 transition-colors hover:bg-blue-600 hover:text-white">
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
                                <a href="{{ route('dashboard.profile') }}" wire:navigate role="menuitem" class="group flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-zinc-800 transition-colors hover:bg-blue-600 hover:text-white">
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

                {{-- Cart. State lives in the global Alpine cart store ($store.cart): the popup
                     drops open automatically when an item is added (store.add sets open = true). --}}
                <div
                    x-data="{ locked: false }"
                    @mouseenter="if (!locked) $store.cart.open = true"
                    @mouseleave="if (!locked) $store.cart.open = false"
                    @click.outside="$store.cart.open = false; locked = false"
                    @keydown.escape.window="$store.cart.open = false; locked = false"
                    class="relative"
                >
                    <button
                        type="button"
                        @click="locked = !locked; $store.cart.open = locked"
                        :aria-expanded="$store.cart.open.toString()"
                        aria-haspopup="menu"
                        class="relative flex items-center justify-center w-9 h-9 md:w-10 md:h-10 rounded-md bg-zinc-200 text-zinc-900 hover:bg-zinc-300 transition-colors duration-150 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/50"
                        aria-label="Shopping cart"
                    >
                        <img src="{{ asset('assets/' . rawurlencode('new cart.svg')) }}" alt="" class="h-[22px] w-[22px] md:h-6 md:w-6" loading="lazy">
                        <span
                            x-show="$store.cart.count > 0"
                            x-text="$store.cart.count"
                            x-cloak
                            class="absolute -top-1 -right-1 inline-flex items-center justify-center min-w-[18px] h-[18px] px-1 rounded-full bg-blue-600 text-[10px] font-bold text-white leading-none"
                        ></span>
                    </button>

                    {{-- Cart popup --}}
                    <div
                        x-show="$store.cart.open"
                        x-transition:enter="transition ease-out duration-150"
                        x-transition:enter-start="opacity-0 -translate-y-1"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-100"
                        x-transition:leave-start="opacity-100 translate-y-0"
                        x-transition:leave-end="opacity-0 -translate-y-1"
                        style="display:none;"
                        class="absolute right-0 top-full z-50 mt-2 w-[340px] overflow-hidden rounded-2xl bg-white/80 backdrop-blur-xl shadow-xl shadow-zinc-900/15 ring-1 ring-zinc-200"
                        role="menu"
                    >
                        {{-- Empty state --}}
                        <div x-show="$store.cart.count === 0" class="flex flex-col items-center px-6 py-7 text-center">
                            <h3 class="text-xl font-bold text-zinc-900">Your cart is empty</h3>
                            <img src="{{ asset('assets/' . rawurlencode('Empty cart.png')) }}" alt="" class="mt-4 h-40 w-auto object-contain animate-float" loading="lazy">
                            <p class="mt-3 text-sm text-zinc-600">Your cart needs items</p>
                        </div>

                        {{-- Populated state --}}
                        <div x-show="$store.cart.count > 0" x-cloak>
                            <div class="flex items-center justify-between px-5 pt-5">
                                <h3 class="text-lg font-bold text-zinc-900">Your cart</h3>
                                <span class="text-sm text-zinc-600" x-text="$store.cart.count + ' item' + ($store.cart.count === 1 ? '' : 's')"></span>
                            </div>

                            <ul class="mt-3 max-h-72 space-y-1 overflow-y-auto px-3 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                                <template x-for="item in $store.cart.items" :key="item.id">
                                    <li class="flex items-center gap-3 rounded-xl px-2 py-2.5">
                                        <span class="flex aspect-[16/10] w-20 shrink-0 items-center justify-center overflow-hidden rounded-[2px] bg-white shadow-sm ring-1 ring-zinc-200">
                                            <template x-if="item.logo">
                                                <img :src="item.logo" alt="" class="h-full w-full object-cover">
                                            </template>
                                            <template x-if="!item.logo">
                                                <span class="text-sm font-black uppercase text-zinc-700" x-text="item.name.substring(0,2).toUpperCase()"></span>
                                            </template>
                                        </span>
                                        <span class="min-w-0 flex-1">
                                            <span class="block truncate text-sm font-bold text-zinc-900" x-text="item.name"></span>
                                            <span class="block truncate text-xs text-zinc-500">
                                                <span x-show="item.face_label" x-text="item.face_label"></span><span x-show="item.face_label && item.country"> &middot; </span><span x-text="item.country"></span>
                                            </span>
                                            <span class="block text-xs font-semibold text-zinc-700">
                                                <span x-text="$store.cart.pay(item.unit_price)"></span>
                                                <span x-show="$store.cart.showUsd" class="font-normal text-zinc-400" x-text="'(' + $store.cart.usd(item.unit_price_usd) + ')'"></span>
                                            </span>
                                        </span>
                                        {{-- Quantity counter --}}
                                        <span class="flex shrink-0 items-center gap-1.5">
                                            <button type="button" @click="$store.cart.setQty(item.id, item.quantity - 1)" class="flex h-7 w-7 items-center justify-center rounded-full text-zinc-600 ring-1 ring-zinc-200 transition-colors hover:bg-zinc-100 hover:text-zinc-900" aria-label="Decrease">
                                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" d="M5 12h14"/></svg>
                                            </button>
                                            <span class="w-5 text-center text-sm font-bold tabular-nums text-zinc-900" x-text="item.quantity"></span>
                                            <button type="button" @click="$store.cart.setQty(item.id, item.quantity + 1)" class="flex h-7 w-7 items-center justify-center rounded-full text-zinc-600 ring-1 ring-zinc-200 transition-colors hover:bg-zinc-100 hover:text-zinc-900" aria-label="Increase">
                                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></svg>
                                            </button>
                                        </span>
                                    </li>
                                </template>
                            </ul>

                            <div class="mx-5 flex items-start justify-between border-t border-zinc-200 py-4">
                                <span class="text-sm font-medium text-zinc-700">Subtotal</span>
                                <span class="text-right">
                                    <span class="block text-base font-bold tabular-nums text-zinc-900" x-text="$store.cart.pay($store.cart.subtotal)"></span>
                                    <span x-show="$store.cart.showUsd" class="block text-xs text-zinc-500" x-text="'(' + $store.cart.usd($store.cart.subtotalUsd) + ' USD)'"></span>
                                </span>
                            </div>

                            <div class="flex gap-2 border-t border-zinc-100 bg-zinc-50 p-3">
                                <a href="{{ route('shop.cart') }}" wire:navigate @click="$store.cart.open = false; locked = false" class="flex-1 inline-flex items-center justify-center rounded-xl bg-white px-4 py-2.5 text-sm font-semibold text-zinc-700 ring-1 ring-zinc-200 transition-colors hover:bg-zinc-100">
                                    View cart
                                </a>
                                <a :href="'{{ route('shop.checkout') }}' + ($store.cart.showUsd ? '?currency=' + $store.cart.currency : '')" wire:navigate @click="$store.cart.open = false" class="flex-1 inline-flex items-center justify-center rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-blue-700">
                                    Checkout
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </div>

    {{-- Category shortcut bar — desktop only. Hidden on mobile to remove the horizontal scroll noise. --}}
    <div class="hidden md:block">
        <nav aria-label="Product categories" class="max-w-[1350px] mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex h-[40px] gap-1 overflow-x-auto justify-start md:justify-center [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                @php
                    $catLinkClass = "group relative flex h-full shrink-0 items-center gap-2 px-3 text-sm font-medium transition-colors duration-150 whitespace-nowrap focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/30 after:content-[''] after:absolute after:bottom-0 after:left-1/2 after:-translate-x-1/2 after:h-0.5 after:w-[0.5cm] after:rounded-full";
                    $catSvgClass = "w-[25px] h-[25px] shrink-0 text-zinc-900";
                @endphp

                @php $catImgClass = 'w-[22px] h-[22px] shrink-0'; @endphp

                {{-- Gift Cards --}}
                <a href="{{ route('shop.gift-cards') }}" wire:navigate @click="activeCategory = 'Gift Cards'" :class="activeCategory === 'Gift Cards' ? 'text-zinc-900 font-semibold after:bg-zinc-900' : 'text-zinc-600 hover:text-zinc-800 after:bg-transparent'" class="{{ $catLinkClass }}">
                    <img src="{{ asset('assets/' . rawurlencode('gift cards.svg')) }}" alt="" class="{{ $catImgClass }}" loading="lazy">
                    Gift Cards
                </a>

                {{-- eSIMs --}}
                <a href="{{ route('shop.esims') }}" wire:navigate @click="activeCategory = 'eSIMs'" :class="activeCategory === 'eSIMs' ? 'text-zinc-900 font-semibold after:bg-zinc-900' : 'text-zinc-600 hover:text-zinc-800 after:bg-transparent'" class="{{ $catLinkClass }}">
                    <img src="{{ asset('assets/' . rawurlencode('esim.svg')) }}" alt="" class="{{ $catImgClass }}" loading="lazy">
                    eSIMs
                </a>

                {{-- Mobile top up --}}
                <a href="#" @click.prevent="activeCategory = 'Mobile top up'" :class="activeCategory === 'Mobile top up' ? 'text-zinc-900 font-semibold after:bg-zinc-900' : 'text-zinc-600 hover:text-zinc-800 after:bg-transparent'" class="{{ $catLinkClass }}">
                    <img src="{{ asset('assets/' . rawurlencode('topup1.svg')) }}" alt="" class="{{ $catImgClass }}" loading="lazy">
                    Mobile top up
                </a>

                {{-- Bill payments --}}
                <a href="#" @click.prevent="activeCategory = 'Bill payments'" :class="activeCategory === 'Bill payments' ? 'text-zinc-900 font-semibold after:bg-zinc-900' : 'text-zinc-600 hover:text-zinc-800 after:bg-transparent'" class="{{ $catLinkClass }}">
                    <img :src="activeCategory === 'Bill payments' ? '{{ asset('assets/' . rawurlencode('bill payment.svg')) }}' : '{{ asset('assets/' . rawurlencode('Bills 2.svg')) }}'" alt="" class="{{ $catImgClass }}" loading="lazy">
                    Bill payments
                </a>

                {{-- Flights --}}
                <a href="#" @click.prevent="activeCategory = 'Flights'" :class="activeCategory === 'Flights' ? 'text-zinc-900 font-semibold after:bg-zinc-900' : 'text-zinc-600 hover:text-zinc-800 after:bg-transparent'" class="{{ $catLinkClass }}">
                    <img :src="activeCategory === 'Flights' ? '{{ asset('assets/' . rawurlencode('flight.svg')) }}' : '{{ asset('assets/' . rawurlencode('flight 2.svg')) }}'" alt="" class="{{ $catImgClass }}" loading="lazy">
                    Flights
                </a>

                {{-- Stays --}}
                <a href="#" @click.prevent="activeCategory = 'Stays'" :class="activeCategory === 'Stays' ? 'text-zinc-900 font-semibold after:bg-zinc-900' : 'text-zinc-600 hover:text-zinc-800 after:bg-transparent'" class="{{ $catLinkClass }}">
                    <img :src="activeCategory === 'Stays' ? '{{ asset('assets/' . rawurlencode('stay.svg')) }}' : '{{ asset('assets/' . rawurlencode('stay 2.svg')) }}'" alt="" class="{{ $catImgClass }}" loading="lazy">
                    Stays
                </a>

            </div>
        </nav>
    </div>

</div>
