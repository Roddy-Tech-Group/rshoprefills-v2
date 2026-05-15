@php
    use App\Models\Payment;

    $payments = Payment::with(['user', 'order'])->latest()->limit(50)->get();
    $totalPayments = Payment::count();
@endphp

<x-layouts.admin>
    <x-slot:heading>Transactions</x-slot:heading>
    <x-slot:subheading>{{ number_format($totalPayments) }} payments total. Showing the latest 50.</x-slot:subheading>

    <div class="flex flex-1 flex-col gap-6">

        <div class="overflow-hidden rounded-[20px] bg-white shadow-sm shadow-zinc-900/5 ring-1 ring-zinc-100">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-[11px]">
                    <thead class="bg-zinc-50 text-[10px] uppercase tracking-wider text-zinc-600">
                        <tr>
                            <th class="px-5 py-3 font-semibold">Txn ID</th>
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
                                $statusValue = $payment->status->value ?? 'pending';
                                $statusClasses = match ($statusValue) {
                                    'completed' => 'bg-emerald-50 text-emerald-700',
                                    'failed' => 'bg-red-50 text-red-700',
                                    'refunded' => 'bg-zinc-100 text-zinc-700',
                                    'processing' => 'bg-blue-50 text-blue-700',
                                    default => 'bg-amber-50 text-amber-700',
                                };
                            @endphp
                            <tr>
                                <td class="px-5 py-3 font-mono text-zinc-600">{{ $payment->gateway_transaction_id ?? '#'.str_pad($payment->id, 5, '0', STR_PAD_LEFT) }}</td>
                                <td class="px-5 py-3 font-medium text-zinc-900">{{ $payment->user?->name ?? '—' }}</td>
                                <td class="px-5 py-3 font-mono text-zinc-600">#{{ $payment->order?->order_number ?? '—' }}</td>
                                <td class="px-5 py-3 font-semibold text-zinc-900">${{ number_format((float) $payment->amount, 2) }}</td>
                                <td class="px-5 py-3 text-zinc-600">{{ $payment->gateway->value ?? '—' }}</td>
                                <td class="px-5 py-3">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $statusClasses }}">
                                        {{ $payment->status->label() }}
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
