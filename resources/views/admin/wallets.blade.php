@php
    use App\Models\Wallet;

    $wallets = Wallet::with(['user', 'transactions'])->latest()->limit(50)->get();
    $totalWallets = Wallet::count();
    $totalBalance = (float) Wallet::sum('balance');
@endphp

<x-layouts.admin>
    <x-slot:heading>Wallets</x-slot:heading>
    <x-slot:subheading>{{ number_format($totalWallets) }} wallets · combined balance ${{ number_format($totalBalance, 2) }}.</x-slot:subheading>

    <div class="flex flex-1 flex-col gap-6">

        <div class="overflow-hidden rounded-[20px] bg-white shadow-sm shadow-zinc-900/5 ring-1 ring-zinc-100">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-[11px]">
                    <thead class="bg-zinc-50 text-[10px] uppercase tracking-wider text-zinc-600">
                        <tr>
                            <th class="px-5 py-3 font-semibold">Owner</th>
                            <th class="px-5 py-3 font-semibold">Balance</th>
                            <th class="px-5 py-3 font-semibold">Currency</th>
                            <th class="px-5 py-3 font-semibold">Transactions</th>
                            <th class="px-5 py-3 font-semibold">Status</th>
                            <th class="px-5 py-3 font-semibold">Created</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100">
                        @forelse ($wallets as $wallet)
                            <tr>
                                <td class="px-5 py-3">
                                    <div class="flex items-center gap-3">
                                        @php
                                            $rowAvatar = $wallet->user?->avatar_url ?: asset('assets/' . rawurlencode(match (strtolower($wallet->user?->gender ?? '')) {
                                                'female', 'f' => 'New Female Account Avatar.png',
                                                default       => 'New male account avatar.png',
                                            }));
                                        @endphp
                                        <img src="{{ $rowAvatar }}" alt="" class="h-9 w-9 shrink-0 rounded-full object-cover ring-1 ring-blue-100">
                                        <div class="leading-tight">
                                            <p class="text-[11px] font-semibold text-zinc-900">{{ $wallet->user?->name ?? '—' }}</p>
                                            <p class="text-[10px] text-zinc-600">{{ $wallet->user?->email ?? '—' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-3 font-semibold text-zinc-900">${{ number_format((float) $wallet->balance, 2) }}</td>
                                <td class="px-5 py-3 text-zinc-600">{{ $wallet->currency }}</td>
                                <td class="px-5 py-3 text-zinc-600">{{ $wallet->transactions->count() }}</td>
                                <td class="px-5 py-3">
                                    @if ($wallet->is_active)
                                        <span class="inline-flex items-center rounded-[5px] bg-emerald-400 px-2.5 py-0.5 text-xs font-semibold text-white">Active</span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-zinc-100 px-2.5 py-0.5 text-xs font-semibold text-zinc-700">Inactive</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3 text-zinc-600">{{ $wallet->created_at->format('M j, Y') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-12 text-center text-sm text-zinc-600">No wallets yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</x-layouts.admin>
