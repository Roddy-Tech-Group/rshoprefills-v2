{{--
    Customer Rewards / Points page — /dashboard/rewards.
    Backend hooks pending:
      - $user->points_balance + $user->points_earned (no Points model yet)
      - $user->pointHistory() — points earned/spent log
      - $user->rewardRedemptions() — past redemptions
      - Reward tier configuration (currently hardcoded LTC tiers)
    Until those ship, sample data renders for layout verification.
--}}
@php
    $user = auth()->user();

    // Placeholders — bind to real Points model when shipped.
    $pointsBalance = 1963;
    $pointsEarned  = 1963;

    // Loyalty tier logic. Backend will eventually own the tier table + ladder configuration.
    // For now we compute the active tier + the next milestone from a hardcoded ladder.
    $tierLadder = [
        ['name' => 'Bronze',   'min' => 0],
        ['name' => 'Silver',   'min' => 1000],
        ['name' => 'Gold',     'min' => 1500],
        ['name' => 'Platinum', 'min' => 3000],
        ['name' => 'Diamond',  'min' => 6000],
    ];
    $currentTier = $tierLadder[0];
    $nextTier    = null;
    foreach ($tierLadder as $idx => $tier) {
        if ($pointsBalance >= $tier['min']) {
            $currentTier = $tier;
            $nextTier    = $tierLadder[$idx + 1] ?? null;
        }
    }
    $pointsToNext = $nextTier ? max(0, $nextTier['min'] - $pointsBalance) : 0;
    $tierProgress = $nextTier
        ? min(100, round((($pointsBalance - $currentTier['min']) / ($nextTier['min'] - $currentTier['min'])) * 100, 1))
        : 100;

    // Referral link — backend will expose $user->referral_code or similar.
    $referralCode = substr(hash('sha256', 'user-'.$user->id), 0, 10);
    $referralUrl  = url('/').'/?ref='.$referralCode;

    // Reward tiers — LTC for now; backend will expose a configurable catalog.
    $rewards = [
        ['label' => '$3 worth of LTC',  'cost' => 365,  'amount' => 3],
        ['label' => '$5 worth of LTC',  'cost' => 535,  'amount' => 5],
        ['label' => '$10 worth of LTC', 'cost' => 1070, 'amount' => 10],
        ['label' => '$20 worth of LTC', 'cost' => 2140, 'amount' => 20],
    ];

    // Points history sample data — backend will replace with a query.
    $pointsHistory = [
        ['label' => 'Welcome points',     'date' => '2026-05-06', 'points' => 25],
        ['label' => 'Order Id: 91df8a1e-a933-4212-b45c-1d8c0d92cca2', 'date' => '2026-04-19', 'points' => 9],
        ['label' => 'Order Id: bb661443-a03f-4ee0-b02c-cb59b4568609', 'date' => '2026-04-18', 'points' => 13],
        ['label' => 'Order Id: bdacc211-cb32-4d2f-a3bd-2e583dcc7b78', 'date' => '2026-04-12', 'points' => 9],
        ['label' => 'Order Id: 9661e79c-dcf9-49c9-8e13-fb7a484945f8', 'date' => '2026-04-12', 'points' => 9],
        ['label' => 'Order Id: 16f13327-1890-48cf-ad56-5f118e9dd9ac', 'date' => '2026-04-05', 'points' => 14],
        ['label' => 'Order Id: 4bb4fef1-c660-4b1b-9200-8cee2937644d', 'date' => '2026-04-02', 'points' => 13],
        ['label' => 'Order Id: dea2abf3-b67c-48b6-9ee3-10eda160707c', 'date' => '2026-03-30', 'points' => 151],
        ['label' => 'Order Id: 18ce047a-82a2-4eb2-93b0-a2d5a9b0a2aa', 'date' => '2026-03-25', 'points' => 2],
        ['label' => 'Order Id: 644635e6-a998-4176-8aa9-39274e0011f4', 'date' => '2026-03-14', 'points' => 13],
        ['label' => 'Order Id: 7373b3eb-f2a2-42cc-8617-9a8655078d90', 'date' => '2026-03-14', 'points' => 7],
    ];
