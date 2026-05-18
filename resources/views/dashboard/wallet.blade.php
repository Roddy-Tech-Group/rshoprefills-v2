{{--
    My Wallets — /dashboard/wallet.
    Shows every wallet card the customer holds (one per currency), the count,
    and an in-card Top Up on each. Wallets are auto-created per currency, so
    this list grows as the customer funds new currencies.
--}}
@php
    use App\Domain\Shared\Enums\Currency;

    $user = auth()->user();
    $allWallets = $user->wallets()->where('is_active', true)->get();

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

    $walletsPayload = $allWallets->map(function ($w) use ($symbolFor, $iconFor) {
        $code = $w->currency?->value ?? 'USD';
        $iconFile = $iconFor($code);

        return [
            'code'      => $code,
            'label'     => Currency::tryFrom((string) $code)?->label() ?? $code,
            'formatted' => $symbolFor($code) . number_format((float) $w->balance, 2),
            'icon'      => $iconFile ? asset('assets/' . rawurlencode($iconFile)) : \App\Models\Product::flagUrl(match (strtoupper((string) $code)) { 'USD' => 'US', 'GBP' => 'GB', 'EUR' => 'EU', 'KES' => 'KE', 'ZAR' => 'ZA', 'UGX' => 'UG', 'TZS' => 'TZ', 'RWF' => 'RW', 'ZMW' => 'ZM', 'MWK' => 'MW', 'ETB' => 'ET', 'EGP' => 'EG', 'MAD' => 'MA', default => '' }),
        ];
    })->values();

    $walletCount = $walletsPayload->count();
@endphp

<x-layouts.dashboard>
    <div class="flex w-full flex-col gap-6">

        {{-- Heading (desktop only — mobile uses the layout's slim top bar) --}}
        <div class="hidden lg:block">
            <h1 class="text-xl font-bold tracking-tight text-zinc-900 sm:text-3xl">My Wallets</h1>
            <p class="mt-1 text-sm text-zinc-600">Your balances across every currency you hold.</p>
        </div>

        {{-- Count summary --}}
        <div class="flex items-center gap-4 rounded-2xl bg-white p-5 shadow-sm shadow-zinc-900/5 ring-1 ring-zinc-100">
            <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-blue-100">
                <img src="{{ asset('assets/' . rawurlencode('Wallet.svg')) }}" alt="" class="h-6 w-6" loading="lazy">
            </span>
            <div class="min-w-0">
                <p class="text-2xl font-bold tracking-tight text-zinc-900">{{ $walletCount }}</p>
                <p class="text-sm text-zinc-600">{{ \Illuminate\Support\Str::plural('wallet card', $walletCount) }} active</p>
            </div>
        </div>

        {{-- Wallet cards --}}
        @if ($walletCount > 0)
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($walletsPayload as $w)
                    <div class="flex flex-col gap-4 rounded-2xl bg-blue-800 p-5 text-white shadow-sm shadow-black/10 ring-1 ring-white/10">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-xs font-semibold uppercase tracking-wider text-blue-100">Wallet Balance</p>
                                <p class="mt-1.5 truncate text-2xl font-bold tracking-tight">{{ $w['formatted'] }}</p>
                                <p class="mt-0.5 text-xs text-blue-100">{{ $w['code'] }} &middot; {{ $w['label'] }}</p>
                            </div>
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-white/15">
                                @if ($w['icon'])
                                    <img src="{{ $w['icon'] }}" alt="" class="h-6 w-6 object-contain" loading="lazy">
                                @else
                                    <img src="{{ asset('assets/' . rawurlencode('Wallet.svg')) }}" alt="" class="h-5 w-5 brightness-0 invert" loading="lazy">
                                @endif
                            </span>
                        </div>

                        {{-- In-card Top Up — fund-wallet Volt component, pre-set to this currency. --}}
                        <livewire:dashboard.fund-wallet :currency="$w['code']" variant="compact" wire:key="wallet-fund-{{ $w['code'] }}" />
                    </div>
                @endforeach
            </div>
        @else
            {{-- Empty state --}}
            <div class="rounded-2xl bg-white px-6 py-16 text-center ring-1 ring-zinc-200">
                <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-blue-50">
                    <img src="{{ asset('assets/' . rawurlencode('Wallet.svg')) }}" alt="" class="h-7 w-7" loading="lazy">
                </span>
                <p class="mt-4 text-base font-semibold text-zinc-900">No wallets yet</p>
                <p class="mt-1 text-sm text-zinc-600">Fund a currency to open your first wallet card.</p>
            </div>
        @endif

    </div>
</x-layouts.dashboard>
