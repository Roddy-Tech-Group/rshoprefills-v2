@php
    // Admin customer detail — a full picture of one user: profile, wallet(s),
    // lifetime stats, and recent commerce activity (orders + wallet movements).
    // Read-only; mirrors the admin order detail page's layout conventions.

    use App\Domain\Order\Enums\OrderStatus;

    $fmtDate = fn ($d) => $d ? $d->format('M j, Y · g:i A') : null;

    // Status -> badge classes, shared across order / fulfilment / payment enums.
    $toneFor = function (?string $v): string {
        return match ($v) {
            'completed', 'paid', 'fulfilled' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
            'partially_completed', 'partially_fulfilled', 'partially_paid', 'processing' => 'bg-blue-50 text-blue-700 ring-blue-200',
            'failed', 'cancelled', 'requires_attention', 'expired' => 'bg-red-50 text-red-700 ring-red-200',
            'refunded', 'partially_refunded' => 'bg-zinc-100 text-zinc-700 ring-zinc-200',
            default => 'bg-amber-50 text-amber-700 ring-amber-200',
        };
    };

    $avatar = $user->avatar_url ?: asset('assets/' . rawurlencode(match (strtolower($user->gender ?? '')) {
        'female', 'f' => 'New Female Account Avatar.png',
        default       => 'New male account avatar.png',
    }));

    $primaryWallet = $user->wallets->first(fn ($wallet) => $wallet->currency->value === 'USD')
        ?? $user->wallets->first();
@endphp

