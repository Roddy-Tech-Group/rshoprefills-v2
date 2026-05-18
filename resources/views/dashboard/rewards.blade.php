{{--
    Customer Rewards page — /dashboard/rewards. The loyalty currency is "Rcoin".

    Frontend only — sample data + placeholder thresholds render until the backend
    Rcoin ledger ships. Backend hooks pending:
      - $user->rcoin_balance + $user->rcoin_earned   (no Rcoin model yet)
      - $user->rcoinHistory()                        (earn/spend ledger)
      - $user->rcoinRedemptions()                    (past conversions/withdrawals)
      - Admin config: earn rate, Rcoin→USD rate, convert + withdraw thresholds
      - POST handlers for convert-to-gift-card and withdraw-to-cash
    Field names below (convert_*, withdraw_*) are the contract for that wiring.
--}}
@php
    use App\Models\Product;

    $user = auth()->user();

    // ── Rcoin balance — there is no Rcoin ledger backend yet, so this is an
    //    honest zero state (no fabricated balance). Bind to the Rcoin model when
    //    it ships; the rest of the page computes off these values. ──
    $rcoinBalance = 0;
    $rcoinEarned  = 0;

    // Admin-configurable economics (placeholders).
    $rcoinPerUsd       = 100;    // 100 Rcoin = $1
    $convertThreshold  = 1000;   // min Rcoin before convert-to-gift-card unlocks
    $withdrawThreshold = 5000;   // min Rcoin before cash withdrawal unlocks

    $cashValue = $rcoinBalance / $rcoinPerUsd; // USD worth of the current balance
    $coin      = asset('assets/favicon.ico');  // the Rcoin coin mark

    // ── Loyalty tier ladder ──
    // Platinum needs email verification, Diamond needs ID (KYC) verification — see /dashboard/kyc.
    $tierLadder = [
        ['name' => 'Bronze',   'min' => 0,    'requires' => null],
        ['name' => 'Silver',   'min' => 1000, 'requires' => null],
        ['name' => 'Gold',     'min' => 1500, 'requires' => null],
        ['name' => 'Platinum', 'min' => 3000, 'requires' => 'Email verified'],
        ['name' => 'Diamond',  'min' => 6000, 'requires' => 'ID verified'],
    ];
    $currentTier = $tierLadder[0];
    $nextTier    = null;
    foreach ($tierLadder as $idx => $tier) {
        if ($rcoinBalance >= $tier['min']) {
            $currentTier = $tier;
            $nextTier    = $tierLadder[$idx + 1] ?? null;
        }
    }
    $rcoinToNext  = $nextTier ? max(0, $nextTier['min'] - $rcoinBalance) : 0;
    $tierProgress = $nextTier
        ? min(100, round((($rcoinBalance - $currentTier['min']) / ($nextTier['min'] - $currentTier['min'])) * 100, 1))
        : 100;

    // ── Convert / withdraw availability ──
    $canConvert  = $rcoinBalance >= $convertThreshold;
    $canWithdraw = $rcoinBalance >= $withdrawThreshold;
    $convertProgress  = min(100, round(($rcoinBalance / $convertThreshold) * 100, 1));
    $withdrawProgress = min(100, round(($rcoinBalance / $withdrawThreshold) * 100, 1));

    // ── Rcoin history — empty until the Rcoin ledger backend ships. ──
    $rcoinHistory = [];
@endphp