@endphp

<x-layouts.dashboard>
    <div class="mx-auto flex max-w-5xl flex-col gap-8">

        {{-- ─── RShop Points balance card ─── --}}
        <section>
            <h1 class="text-2xl font-bold tracking-tight text-black sm:text-3xl">Earned</h1>

            <div class="mt-4 rounded-2xl bg-white p-5 shadow-sm shadow-zinc-900/[0.04] ring-1 ring-zinc-100 sm:p-6">
                <div class="flex items-start gap-4">
                    {{-- R-square brand tile — gray-800 in light mode, white in dark mode, blue R either way --}}
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-gray-800 text-blue-600 shadow-sm shadow-zinc-900/10 dark:bg-white">
                        <span class="text-lg font-black leading-none">R</span>
                    </span>

                    <div class="min-w-0 flex-1">
                        <p class="text-base font-bold text-black">RShop Points</p>
                        <div class="mt-1 flex flex-wrap items-center gap-2">
                            <p class="text-3xl font-extrabold tracking-tight text-black">{{ number_format($pointsBalance) }}</p>
                            <span class="inline-flex items-center rounded-[5px] bg-amber-500 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-white">{{ strtoupper($currentTier['name']) }} MEMBER</span>
                        </div>
                    </div>
                </div>

                @if ($nextTier)
                    <p class="mt-5 text-sm text-zinc-600">You're {{ number_format($pointsToNext) }} points away from {{ $nextTier['name'] }} level</p>

                    {{-- Progress bar with rounded fill --}}
                    <div class="mt-2 h-2 w-full overflow-hidden rounded-full bg-zinc-200">
                        <div class="h-full rounded-full bg-blue-600 transition-all duration-500" style="width: {{ $tierProgress }}%;"></div>
                    </div>

                    <p class="mt-2 text-right text-xs text-zinc-600">{{ number_format($pointsBalance) }} / {{ number_format($nextTier['min']) }}</p>
                @else
                    <p class="mt-5 text-sm text-zinc-600">You've reached the highest tier. Thank you for being a loyal member.</p>
                    <div class="mt-2 h-2 w-full overflow-hidden rounded-full bg-zinc-200">
                        <div class="h-full rounded-full bg-blue-600" style="width: 100%;"></div>
                    </div>
                @endif
            </div>

            <p class="mt-3 text-sm text-zinc-600">Your RShop points will become available in 48 hours after your purchase.</p>

            {{-- Trust card --}}
            <div class="mt-4 flex items-center gap-4 rounded-2xl bg-white p-4 shadow-sm shadow-zinc-900/[0.04] ring-1 ring-zinc-100 sm:p-5">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-blue-50">
                    <img src="{{ asset('assets/' . rawurlencode('secure fast reliable.svg')) }}" alt="" class="h-6 w-6" loading="lazy">
                </span>
                <div class="min-w-0">
                    <p class="text-sm font-bold text-black">Secure. Fast. Reliable.</p>
                    <p class="mt-0.5 text-xs text-zinc-600">Your transactions are protected with bank-level security.</p>
                </div>
            </div>
        </section>

        {{-- ─── Referral link section ─── --}}
        <section x-data="{
            copied: false,
            copy() {
                navigator.clipboard.writeText($refs.url.value).then(() => {
                    this.copied = true;
                    setTimeout(() => this.copied = false, 1500);
                });
            }
        }">
            <h2 class="text-sm font-bold text-black">Level up your points with your referral link</h2>

            <div class="mt-3 flex items-center gap-2 rounded-xl bg-zinc-100 px-4 py-3">
                <input
                    type="text"
                    readonly
                    x-ref="url"
                    value="{{ $referralUrl }}"
                    class="flex-1 truncate bg-transparent text-sm text-zinc-700 outline-none"
                    onfocus="this.select()"
                />
                <button type="button" @click="copy()" class="inline-flex shrink-0 items-center gap-1.5 rounded-lg px-3 py-1.5 text-sm font-semibold text-zinc-700 transition-colors hover:bg-zinc-200">
                    <svg x-show="!copied" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 01-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 011.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 00-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 01-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 00-3.375-3.375h-1.5a1.125 1.125 0 01-1.125-1.125v-1.5a3.375 3.375 0 00-3.375-3.375H9.75"/>
                    </svg>
                    <svg x-show="copied" class="h-4 w-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true" style="display:none;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                    </svg>
                    <span x-text="copied ? 'Copied' : 'Copy'">Copy</span>
                </button>
            </div>

            <a href="{{ route('dashboard.referrals') }}" wire:navigate class="mt-2 inline-block text-sm font-medium text-black underline decoration-zinc-300 underline-offset-4 hover:text-blue-700 hover:decoration-blue-300">
                Learn more about the referral program
            </a>
        </section>

        {{-- ─── Redeem your points for crypto ─── --}}
        <section>
            <h2 class="text-sm font-bold text-black">Redeem your points for crypto</h2>
            <div class="mt-3 grid grid-cols-2 gap-4 sm:grid-cols-4">
                @foreach ($rewards as $r)
                    @php $affordable = $pointsBalance >= $r['cost']; @endphp
                    <button
                        type="button"
                        @disabled(! $affordable)
                        class="group flex flex-col items-stretch gap-3 rounded-2xl p-4 text-left transition-colors {{ $affordable ? 'bg-blue-600 hover:bg-blue-700 text-white' : 'bg-zinc-400 text-white/80 cursor-not-allowed' }}"
                    >
                        <span class="flex aspect-[5/3] items-center justify-center overflow-hidden rounded-xl bg-white/15">
                            <img src="{{ asset('assets/LTC.png') }}" alt="" class="h-12 w-12 object-contain {{ $affordable ? '' : 'opacity-70' }}" loading="lazy">
                        </span>
                        <div>
                            <p class="text-sm font-bold">{{ $r['label'] }}</p>
                            <p class="mt-1 inline-flex items-center gap-1 text-xs">
                                Cost
                                <img src="{{ asset('assets/PWAicon.png') }}" alt="" class="h-3.5 w-3.5" loading="lazy">
                                <span class="font-semibold">{{ number_format($r['cost']) }}</span>
                            </p>
                        </div>
                    </button>
                @endforeach
            </div>
        </section>

        {{-- ─── Redeem history ─── --}}
        <section>
            <h2 class="mb-3 text-sm font-bold text-black">Redeem history</h2>
            <div class="rounded-2xl bg-white p-6 text-center shadow-sm shadow-zinc-900/5 ring-1 ring-zinc-100">
                <p class="text-sm text-zinc-600">No redemption history available</p>
            </div>
        </section>

        {{-- ─── Points history ─── --}}
        <section>
            <h2 class="mb-3 text-sm font-bold text-black">Points history</h2>
            <div class="divide-y divide-zinc-100 rounded-2xl bg-white shadow-sm shadow-zinc-900/5 ring-1 ring-zinc-100">
                @foreach ($pointsHistory as $entry)
                    <div class="flex items-center justify-between gap-4 px-5 py-3">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium text-zinc-700">{{ $entry['label'] }}</p>
                            <p class="mt-0.5 text-xs text-zinc-600">{{ \Illuminate\Support\Carbon::parse($entry['date'])->format('d/m/Y') }}</p>
                        </div>
                        <div class="inline-flex shrink-0 items-center gap-1.5 text-sm font-semibold">
                            <svg class="h-3.5 w-3.5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                            </svg>
                            <img src="{{ asset('assets/PWAicon.png') }}" alt="" class="h-4 w-4" loading="lazy">
                            <span class="text-black">{{ number_format($entry['points']) }} points</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

    </div>
</x-layouts.dashboard>