<x-layouts.admin>
    <x-slot:heading>{{ $user->name }}</x-slot:heading>
    <x-slot:subheading>Customer #{{ $user->id }} · Joined {{ $user->created_at->format('M j, Y') }}</x-slot:subheading>

    <div class="flex flex-1 flex-col gap-6">

        {{-- Back link --}}
        <a href="{{ route('admin.customers') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-blue-600 hover:text-blue-700">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/>
            </svg>
            All customers
        </a>

        {{-- Profile header --}}
        <div class="rounded-[20px] bg-white p-5 shadow-sm shadow-zinc-900/5 ring-1 ring-zinc-100">
            <div class="flex flex-wrap items-center gap-4">
                <img src="{{ $avatar }}" alt="" class="h-16 w-16 shrink-0 rounded-full object-cover ring-1 ring-blue-100">
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="text-lg font-bold text-zinc-900">{{ $user->name }}</h2>
                        @if ($user->email_verified_at)
                            <span class="inline-flex items-center rounded-[5px] bg-emerald-400 px-2.5 py-0.5 text-xs font-semibold text-white">Active</span>
                        @else
                            <span class="inline-flex items-center rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-semibold text-amber-700">Pending</span>
                        @endif
                    </div>
                    <p class="mt-0.5 text-sm text-zinc-600">{{ $user->email }}</p>
                </div>
            </div>

            <dl class="mt-5 grid grid-cols-2 gap-x-6 gap-y-3 text-xs sm:grid-cols-4">
                <div>
                    <dt class="text-[10px] font-semibold uppercase tracking-wider text-zinc-800">Phone</dt>
                    <dd class="mt-1 font-medium text-zinc-700">{{ $user->phone ?: '-' }}</dd>
                </div>
                <div>
                    <dt class="text-[10px] font-semibold uppercase tracking-wider text-zinc-800">Gender</dt>
                    <dd class="mt-1 font-medium capitalize text-zinc-700">{{ $user->gender ?: '-' }}</dd>
                </div>
                <div>
                    <dt class="text-[10px] font-semibold uppercase tracking-wider text-zinc-800">Sign-in</dt>
                    <dd class="mt-1 font-medium text-zinc-700">{{ $user->google_id ? 'Google' : 'Email & password' }}</dd>
                </div>
                <div>
                    <dt class="text-[10px] font-semibold uppercase tracking-wider text-zinc-800">Email verified</dt>
                    <dd class="mt-1 font-medium text-zinc-700">{{ $fmtDate($user->email_verified_at) ?? 'Not verified' }}</dd>
                </div>
            </dl>
        </div>

        {{-- Lifetime stats --}}
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            @php
                $walletLabel = $primaryWallet
                    ? '$' . number_format($primaryWallet->availableBalance(), 2) . ' ' . $primaryWallet->currency->value
                    : 'No wallet';
            @endphp
            @foreach ([
                ['Total orders', number_format($ordersCount)],
                ['Total spent', '$' . number_format($totalSpent, 2)],
                ['Wallet balance', $walletLabel],
                ['Unread alerts', number_format($unreadNotifications)],
            ] as [$label, $value])
                <div class="rounded-[16px] bg-white p-4 shadow-sm shadow-zinc-900/5 ring-1 ring-zinc-100">
                    <p class="text-[10px] font-semibold uppercase tracking-wider text-zinc-800">{{ $label }}</p>
                    <p class="mt-1.5 text-lg font-bold text-zinc-900">{{ $value }}</p>
                </div>
            @endforeach
        </div>

        {{-- Wallets --}}
        <div class="rounded-[20px] bg-white shadow-sm shadow-zinc-900/5 ring-1 ring-zinc-100">
            <div class="border-b border-zinc-100 px-5 py-4">
                <h3 class="text-sm font-bold text-zinc-900">Wallets ({{ $user->wallets->count() }})</h3>
            </div>
            <div class="divide-y divide-zinc-100">
                @forelse ($user->wallets as $wallet)
                    <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4">
                        <div>
                            <p class="text-sm font-bold text-zinc-900">${{ number_format((float) $wallet->balance, 2) }} {{ $wallet->currency->value }}</p>
                            <p class="mt-0.5 text-[11px] text-zinc-800">
                                Available ${{ number_format($wallet->availableBalance(), 2) }}
                                · Locked ${{ number_format((float) $wallet->locked_balance, 2) }}
                            </p>
                        </div>
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-semibold ring-1 {{ $wallet->is_active ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : 'bg-zinc-100 text-zinc-700 ring-zinc-200' }}">
                            {{ $wallet->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                @empty
                    <div class="px-5 py-10 text-center text-sm text-zinc-600">This customer has no wallet yet.</div>
                @endforelse
            </div>
        </div>

        {{-- Recent orders --}}
        <div class="rounded-[20px] bg-white shadow-sm shadow-zinc-900/5 ring-1 ring-zinc-100">
            <div class="border-b border-zinc-100 px-5 py-4">
                <h3 class="text-sm font-bold text-zinc-900">Recent orders</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-[11px]">
                    <thead class="bg-zinc-50 text-[10px] uppercase tracking-wider text-zinc-800">
                        <tr>
                            <th class="px-5 py-3 font-semibold">Order</th>
                            <th class="px-5 py-3 font-semibold">Status</th>
                            <th class="px-5 py-3 font-semibold">Total</th>
                            <th class="px-5 py-3 font-semibold">Placed</th>
                            <th class="px-5 py-3 text-right font-semibold">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100">
                        @forelse ($user->orders as $order)
                            <tr>
                                <td class="px-5 py-3 font-mono font-semibold text-zinc-900">#{{ $order->order_number }}</td>
                                <td class="px-5 py-3">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-semibold ring-1 {{ $toneFor($order->order_status?->value) }}">
                                        {{ $order->order_status?->label() ?? 'Pending' }}
                                    </span>
                                </td>
                                <td class="px-5 py-3 font-semibold text-zinc-900">{{ $order->display_currency }} {{ number_format((float) $order->total_amount, 2) }}</td>
                                <td class="px-5 py-3 text-zinc-600">{{ $fmtDate($order->placed_at ?? $order->created_at) }}</td>
                                <td class="px-5 py-3 text-right">
                                    <a href="{{ route('admin.order', $order) }}" class="inline-flex items-center rounded-lg border border-zinc-200 bg-white px-3 py-1 text-xs font-medium text-zinc-600 hover:bg-zinc-50">View</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-10 text-center text-sm text-zinc-600">No orders placed yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Recent wallet activity --}}
        <div class="rounded-[20px] bg-white shadow-sm shadow-zinc-900/5 ring-1 ring-zinc-100">
            <div class="border-b border-zinc-100 px-5 py-4">
                <h3 class="text-sm font-bold text-zinc-900">Recent wallet activity</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-[11px]">
                    <thead class="bg-zinc-50 text-[10px] uppercase tracking-wider text-zinc-800">
                        <tr>
                            <th class="px-5 py-3 font-semibold">Type</th>
                            <th class="px-5 py-3 font-semibold">Amount</th>
                            <th class="px-5 py-3 font-semibold">Description</th>
                            <th class="px-5 py-3 font-semibold">Balance after</th>
                            <th class="px-5 py-3 font-semibold">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100">
                        @forelse ($user->walletTransactions as $tx)
                            @php $isCredit = $tx->type === \App\Domain\Shared\Enums\WalletTransactionType::Credit; @endphp
                            <tr>
                                <td class="px-5 py-3">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-semibold ring-1 {{ $isCredit ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : 'bg-zinc-100 text-zinc-700 ring-zinc-200' }}">
                                        {{ $tx->type->label() }}
                                    </span>
                                </td>
                                <td class="px-5 py-3 font-semibold {{ $isCredit ? 'text-emerald-700' : 'text-zinc-900' }}">
                                    {{ $isCredit ? '+' : '-' }}{{ $tx->currency->value }} {{ number_format((float) $tx->amount, 2) }}
                                </td>
                                <td class="px-5 py-3 text-zinc-600">{{ $tx->description ?: '-' }}</td>
                                <td class="px-5 py-3 text-zinc-600">{{ $tx->currency->value }} {{ number_format((float) $tx->balance_after, 2) }}</td>
                                <td class="px-5 py-3 text-zinc-600">{{ $fmtDate($tx->created_at) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-10 text-center text-sm text-zinc-600">No wallet activity yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</x-layouts.admin>