<x-layouts.dashboard>
    <div class="flex w-full flex-col gap-8">

        {{-- ─── Rcoin balance card ─── --}}
        <section>
            <h1 class="hidden text-xl font-bold tracking-tight text-black sm:text-3xl lg:block">Your Rcoin</h1>

            <div class="mt-4 rounded-2xl bg-white p-5 shadow-sm shadow-zinc-900/[0.04] ring-1 ring-zinc-100 sm:p-6">
                <div class="flex items-start gap-4">
                    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-blue-50 shadow-sm shadow-zinc-900/10">
                        <img src="{{ $coin }}" alt="Rcoin" class="h-7 w-7 object-contain">
                    </span>

                    <div class="min-w-0 flex-1">
                        <p class="text-base font-bold text-black">RShop Rcoin</p>
                        <div class="mt-1 flex flex-wrap items-center gap-2">
                            <p class="text-3xl font-extrabold tracking-tight text-black">{{ number_format($rcoinBalance) }}</p>
                            <span class="inline-flex items-center rounded-[5px] bg-amber-500 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-white">{{ strtoupper($currentTier['name']) }} MEMBER</span>
                        </div>
                        <p class="mt-0.5 text-sm text-zinc-600">Worth about ${{ number_format($cashValue, 2) }}</p>
                    </div>
                </div>

                @if ($nextTier)
                    <p class="mt-5 text-sm text-zinc-600">You're {{ number_format($rcoinToNext) }} Rcoin away from {{ $nextTier['name'] }} level</p>
                    <div class="mt-2 h-2 w-full overflow-hidden rounded-full bg-zinc-200">
                        <div class="h-full rounded-full bg-blue-600 transition-all duration-500" style="width: {{ $tierProgress }}%;"></div>
                    </div>
                    <p class="mt-2 text-right text-xs text-zinc-600">{{ number_format($rcoinBalance) }} / {{ number_format($nextTier['min']) }}</p>
                @else
                    <p class="mt-5 text-sm text-zinc-600">You've reached the highest tier. Thank you for being a loyal member.</p>
                    <div class="mt-2 h-2 w-full overflow-hidden rounded-full bg-zinc-200">
                        <div class="h-full rounded-full bg-blue-600" style="width: 100%;"></div>
                    </div>
                @endif
            </div>

            <p class="mt-3 text-sm text-zinc-600">Rcoin is earned on every purchase and becomes available 48 hours after the order completes.</p>
        </section>

        {{-- ─── Earn more Rcoin: referral link ─── --}}
        <section>
            <h2 class="text-sm font-bold text-black">Earn more Rcoin</h2>

            <div class="mt-3 rounded-2xl bg-white p-5 shadow-sm shadow-zinc-900/[0.04] ring-1 ring-zinc-100 sm:p-6">
                <div class="flex items-start gap-4">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50">
                        <img src="{{ asset('assets/referals.png') }}" alt="" class="h-5 w-5 object-contain" loading="lazy">
                    </span>
                    <div class="min-w-0">
                        <p class="text-base font-bold text-black">Refer friends, earn Rcoin</p>
                        <p class="mt-1 text-sm text-zinc-600">Share your link. You earn Rcoin when a friend signs up, and again every time they place an order.</p>
                    </div>
                </div>

                <div class="mt-4">
                    <livewire:referral-code />
                </div>

                {{-- Two ways referrals pay out Rcoin. --}}
                <div class="mt-3 grid grid-cols-2 gap-3 text-sm">
                    <div class="rounded-xl bg-zinc-50 px-3 py-2.5">
                        <p class="font-semibold text-zinc-900">On sign-up</p>
                        <p class="text-xs text-zinc-600">Rcoin lands when your referral creates their account.</p>
                    </div>
                    <div class="rounded-xl bg-zinc-50 px-3 py-2.5">
                        <p class="font-semibold text-zinc-900">On every order</p>
                        <p class="text-xs text-zinc-600">Keep earning Rcoin each time they buy.</p>
                    </div>
                </div>
            </div>
        </section>

        {{-- ─── Spend your Rcoin: convert to gift card / withdraw to cash ─── --}}
        <section>
            <h2 class="text-sm font-bold text-black">Spend your Rcoin</h2>
            <div class="mt-3 grid grid-cols-1 gap-4 sm:grid-cols-2">

                {{-- Convert to gift card --}}
                <div class="flex flex-col rounded-2xl bg-white p-5 shadow-sm shadow-zinc-900/[0.04] ring-1 ring-zinc-100">
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50">
                        <img src="{{ asset('assets/' . rawurlencode('gift cards.svg')) }}" alt="" class="h-5 w-5" loading="lazy">
                    </span>
                    <p class="mt-3 text-base font-bold text-black">Convert to a gift card</p>
                    <p class="mt-1 text-sm text-zinc-600">Turn your Rcoin into a gift card of your choice. Your balance decides the amount.</p>

                    <div class="mt-4 rounded-xl bg-zinc-50 px-3 py-2.5 text-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-zinc-600">Available</span>
                            <span class="inline-flex items-center gap-1 font-bold text-zinc-900">
                                <img src="{{ $coin }}" alt="" class="h-4 w-4">{{ number_format($rcoinBalance) }}
                            </span>
                        </div>
                        <div class="mt-1 flex items-center justify-between">
                            <span class="text-zinc-600">Gift card value</span>
                            <span class="font-bold text-zinc-900">${{ number_format($cashValue, 2) }}</span>
                        </div>
                    </div>

                    <div class="mt-auto pt-4">
                        @if ($canConvert)
                            <a href="{{ route('shop.gift-cards') }}" wire:navigate class="inline-flex w-full items-center justify-center rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-blue-700">
                                Choose a gift card
                            </a>
                        @else
                            <button type="button" disabled class="w-full cursor-not-allowed rounded-xl bg-zinc-200 px-4 py-2.5 text-sm font-semibold text-zinc-500">
                                {{ number_format($convertThreshold - $rcoinBalance) }} more Rcoin to unlock
                            </button>
                            <div class="mt-2 h-1.5 w-full overflow-hidden rounded-full bg-zinc-200">
                                <div class="h-full rounded-full bg-blue-600" style="width: {{ $convertProgress }}%;"></div>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Withdraw to cash --}}
                <div
                    x-data="{ amount: '', method: 'wallet' }"
                    class="flex flex-col rounded-2xl bg-white p-5 shadow-sm shadow-zinc-900/[0.04] ring-1 ring-zinc-100"
                >
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50">
                        <img src="{{ asset('assets/' . rawurlencode('wallet 2.svg')) }}" alt="" class="h-5 w-5" loading="lazy">
                    </span>
                    <p class="mt-3 text-base font-bold text-black">Withdraw to cash</p>
                    <p class="mt-1 text-sm text-zinc-600">Cash out your Rcoin balance once you reach the minimum.</p>

                    <div class="mt-4 rounded-xl bg-zinc-50 px-3 py-2.5 text-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-zinc-600">Available</span>
                            <span class="inline-flex items-center gap-1 font-bold text-zinc-900">
                                <img src="{{ $coin }}" alt="" class="h-4 w-4">{{ number_format($rcoinBalance) }}
                            </span>
                        </div>
                        <div class="mt-1 flex items-center justify-between">
                            <span class="text-zinc-600">Cash value</span>
                            <span class="font-bold text-zinc-900">${{ number_format($cashValue, 2) }}</span>
                        </div>
                    </div>

                    @if ($canWithdraw)
                        {{-- Withdrawal request form. Field names: withdraw_amount, withdraw_method.
                             Backend wires the POST handler + creates a withdrawal request record. --}}
                        <form method="POST" action="#" class="mt-4 flex flex-col gap-2.5">
                            @csrf
                            <input
                                type="number"
                                name="withdraw_amount"
                                x-model="amount"
                                min="{{ $withdrawThreshold }}"
                                max="{{ $rcoinBalance }}"
                                placeholder="Rcoin to withdraw"
                                class="w-full rounded-xl border border-zinc-200 bg-white px-3 py-2.5 text-sm text-zinc-900 outline-none transition-colors focus:border-blue-500 focus:ring-2 focus:ring-blue-500/15"
                            >
                            <select name="withdraw_method" x-model="method" class="w-full rounded-xl border border-zinc-200 bg-white px-3 py-2.5 text-sm text-zinc-900 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/15">
                                <option value="wallet">RShop wallet</option>
                                <option value="bank">Bank transfer</option>
                                <option value="mobile_money">Mobile money</option>
                            </select>
                            <button type="submit" class="mt-1 w-full rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-emerald-700">
                                Request withdrawal
                            </button>
                        </form>
                    @else
                        <div class="mt-auto pt-4">
                            <button type="button" disabled class="w-full cursor-not-allowed rounded-xl bg-zinc-200 px-4 py-2.5 text-sm font-semibold text-zinc-500">
                                {{ number_format($withdrawThreshold - $rcoinBalance) }} more Rcoin to unlock
                            </button>
                            <div class="mt-2 h-1.5 w-full overflow-hidden rounded-full bg-zinc-200">
                                <div class="h-full rounded-full bg-emerald-600" style="width: {{ $withdrawProgress }}%;"></div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </section>

        {{-- ─── Tier ladder ─── --}}
        <section>
            <h2 class="mb-3 text-sm font-bold text-black">Membership tiers</h2>
            @php
                // Solid-colour medal palettes per tier (no gradients — project rule).
                // rim = outer disc, face = inner disc, ribbon = hanging tails, star = emblem.
                $medals = [
                    'Bronze'   => ['rim' => '#8a5a2b', 'face' => '#c47f3e', 'ribbon' => '#a3672f', 'star' => '#fdf1e0'],
                    'Silver'   => ['rim' => '#7f8794', 'face' => '#c3c9d3', 'ribbon' => '#99a0ad', 'star' => '#ffffff'],
                    'Gold'     => ['rim' => '#b07d12', 'face' => '#e6b03a', 'ribbon' => '#c99a26', 'star' => '#fffaef'],
                    'Platinum' => ['rim' => '#5f6b7e', 'face' => '#9fadc4', 'ribbon' => '#8390a6', 'star' => '#ffffff'],
                    'Diamond'  => ['rim' => '#0e7490', 'face' => '#3bbdf4', 'ribbon' => '#0a90b6', 'star' => '#ffffff'],
                ];
            @endphp
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-5">
                @foreach ($tierLadder as $tier)
                    @php
                        $reached = $rcoinBalance >= $tier['min'];
                        $isCurrent = $tier['name'] === $currentTier['name'];
                        $m = $medals[$tier['name']] ?? $medals['Bronze'];
                    @endphp
                    <div @class([
                        'rounded-2xl p-4 text-center ring-1 transition-colors',
                        'bg-blue-600 text-white ring-blue-600' => $isCurrent,
                        'bg-white text-zinc-900 ring-zinc-100' => ! $isCurrent && $reached,
                        'bg-white text-zinc-400 ring-zinc-100' => ! $reached,
                    ])>
                        {{-- Tier medal — ribboned medallion with a star emblem. --}}
                        <svg viewBox="0 0 48 56" class="mx-auto mb-3 h-14 w-12 {{ $reached ? '' : 'opacity-40' }}" aria-hidden="true">
                            <path d="M15 24 L9 54 L19 46 Z" fill="{{ $m['ribbon'] }}"/>
                            <path d="M33 24 L39 54 L29 46 Z" fill="{{ $m['ribbon'] }}"/>
                            <circle cx="24" cy="20" r="19" fill="{{ $m['rim'] }}"/>
                            <circle cx="24" cy="20" r="13.5" fill="{{ $m['face'] }}"/>
                            <g transform="translate(15.5 11.5) scale(0.7)">
                                <path d="M12 .587l3.668 7.568L24 9.423l-6 5.951L19.336 24 12 19.897 4.664 24 6 15.374 0 9.423l8.332-1.268z" fill="{{ $m['star'] }}"/>
                            </g>
                        </svg>
                        <p class="text-sm font-bold">{{ $tier['name'] }}</p>
                        <p @class(['mt-0.5 text-xs', 'text-white/80' => $isCurrent, 'text-zinc-500' => ! $isCurrent])>{{ number_format($tier['min']) }} Rcoin</p>
                        @if ($tier['requires'])
                            <p @class(['mt-1.5 inline-flex items-center gap-1 rounded-[5px] px-1.5 py-0.5 text-[10px] font-semibold', 'bg-white/15 text-white' => $isCurrent, 'bg-amber-50 text-amber-700' => ! $isCurrent])>
                                <svg class="h-2.5 w-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75M3.75 21.75h16.5a.75.75 0 00.75-.75v-9a.75.75 0 00-.75-.75H3.75a.75.75 0 00-.75.75v9c0 .414.336.75.75.75z"/>
                                </svg>
                                {{ $tier['requires'] }}
                            </p>
                        @endif
                    </div>
                @endforeach
            </div>
        </section>

        {{-- ─── Rcoin history ─── --}}
        <section>
            <h2 class="mb-3 text-sm font-bold text-black">Rcoin history</h2>
            <div class="divide-y divide-zinc-100 rounded-2xl bg-white shadow-sm shadow-zinc-900/5 ring-1 ring-zinc-100">
                @forelse ($rcoinHistory as $entry)
                    <div class="flex items-center justify-between gap-4 px-5 py-3">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium text-zinc-700">{{ $entry['label'] }}</p>
                            <p class="mt-0.5 text-xs text-zinc-600">{{ \Illuminate\Support\Carbon::parse($entry['date'])->format('d/m/Y') }}</p>
                        </div>
                        <div class="inline-flex shrink-0 items-center gap-1.5 text-sm font-semibold">
                            <svg class="h-3.5 w-3.5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                            </svg>
                            <img src="{{ $coin }}" alt="" class="h-4 w-4">
                            <span class="text-black">{{ number_format($entry['rcoin']) }} Rcoin</span>
                        </div>
                    </div>
                @empty
                    <div class="px-5 py-10 text-center">
                        <p class="text-sm font-semibold text-zinc-900">No Rcoin activity yet</p>
                        <p class="mt-1 text-xs text-zinc-600">Earn Rcoin on every order and referral, then track it here.</p>
                    </div>
                @endforelse
            </div>
        </section>

    </div>
</x-layouts.dashboard>
