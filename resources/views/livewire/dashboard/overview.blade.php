<?php

use App\Domain\Shared\Enums\Currency;
use Livewire\Attributes\Lazy;
use Livewire\Volt\Component;

/**
 * Customer dashboard overview — the main content area of /dashboard.
 *
 * Rendered as a #[Lazy] component: the server returns the skeleton placeholder
 * instantly, then this component boots in a follow-up request, queries its data,
 * and swaps the real content in. The blue mobile hero + sidebar + bottom nav are
 * the layout shell (rendered immediately by dashboard.blade.php).
 */
new #[Lazy] class extends Component
{
    /** Skeleton shown while the component lazy-boots. */
    public function placeholder()
    {
        return view('components.skeletons.dashboard-overview');
    }

    public function with(): array
    {
        $user = auth()->user();

        // ── Wallet data ──────────────────────────────────────────────
        // Default to the user's USD wallet (auto-created on registration).
        $primaryWallet = $user->wallets()->where('currency', 'USD')->first()
            ?? $user->wallets()->first();

        $walletBalance = (float) ($primaryWallet?->balance ?? 0);
        $walletCurrencyCode = $primaryWallet?->currency?->value ?? 'USD';
        $walletCurrencyCase = Currency::tryFrom($walletCurrencyCode);
        $walletSymbol = $walletCurrencyCase?->symbol() ?? '$';

        $allWallets = $user->wallets()->where('is_active', true)->get();

        // Recent wallet ledger movements for the overview card (latest 6).
        $recentTransactions = $user->walletTransactions()->latest()->limit(6)->get();

        // Symbol/label map covering fiat + crypto.
        $symbolFor = function (string $code): string {
            return match (strtoupper($code)) {
                'USD' => '$',  'NGN'  => '₦', 'GHS'  => '₵', 'GBP' => '£',
                'XAF' => 'FCFA','XOF' => 'CFA','EUR' => '€',
                'KES' => 'KSh','ZAR'  => 'R', 'UGX'  => 'USh','TZS' => 'TSh',
                'RWF' => 'FRw','ZMW'  => 'K', 'MWK'  => 'MK', 'ETB' => 'Br',
                'EGP' => 'E£', 'MAD'  => 'DH',
                'BTC' => '₿',  'USDT' => '₮', 'BUSD' => '$', 'SOL' => '◎',
                'BNB' => 'B',  'LTC'  => 'Ł', 'ETH'  => 'Ξ',
                default => '',
            };
        };

        $isCryptoCode = fn (string $code) => in_array(strtoupper($code), ['BTC', 'USDT', 'BUSD', 'SOL', 'BNB', 'LTC', 'ETH', 'USDC'], true);

        // Icon resolver: maps a currency code to an asset filename.
        $iconFor = function (string $code): ?string {
            return match (strtoupper($code)) {
                'NGN'  => 'NGN.svg',
                'GHS'  => 'GH.svg',
                'XAF'  => 'XAF.svg',
                'BTC'  => 'BTC.svg',
                'USDT' => 'USDT.svg',
                'BUSD' => 'USDT.svg',
                'SOL'  => 'SOLANA.svg',
                'BNB'  => 'BNB.png',
                'LTC'  => 'LTC.png',
                default => null,
            };
        };

        // Wallet card bg — every wallet uses the same colour as the USD card.
        $colorFor = fn (string $code): string => 'bg-blue-800';

        $walletsPayload = $allWallets->map(function ($w) use ($symbolFor, $isCryptoCode, $iconFor, $colorFor) {
            $code = $w->currency?->value ?? 'USD';
            $iconFile = $iconFor($code);

            return [
                'code'      => $code,
                'symbol'    => $symbolFor($code),
                'label'     => Currency::tryFrom((string) $code)?->label() ?? $code,
                'balance'   => (float) $w->balance,
                'formatted' => $symbolFor($code) . number_format((float) $w->balance, 2),
                'type'      => $isCryptoCode((string) $code) ? 'crypto' : 'fiat',
                'icon'      => $iconFile ? asset('assets/' . rawurlencode($iconFile)) : null,
                'color'     => $colorFor($code),
            ];
        })->values();

        return [
            'user' => $user,
            'walletBalance' => $walletBalance,
            'walletCurrencyCode' => $walletCurrencyCode,
            'walletCurrencyCase' => $walletCurrencyCase,
            'walletSymbol' => $walletSymbol,
            'walletsPayload' => $walletsPayload,
            'recentTransactions' => $recentTransactions,
            'initialWalletColor' => $colorFor($walletCurrencyCode),
        ];
    }
}; ?>

