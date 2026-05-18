<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-[#eff6ff] text-zinc-900">

        @php
            $user = Auth::user();
            $isCurrent = fn (...$patterns) => request()->routeIs(...$patterns);

            $navItem = fn (bool $active) => $active
                ? 'group flex items-center justify-between gap-3 rounded-2xl bg-blue-600 px-3 py-3 text-sm font-semibold text-white shadow-sm'
                : 'group flex items-center justify-between gap-3 rounded-2xl px-3 py-3 text-sm font-medium text-zinc-700 transition-colors hover:bg-blue-100 hover:text-blue-700';
            $iconCls = fn (bool $active) => $active
                ? 'h-5 w-5 shrink-0 text-white'
                : 'h-5 w-5 shrink-0 text-zinc-600 transition-colors group-hover:text-blue-700';
            $subItem = fn (bool $active) => $active
                ? 'flex items-center gap-3 rounded-2xl bg-blue-100 px-3 py-2 text-sm font-semibold text-blue-700'
                : 'flex items-center gap-3 rounded-2xl px-3 py-2 text-sm font-medium text-zinc-600 transition-colors hover:bg-blue-100 hover:text-blue-700';
            // Inline style filters that force a colored SVG (loaded as <img>) to render either as pure black
            // (default state) or pure white (active state). Keeps all sidebar icons visually consistent
            // regardless of the source SVG's own colors.
            $imgIconBlack = 'filter: brightness(0) saturate(100%);';
            $imgIconWhite = 'filter: brightness(0) invert(1);';
            $imgIconStyle = fn (bool $active) => $active ? $imgIconWhite : $imgIconBlack;

            $ordersCount = $user?->orders()->count() ?? 0;
            // Unread notification count — drives the sidebar/avatar/menu badges.
            // The bell dropdown (<livewire:notifications-menu />) computes its own.
            $notificationCount = $user?->notifications()->whereNull('read_at')->count() ?? 0;

            // Default avatar by gender. Backend hook: add a nullable `gender` enum (male|female|other) on users
            // and the right portrait is picked up automatically. Falls back to the male portrait until the column ships.
            $defaultAvatar = asset('assets/' . rawurlencode(match (strtolower($user?->gender ?? '')) {
                'female', 'f' => 'New Female Account Avatar.png',
                default       => 'New male account avatar.png',
            }));
        @endphp

        <flux:sidebar sticky stashable class="hidden w-[256px] bg-white lg:flex">
            <flux:sidebar.toggle class="lg:hidden" icon="x-mark" />

            {{-- Brand --}}
            <a href="{{ route('dashboard') }}" wire:navigate class="mr-5 -ml-1 flex flex-col rounded-md focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/40">
                <span class="flex h-10 items-center">
                    <img
                        src="{{ asset('assets/Rshoprefillslogo.png') }}"
                        alt="RshopRefills"
                        class="h-full w-auto object-contain"
                    />
                </span>
                <span class="mt-0.5 pl-1 text-[10px] font-medium italic leading-none text-zinc-600">Est. 2024</span>
            </a>

            {{-- Primary nav --}}
            <nav class="mt-6 flex flex-col gap-1" aria-label="Account">
                @php $active = $isCurrent('dashboard'); @endphp
                <a href="{{ route('dashboard') }}" wire:navigate class="{{ $navItem($active) }}">
                    <span class="flex items-center gap-3">
                        <svg class="{{ $iconCls($active) }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/>
                        </svg>
                        Overview
                    </span>
                </a>
            </nav>

            {{-- SHOP section (expandable, mirrors admin Products dropdown) --}}
            <p class="mt-6 px-3 text-base font-bold text-zinc-900">Shop</p>
            <nav class="mt-2 flex flex-col gap-1" aria-label="Shop">
                <div
                    x-data="{ expanded: false, locked: false }"
                    @mouseenter="expanded = true"
                    @mouseleave="if (! locked) expanded = false"
                    @click.outside="locked = false; expanded = false"
                    class="flex flex-col gap-1"
                >
                    <button type="button" @click.stop="locked = ! locked; expanded = locked" :aria-expanded="expanded.toString()" class="{{ $navItem(false) }} w-full">
                        <span class="flex items-center gap-3">
                            <img src="{{ asset('assets/' . rawurlencode('Shop.svg')) }}" alt="" class="h-5 w-5 shrink-0" style="{{ $imgIconBlack }}" loading="lazy">
                            Shop
                        </span>
                        <span class="flex items-center gap-2">
                            <span class="rounded-[5px] bg-blue-600 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-white">New</span>
                            <svg :class="expanded && 'rotate-180'" class="h-4 w-4 shrink-0 text-zinc-600 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </span>
                    </button>

                    <div x-show="expanded" x-collapse class="ml-5 flex flex-col gap-1 border-l border-zinc-200 pl-3">
                        <a href="{{ route('home') }}" wire:navigate class="{{ $subItem(false) }}">
                            <img src="{{ asset('assets/' . rawurlencode('Shop.svg')) }}" alt="" class="h-4 w-4 shrink-0" style="{{ $imgIconBlack }}" loading="lazy">
                            All Categories
                        </a>
                        @foreach ([
                            ['Gift Cards',     'gift cards.svg', route('shop.gift-cards'), true],
                            ['eSIMs',          'esim.svg',       route('shop.esims'),      true],
                            ['Flights',        'flight 2.svg',   '#',                      false],
                            ['Stays',          'stay 2.svg',     '#',                      false],
                            ['Topups & Bills', 'Bills 2.svg',    '#',                      false],
                        ] as [$label, $icon, $href, $live])
                            <a href="{{ $href }}" @if ($live) wire:navigate @endif class="{{ $subItem(false) }}">
                                <img src="{{ asset('assets/' . rawurlencode($icon)) }}" alt="" class="h-4 w-4 shrink-0" style="{{ $imgIconBlack }}" loading="lazy">
                                {{ $label }}
                            </a>
                        @endforeach
                    </div>
                </div>
            </nav>

            {{-- ACCOUNT section --}}
            <p class="mt-6 px-3 text-base font-bold text-zinc-900">Account</p>
            <nav class="mt-2 flex flex-col gap-1" aria-label="Account management">
                {{-- Orders (placeholder route — view ships when dashboard.orders does) --}}
                @php $active = $isCurrent('dashboard.orders*'); @endphp
                <a href="{{ route('dashboard.orders') }}" wire:navigate class="{{ $navItem($active) }}">
                    <span class="flex items-center gap-3">
                        <img src="{{ asset('assets/' . rawurlencode('order.svg')) }}" alt="" class="h-5 w-5 shrink-0" style="{{ $imgIconStyle($active) }}" loading="lazy">
                        Orders
                    </span>
                    @if ($ordersCount > 0)
                        <span class="rounded-[5px] bg-blue-600 px-2 py-0.5 text-[10px] font-bold text-white">{{ $ordersCount }}</span>
                    @endif
                </a>

                {{-- Wallet --}}
                <a href="#" class="{{ $navItem(false) }}">
                    <span class="flex items-center gap-3">
                        <img src="{{ asset('assets/' . rawurlencode('Wallet.svg')) }}" alt="" class="h-5 w-5 shrink-0" style="{{ $imgIconBlack }}" loading="lazy">
                        Wallet
                    </span>
                </a>

                {{-- Transactions --}}
                <a href="#" class="{{ $navItem(false) }}">
                    <span class="flex items-center gap-3">
                        <img src="{{ asset('assets/' . rawurlencode('transactions.svg')) }}" alt="" class="h-5 w-5 shrink-0" style="{{ $imgIconBlack }}" loading="lazy">
                        Transactions
                    </span>
                </a>

                {{-- Profile --}}
                @php $active = $isCurrent('dashboard.profile'); @endphp
                <a href="{{ route('dashboard.profile') }}" wire:navigate class="{{ $navItem($active) }}">
                    <span class="flex items-center gap-3">
                        <img src="{{ asset('assets/user.svg') }}" alt="" class="h-5 w-5 shrink-0" style="{{ $imgIconStyle($active) }}" loading="lazy">
                        Profile
                    </span>
                </a>

                {{-- Identity verification (KYC) --}}
                @php $active = $isCurrent('dashboard.kyc'); @endphp
                <a href="{{ route('dashboard.kyc') }}" wire:navigate class="{{ $navItem($active) }}">
                    <span class="flex items-center gap-3">
                        <img src="{{ asset('assets/customer.svg') }}" alt="" class="h-5 w-5 shrink-0" style="{{ $imgIconStyle($active) }}" loading="lazy">
                        Verify Identity
                    </span>
                    <span class="rounded-[5px] bg-amber-500 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-white">KYC</span>
                </a>

                {{-- Security (password) --}}
                @php $active = $isCurrent('dashboard.password'); @endphp
                <a href="{{ route('dashboard.password') }}" wire:navigate class="{{ $navItem($active) }}">
                    <span class="flex items-center gap-3">
                        <img src="{{ asset('assets/' . rawurlencode('admin access.svg')) }}" alt="" class="h-5 w-5 shrink-0" style="{{ $imgIconStyle($active) }}" loading="lazy">
                        Security
                    </span>
                </a>

                {{-- Notifications --}}
                <a href="#" class="{{ $navItem(false) }}">
                    <span class="flex items-center gap-3">
                        <img src="{{ asset('assets/' . rawurlencode('Notification 3.svg')) }}" alt="" class="h-5 w-5 shrink-0" style="{{ $imgIconBlack }}" loading="lazy">
                        Notifications
                    </span>
                    @if ($notificationCount > 0)
                        <span class="rounded-[5px] bg-red-500 px-2 py-0.5 text-[10px] font-bold text-white">{{ $notificationCount }}</span>
                    @endif
                </a>

                {{-- Saved Cards --}}
                <a href="#" class="{{ $navItem(false) }}">
                    <span class="flex items-center gap-3">
                        <img src="{{ asset('assets/' . 'savedcard.svg') }}" alt="" class="h-5 w-5 shrink-0" style="{{ $imgIconBlack }}" loading="lazy">
                        Saved Cards
                    </span>
                </a>

                {{-- Referrals (lives on the Rcoin rewards page) --}}
                <a href="{{ route('dashboard.rewards') }}" wire:navigate class="{{ $navItem(request()->routeIs('dashboard.rewards')) }}">
                    <span class="flex items-center gap-3">
                        <img src="{{ asset('assets/referals.png') }}" alt="" class="h-5 w-5 shrink-0 object-contain" loading="lazy">
                        Referrals
                    </span>
                    <span class="rounded-[5px] bg-emerald-500 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-white">Earn</span>
                </a>
            </nav>

            {{-- Need Help card --}}
            <div class="mt-auto pt-6">
                <a href="#" class="flex items-center gap-3 rounded-2xl bg-blue-50 p-3 transition-colors hover:bg-blue-100">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white">
                        <img src="{{ asset('assets/support.svg') }}" alt="" class="h-5 w-5" loading="lazy">
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-semibold text-zinc-900">Need help?</p>
                        <p class="truncate text-xs text-zinc-600">Chat with support</p>
                    </div>
                    <svg class="h-4 w-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
                    </svg>
                </a>
            </div>
        </flux:sidebar>

        {{-- Top header (desktop only). Search is absolutely centered horizontally so it stays middle regardless of cart/profile width on the right. --}}
        <flux:header sticky class="sticky top-0 z-40 hidden min-h-[64px] items-center gap-3 !border-b-0 bg-white/95 px-4 py-2 backdrop-blur-xl sm:px-6 lg:flex">

            {{-- Search bar (matches storefront home-nav style) with results panel --}}
            <div
                x-data="{
                    open: false,
                    query: '',
                    activeTab: 'Overview',
                    tabs: ['Overview', 'Orders', 'Wallet', 'Transactions', 'Profile', 'Settings']
                }"
                @click.outside="open = false"
                @keydown.escape.window="open = false"
                @keydown.window.prevent.ctrl.k="open = true; $nextTick(() => $refs.searchInput.focus())"
                @keydown.window.prevent.meta.k="open = true; $nextTick(() => $refs.searchInput.focus())"
                class="absolute left-1/2 top-1/2 w-full max-w-xl -translate-x-1/2 -translate-y-1/2 px-4"
            >
                <div
                    role="search"
                    @click="$refs.searchInput.focus(); open = true"
                    :class="open ? 'border-blue-500 ring-2 ring-blue-500/15' : 'border-zinc-400 hover:border-zinc-500'"
                    class="group flex w-full items-center gap-3 cursor-text rounded-2xl border-2 bg-white px-4 py-2 transition-all duration-200"
                >
                    <svg class="h-5 w-5 shrink-0 text-zinc-900" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input
                        x-ref="searchInput"
                        x-model="query"
                        @focus="open = true"
                        type="search"
                        placeholder="Search products, brands or categories"
                        aria-label="Search products, brands or categories"
                        autocomplete="off"
                        spellcheck="false"
                        class="flex-1 min-w-0 bg-transparent text-base text-zinc-800 placeholder:text-zinc-600 outline-none"
                    />
                    <span class="hidden items-center gap-1 rounded-md border border-zinc-200 bg-zinc-50 px-1.5 py-0.5 text-[10px] font-semibold text-zinc-600 sm:inline-flex">
                        CTRL <span class="text-zinc-600">+</span> K
                    </span>
                </div>

                {{-- Results panel --}}
                <div
                    x-show="open"
                    x-transition:enter="transition ease-out duration-150"
                    x-transition:enter-start="opacity-0 translate-y-1"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-100"
                    x-transition:leave-start="opacity-100 translate-y-0"
                    x-transition:leave-end="opacity-0 translate-y-1"
                    style="display:none;"
                    class="absolute left-0 right-0 top-full z-50 mt-2 overflow-hidden rounded-2xl bg-white shadow-xl shadow-zinc-900/10 ring-1 ring-zinc-200"
                >
                    {{-- Filter tabs --}}
                    <div class="border-b border-zinc-100 p-3">
                        <div class="flex flex-wrap gap-2">
                            <template x-for="tab in tabs" :key="tab">
                                <button
                                    type="button"
                                    @click="activeTab = tab"
                                    :class="activeTab === tab ? 'bg-zinc-900 text-white' : 'bg-zinc-50 text-zinc-700 hover:bg-blue-100 hover:text-blue-700'"
                                    class="rounded-full px-3 py-1.5 text-xs font-semibold transition-colors"
                                    x-text="tab"
                                ></button>
                            </template>
                        </div>
                    </div>

                    {{-- Most used --}}
                    <div class="p-2">
                        <p class="px-3 pb-1 pt-2 text-xs font-semibold text-zinc-600">Most used</p>

                        @php
                            $searchItems = [
                                ['Wallet',          'Top up & balance',       'Wallet.svg',         '#'],
                                ['Orders',          'Recent purchases',       'order.svg',          route('dashboard.orders')],
                                ['Transactions',    'Activity history',       'transactions.svg',   '#'],
                                ['Profile',         'Account information',    'user.svg',           route('dashboard.profile')],
                                ['Security',        'Password & sessions',    'admin access.svg',   route('dashboard.password')],
                            ];
                        @endphp

                        @foreach ($searchItems as [$title, $subtitle, $icon, $href])
                            <a href="{{ $href }}" @if($href !== '#') wire:navigate @endif class="flex items-center justify-between gap-3 rounded-xl px-3 py-2.5 transition-colors hover:bg-blue-100">
                                <span class="flex items-center gap-3">
                                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-zinc-100 ring-1 ring-zinc-200">
                                        <img src="{{ asset('assets/' . rawurlencode($icon)) }}" alt="" class="h-4 w-4" loading="lazy">
                                    </span>
                                    <span class="leading-tight">
                                        <span class="block text-sm font-semibold text-zinc-900">{{ $title }}</span>
                                        <span class="block text-xs text-zinc-600">{{ $subtitle }}</span>
                                    </span>
                                </span>
                                <svg class="h-4 w-4 text-zinc-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
                                </svg>
                            </a>
                        @endforeach
                    </div>

                    {{-- Help footer --}}
                    <div class="border-t border-zinc-100 px-4 py-3">
                        <p class="text-xs text-zinc-600">Type above to search products, brands, orders or account settings.</p>
                    </div>
                </div>
            </div>

            <flux:spacer />

            {{-- Cart. State lives in the global Alpine cart store ($store.cart) — the same
                 store the storefront nav uses. The popup drops open automatically when an
                 item is added (store.add sets open = true). --}}
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
                    class="relative flex h-11 w-11 items-center justify-center rounded-xl text-zinc-600 transition-colors hover:bg-zinc-50"
                    aria-label="Shopping cart"
                >
                    <img src="{{ asset('assets/' . rawurlencode('new cart.svg')) }}" alt="" class="h-7 w-7" loading="lazy">
                    <span
                        x-show="$store.cart.count > 0"
                        x-text="$store.cart.count"
                        x-cloak
                        class="absolute -top-0.5 -right-0.5 inline-flex h-5 min-w-[20px] items-center justify-center rounded-[5px] bg-blue-600 px-1 text-[10px] font-bold text-white"
                    ></span>
                </button>

                {{-- Cart popup — mirrors the storefront nav popup --}}
                <div
                    x-show="$store.cart.open"
                    x-transition:enter="transition ease-out duration-150"
                    x-transition:enter-start="opacity-0 -translate-y-1"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-100"
                    x-transition:leave-start="opacity-100 translate-y-0"
                    x-transition:leave-end="opacity-0 -translate-y-1"
                    style="display:none;"
                    class="absolute right-0 top-full z-50 mt-2 w-[340px] overflow-hidden rounded-2xl bg-white/80 px-3 py-2 backdrop-blur-xl shadow-xl shadow-zinc-900/15 ring-1 ring-zinc-200"
                    role="menu"
                >
                    {{-- Empty state --}}
                    <div x-show="$store.cart.count === 0" class="flex flex-col items-center px-3 py-5 text-center">
                        <h3 class="text-xl font-bold text-zinc-900">Your cart is empty</h3>
                        <img src="{{ asset('assets/' . rawurlencode('Empty cart.png')) }}" alt="" class="mt-4 h-40 w-auto object-contain animate-float" loading="lazy">
                        <p class="mt-3 text-sm text-zinc-600">Your cart needs items</p>
                    </div>

                    {{-- Populated state --}}
                    <div x-show="$store.cart.count > 0" x-cloak>
                        <div class="flex items-center justify-between px-3 pt-3">
                            <h3 class="text-lg font-bold text-zinc-900">Your cart</h3>
                            <span class="text-sm text-zinc-600" x-text="$store.cart.count + ' item' + ($store.cart.count === 1 ? '' : 's')"></span>
                        </div>

                        <ul class="mt-3 max-h-72 space-y-1 overflow-y-auto px-0 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                            <template x-for="item in $store.cart.items" :key="item.id">
                                <li class="flex items-center gap-3 rounded-xl px-3 py-2.5">
                                    <span class="flex h-16 w-24 shrink-0 items-center justify-center overflow-hidden rounded-[10px] bg-white shadow-sm ring-1 ring-zinc-200">
                                        <template x-if="item.logo">
                                            <img :src="item.logo" alt="" class="h-full w-full object-cover">
                                        </template>
                                        <template x-if="!item.logo">
                                            <span class="text-sm font-black uppercase text-zinc-700" x-text="item.name.substring(0,2).toUpperCase()"></span>
                                        </template>
                                    </span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block truncate text-sm font-bold text-zinc-900" x-text="item.name"></span>
                                        <span class="mt-0.5 block text-xs font-semibold text-zinc-700" x-text="$store.cart.pay(item.unit_price)"></span>
                                    </span>
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

                        <div class="mt-3 flex gap-2 rounded-xl bg-zinc-50 px-3 py-3">
                            <a href="{{ route('shop.cart') }}" wire:navigate @click="$store.cart.open = false; locked = false" class="flex-1 inline-flex items-center justify-center rounded-[10px] bg-white px-4 py-3.5 text-sm font-semibold text-zinc-700 ring-1 ring-zinc-200 transition-colors hover:bg-zinc-100">
                                View cart
                            </a>
                            <a :href="'{{ route('shop.checkout') }}' + ($store.cart.showUsd ? '?currency=' + $store.cart.currency : '')" wire:navigate @click="$store.cart.open = false" class="flex-1 inline-flex items-center justify-center rounded-[10px] bg-blue-600 px-4 py-3.5 text-sm font-semibold text-white transition-colors hover:bg-blue-700">
                                Checkout
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Notification bell (desktop) — hidden lg:block so it never doubles
                 up with the mobile hero bell on small screens. --}}
            <div class="hidden lg:block">
                <livewire:notifications-menu tone="dark" />
            </div>

            {{-- Profile dropdown.
                 Owns its own dropdown state + a small inline currency picker.
                 Hovers open the dropdown; clicks lock it open until clicked elsewhere.
                 Locale state lives on a separate body-root wrapper around the modal
                 so the modal can render outside flux:header (avoids broken centering
                 from transformed sticky ancestors). The locale chip values here listen
                 to a 'locale-updated' window event dispatched by the modal wrapper. --}}
            <div
                x-data="{
                    open: false,
                    locked: false,
                    country: 'United States',
                    countryFlag: '🇺🇸',
                    language: 'English',
                    currency: 'USD',
                    currencySymbol: '$',
                    currencyMenuOpen: false,
                    currencies: [
                        { code: 'USD', name: 'US Dollar',       symbol: '$' },
                        { code: 'XAF', name: 'Central African CFA', symbol: 'FCFA' },
                        { code: 'NGN', name: 'Nigerian Naira',  symbol: '₦' },
                        { code: 'GHS', name: 'Ghanaian Cedi',   symbol: '₵' },
                        { code: 'EUR', name: 'Euro',            symbol: '€' },
                        { code: 'GBP', name: 'British Pound',   symbol: '£' },
                    ],
                    pickCurrency(c) {
                        this.currency = c.code;
                        this.currencySymbol = c.symbol;
                        this.currencyMenuOpen = false;
                    },
                }"
                @mouseenter="if (!locked) open = true"
                @mouseleave="if (!locked) open = false"
                @click.outside="open = false; locked = false; currencyMenuOpen = false"
                @keydown.escape.window="open = false; locked = false; currencyMenuOpen = false"
                @locale-updated.window="country = $event.detail.country; countryFlag = $event.detail.countryFlag; language = $event.detail.language"
                class="relative"
            >
                <button type="button" @click="locked = !locked; open = locked" :aria-expanded="open.toString()" aria-label="{{ $user?->name ?? 'Account' }}" class="relative flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-full bg-blue-100 ring-1 ring-blue-200 transition-all hover:ring-2 hover:ring-blue-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/40">
                    <img src="{{ $user?->avatar_url ?: $defaultAvatar }}" alt="{{ $user?->name ?? 'Account' }}" class="h-full w-full object-cover">
                    @if ($notificationCount > 0)
                        <span class="absolute -top-0.5 -right-0.5 inline-flex h-3 w-3 rounded-full bg-red-500 ring-2 ring-white" aria-label="{{ $notificationCount }} unread notifications"></span>
                    @endif
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
                    class="absolute right-0 top-full z-50 mt-2 w-[260px] overflow-hidden rounded-2xl bg-white shadow-xl shadow-zinc-900/10 ring-1 ring-zinc-200"
                    role="menu"
                >
                    <div class="border-b border-zinc-100 px-4 py-3">
                        <p class="truncate text-sm font-semibold text-zinc-900">{{ $user?->name ?? 'Account' }}</p>
                        <p class="truncate text-xs text-zinc-600">{{ $user?->email ?? '' }}</p>
                    </div>

                    @php
                        // Inline style filter that forces any colored SVG (loaded as <img>) to render as pure black,
                        // so all menu icons share the same monochrome treatment regardless of their source colors.
                        $iconBlack = 'filter: brightness(0) saturate(100%);';
                    @endphp
                    <div class="p-1.5">
                        <a href="{{ route('dashboard.profile') }}" wire:navigate class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-zinc-900 transition-colors hover:bg-blue-100" role="menuitem">
                            <img src="{{ asset('assets/user.svg') }}" alt="" class="h-5 w-5 shrink-0" style="{{ $iconBlack }}" loading="lazy">
                            Profile
                        </a>
                        <a href="{{ route('dashboard.password') }}" wire:navigate class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-zinc-900 transition-colors hover:bg-blue-100" role="menuitem">
                            <img src="{{ asset('assets/' . rawurlencode('admin access.svg')) }}" alt="" class="h-5 w-5 shrink-0" style="{{ $iconBlack }}" loading="lazy">
                            Security
                        </a>
                        <a href="{{ route('dashboard.appearance') }}" wire:navigate class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-zinc-900 transition-colors hover:bg-blue-100" role="menuitem">
                            <img src="{{ asset('assets/' . rawurlencode('Appearance.svg')) }}" alt="" class="h-5 w-5 shrink-0" style="{{ $iconBlack }}" loading="lazy">
                            Appearance
                        </a>

                        {{-- Divider before locale block --}}
                        <div class="my-1 h-px bg-zinc-100"></div>

                        {{-- Language (opens shared locale modal) --}}
                        <button type="button" @click="locked = false; open = false; $dispatch('open-locale-modal')" class="flex w-full items-center justify-between gap-3 rounded-lg px-3 py-2 text-sm font-medium text-zinc-900 transition-colors hover:bg-blue-100" role="menuitem">
                            <span class="flex items-center gap-3">
                                <img src="{{ asset('assets/' . rawurlencode('global svg.svg')) }}" alt="" class="h-5 w-5 shrink-0" style="{{ $iconBlack }}" loading="lazy">
                                Language
                            </span>
                            <span class="text-xs font-semibold text-zinc-600" x-text="language">English</span>
                        </button>

                        {{-- Country (opens shared locale modal) --}}
                        <button type="button" @click="locked = false; open = false; $dispatch('open-locale-modal')" class="flex w-full items-center justify-between gap-3 rounded-lg px-3 py-2 text-sm font-medium text-zinc-900 transition-colors hover:bg-blue-100" role="menuitem">
                            <span class="flex items-center gap-3">
                                <svg class="h-5 w-5 shrink-0 text-zinc-900" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/>
                                </svg>
                                Country
                            </span>
                            <span class="flex items-center gap-1.5 text-xs font-semibold text-zinc-600">
                                <span class="text-sm leading-none" x-text="countryFlag">🇺🇸</span>
                                <span class="max-w-[80px] truncate" x-text="country">United States</span>
                            </span>
                        </button>

                        {{-- Currency (inline expandable list) --}}
                        <div class="flex flex-col">
                            <button type="button" @click="currencyMenuOpen = !currencyMenuOpen" :aria-expanded="currencyMenuOpen.toString()" class="flex w-full items-center justify-between gap-3 rounded-lg px-3 py-2 text-sm font-medium text-zinc-900 transition-colors hover:bg-blue-100" role="menuitem">
                                <span class="flex items-center gap-3">
                                    <svg class="h-5 w-5 shrink-0 text-zinc-900" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                        <circle cx="12" cy="12" r="9"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.5 8h4.25a2 2 0 010 4H9.5m0 0h4.5a2 2 0 010 4H9.5m0-8v8m0-8h-1m1 8h-1m2-10v2m0 8v2"/>
                                    </svg>
                                    Currency
                                </span>
                                <span class="text-xs font-semibold text-zinc-600" x-text="currency">USD</span>
                            </button>

                            <div x-show="currencyMenuOpen" x-collapse class="ml-8 mt-0.5 flex flex-col gap-0.5 border-l border-zinc-200 pl-2">
                                <template x-for="c in currencies" :key="c.code">
                                    <button
                                        type="button"
                                        @click="pickCurrency(c)"
                                        :class="currency === c.code ? 'bg-blue-50 text-blue-700' : 'text-zinc-700 hover:bg-blue-100'"
                                        class="flex items-center justify-between gap-2 rounded-md px-2.5 py-1.5 text-left text-xs font-medium transition-colors"
                                    >
                                        <span class="flex items-center gap-2">
                                            <span class="inline-flex h-4 min-w-[20px] items-center justify-center text-[10px] font-bold text-zinc-900" x-text="c.symbol">$</span>
                                            <span x-text="c.code">USD</span>
                                            <span class="text-zinc-600" x-text="c.name">US Dollar</span>
                                        </span>
                                        <svg x-show="currency === c.code" class="h-3.5 w-3.5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                                        </svg>
                                    </button>
                                </template>
                            </div>
                        </div>

                        {{-- Divider after locale block --}}
                        <div class="my-1 h-px bg-zinc-100"></div>

                        <a href="#" class="flex items-center justify-between gap-3 rounded-lg px-3 py-2 text-sm font-medium text-zinc-900 transition-colors hover:bg-blue-100" role="menuitem">
                            <span class="flex items-center gap-3">
                                <img src="{{ asset('assets/' . rawurlencode('Notification 3.svg')) }}" alt="" class="h-5 w-5 shrink-0" style="{{ $iconBlack }}" loading="lazy">
                                Notifications
                            </span>
                            @if ($notificationCount > 0)
                                <span class="inline-flex h-5 min-w-[20px] items-center justify-center rounded-[5px] bg-red-500 px-1 text-[10px] font-bold text-white">{{ $notificationCount }}</span>
                            @endif
                        </a>
                        <a href="{{ route('home') }}" wire:navigate class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-zinc-900 transition-colors hover:bg-blue-100" role="menuitem">
                            <img src="{{ asset('assets/' . rawurlencode('Back to shop.svg')) }}" alt="" class="h-5 w-5 shrink-0" style="{{ $iconBlack }}" loading="lazy">
                            Back to store
                        </a>
                    </div>

                    <div class="border-t border-zinc-100 p-1.5">
                        <form method="POST" action="{{ route('logout') }}" class="w-full">
                            @csrf
                            <button type="submit" class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-left text-sm font-medium text-red-600 transition-colors hover:bg-red-100 hover:text-red-700">
                                <img src="{{ asset('assets/Logout.svg') }}" alt="" class="h-5 w-5 shrink-0" style="filter: brightness(0) saturate(100%) invert(20%) sepia(98%) saturate(7468%) hue-rotate(355deg) brightness(94%) contrast(101%);" loading="lazy">
                                Log out
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </flux:header>

        @php
            $skipMobileHero = request()->routeIs('dashboard.profile', 'dashboard.password', 'dashboard.appearance');
        @endphp

        {{-- flux:main is the single main-axis child of Flux's layout container.
             ANYTHING that should stack vertically with the page content (mobile blue hero,
             slim inner-page bar, the page slot) goes INSIDE it — otherwise Flux's flex
             container puts them side-by-side with flux:main on mobile. --}}
        <flux:main class="!p-0 !bg-white">

        {{-- Mobile header (blue hero, visible only on mobile).
             Pages can extend the blue area by filling the $mobileHero slot
             (e.g. wallet card on the overview). Inner pages with their own sticky header
             (settings, password, appearance) skip this hero entirely. --}}
        @unless ($skipMobileHero)
        <header class="bg-blue-600 px-5 pb-6 lg:hidden" style="padding-top: max(1.25rem, env(safe-area-inset-top));">
            {{-- Top row: hamburger + brand pill + notifications --}}
            <div class="mb-4 flex items-center justify-between gap-3">
                <button
                    type="button"
                    x-data
                    x-on:click="$dispatch('open-mobile-menu')"
                    aria-label="Open menu"
                    class="flex h-10 w-10 items-center justify-center rounded-full transition-colors hover:bg-white/10 active:scale-95"
                >
                    <img src="{{ asset('assets/' . rawurlencode('Hamburger menu.svg')) }}" alt="" class="h-6 w-6 brightness-0 invert" loading="lazy">
                </button>

                <a href="{{ route('home') }}" wire:navigate class="flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1 text-[11px] font-semibold text-white transition-colors hover:bg-white/15">
                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/>
                    </svg>
                    Back to shop
                </a>

                <livewire:notifications-menu tone="light" />
            </div>

            {{-- Greeting row --}}
            <div class="text-white">
                <h1 class="flex items-center gap-2 text-2xl font-bold tracking-tight sm:text-3xl">
                    <span class="truncate">Hello {{ $user?->name ? str($user->name)->before(' ') : 'there' }}</span>
                    <svg class="h-6 w-6 shrink-0 text-white sm:h-7 sm:w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.05 4.575a1.575 1.575 0 1 0-3.15 0v3m3.15-3v-1.5a1.575 1.575 0 0 1 3.15 0v1.5m-3.15 0 .075 5.925m3.075.75V4.575m0 0a1.575 1.575 0 0 1 3.15 0V15M6.9 7.575a1.575 1.575 0 1 0-3.15 0v8.175a6.75 6.75 0 0 0 6.75 6.75h2.018a5.25 5.25 0 0 0 3.712-1.538l1.732-1.732a5.25 5.25 0 0 0 1.538-3.712l.003-2.024a.668.668 0 0 1 .198-.471 1.575 1.575 0 1 0-2.228-2.228 3.818 3.818 0 0 0-1.12 2.687M6.9 7.575V12m6.27 4.318A4.49 4.49 0 0 1 16.35 15"/>
                    </svg>
                </h1>
                <p class="mt-1 text-sm text-blue-100/85">Welcome back</p>
            </div>

            {{-- Optional mobile hero content rendered inside the blue area --}}
            @isset($mobileHero)
                <div class="mt-5">
                    {{ $mobileHero }}
                </div>
            @endisset
        </header>
        @endunless

        {{-- Sticky mobile top bar for inner pages (settings, password, appearance).
             Acts as the single mobile chrome on those pages — title derived from the route
             so individual pages don't need to render their own sticky header. --}}
        @if ($skipMobileHero)
            @php
                $innerTitle = match (true) {
                    request()->routeIs('dashboard.password')   => 'Security',
                    request()->routeIs('dashboard.appearance') => 'Appearance',
                    default                                    => 'Settings',
                };
            @endphp
            <div class="sticky top-0 z-40 flex items-center justify-between gap-2 border-b border-zinc-100 bg-white px-3 py-2.5 lg:hidden">
                <button
                    type="button"
                    x-data
                    x-on:click="$dispatch('open-mobile-menu')"
                    aria-label="Open menu"
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full transition-colors hover:bg-zinc-100 active:scale-95"
                >
                    <img src="{{ asset('assets/' . rawurlencode('Hamburger menu.svg')) }}" alt="" class="h-5 w-5" style="filter: brightness(0) saturate(100%);" loading="lazy">
                </button>
                <h1 class="truncate text-base font-bold text-zinc-900">{{ $innerTitle }}</h1>
                <livewire:notifications-menu tone="dark" />
            </div>
        @endif

            <div class="min-h-full rounded-tl-[60px] rounded-tr-[60px] bg-[#eff6ff] px-4 pt-5 pb-28 sm:px-6 sm:pt-6 lg:rounded-tr-none lg:px-10 lg:py-8">
                <div class="mx-auto max-w-7xl">
                    {{ $slot }}
                </div>
            </div>
        </flux:main>

        {{-- Mobile bottom tab bar with floating center Menu FAB (mobile + tablet).
             A single bubble slides smoothly between active tabs via Alpine. Initial active index is derived
             from the current route so the bubble lands in the right slot on first paint. --}}
        @php
            $activeIndex = match (true) {
                request()->routeIs('dashboard.orders*')       => 1,
                request()->routeIs('dashboard.transactions*') => 3,
                request()->routeIs('dashboard.profile')       => 4,
                request()->routeIs('dashboard.password')      => 4,
                request()->routeIs('dashboard.appearance')    => 4,
                default                                       => 0, // dashboard / overview
            };
        @endphp
        <div
            x-data="{ active: {{ $activeIndex }} }"
            class="fixed inset-x-0 bottom-0 z-50 lg:hidden"
        >
            <div class="relative">
                {{-- Tab bar shell --}}
                <nav class="relative grid grid-cols-5 items-center rounded-t-3xl border-t border-zinc-100 bg-white px-1.5 py-2 shadow-[0_-4px_20px_-4px_rgba(0,0,0,0.08)]" aria-label="Primary">

                    {{-- Sliding circle bubble. A perfect 48px disc centered on the active column.
                         Position uses `left: calc(N * 20% + 10%)` (column center) + a -translate-x-1/2 to
                         center the bubble on that point. Width % is column width (1/5 of nav). The static
                         style on first paint matches Alpine's first :style evaluation so the bubble doesn't
                         animate from column 0 on initial render. --}}
                    <span
                        aria-hidden="true"
                        style="left: calc({{ $activeIndex }} * 20% + 10%);{{ $activeIndex === 2 ? ' opacity: 0;' : '' }}"
                        :style="`left: calc(${active} * 20% + 10%);`"
                        :class="active === 2 ? 'opacity-0' : 'opacity-100'"
                        class="pointer-events-none absolute top-1/2 h-12 w-12 -translate-x-1/2 -translate-y-1/2 rounded-full bg-white/40 backdrop-blur-md ring-1 ring-white/60 shadow-sm shadow-zinc-900/5 transition-[left,opacity] duration-300 ease-[cubic-bezier(0.22,1,0.36,1)]"
                    ></span>

                    @php
                        $tabs = [
                            ['idx' => 0, 'href' => route('dashboard'),         'icon' => 'Home.svg',           'label' => 'Home',         'nav' => true],
                            ['idx' => 1, 'href' => route('dashboard.orders'), 'icon' => 'order.svg',          'label' => 'Orders',       'nav' => true],
                            // index 2 is the FAB spacer — handled separately below
                            ['idx' => 3, 'href' => '#',                        'icon' => 'transactions 1.svg', 'label' => 'Transactions', 'nav' => false],
                            ['idx' => 4, 'href' => route('dashboard.profile'), 'icon' => 'Profile 1.svg',      'label' => 'Profile',      'nav' => true],
                        ];
                    @endphp

                    {{-- Tabs 0 + 1 --}}
                    @foreach (array_slice($tabs, 0, 2) as $t)
                        <a
                            href="{{ $t['href'] }}"
                            @if ($t['nav'] && $t['href'] !== '#') wire:navigate @endif
                            @click="active = {{ $t['idx'] }}"
                            aria-label="{{ $t['label'] }}"
                            class="relative z-10 flex flex-col items-center justify-center gap-0.5 py-2.5 transition-transform duration-200 active:scale-90"
                        >
                            <img src="{{ asset('assets/' . rawurlencode($t['icon'])) }}" alt="" class="h-5 w-5" loading="lazy">
                            <span class="text-[9px] font-medium leading-none text-blue-600 transition-opacity duration-200" :class="active === {{ $t['idx'] }} ? 'opacity-100 font-semibold' : 'opacity-70'">{{ $t['label'] }}</span>
                        </a>
                    @endforeach

                    {{-- Spacer column for the floating Menu FAB --}}
                    <div class="relative z-10 h-12" aria-hidden="true"></div>

                    {{-- Tabs 2 + 3 (transactions + profile) --}}
                    @foreach (array_slice($tabs, 2, 2) as $t)
                        <a
                            href="{{ $t['href'] }}"
                            @if ($t['nav'] && $t['href'] !== '#') wire:navigate @endif
                            @click="active = {{ $t['idx'] }}"
                            aria-label="{{ $t['label'] }}"
                            class="relative z-10 flex flex-col items-center justify-center gap-0.5 py-2.5 transition-transform duration-200 active:scale-90"
                        >
                            <img src="{{ asset('assets/' . rawurlencode($t['icon'])) }}" alt="" class="h-5 w-5" loading="lazy">
                            <span class="text-[9px] font-medium leading-none text-blue-600 transition-opacity duration-200" :class="active === {{ $t['idx'] }} ? 'opacity-100 font-semibold' : 'opacity-70'">{{ $t['label'] }}</span>
                        </a>
                    @endforeach
                </nav>

                {{-- Safe-area spacer for iOS home indicator. White bg continues seamlessly below the nav. --}}
                <div class="bg-white" style="height: env(safe-area-inset-bottom);"></div>

                {{-- Floating Menu FAB centered above the tab bar — opens the menu popup --}}
                <button
                    type="button"
                    x-data
                    x-on:click="$dispatch('open-mobile-menu')"
                    aria-label="Open menu"
                    class="absolute left-1/2 top-0 flex h-14 w-14 -translate-x-1/2 -translate-y-1/2 items-center justify-center rounded-full bg-blue-600 shadow-lg shadow-blue-600/40 ring-4 ring-white transition-transform hover:scale-105 active:scale-95"
                >
                    <img src="{{ asset('assets/' . rawurlencode('Hamburger menu.svg')) }}" alt="" class="h-6 w-6 brightness-0 invert" loading="lazy">
                </button>
            </div>
        </div>

        {{-- Locale modal at body root.
             Mounted outside flux:header so its position:fixed centers against the viewport
             rather than against a transformed sticky ancestor. Profile dropdown menu items
             dispatch 'open-locale-modal' to open it; we dispatch 'locale-updated' back
             whenever country / language change so the profile chip values stay in sync. --}}
        <div
            x-data="storefrontLocale()"
            x-init="
                init();
                // Profile dropdown chip listens for these events to keep its display values in sync.
                $watch('country',  v => $dispatch('locale-updated', { country: country, countryFlag: countryFlag, language: language }));
                $watch('language', v => $dispatch('locale-updated', { country: country, countryFlag: countryFlag, language: language }));
            "
            x-on:open-locale-modal.window="localeModalOpen = true"
        >
            <x-nav.locale-modal />
        </div>

        {{-- Mobile menu popup. Triggered by every mobile hamburger / Menu FAB via the
             'open-mobile-menu' window event. Renders the sidebar nav as a card grid
             instead of sliding the Flux sidebar drawer out. lg:hidden so it never shows on desktop. --}}
        @php
            $mobileMenuItems = [
                ['label' => 'Home',          'href' => route('dashboard'),           'icon' => 'Home.svg',           'tone' => 'bg-blue-500',     'nav' => true],
                ['label' => 'Shop',          'href' => route('home'),                'icon' => 'Shop.svg',           'tone' => 'bg-pink-500',     'nav' => true],
                ['label' => 'Orders',        'href' => route('dashboard.orders'),    'icon' => 'order.svg',          'tone' => 'bg-sky-500',      'nav' => true],
                ['label' => 'Wallet',        'href' => '#',                          'icon' => 'Wallet.svg',         'tone' => 'bg-emerald-500',  'nav' => false],
                ['label' => 'Transactions',  'href' => '#',                          'icon' => 'transactions 1.svg', 'tone' => 'bg-teal-500',     'nav' => false],
                ['label' => 'Profile',       'href' => route('dashboard.profile'),   'icon' => 'Profile 1.svg',      'tone' => 'bg-indigo-500',   'nav' => true],
                ['label' => 'Verify (KYC)',  'href' => route('dashboard.kyc'),       'icon' => 'customer.svg',       'tone' => 'bg-amber-500',    'nav' => true],
                ['label' => 'Security',      'href' => route('dashboard.password'),  'icon' => 'admin access.svg',   'tone' => 'bg-violet-500',   'nav' => true],
                ['label' => 'Appearance',    'href' => route('dashboard.appearance'),'icon' => 'Appearance.svg',     'tone' => 'bg-fuchsia-500',  'nav' => true],
                ['label' => 'Notifications', 'href' => '#',                          'icon' => 'Notification 3.svg', 'tone' => 'bg-amber-500',    'nav' => false],
                ['label' => 'Saved Cards',   'href' => '#',                          'icon' => 'savedcard.svg',      'tone' => 'bg-rose-500',     'nav' => false],
                ['label' => 'Referrals',     'href' => route('dashboard.rewards'),    'icon' => 'referals.png',       'tone' => 'bg-orange-500',   'nav' => true],
                ['label' => 'Support',       'href' => '#',                          'icon' => 'support.svg',        'tone' => 'bg-cyan-500',     'nav' => false],
            ];
        @endphp
        {{-- Mobile menu wrapper: `contents` removes the div from Flux's grid layout so it
             doesn't push flux:sidebar/header/main into an unexpected row. --}}
        <div
            x-data="{ menuOpen: false }"
            x-on:open-mobile-menu.window="$nextTick(() => menuOpen = true)"
            x-on:keydown.escape.window="menuOpen = false"
            class="contents lg:hidden"
        >
            {{-- Backdrop --}}
            <div
                x-show="menuOpen"
                x-transition:enter="transition-opacity ease-out duration-200"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition-opacity ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                @click="menuOpen = false"
                style="display: none;"
                class="fixed inset-0 z-[60] bg-zinc-900/45 backdrop-blur-sm"
                aria-hidden="true"
            ></div>

            {{-- Bottom-sheet panel (full width, slides up from below) --}}
            <div
                x-show="menuOpen"
                x-transition:enter="transition-transform duration-300 ease-[cubic-bezier(0.22,1,0.36,1)]"
                x-transition:enter-start="translate-y-full"
                x-transition:enter-end="translate-y-0"
                x-transition:leave="transition-transform duration-250 ease-in"
                x-transition:leave-start="translate-y-0"
                x-transition:leave-end="translate-y-full"
                style="display: none;"
                class="fixed inset-x-0 bottom-0 z-[70] rounded-t-3xl bg-white shadow-2xl shadow-zinc-900/25"
                role="dialog"
                aria-modal="true"
                aria-labelledby="mobile-menu-title"
            >
                {{-- Drag handle (visual affordance) --}}
                <div class="flex justify-center pt-3">
                    <span class="h-1.5 w-10 rounded-full bg-zinc-300"></span>
                </div>

                <div class="px-5 pb-[max(20px,env(safe-area-inset-bottom))] pt-4">
                    {{-- Header --}}
                    <div class="mb-5 flex items-center justify-between">
                        <h2 id="mobile-menu-title" class="text-lg font-bold text-zinc-900">Menu</h2>
                        <button
                            type="button"
                            @click="menuOpen = false"
                            class="flex h-9 w-9 items-center justify-center rounded-full text-blue-600 transition-colors hover:bg-blue-50 hover:text-blue-700"
                            aria-label="Close menu"
                        >
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    {{-- Card grid, staggered entrance --}}
                    <div class="skeleton-stagger-fast grid grid-cols-4 gap-3">
                        @foreach ($mobileMenuItems as $i => $item)
                            <a
                                href="{{ $item['href'] }}"
                                @if ($item['nav'] && $item['href'] !== '#') wire:navigate @endif
                                @click="menuOpen = false"
                                style="--i: {{ $i }}"
                                class="group flex flex-col items-center justify-center gap-2 rounded-2xl bg-zinc-100 px-2 py-3 text-center transition-transform duration-200 active:scale-95"
                            >
                                <span class="flex h-12 w-12 items-center justify-center rounded-full {{ $item['tone'] }} shadow-sm transition-transform duration-200 group-hover:scale-105">
                                    <img src="{{ asset('assets/' . rawurlencode($item['icon'])) }}" alt="" class="h-6 w-6 brightness-0 invert" loading="lazy">
                                </span>
                                <span class="text-[11px] font-semibold leading-tight text-zinc-900">{{ $item['label'] }}</span>
                            </a>
                        @endforeach
                    </div>

                    {{-- Logout footer --}}
                    <form method="POST" action="{{ route('logout') }}" class="mt-5 border-t border-zinc-100 pt-4">
                        @csrf
                        <button
                            type="submit"
                            class="flex w-full items-center justify-center gap-2 rounded-xl bg-red-50 px-4 py-2.5 text-sm font-semibold text-red-600 transition-colors hover:bg-red-100"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75"/>
                            </svg>
                            Log out
                        </button>
                    </form>
                </div>
            </div>
        </div>

        @fluxScripts
    </body>
</html>
