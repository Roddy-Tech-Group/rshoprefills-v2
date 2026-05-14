<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-[#f1f6fb] text-zinc-900">
        <flux:sidebar sticky stashable class="bg-white">
            <flux:sidebar.toggle class="lg:hidden" icon="x-mark" />

            {{-- Brand — uses the storefront logo PNG (oversized + clipped to wordmark band, same pattern as nav) --}}
            <a href="{{ route('dashboard') }}" class="mr-5 -ml-1 flex flex-col rounded-md focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/40" wire:navigate>
                <span class="flex h-9 items-center overflow-hidden">
                    <img
                        src="{{ asset('assets/Rshoprefillslogo.png') }}"
                        alt="RshopRefills"
                        class="h-[190px] w-auto max-w-none object-contain saturate-[1.25]"
                    />
                </span>
                <span class="pl-8 mt-0.5 text-[10px] font-medium italic leading-none text-zinc-400">Est. 2024</span>
            </a>

            {{--
                Main navigation — plain HTML so it's guaranteed to render without Flux Pro.

                Wired (real routes exist on backend):
                  - Overview          → route('dashboard')
                  - Orders            → route('admin.orders')
                  - Customers         → route('admin.customers')
                  - Transactions      → route('admin.transactions')
                  - Wallets           → route('admin.wallets')
                  - System Settings   → route('settings.profile')

                Pending (waiting on backend admin controllers + routes — placeholders for now):
                  - admin.products, admin.gift-cards, admin.esim-orders, admin.topup-orders,
                    admin.reports, admin.marketing, admin.support-tickets
            --}}
            @php
                $isCurrent = fn (...$patterns) => request()->routeIs(...$patterns);
                $navItemClass = fn (bool $active) => $active
                    ? 'group flex items-center gap-3 rounded-[20px] bg-blue-600 px-3 py-3 text-sm font-semibold text-white shadow-sm'
                    : 'group flex items-center gap-3 rounded-[20px] px-3 py-3 text-sm font-medium text-zinc-700 transition-colors hover:bg-blue-600 hover:text-white';
                $iconClass = fn (bool $active) => $active
                    ? 'h-5 w-5 shrink-0 text-white'
                    : 'h-5 w-5 shrink-0 text-zinc-500 transition-colors group-hover:text-white';
            @endphp

            <nav class="mt-4 flex flex-col gap-1" aria-label="Admin">
                {{-- Overview --}}
                @php $active = $isCurrent('dashboard'); @endphp
                <a href="{{ route('dashboard') }}" wire:navigate class="{{ $navItemClass($active) }}">
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
                <div x-data="{ expanded: {{ $productActive ? 'true' : 'false' }} }" class="flex flex-col gap-1">
                    <button
                        type="button"
                        @click="expanded = !expanded"
                        :aria-expanded="expanded.toString()"
                        class="{{ $navItemClass($productActive) }} w-full justify-between"
                    >
                        <span class="flex items-center gap-3">
                            <svg class="{{ $iconClass($productActive) }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007zM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0z"/>
                            </svg>
                            Products
                        </span>
                        <svg :class="expanded && 'rotate-180'" class="h-4 w-4 shrink-0 transition-transform {{ $productActive ? 'text-white' : 'text-zinc-400 group-hover:text-white' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <div x-show="expanded" x-collapse class="ml-5 flex flex-col gap-1 border-l border-zinc-200 pl-4">
                        @php $sub = $isCurrent('admin.products') && !$isCurrent('admin.products.*'); @endphp
                        <a href="#" class="{{ $subItemClass($sub) }}">All Products</a>

                        @php $sub = $isCurrent('admin.gift-cards*'); @endphp
                        <a href="#" class="{{ $subItemClass($sub) }}">Gift Cards</a>

                        @php $sub = $isCurrent('admin.esims*'); @endphp
                        <a href="#" class="{{ $subItemClass($sub) }}">eSIMs</a>

                        @php $sub = $isCurrent('admin.mobile-topups*'); @endphp
                        <a href="#" class="{{ $subItemClass($sub) }}">Mobile Top-ups</a>

                        @php $sub = $isCurrent('admin.bill-payments*'); @endphp
                        <a href="#" class="{{ $subItemClass($sub) }}">Bill Payments</a>

                        @php $sub = $isCurrent('admin.flights*'); @endphp
                        <a href="#" class="{{ $subItemClass($sub) }}">Flights</a>

                        @php $sub = $isCurrent('admin.stays*'); @endphp
                        <a href="#" class="{{ $subItemClass($sub) }}">Stays</a>
                    </div>
                </div>

                {{-- Orders --}}
                @php $active = $isCurrent('admin.orders*'); @endphp
                <a href="{{ route('admin.orders') }}" wire:navigate class="{{ $navItemClass($active) }}">
                    <svg class="{{ $iconClass($active) }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z"/>
                    </svg>
                    Orders
                </a>

                {{-- Customers --}}
                @php $active = $isCurrent('admin.customers*'); @endphp
                <a href="{{ route('admin.customers') }}" wire:navigate class="{{ $navItemClass($active) }}">
                    <svg class="{{ $iconClass($active) }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0z"/>
                    </svg>
                    Customers
                </a>

                {{-- Transactions --}}
                @php $active = $isCurrent('admin.transactions*'); @endphp
                <a href="{{ route('admin.transactions') }}" wire:navigate class="{{ $navItemClass($active) }}">
                    <svg class="{{ $iconClass($active) }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z"/>
                    </svg>
                    Transactions
                </a>

                {{-- Wallets --}}
                @php $active = $isCurrent('admin.wallets*'); @endphp
                <a href="{{ route('admin.wallets') }}" wire:navigate class="{{ $navItemClass($active) }}">
                    <svg class="{{ $iconClass($active) }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a2.25 2.25 0 0 0-2.25-2.25H15a3 3 0 1 1-6 0H5.25A2.25 2.25 0 0 0 3 12m18 0v6a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 18v-6m18 0V9M3 12V9m18 0a2.25 2.25 0 0 0-2.25-2.25H5.25A2.25 2.25 0 0 0 3 9m18 0V6a2.25 2.25 0 0 0-2.25-2.25H5.25A2.25 2.25 0 0 0 3 6v3"/>
                    </svg>
                    Wallets
                </a>

                {{-- Reports --}}
                @php $active = $isCurrent('admin.reports*'); @endphp
                <a href="#" class="{{ $navItemClass($active) }}">
                    <svg class="{{ $iconClass($active) }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125z"/>
                    </svg>
                    Reports
                </a>

                {{-- Marketing --}}
                @php $active = $isCurrent('admin.marketing*'); @endphp
                <a href="#" class="{{ $navItemClass($active) }}">
                    <svg class="{{ $iconClass($active) }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.34 15.84c-.688-.06-1.386-.09-2.09-.09H7.5a4.5 4.5 0 1 1 0-9h.75c.704 0 1.402-.03 2.09-.09m0 9.18c.253.962.584 1.892.985 2.783.247.55.06 1.21-.463 1.511l-.657.38c-.551.318-1.26.117-1.527-.461a20.845 20.845 0 0 1-1.44-4.282m3.102.069a18.03 18.03 0 0 1-.59-4.59c0-1.586.205-3.124.59-4.59m0 9.18a23.848 23.848 0 0 1 8.835 2.535M10.34 6.66a23.847 23.847 0 0 0 8.835-2.535m0 0A23.74 23.74 0 0 0 18.795 3m.38 1.125a23.91 23.91 0 0 1 1.014 5.395m-1.014 8.855c-.118.38-.245.754-.38 1.125m.38-1.125a23.91 23.91 0 0 0 1.014-5.395m0-3.46c.495.413.811 1.035.811 1.73 0 .695-.316 1.317-.811 1.73m0-3.46a24.347 24.347 0 0 1 0 3.46"/>
                    </svg>
                    Marketing
                </a>

                {{-- Support Tickets --}}
                @php $active = $isCurrent('admin.support-tickets*'); @endphp
                <a href="#" class="{{ $navItemClass($active) }}">
                    <svg class="{{ $iconClass($active) }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.712 4.33a9.027 9.027 0 0 1 1.652 1.306c.51.51.944 1.064 1.306 1.652M16.712 4.33l-3.448 4.138m3.448-4.138a9.014 9.014 0 0 0-9.424 0M19.67 7.288l-4.138 3.448m4.138-3.448a9.014 9.014 0 0 1 0 9.424m-4.138-5.976a3.736 3.736 0 0 0-.88-1.388 3.737 3.737 0 0 0-1.388-.88m2.268 2.268a3.765 3.765 0 0 1 0 2.528m-2.268-4.796a3.765 3.765 0 0 0-2.528 0m4.796 4.796c-.181.506-.475.982-.88 1.388a3.736 3.736 0 0 1-1.388.88m2.268-2.268 4.138 3.448m0 0a9.027 9.027 0 0 1-1.306 1.652c-.51.51-1.064.944-1.652 1.306m0 0-3.448-4.138m3.448 4.138a9.014 9.014 0 0 1-9.424 0m5.976-4.138a3.765 3.765 0 0 1-2.528 0m0 0a3.736 3.736 0 0 1-1.388-.88 3.737 3.737 0 0 1-.88-1.388m2.268 2.268L7.288 19.67m0 0a9.024 9.024 0 0 1-1.652-1.306 9.027 9.027 0 0 1-1.306-1.652m0 0 4.138-3.448m-4.138 3.448a9.014 9.014 0 0 1 0-9.424m4.138 5.976a3.765 3.765 0 0 1 0-2.528m0 0c.181-.506.475-.982.88-1.388a3.736 3.736 0 0 1 1.388-.88m-2.268 2.268L4.33 7.288m6.406 1.18L7.288 4.33m0 0a9.024 9.024 0 0 0-1.652 1.306A9.025 9.025 0 0 0 4.33 7.288"/>
                    </svg>
                    Support Tickets
                </a>

                {{-- System Settings --}}
                @php $active = $isCurrent('settings.*'); @endphp
                <a href="{{ route('settings.profile') }}" wire:navigate class="{{ $navItemClass($active) }}">
                    <svg class="{{ $iconClass($active) }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a6.759 6.759 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/>
                    </svg>
                    System Settings
                </a>
            </nav>

        </flux:sidebar>

        {{-- Top header (visible on all sizes). Search, country, language, help, notifications are placeholder UI; profile is wired to auth + logout. --}}
        <flux:header sticky class="sticky top-0 z-40 min-h-[60px] items-center !border-b-0 bg-white px-4 py-2 sm:px-6">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            {{-- Page title (replaces the search bar). Static "Overview" for now; refactor to a dynamic prop when more admin pages exist. --}}
            <div class="hidden md:flex md:flex-col md:leading-tight">
                <h1 class="text-xl font-bold text-zinc-900 sm:text-2xl">Overview</h1>
                <p class="text-xs text-zinc-900">Track performance and key metrics across your marketplace.</p>
            </div>

            <flux:spacer />

            {{-- Language dropdown (Alpine-driven, light theme) --}}
            <div
                x-data="{ open: false, selected: 'English', options: ['English','French','Spanish','Portuguese','Arabic'] }"
                @click.outside="open = false"
                @keydown.escape.window="open = false"
                class="relative hidden lg:block"
            >
                <button
                    type="button"
                    @click="open = !open"
                    :aria-expanded="open.toString()"
                    class="inline-flex items-center gap-2 rounded-xl border border-zinc-200 bg-white px-3 py-2 text-sm font-medium text-zinc-700 transition-colors hover:bg-zinc-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/40"
                >
                    <img src="{{ asset('assets/' . rawurlencode('global svg.svg')) }}" alt="" class="h-4 w-4" loading="lazy">
                    <span x-text="selected">English</span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" :class="open && 'rotate-180'" class="h-4 w-4 text-zinc-400 transition-transform" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" /></svg>
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

            {{-- Notifications dropdown (Alpine-driven, light theme).
                 Backend hook: swap $notificationCount and loop real items inside the populated branch. --}}
            @php $notificationCount = 1; @endphp
            <div
                x-data="{ open: false }"
                @click.outside="open = false"
                @keydown.escape.window="open = false"
                class="relative"
            >
                <button
                    type="button"
                    @click="open = !open"
                    :aria-expanded="open.toString()"
                    class="relative flex h-10 w-10 items-center justify-center rounded-xl text-zinc-600 transition-colors hover:bg-zinc-100"
                    aria-label="Notifications"
                >
                    <img src="{{ asset('assets/' . rawurlencode('notification svg 2.svg')) }}" alt="" class="h-5 w-5" loading="lazy">
                    @if ($notificationCount > 0)
                        <span class="pointer-events-none absolute -top-0.5 -right-0.5 inline-flex">
                            <span class="absolute inset-0 inline-flex animate-ping rounded-full bg-red-400 opacity-75"></span>
                            <span class="relative inline-flex h-5 min-w-[20px] items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-bold text-white">{{ $notificationCount }}</span>
                        </span>
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
                    class="absolute right-0 top-full z-50 mt-2 w-[320px] overflow-hidden rounded-xl bg-white shadow-xl shadow-zinc-900/10 ring-1 ring-zinc-200"
                    role="menu"
                >
                    <div class="flex items-center justify-between border-b border-zinc-100 px-4 py-3">
                        <p class="text-sm font-semibold text-zinc-900">Notifications</p>
                        @if ($notificationCount > 0)
                            <span class="text-[11px] font-medium text-blue-600">{{ $notificationCount }} new</span>
                        @endif
                    </div>

                    @if ($notificationCount === 0)
                        <div class="flex flex-col items-center px-4 py-8 text-center">
                            <span class="flex h-12 w-12 items-center justify-center rounded-full bg-zinc-100">
                                <img src="{{ asset('assets/' . rawurlencode('notification svg 2.svg')) }}" alt="" class="h-6 w-6 opacity-40" loading="lazy">
                            </span>
                            <p class="mt-3 text-sm font-medium text-zinc-700">You're all caught up</p>
                            <p class="mt-1 text-xs text-zinc-500">New notifications will appear here.</p>
                        </div>
                    @else
                        <div class="max-h-80 overflow-y-auto p-2">
                            <a href="#" class="group flex items-start gap-3 rounded-[10px] px-3 py-2.5 transition-colors hover:bg-zinc-400">
                                <span class="mt-1 flex h-2 w-2 shrink-0 rounded-full bg-blue-600"></span>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-medium text-zinc-900">New order placed</p>
                                    <p class="mt-0.5 text-xs text-zinc-500">A customer just placed a new order.</p>
                                    <p class="mt-1 text-[10px] text-zinc-400">just now</p>
                                </div>
                            </a>
                        </div>
                    @endif

                    <div class="border-t border-zinc-100 p-1.5">
                        <a href="#" class="group flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-zinc-700 transition-colors hover:bg-blue-600 hover:text-white">
                            <svg class="h-4 w-4 text-zinc-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 13.5h3.86a2.25 2.25 0 012.012 1.244l.256.512a2.25 2.25 0 002.013 1.244h3.218a2.25 2.25 0 002.013-1.244l.256-.512a2.25 2.25 0 012.013-1.244h3.859m-19.5.338V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18v-4.162c0-.224-.034-.447-.1-.661L19.24 5.338a2.25 2.25 0 00-2.15-1.588H6.911a2.25 2.25 0 00-2.15 1.588L2.35 13.177a2.25 2.25 0 00-.1.661z" />
                            </svg>
                            View all notifications
                        </a>
                    </div>
                </div>
            </div>

            {{-- Profile dropdown (Alpine-driven, light theme) --}}
            <div
                x-data="{ open: false }"
                @click.outside="open = false"
                @keydown.escape.window="open = false"
                class="relative"
            >
                <button
                    type="button"
                    @click="open = !open"
                    :aria-expanded="open.toString()"
                    class="ml-1 flex items-center gap-2.5 rounded-xl py-1.5 pl-1.5 pr-3 transition-colors hover:bg-zinc-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/40"
                >
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-blue-50 ring-1 ring-blue-100">
                        <img src="{{ asset('assets/' . rawurlencode('new user svg.svg')) }}" alt="" class="h-5 w-5" loading="lazy">
                    </span>
                    <span class="hidden text-left leading-tight sm:block">
                        <span class="block truncate text-sm font-semibold text-zinc-900">{{ auth()->user()->name }}</span>
                        <span class="block truncate text-[11px] text-zinc-500">Super Admin</span>
                    </span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" :class="open && 'rotate-180'" class="hidden h-4 w-4 text-zinc-400 transition-transform sm:block" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" /></svg>
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
                    {{-- User info card --}}
                    <div class="border-b border-zinc-100 px-4 py-3">
                        <p class="truncate text-sm font-semibold text-zinc-900">{{ auth()->user()->name }}</p>
                        <p class="truncate text-xs text-zinc-500">{{ auth()->user()->email }}</p>
                    </div>

                    <div class="p-1.5">
                        <a href="{{ route('settings.profile') }}" wire:navigate class="group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-zinc-700 transition-colors hover:bg-blue-600 hover:text-white">
                            <img src="{{ asset('assets/' . rawurlencode('user.svg')) }}" alt="" class="h-4 w-4 shrink-0 transition group-hover:brightness-0 group-hover:invert" loading="lazy">
                            Account information
                        </a>
                        <a href="#" class="group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-zinc-700 transition-colors hover:bg-blue-600 hover:text-white">
                            <img src="{{ asset('assets/' . rawurlencode('Account activities.svg')) }}" alt="" class="h-4 w-4 shrink-0 transition group-hover:brightness-0 group-hover:invert" loading="lazy">
                            Account activity
                        </a>
                        <a href="#" class="group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-zinc-700 transition-colors hover:bg-blue-600 hover:text-white">
                            <img src="{{ asset('assets/' . rawurlencode('notification svg 2.svg')) }}" alt="" class="h-4 w-4 shrink-0 transition group-hover:brightness-0 group-hover:invert" loading="lazy">
                            Notifications Log
                        </a>
                    </div>

                    <div class="border-t border-zinc-100 p-1.5">
                        <form method="POST" action="{{ route('logout') }}" class="w-full">
                            @csrf
                            <button type="submit" class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-left text-sm font-medium text-red-600 transition-colors hover:bg-red-50">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75" />
                                </svg>
                                Log Out
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </flux:header>

        {{ $slot }}

        @fluxScripts
    </body>
</html>