{{-- Single root element (Livewire requirement). --}}
<div>

    {{-- ─────────────────────────────────────────────────────── --}}
    {{-- MOBILE VIEW (< lg)                                      --}}
    {{-- ─────────────────────────────────────────────────────── --}}
    <div class="flex flex-col gap-5 lg:hidden">

        {{-- Quick Actions (mirrors the desktop card so customers can jump straight into a category). --}}
        <div class="rounded-2xl bg-white p-5 shadow-sm shadow-zinc-900/5 ring-1 ring-zinc-100">
            <h3 class="text-base font-bold text-zinc-900">Quick Actions</h3>
            <div class="mt-4 grid grid-cols-3 gap-3">
                @foreach ([
                    ['Gift Cards', 'gift cards.svg', 'bg-pink-500',    route('shop.gift-cards'), true],
                    ['eSIMs',      'esim.svg',       'bg-sky-500',     route('shop.esims'),      true],
                    ['Topups',     'topup1.svg',     'bg-emerald-500', route('shop.topups'),     true],
                    ['Bills',      'Bills 2.svg',    'bg-teal-500',    route('shop.bills'),      true],
                    ['Flights',    'flight 2.svg',   'bg-indigo-500',  route('shop.flights'),    true],
                    ['Stays',      'stay 2.svg',     'bg-orange-500',  route('shop.stays'),      true],
                ] as [$label, $icon, $bg, $href, $live])
                    <a href="{{ $href }}" @if ($live) wire:navigate @endif class="group flex flex-col items-center gap-1.5 text-center">
                        <span class="flex h-12 w-12 items-center justify-center rounded-full {{ $bg }} shadow-sm transition-transform group-hover:scale-105 group-active:scale-95">
                            <img src="{{ asset('assets/' . rawurlencode($icon)) }}" alt="" class="h-6 w-6 brightness-0 invert" loading="lazy">
                        </span>
                        <span class="text-[11px] font-medium text-zinc-700">{{ $label }}</span>
                    </a>
                @endforeach
            </div>
        </div>

        {{-- Give the Perfect Gift promo — placed here on mobile (desktop keeps its own copy in the right rail). --}}
        <div class="relative overflow-hidden rounded-2xl bg-blue-950 p-5 text-white">
            <div class="relative z-10 max-w-[64%]">
                <h3 class="text-lg font-bold tracking-tight">Give the Perfect Gift</h3>
                <p class="mt-1 text-sm text-blue-100/80">Gift cards for every occasion and everyone.</p>
                <a href="{{ route('shop.gift-cards') }}" wire:navigate class="mt-4 inline-flex items-center gap-2 rounded-xl bg-[#ffffff] px-4 py-2 text-sm font-semibold text-blue-950 transition-colors hover:bg-[#dbeafe]">
                    Shop Now
                    <img src="{{ asset('assets/' . rawurlencode('Shop.svg')) }}" alt="" class="h-4 w-4 no-dark-invert" loading="lazy">
                </a>
            </div>
            <img
                src="{{ asset('assets/' . rawurlencode('Pick a product first process.png')) }}"
                alt=""
                class="pointer-events-none absolute -right-5 -bottom-3 h-28 w-auto select-none object-contain drop-shadow-2xl"
                loading="lazy"
            >
        </div>
    </div>

    {{-- ─────────────────────────────────────────────────────── --}}
    {{-- DESKTOP VIEW (lg+)                                      --}}
    {{-- ─────────────────────────────────────────────────────── --}}
    <div class="hidden lg:block">

        {{-- 12-col grid: heading + content live in left (cols 1-8), right rail (cols 9-12) starts at the top --}}
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">

            {{-- LEFT: heading row, wallet/QA/orders, trust, shop by cat, recommended --}}
            <div class="flex flex-col gap-6 lg:col-span-8">

                {{-- Page heading row (sits inline with RShop Points on the right) --}}
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h1 class="flex items-center gap-2 text-2xl font-bold tracking-tight text-zinc-900 sm:text-3xl">
                            Welcome back, {{ $user->name }}!
                            <svg class="h-7 w-7 shrink-0 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10.05 4.575a1.575 1.575 0 1 0-3.15 0v3m3.15-3v-1.5a1.575 1.575 0 0 1 3.15 0v1.5m-3.15 0 .075 5.925m3.075.75V4.575m0 0a1.575 1.575 0 0 1 3.15 0V15M6.9 7.575a1.575 1.575 0 1 0-3.15 0v8.175a6.75 6.75 0 0 0 6.75 6.75h2.018a5.25 5.25 0 0 0 3.712-1.538l1.732-1.732a5.25 5.25 0 0 0 1.538-3.712l.003-2.024a.668.668 0 0 1 .198-.471 1.575 1.575 0 1 0-2.228-2.228 3.818 3.818 0 0 0-1.12 2.687M6.9 7.575V12m6.27 4.318A4.49 4.49 0 0 1 16.35 15"/>
                            </svg>
                        </h1>
                        <p class="mt-1 text-sm text-zinc-600">Here's what's happening with your account today.</p>
                    </div>

                    <button type="button" class="inline-flex items-center gap-2 self-start rounded-xl border border-zinc-200 bg-white px-4 py-2 text-sm font-semibold text-zinc-700 transition-colors hover:bg-zinc-50">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a6.759 6.759 0 010 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 010-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        Customize
                        <svg class="h-4 w-4 text-zinc-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                </div>

                {{-- Wallet + Quick Actions + Recent Order row --}}
                <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">

                    {{-- Wallet balance card. Switchable per-currency. Card bg adopts the active wallet's brand color. --}}
                    <div
                        x-data="{
                            visible: true,
                            wallets: @js($walletsPayload),
                            get current() { return this.wallets[this.$store.wallet.active] ?? { code: 'USD', symbol: '$', label: 'US Dollar', formatted: '$0.00', type: 'fiat', color: 'bg-blue-800', icon: null }; }
                        }"
                        :class="current.color"
                        class="flex flex-col justify-center gap-4 rounded-2xl {{ $initialWalletColor }} p-5 text-left text-white shadow-sm shadow-black/10 transition-colors duration-300"
                    >
                        <div class="flex items-start justify-between">
                            <div class="min-w-0">
                                <p class="text-xs font-semibold uppercase tracking-wider text-blue-100">Total Wallet Balance</p>
                                <div class="mt-1.5 flex items-center gap-2">
                                    <p class="truncate text-3xl font-bold tracking-tight" x-text="visible ? current.formatted : (current.symbol + ' ••••')">{{ $walletSymbol }}{{ number_format($walletBalance, 2) }}</p>
                                    <button type="button" @click="visible = !visible" class="shrink-0 rounded-md p-1 text-blue-200 transition-colors hover:bg-white/10 hover:text-white" :aria-label="visible ? 'Hide balance' : 'Show balance'">
                                        <svg x-show="visible" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.244 7.244L19.5 19.5m-2.876-2.876L13.875 13.875M9.878 9.878a3 3 0 105.249 5.249"/>
                                        </svg>
                                        <svg x-show="!visible" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true" style="display:none;">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                    </button>
                                </div>
                                <p class="mt-0.5 text-xs text-blue-100"><span x-text="current.code">{{ $walletCurrencyCode }}</span> · <span x-text="current.label">{{ $walletCurrencyCase?->label() ?? 'Wallet' }}</span></p>
                            </div>

                            <span class="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-white/15">
                                {{-- Show active wallet's specific icon when available, otherwise the generic wallet glyph --}}
                                <template x-if="current.icon">
                                    <img :src="current.icon" alt="" class="h-7 w-7 object-contain" loading="lazy">
                                </template>
                                <template x-if="!current.icon">
                                    <img src="{{ asset('assets/' . rawurlencode('Wallet.svg')) }}" alt="" class="h-6 w-6 brightness-0 invert" loading="lazy">
                                </template>
                            </span>
                        </div>

                        {{-- Fund Wallet — embedded Volt component: amount/currency modal that
                             calls WalletFundingService and hands off to the payment gateway. --}}
                        <livewire:dashboard.fund-wallet :currency="$walletCurrencyCode" wire:key="fund-desktop" />

                        {{-- Currency switcher: user clicks a chip to swap the displayed wallet --}}
                        @if ($walletsPayload->count() > 1)
                            <div class="-mx-1 -mb-1 rounded-xl bg-white/10 p-2">
                                <p class="px-2 pb-1.5 text-[10px] font-semibold uppercase tracking-wider text-blue-100">Switch wallet</p>
                                <div class="flex flex-wrap gap-1.5">
                                    <template x-for="(w, i) in wallets" :key="w.code">
                                        <button
                                            type="button"
                                            @click="$store.wallet.active = i"
                                            :class="$store.wallet.active === i ? 'bg-white text-blue-700 shadow-sm' : 'bg-white/20 text-white hover:bg-white/30'"
                                            class="inline-flex items-center gap-1.5 rounded-[5px] px-2 py-1 text-xs font-semibold transition-colors"
                                        >
                                            <img :src="w.icon" alt="" class="h-4 w-4 shrink-0 object-contain" x-show="w.icon" loading="lazy">
                                            <span class="text-[10px]" :class="$store.wallet.active === i ? 'text-blue-500' : 'text-blue-100'" x-text="w.code"></span>
                                            <span x-text="w.formatted"></span>
                                        </button>
                                    </template>
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- Quick Actions card (mirrors the Shop by Category buckets so customers can jump straight in) --}}
                    <div class="rounded-2xl bg-white p-5 shadow-sm shadow-zinc-900/5 ring-1 ring-zinc-100">
                        <h3 class="text-base font-semibold text-zinc-900">Quick Actions</h3>
                        <div class="mt-4 grid grid-cols-3 gap-3">
                            @foreach ([
                                ['Gift Cards', 'gift cards.svg', 'bg-pink-500',    null],
                                ['eSIMs',      'esim.svg',       'bg-sky-500',     null],
                                ['Topups',     'topup1.svg',     'bg-emerald-500', null],
                                ['Bills',      'Bills 2.svg',    'bg-teal-500',    'bill payment.svg'],
                                ['Flights',    'flight 2.svg',   'bg-indigo-500',  'flight.svg'],
                                ['Stays',      'stay 2.svg',     'bg-orange-500',  'stay.svg'],
                            ] as [$label, $icon, $bg, $hoverIcon])
                                <a href="#" class="group flex flex-col items-center gap-1.5 text-center">
                                    <span class="flex h-12 w-12 items-center justify-center rounded-2xl {{ $bg }} transition-transform group-hover:scale-105">
                                        @if ($hoverIcon)
                                            <img src="{{ asset('assets/' . rawurlencode($icon)) }}" alt="" class="h-6 w-6 brightness-0 invert group-hover:hidden" loading="lazy">
                                            <img src="{{ asset('assets/' . rawurlencode($hoverIcon)) }}" alt="" class="hidden h-6 w-6 brightness-0 invert group-hover:block" loading="lazy">
                                        @else
                                            <img src="{{ asset('assets/' . rawurlencode($icon)) }}" alt="" class="h-6 w-6 brightness-0 invert" loading="lazy">
                                        @endif
                                    </span>
                                    <span class="text-[11px] font-medium text-zinc-700">{{ $label }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>

                    {{-- Recent Order card --}}
                    <div class="rounded-2xl bg-white p-5 shadow-sm shadow-zinc-900/5 ring-1 ring-zinc-100">
                        <div class="flex items-center justify-between">
                            <h3 class="text-base font-semibold text-zinc-900">Recent Orders</h3>
                            <a href="#" class="text-xs font-semibold text-blue-600 hover:text-blue-700">View all</a>
                        </div>

                        @php
                            // Status badges follow the project rule: solid saturated bg + white text + rounded-[5px].
                            $statusToneClasses = [
                                'success'    => 'bg-emerald-500 text-white',
                                'processing' => 'bg-amber-500 text-white',
                                'neutral'    => 'bg-zinc-500 text-white',
                            ];
                        @endphp
                        <ul class="mt-4 space-y-3">
                            @foreach ([
                                ['Apple iTunes Gift Card', '$50',         'Delivered',  'apple.png',  'success',    '2 mins ago'],
                                ['MTN Ghana Airtime',      'GHS 100',     'Delivered',  'mobile.svg', 'success',    '1 hour ago'],
                                ['Airalo eSIM',            'Turkey 10GB', 'Processing', 'esim.svg',   'processing', '3 hours ago'],
                            ] as [$name, $amount, $status, $icon, $tone, $time])
                                <li class="flex items-center gap-3">
                                    <span class="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-white ring-1 ring-zinc-100">
                                        <img src="{{ asset('assets/' . rawurlencode($icon)) }}" alt="" class="h-8 w-8 object-contain" loading="lazy">
                                    </span>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center justify-between gap-2">
                                            <p class="truncate text-sm font-semibold text-zinc-900">{{ $name }}</p>
                                            <span class="shrink-0 rounded-[5px] px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide {{ $statusToneClasses[$tone] }}">{{ $status }}</span>
                                        </div>
                                        <div class="mt-0.5 flex items-center justify-between gap-2">
                                            <p class="truncate text-xs text-zinc-600">{{ $amount }}</p>
                                            <p class="shrink-0 text-[10px] text-zinc-600">{{ $time }}</p>
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>

                {{-- Trust strip --}}
                <div class="flex items-center gap-4 rounded-2xl bg-white p-5 shadow-sm shadow-zinc-900/5 ring-1 ring-zinc-100">
                    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-blue-100">
                        <img src="{{ asset('assets/secure payments.svg') }}" alt="" class="h-6 w-6" loading="lazy">
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-semibold text-zinc-900">Secure. Fast. Reliable.</p>
                        <p class="mt-0.5 text-xs text-zinc-600">Your transactions are protected with bank-level security.</p>
                    </div>
                    <a href="#" class="inline-flex shrink-0 items-center gap-1 text-xs font-semibold text-blue-600 hover:text-blue-700">
                        Learn more
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
                        </svg>
                    </a>
                </div>

                {{-- Shop by Category + Recommended for you (combined card with divider) --}}
                <div class="rounded-2xl bg-white shadow-sm shadow-zinc-900/5 ring-1 ring-zinc-100">

                    {{-- Shop by Category section --}}
                    <div class="p-5 sm:p-6">
                        <div class="flex items-center justify-between">
                            <h3 class="text-base font-semibold text-zinc-900">Shop by Category</h3>
                            <a href="{{ route('home') }}" wire:navigate class="text-xs font-semibold text-blue-600 hover:text-blue-700">View all</a>
                        </div>

                        <div class="mt-4 grid grid-cols-4 gap-3 sm:grid-cols-8">
                            @foreach ([
                                ['Gift Cards', 'gift cards.svg',   'bg-pink-500',     null],
                                ['eSIMs',      'esim.svg',         'bg-sky-500',      null],
                                ['Flights',    'flight 2.svg',     'bg-indigo-500',   'flight.svg'],
                                ['Stays',      'stay 2.svg',       'bg-orange-500',   'stay.svg'],
                                ['Topups',     'topup1.svg',       'bg-emerald-500',  null],
                                ['Bills',      'bill payment.svg', 'bg-teal-500',     'Bills 2.svg'],
                                ['Gaming',     'Gaming.svg',       'bg-fuchsia-500',  'gaming two.svg'],
                                ['More',       'More.svg',         'bg-blue-500',     'more two.svg'],
                            ] as [$label, $icon, $bg, $hoverIcon])
                                <a href="#" class="group flex flex-col items-center gap-2 text-center">
                                    <span class="flex h-14 w-14 items-center justify-center rounded-full {{ $bg }} transition-transform group-hover:scale-105">
                                        @if ($hoverIcon)
                                            <img src="{{ asset('assets/' . rawurlencode($icon)) }}" alt="" class="h-6 w-6 brightness-0 invert group-hover:hidden" loading="lazy">
                                            <img src="{{ asset('assets/' . rawurlencode($hoverIcon)) }}" alt="" class="hidden h-6 w-6 brightness-0 invert group-hover:block" loading="lazy">
                                        @else
                                            <img src="{{ asset('assets/' . rawurlencode($icon)) }}" alt="" class="h-6 w-6 brightness-0 invert" loading="lazy">
                                        @endif
                                    </span>
                                    <span class="text-xs font-medium text-zinc-700 transition-colors group-hover:text-blue-700">{{ $label }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>

                    {{-- Divider --}}
                    <div class="border-t border-zinc-100"></div>

                    {{-- Recommended for you section --}}
                    <div class="p-5 sm:p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-base font-semibold text-zinc-900">Recommended for you</h3>
                                <p class="mt-0.5 text-xs text-zinc-600">Based on your recent activity</p>
                            </div>
                            <a href="#" class="inline-flex items-center gap-1 text-xs font-semibold text-blue-600 hover:text-blue-700">
                                View all
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
                                </svg>
                            </a>
                        </div>

                        <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
                            @foreach ([
                                ['Netflix Gift Card',    'Worldwide',     '$15.00', 'netflix.webp'],
                                ['Google Play Gift Card', 'USA',          '$10.00', 'googleplay.png'],
                                ['Airalo eSIM',          'USA · 10GB',    '$4.50',  'esim.svg'],
                                ['MTN Ghana Airtime',    'GHS 50',        '$12.50', 'mobile.svg'],
                            ] as [$name, $tag, $price, $icon])
                                <a href="#" class="group flex flex-col gap-3 rounded-2xl bg-blue-100 p-4 transition-colors hover:bg-blue-200">
                                    <div class="flex items-start gap-3">
                                        <span class="flex h-14 w-14 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-white ring-1 ring-zinc-100">
                                            <img src="{{ asset('assets/' . rawurlencode($icon)) }}" alt="" class="h-10 w-10 object-contain" loading="lazy">
                                        </span>
                                        <div class="min-w-0 flex-1">
                                            <p class="truncate text-sm font-semibold text-zinc-900">{{ $name }}</p>
                                            <p class="truncate text-[11px] text-zinc-600">{{ $tag }}</p>
                                        </div>
                                    </div>
                                    <div class="flex items-end justify-between">
                                        <div>
                                            <p class="text-[10px] uppercase tracking-wider text-zinc-600">From</p>
                                            <p class="text-base font-bold text-zinc-900">{{ $price }}</p>
                                        </div>
                                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-600 transition-colors group-hover:bg-blue-700">
                                            <img src="{{ asset('assets/' . rawurlencode('new cart.svg')) }}" alt="" class="h-5 w-5 brightness-0 invert" loading="lazy">
                                        </span>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            {{-- RIGHT RAIL: points, gift promo, recent transactions --}}
            <div class="flex flex-col gap-6 lg:col-span-4">

                {{-- RShop Rcoin card --}}
                <div class="rounded-2xl bg-white p-5 shadow-sm shadow-zinc-900/5 ring-1 ring-zinc-100">
                    <div class="flex items-start gap-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-blue-100">
                            <img src="{{ asset('assets/favicon.ico') }}" alt="" class="h-6 w-6 object-contain" loading="lazy">
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-zinc-900">RShop Rcoin</p>
                            <div class="mt-1 flex items-center gap-2">
                                <span class="text-2xl font-bold tracking-tight text-zinc-900">2,650</span>
                                <span class="rounded-[5px] bg-amber-500 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-white">Gold Member</span>
                            </div>
                        </div>
                    </div>
                    <p class="mt-4 text-xs text-zinc-600">You're 350 Rcoin away from Platinum level</p>
                    <div class="mt-2 h-2 w-full overflow-hidden rounded-full bg-zinc-100">
                        <div class="h-full rounded-full bg-blue-600" style="width: 88%;"></div>
                    </div>
                    <p class="mt-1.5 text-right text-[10px] font-semibold text-zinc-600">2,650 / 3,000</p>

                    <a href="{{ route('dashboard.rewards') }}" wire:navigate class="mt-4 inline-flex w-full items-center justify-center gap-1.5 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-blue-700">
                        {{-- Favicon is blue; force it white so it shows on the blue button. --}}
                        <img src="{{ asset('assets/favicon.ico') }}" alt="" class="h-4 w-4 object-contain" style="filter: brightness(0) invert(1);">
                        View coins
                    </a>
                </div>

                {{-- Give the Perfect Gift promo --}}
                <div class="relative overflow-hidden rounded-2xl bg-blue-950 p-5 text-white">
                    <div class="relative z-10 max-w-[60%]">
                        <h3 class="text-lg font-bold tracking-tight">Give the Perfect Gift</h3>
                        <p class="mt-1 text-sm text-blue-100/80">Gift cards for every occasion and everyone.</p>
                        {{-- Literal-hex bg + no-dark-invert icon so this light button stays
                             light on the always-dark promo card in both themes. --}}
                        <button type="button" class="mt-4 inline-flex items-center gap-2 rounded-xl bg-[#ffffff] px-4 py-2 text-sm font-semibold text-blue-950 transition-colors hover:bg-[#dbeafe]">
                            Shop Now
                            <img src="{{ asset('assets/' . rawurlencode('Shop.svg')) }}" alt="" class="h-4 w-4 no-dark-invert" loading="lazy">
                        </button>
                    </div>

                    <img
                        src="{{ asset('assets/' . rawurlencode('Pick a product first process.png')) }}"
                        alt=""
                        class="pointer-events-none absolute -right-5 -bottom-3 h-36 w-auto select-none object-contain drop-shadow-2xl"
                        loading="lazy"
                    >
                </div>

                {{-- Recent Transactions (stretches to match left column height) --}}
                <div class="flex flex-1 flex-col rounded-2xl bg-white p-5 shadow-sm shadow-zinc-900/5 ring-1 ring-zinc-100">
                    <div class="flex items-center justify-between">
                        <h3 class="text-base font-semibold text-zinc-900">Recent Transactions</h3>
                        <a href="{{ route('dashboard.transactions') }}" wire:navigate class="text-xs font-semibold text-blue-600 hover:text-blue-700">View all</a>
                    </div>

                    <ul class="mt-4 flex-1 space-y-3">
                        @forelse ($recentTransactions as $txn)
                            @php
                                $isCredit = $txn->type === \App\Domain\Shared\Enums\WalletTransactionType::Credit;
                                $sym = $txn->currency?->symbol() ?? '';
                            @endphp
                            <li class="flex items-center gap-3">
                                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl {{ $isCredit ? 'bg-emerald-50 text-emerald-600' : 'bg-zinc-100 text-zinc-600' }}">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                        @if ($isCredit)
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m0 0l6-6m-6 6l-6-6"/>
                                        @else
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 19.5v-15m0 0l6 6m-6-6l-6 6"/>
                                        @endif
                                    </svg>
                                </span>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-semibold text-zinc-900">{{ $txn->description ?: ($txn->transaction_category?->label() ?? 'Wallet transaction') }}</p>
                                    <p class="truncate text-[11px] text-zinc-600">{{ $txn->transaction_category?->label() ?? $txn->type->label() }}</p>
                                </div>
                                <div class="shrink-0 text-right">
                                    <p class="text-sm font-bold {{ $isCredit ? 'text-emerald-600' : 'text-zinc-900' }}">{{ $isCredit ? '+' : '-' }}{{ $sym }}{{ number_format((float) $txn->amount, 2) }}</p>
                                    <p class="text-[10px] text-zinc-600">{{ $txn->created_at->format('d M, H:i') }}</p>
                                </div>
                            </li>
                        @empty
                            <li class="flex flex-1 flex-col items-center justify-center py-10 text-center">
                                <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-50 text-blue-600">
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z"/>
                                    </svg>
                                </span>
                                <p class="mt-3 text-sm font-semibold text-zinc-900">No transactions yet</p>
                                <p class="mt-1 text-xs text-zinc-600">Fund your wallet to get started.</p>
                            </li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>

    </div>
</div>
