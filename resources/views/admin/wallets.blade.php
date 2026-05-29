@php
    use App\Models\Wallet;

    $wallets = Wallet::with(['user', 'transactions'])->latest()->limit(50)->get();
    $totalWallets = Wallet::count();

    // Per-currency totals — DON'T sum across currencies (the previous "$100,104.69"
    // headline added NGN + USD + GHS + GBP + XAF as if they were the same unit).
    // Each row in the subheading reads "<symbol><amount> <code>", so the admin
    // sees an honest breakdown instead of a fake aggregate.
    $balancesByCurrency = Wallet::query()
        ->selectRaw('UPPER(currency) as currency, SUM(balance) as total')
        ->groupBy('currency')
        ->orderBy('currency')
        ->get()
        ->filter(fn ($row) => (float) $row->total > 0);
@endphp

<x-layouts.admin>
    <x-slot:heading>Wallets</x-slot:heading>
    <x-slot:subheading>
        {{ number_format($totalWallets) }} {{ \Illuminate\Support\Str::plural('wallet', $totalWallets) }}
        @if ($balancesByCurrency->isNotEmpty())
            ·
            @foreach ($balancesByCurrency as $row)
                @money((float) $row->total, $row->currency){{ ' ' }}{{ $row->currency }}@if (! $loop->last), @endif
            @endforeach
        @endif
    </x-slot:subheading>

    <div class="flex flex-1 flex-col gap-6">

        <div class="overflow-hidden rounded-[10px] bg-white shadow-sm shadow-zinc-900/5 ring-1 ring-zinc-100 dark:bg-[#1d3252] dark:ring-zinc-700/60">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-[11px]">
                    {{-- Header treatment matches the products / orders / transactions
                         pages: light-blue surface + blue-700 caps for the column
                         labels. Same colour, same typography, every list page now
                         reads the same. --}}
                    <thead class="bg-blue-50 text-[10px] font-bold uppercase tracking-wider text-blue-700 dark:bg-blue-600/15 dark:text-blue-300">
                        <tr>
                            <th class="px-5 py-3">Owner</th>
                            <th class="px-5 py-3">Balance</th>
                            <th class="px-5 py-3">Currency</th>
                            <th class="px-5 py-3">Transactions</th>
                            <th class="px-5 py-3">Status</th>
                            <th class="px-5 py-3">Created</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700/60">
                        @forelse ($wallets as $wallet)
                            <tr class="transition-colors hover:bg-zinc-50 dark:hover:bg-[#26416b]/40">
                                <td class="px-5 py-3">
                                    <div class="flex items-center gap-3">
                                        @php
                                            $rowAvatar = $wallet->user?->avatar_url ?: asset('assets/' . rawurlencode(match (strtolower($wallet->user?->gender ?? '')) {
                                                'female', 'f' => 'New Female Account Avatar.png',
                                                default       => 'New male account avatar.png',
                                            }));
                                        @endphp
                                        <img src="{{ $rowAvatar }}" alt="" class="h-9 w-9 shrink-0 rounded-[10px] object-cover ring-1 ring-blue-100 dark:ring-blue-500/30">
                                        <div class="leading-tight">
                                            <p class="text-[11px] font-semibold text-zinc-900 dark:text-white">{{ $wallet->user?->name ?? '—' }}</p>
                                            <p class="text-[10px] text-zinc-600 dark:text-zinc-400">{{ $wallet->user?->email ?? '—' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-3 font-semibold text-zinc-900 dark:text-white">@money((float) $wallet->balance, $wallet->currency->value)</td>
                                <td class="px-5 py-3 text-zinc-600 dark:text-zinc-400">{{ $wallet->currency }}</td>
                                <td class="px-5 py-3 text-zinc-600 dark:text-zinc-400">{{ $wallet->transactions->count() }}</td>
                                <td class="px-5 py-3">
                                    <x-admin.badge :tone="$wallet->is_active ? 'emerald' : 'zinc'">
                                        {{ $wallet->is_active ? 'Active' : 'Inactive' }}
                                    </x-admin.badge>
                                </td>
                                <td class="px-5 py-3 text-zinc-600 dark:text-zinc-400">{{ $wallet->created_at->format('M j, Y') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-12 text-center text-sm text-zinc-600 dark:text-zinc-400">No wallets yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</x-layouts.admin>
