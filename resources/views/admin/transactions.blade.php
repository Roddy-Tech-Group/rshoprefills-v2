@php
    use App\Models\PaymentAttempt;

    // The pre-merge `payments` table was dropped and replaced by `payment_attempts`
    // (polymorphic — an attempt belongs to an Order or a WalletFunding).
    $payments = PaymentAttempt::with(['user', 'order'])->latest()->limit(50)->get();
    $totalPayments = PaymentAttempt::count();
@endphp

<x-layouts.admin>
    <x-slot:heading>Transactions</x-slot:heading>
    <x-slot:subheading>{{ number_format($totalPayments) }} payment attempts total. Showing the latest 50.</x-slot:subheading>

    <div class="flex flex-1 flex-col gap-6">

        <div class="overflow-hidden rounded-[20px] bg-white shadow-sm shadow-zinc-900/5 ring-1 ring-zinc-100">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-[11px]">
                    <thead class="bg-zinc-50 text-[10px] uppercase tracking-wider text-zinc-600">
                        <tr>
                            <th class="px-5 py-3 font-semibold">Reference</th>
                            <th class="px-5 py-3 font-semibold">Customer</th>
                            <th class="px-5 py-3 font-semibold">Order</th>
                            <th class="px-5 py-3 font-semibold">Amount</th>
                            <th class="px-5 py-3 font-semibold">Gateway</th>
                            <th class="px-5 py-3 font-semibold">Status</th>
                            <th class="px-5 py-3 font-semibold">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100">
                        @forelse ($payments as $payment)
                            @php
                                $statusValue = $payment->payment_status?->value ?? 'pending';
                                $statusClasses = match ($statusValue) {
                                    'paid' => 'bg-emerald-50 text-emerald-700',
                                    'failed', 'expired' => 'bg-red-50 text-red-700',
                                    'refunded', 'partially_refunded' => 'bg-zinc-100 text-zinc-700',
                                    'processing', 'reserved' => 'bg-blue-50 text-blue-700',
                                    default => 'bg-amber-50 text-amber-700',
                                };
                                $reference = $payment->gateway_reference ?: $payment->idempotency_key;
                            @endphp
                            <tr>
                                <td class="px-5 py-3 font-mono text-zinc-600">{{ $reference }}</td>
                                <td class="px-5 py-3 font-medium text-zinc-900">{{ $payment->user?->name ?? 'Unknown' }}</td>
                                <td class="px-5 py-3 font-mono text-zinc-600">{{ $payment->order?->order_number ? '#'.$payment->order->order_number : 'Wallet funding' }}</td>
                                <td class="px-5 py-3 font-semibold text-zinc-900">{{ $payment->currency }} {{ number_format((float) $payment->amount, 2) }}</td>
                                <td class="px-5 py-3 capitalize text-zinc-600">{{ $payment->gateway }}</td>
                                <td class="px-5 py-3">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $statusClasses }}">
                                        {{ $payment->payment_status?->label() ?? 'Pending' }}
                                    </span>
                                </td>
                                <td class="px-5 py-3 text-zinc-600">{{ $payment->created_at->format('M j, Y') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-5 py-12 text-center text-sm text-zinc-600">No transactions yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</x-layouts.admin>
