<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    {{-- Desktop sidebar-collapse styling. The 'admin-sidebar-collapsed' class
         on <html> is toggled by the round chevron in the header — when set,
         the Flux sidebar shrinks to an icon-only rail and every text label
         hides. We zero out font-size on the nav items to hide raw text nodes
         without rewriting the markup; <svg> and <img> have explicit sizes so
         they keep rendering. Mobile is unaffected (stash already covers it). --}}
    <style>
        @media (min-width: 1024px) {
            html.admin-sidebar-collapsed [data-flux-sidebar] {
                width: 72px !important;
            }
            html.admin-sidebar-collapsed [data-flux-sidebar] nav a,
            html.admin-sidebar-collapsed [data-flux-sidebar] nav button {
                font-size: 0 !important;
                justify-content: center;
                padding-left: 0.5rem !important;
                padding-right: 0.5rem !important;
                gap: 0 !important;
            }
            html.admin-sidebar-collapsed [data-flux-sidebar] nav a > svg,
            html.admin-sidebar-collapsed [data-flux-sidebar] nav button > svg,
            html.admin-sidebar-collapsed [data-flux-sidebar] nav a > img,
            html.admin-sidebar-collapsed [data-flux-sidebar] nav button > img,
            html.admin-sidebar-collapsed [data-flux-sidebar] nav button > span {
                margin: 0 auto;
            }
            /* Expandable groups (Products) — hide chevron + sub-menu in collapsed mode. */
            html.admin-sidebar-collapsed [data-flux-sidebar] nav button > svg:last-child {
                display: none !important;
            }
            html.admin-sidebar-collapsed [data-flux-sidebar] nav .border-l {
                display: none !important;
            }
            /* Brand: hide the "Est. 2024" subtitle so just the wordmark sits centred. */
            html.admin-sidebar-collapsed [data-flux-sidebar] > a > span:last-child {
                display: none !important;
            }
            html.admin-sidebar-collapsed [data-flux-sidebar] > a {
                margin-right: 0 !important;
            }
            /* Soften the resize so the rail glides rather than snaps. */
            [data-flux-sidebar] {
                transition: width 220ms cubic-bezier(0.22, 1, 0.36, 1);
            }
        }
    </style>
    <body class="min-h-screen bg-[#eff6ff] text-zinc-900">

        <flux:sidebar sticky stashable class="bg-white">

            {{-- Brand --}}
            <a href="{{ route('admin.dashboard') }}" class="mr-5 -ml-1 flex flex-col rounded-md focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/40">
                <span class="flex h-10 items-center">
                    <img
                        src="{{ asset('assets/Rshoprefillslogo.png') }}"
                        alt="RshopRefills"
                        class="h-full w-auto object-contain"
                    />
                </span>
                <span class="mt-0.5 pl-1 text-[10px] font-medium italic leading-none text-zinc-600">Est. 2024</span>
            </a>

            {{--
                Admin navigation — plain HTML so it's guaranteed to render without Flux Pro.

                Wired:
                  - Overview, Products (All Products), Orders, Customers, Transactions, Wallets

                Pending (no view shipped yet):
                  - admin.gift-cards, admin.esims, admin.mobile-topups, admin.bill-payments,
                    admin.flights, admin.stays, admin.reports, admin.marketing,
                    admin.support-tickets, admin.settings
            --}}
            @php
                $isCurrent = fn (...$patterns) => request()->routeIs(...$patterns);
                $navItemClass = fn (bool $active) => $active
                    ? 'group flex items-center gap-3 rounded-[20px] bg-blue-600 px-3 py-3 text-sm font-semibold text-white shadow-sm'
                    : 'group flex items-center gap-3 rounded-[20px] px-3 py-3 text-sm font-medium text-zinc-700 transition-colors hover:bg-blue-600 hover:text-white';
                $iconClass = fn (bool $active) => $active
                    ? 'h-5 w-5 shrink-0 text-white'
                    : 'h-5 w-5 shrink-0 text-zinc-600 transition-colors group-hover:text-white';
                // For <img>-based icons (file SVGs): whiten on hover/active to match the blue-600 background.
                $imgIconClass = fn (bool $active) => $active
                    ? 'h-5 w-5 shrink-0 brightness-0 invert'
                    : 'h-5 w-5 shrink-0 transition group-hover:brightness-0 group-hover:invert';
            @endphp

            <nav class="mt-4 flex flex-col gap-1" aria-label="Admin">
                {{-- Overview --}}
                @php $active = $isCurrent('admin.dashboard'); @endphp
                <a href="{{ route('admin.dashboard') }}" class="{{ $navItemClass($active) }}">
                    <svg class="{{ $iconClass($active) }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25zM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25z"/>
                    </svg>
                    Overview
                </a>

                {{-- Products (expandable group — SoC for product/service categories) --}}
                @php
                    $productPatterns = ['admin.products*', 'admin.gift-cards*', 'admin.esims*', 'admin.mobile-topups*', 'admin.bill-payments*', 'admin.flights*', 'admin.stays*'];
                    $productActive = $isCurrent(...$productPatterns);
                    $subItemClass = fn (bool $active) => $active
                        ? 'flex items-center rounded-[20px] bg-blue-50 px-3 py-2.5 text-sm font-semibold text-blue-700'
                        : 'flex items-center rounded-[20px] px-3 py-2.5 text-sm font-medium text-zinc-600 transition-colors hover:bg-blue-600 hover:text-white';
                @endphp
                <div
                    x-data="{ expanded: {{ $productActive ? 'true' : 'false' }}, locked: {{ $productActive ? 'true' : 'false' }} }"
                    @mouseenter="expanded = true"
                    @mouseleave="if (! locked) expanded = false"
                    @click.outside="locked = false; expanded = false"
                    class="flex flex-col gap-1"
                >
                    <button
                        type="button"
                        @click.stop="locked = ! locked; expanded = locked"
                        :aria-expanded="expanded.toString()"
                        class="{{ $navItemClass($productActive) }} w-full justify-between"
                    >
                        <span class="flex items-center gap-3">
                            <img src="{{ asset('assets/' . rawurlencode('Shop.svg')) }}" alt="" class="{{ $imgIconClass($productActive) }}" loading="lazy">
                            Products
                        </span>
                        <svg :class="expanded && 'rotate-180'" class="h-4 w-4 shrink-0 transition-transform {{ $productActive ? 'text-white' : 'text-zinc-600 group-hover:text-white' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <div x-show="expanded" x-collapse class="ml-5 flex flex-col gap-1 border-l border-zinc-200 pl-4">
                        <a href="{{ route('admin.products') }}" class="{{ $subItemClass($isCurrent('admin.products') && !$isCurrent('admin.products.*')) }}">All Products</a>
                        <a href="{{ route('shop.gift-cards') }}" class="{{ $subItemClass($isCurrent('admin.gift-cards*')) }}">Gift Cards</a>
                        <a href="#" class="{{ $subItemClass($isCurrent('admin.esims*')) }}">eSIMs</a>
                        <a href="#" class="{{ $subItemClass($isCurrent('admin.mobile-topups*')) }}">Mobile Top-ups</a>
                        <a href="#" class="{{ $subItemClass($isCurrent('admin.bill-payments*')) }}">Bill Payments</a>
                        <a href="#" class="{{ $subItemClass($isCurrent('admin.flights*')) }}">Flights</a>
                        <a href="#" class="{{ $subItemClass($isCurrent('admin.stays*')) }}">Stays</a>
                    </div>
                </div>

                {{-- Orders --}}
                @php $active = $isCurrent('admin.orders*'); @endphp
                <a href="{{ route('admin.orders') }}" class="{{ $navItemClass($active) }}">
                    <img src="{{ asset('assets/' . rawurlencode('order.svg')) }}" alt="" class="{{ $imgIconClass($active) }}" loading="lazy">
                    Orders
                </a>

                {{-- Customers --}}
                @php $active = $isCurrent('admin.customers*'); @endphp
                <a href="{{ route('admin.customers') }}" class="{{ $navItemClass($active) }}">
                    <img src="{{ asset('assets/' . rawurlencode('customer.svg')) }}" alt="" class="{{ $imgIconClass($active) }}" loading="lazy">
                    Customers
                </a>

                {{-- Transactions --}}
                @php $active = $isCurrent('admin.transactions*'); @endphp
                <a href="{{ route('admin.transactions') }}" class="{{ $navItemClass($active) }}">
                    <img src="{{ asset('assets/' . rawurlencode('transactions.svg')) }}" alt="" class="{{ $imgIconClass($active) }}" loading="lazy">
                    Transactions
                </a>

                {{-- Wallets --}}
                @php $active = $isCurrent('admin.wallets*'); @endphp
                <a href="{{ route('admin.wallets') }}" class="{{ $navItemClass($active) }}">
                    <img src="{{ asset('assets/' . rawurlencode('Wallet.svg')) }}" alt="" class="{{ $imgIconClass($active) }}" loading="lazy">
                    Wallets
                </a>

                {{-- Reports --}}
                @php $active = $isCurrent('admin.reports*'); @endphp
                <a href="#" class="{{ $navItemClass($active) }}">
                    <img src="{{ asset('assets/report.svg') }}" alt="" class="{{ $imgIconClass($active) }}" loading="lazy">
                    Reports
                </a>

                {{-- Marketing --}}
                @php $active = $isCurrent('admin.marketing*'); @endphp
                <a href="#" class="{{ $navItemClass($active) }}">
                    <img src="{{ asset('assets/' . rawurlencode('marketing.svg')) }}" alt="" class="{{ $imgIconClass($active) }}" loading="lazy">
                    Marketing
                </a>

                {{-- Support Tickets --}}
                @php $active = $isCurrent('admin.support-tickets*'); @endphp
                <a href="#" class="{{ $navItemClass($active) }}">
                    <img src="{{ asset('assets/support.svg') }}" alt="" class="{{ $imgIconClass($active) }}" loading="lazy">
                    Support Tickets
                </a>

                {{-- Admins --}}
                @php $active = $isCurrent('admin.admins*'); @endphp
                <a href="#" class="{{ $navItemClass($active) }}">
                    <img src="{{ asset('assets/' . rawurlencode('admin access.svg')) }}" alt="" class="{{ $imgIconClass($active) }}" loading="lazy">
                    Admins
                </a>

                {{-- Rate Management --}}
                @php $active = $isCurrent('admin.rates*'); @endphp
                <a href="{{ route('admin.rates') }}" class="{{ $navItemClass($active) }}">
                    <svg class="{{ $iconClass($active) }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="12" cy="12" r="9"/>
                        <path d="M9.5 8h4.25a2 2 0 010 4H9.5m0 0h4.5a2 2 0 010 4H9.5m0-8v8m0-8h-1m1 8h-1m2-10v2m0 8v2"/>
                    </svg>
                    Rate Management
                </a>

                {{-- System Settings --}}
                @php $active = $isCurrent('admin.settings*'); @endphp
                <a href="#" class="{{ $navItemClass($active) }}">
                    <img src="{{ asset('assets/' . rawurlencode('system setting.svg')) }}" alt="" class="{{ $imgIconClass($active) }}" loading="lazy">
                    System Settings
                </a>
            </nav>

        </flux:sidebar>

        {{-- Top header (sticky). Notifications + profile wired to admin guard. --}}
        {{-- NOTE: do NOT add Flux's `sticky` prop here. It injects an Alpine binding that
             freezes `top` to the header's offsetTop read once at init; if that fires before
             the body grid settles (wire:navigate morph, slow paint) it captures the header's
             block-flow position below the sidebar (~478px) and the bar floats mid-page.
             The `sticky top-0` classes below are pure CSS — no race. --}}
        <flux:header class="sticky top-0 z-40 min-h-[60px] items-center gap-2 !border-b-0 bg-white px-3 py-2 sm:gap-3 sm:px-6">
            {{-- Plain button toggle. Bypasses <flux:sidebar.toggle>, which is a self-closing wrapper
                 around <flux:button square /> and doesn't render slot children. Dispatching
                 'flux-sidebar-toggle' is exactly what Flux's own toggle does internally. --}}
            <button
                type="button"
                x-data
                x-on:click="$dispatch('open-mobile-menu')"
                aria-label="Open menu"
                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl transition-colors hover:bg-zinc-100 active:scale-95 lg:hidden"
            >
                <img src="{{ asset('assets/' . rawurlencode('Hamburger menu.svg')) }}" alt="" class="h-6 w-6" style="filter: brightness(0) saturate(100%);" loading="lazy">
            </button>

            {{-- Desktop sidebar collapse toggle. Rotates 180° when collapsed so
                 the arrow always points the direction the sidebar will move. --}}
            <button
                type="button"
                x-data
                @click="$store.adminSidebar.toggle()"
                :aria-label="$store.adminSidebar.collapsed ? 'Expand sidebar' : 'Collapse sidebar'"
                :aria-pressed="$store.adminSidebar.collapsed.toString()"
                class="hidden h-9 w-9 shrink-0 items-center justify-center rounded-full bg-zinc-100 text-zinc-700 transition-all hover:bg-zinc-200 hover:text-zinc-900 active:scale-95 lg:flex"
            >
                <svg
                    class="h-4 w-4 transition-transform duration-200"
                    :class="$store.adminSidebar.collapsed ? 'rotate-0' : 'rotate-180'"
                    fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
                    aria-hidden="true"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7-7 7M3 12h18"/>
                </svg>
            </button>

            {{-- Page heading is rendered inside the content area (see <flux:main> below),
                 not in this top bar — so every admin page title sits within the page. --}}
            <flux:spacer />

            {{-- Language dropdown (Alpine-driven). Hover opens; click locks open. --}}
            <div
                x-data="{ open: false, locked: false, selected: 'English', options: ['English','French','Spanish','Portuguese','Arabic'] }"
                @mouseenter="if (!locked) open = true"
                @mouseleave="if (!locked) open = false"
                @click.outside="open = false; locked = false"
                @keydown.escape.window="open = false; locked = false"
                class="relative hidden lg:block"
            >
                <button
                    type="button"
                    @click="locked = !locked; open = locked"
                    :aria-expanded="open.toString()"
                    class="inline-flex items-center gap-2 rounded-xl border border-zinc-200 bg-white px-3 py-2 text-sm font-medium text-zinc-700 transition-colors hover:bg-blue-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/40"
                >
                    <img src="{{ asset('assets/' . rawurlencode('global svg.svg')) }}" alt="" class="h-5 w-5" style="filter: brightness(0) saturate(100%);" loading="lazy">
                    <span x-text="selected">English</span>
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
                    class="absolute right-0 top-full z-50 mt-2 w-[200px] overflow-hidden rounded-xl bg-white shadow-xl shadow-zinc-900/10 ring-1 ring-zinc-200"
                    role="menu"
                >
                    <div class="p-1.5">
                        <template x-for="opt in options" :key="opt">
                            <button
                                type="button"
                                @click="selected = opt; open = false"
                                :class="selected === opt ? 'bg-blue-50 text-blue-700' : 'text-zinc-700 hover:bg-blue-600 hover:text-white'"
                                class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-left text-sm font-medium transition-colors"
                            >
                                <span x-text="opt"></span>
                                <svg x-show="selected === opt" class="h-4 w-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                </svg>
                            </button>
                        </template>
                    </div>
                </div>
            </div>

            {{-- Theme toggle — admin's own light/dark/system control, saved per
                 admin account and kept separate from the customer side. --}}
            <x-theme-toggle class="h-10 w-10 rounded-xl text-zinc-600 hover:bg-blue-100" />

            {{-- Admin notifications bell — real AdminNotification feed (KYC, orders, etc.). --}}
            <livewire:admin.notifications-menu />

            {{-- Profile dropdown — wired to admin auth guard. Hover opens; click locks open. --}}
            @php $admin = Auth::guard('admin')->user(); @endphp
            <div
                x-data="{ open: false, locked: false }"
                @mouseenter="if (!locked) open = true"
                @mouseleave="if (!locked) open = false"
                @click.outside="open = false; locked = false"
                @keydown.escape.window="open = false; locked = false"
                class="relative"
            >
                @php
                    // Default avatar by admin gender. Backend can add a `gender` column to admins later
                    // and the right portrait will be picked up automatically. Falls back to the male portrait.
                    $adminDefaultAvatar = asset('assets/' . rawurlencode(match (strtolower($admin?->gender ?? '')) {
                        'female', 'f' => 'New Female Account Avatar.png',
                        default       => 'New male account avatar.png',
                    }));
                @endphp
                <button
                    type="button"
                    @click="locked = !locked; open = locked"
                    :aria-expanded="open.toString()"
                    aria-label="{{ $admin?->name ?? 'Admin' }}"
                    class="ml-1 relative flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-full bg-blue-100 ring-1 ring-blue-200 transition-all hover:ring-2 hover:ring-blue-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/40"
                >
                    <img src="{{ $admin?->avatar_url ?: $adminDefaultAvatar }}" alt="{{ $admin?->name ?? 'Admin' }}" class="h-full w-full object-cover" loading="lazy">
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
                    class="absolute right-0 top-full z-50 mt-2 w-[260px] overflow-hidden rounded-xl bg-white shadow-xl shadow-zinc-900/10 ring-1 ring-zinc-200"
                    role="menu"
                >
                    {{-- Admin info card --}}
                    <div class="border-b border-zinc-100 px-4 py-3">
                        <p class="truncate text-sm font-semibold text-zinc-900">{{ $admin?->name ?? 'Admin' }}</p>
                        <p class="truncate text-xs text-zinc-600">{{ $admin?->email ?? '' }}</p>
                    </div>

                    @php $iconBlack = 'filter: brightness(0) saturate(100%);'; @endphp
                    <div class="p-1.5">
                        <a href="{{ route('admin.account') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-zinc-900 transition-colors hover:bg-blue-100" role="menuitem">
                            <img src="{{ asset('assets/' . rawurlencode('user.svg')) }}" alt="" class="h-5 w-5 shrink-0" style="{{ $iconBlack }}" loading="lazy">
                            Account information
                        </a>
                        <a href="#" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-zinc-900 transition-colors hover:bg-blue-100" role="menuitem">
                            <img src="{{ asset('assets/' . rawurlencode('account avtivities 3.svg')) }}" alt="" class="h-5 w-5 shrink-0" style="{{ $iconBlack }}" loading="lazy">
                            Account activity
                        </a>
                        <a href="{{ route('admin.notifications') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-zinc-900 transition-colors hover:bg-blue-100" role="menuitem">
                            <img src="{{ asset('assets/' . rawurlencode('notification 2.svg')) }}" alt="" class="h-5 w-5 shrink-0" style="{{ $iconBlack }}" loading="lazy">
                            Notifications Log
                        </a>
                    </div>

                    <div class="border-t border-zinc-100 p-1.5">
                        <form method="POST" action="{{ route('admin.logout') }}" class="w-full">
                            @csrf
                            <button type="submit" class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-left text-sm font-medium text-red-600 transition-colors hover:bg-red-100 hover:text-red-700">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75" />
                                </svg>
                                Log Out
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </flux:header>

        {{-- Content area with rounded top-left corner. Padding is provided here so pages just provide their content. --}}
        <flux:main class="!p-0 bg-white">
            <div class="min-h-full rounded-tl-[60px] rounded-tr-[60px] bg-[#eff6ff] px-4 py-6 sm:px-6 lg:rounded-tr-none lg:px-10 lg:py-8">
                <div class="mx-auto max-w-7xl">
                    {{-- Page heading — moved out of the top nav bar into the page itself. --}}
                    <div class="mb-6">
                        <h1 class="text-xl font-bold tracking-tight text-zinc-900 sm:text-2xl">{{ $heading ?? 'Overview' }}</h1>
                        <p class="mt-1 text-sm text-zinc-600">{{ $subheading ?? 'Track performance and key metrics across your marketplace.' }}</p>
                    </div>
                    {{ $slot }}
                </div>
            </div>
        </flux:main>

        {{-- Mobile menu popup. Triggered by the mobile hamburger via 'open-mobile-menu' window event.
             Card grid of admin nav items instead of the Flux sidebar drawer. lg:hidden so it never shows on desktop. --}}
        @php
            $adminMenuItems = [
                ['label' => 'Home',         'href' => route('admin.dashboard'),     'icon' => 'Home.svg',           'tone' => 'bg-blue-500'],
                ['label' => 'Products',     'href' => route('admin.products'),      'icon' => 'Shop.svg',           'tone' => 'bg-pink-500'],
                ['label' => 'Orders',       'href' => route('admin.orders'),        'icon' => 'order.svg',          'tone' => 'bg-sky-500'],
                ['label' => 'Customers',    'href' => route('admin.customers'),     'icon' => 'customer.svg',       'tone' => 'bg-emerald-500'],
                ['label' => 'Transactions', 'href' => route('admin.transactions'),  'icon' => 'transactions.svg',   'tone' => 'bg-teal-500'],
                ['label' => 'Wallets',      'href' => route('admin.wallets'),       'icon' => 'Wallet.svg',         'tone' => 'bg-indigo-500'],
                ['label' => 'Reports',      'href' => '#',                          'icon' => 'report.svg',         'tone' => 'bg-violet-500'],
                ['label' => 'Marketing',    'href' => '#',                          'icon' => 'marketing.svg',      'tone' => 'bg-fuchsia-500'],
                ['label' => 'Support',      'href' => '#',                          'icon' => 'support.svg',        'tone' => 'bg-amber-500'],
                ['label' => 'Admins',       'href' => '#',                          'icon' => 'admin access.svg',   'tone' => 'bg-rose-500'],
                ['label' => 'Rates',        'href' => '#',                          'icon' => 'transactions.svg',   'tone' => 'bg-orange-500'],
                ['label' => 'Settings',     'href' => '#',                          'icon' => 'system setting.svg', 'tone' => 'bg-cyan-500'],
            ];
        @endphp
        {{-- Mobile menu wrapper: `contents` removes the div from Flux's grid layout so it
             doesn't push flux:sidebar/header/main into an unexpected row. The fixed-position
             children inside still anchor correctly to the viewport. --}}
        <div
            x-data="{ menuOpen: false }"
            x-on:open-mobile-menu.window="$nextTick(() => menuOpen = true)"
            x-on:keydown.escape.window="menuOpen = false"
            class="contents lg:hidden"
        >
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

            <div
                x-show="menuOpen"
                x-transition:enter="transition-transform duration-300 ease-[cubic-bezier(0.22,1,0.36,1)]"
                x-transition:enter-start="translate-y-full"
                x-transition:enter-end="translate-y-0"
                x-transition:leave="transition-transform duration-250 ease-in"
                x-transition:leave-start="translate-y-0"
                x-transition:leave-end="translate-y-full"
                style="display: none;"
                class="modal-norise fixed inset-x-0 bottom-0 z-[70] rounded-t-3xl bg-white shadow-2xl shadow-zinc-900/25"
                role="dialog"
                aria-modal="true"
                aria-labelledby="admin-mobile-menu-title"
            >
                <div class="flex justify-center pt-3">
                    <span class="h-1.5 w-10 rounded-full bg-zinc-300"></span>
                </div>

                <div class="px-5 pb-[max(20px,env(safe-area-inset-bottom))] pt-4">
                    {{-- No close X here — backdrop tap and the grab handle above
                         already dismiss the sheet, so the button was redundant. --}}
                    <h2 id="admin-mobile-menu-title" class="mb-5 text-lg font-bold text-zinc-900">Admin menu</h2>

                    <div class="skeleton-stagger-fast grid grid-cols-4 gap-3">
                        @foreach ($adminMenuItems as $i => $item)
                            <a
                                href="{{ $item['href'] }}"
                                @if ($item['href'] !== '#') wire:navigate @endif
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

                    <form method="POST" action="{{ route('admin.logout') }}" class="mt-5 border-t border-zinc-100 pt-4">
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
