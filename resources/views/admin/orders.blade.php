@php
    use App\Models\Order;

    $orders = Order::with(['user', 'items'])->latest()->limit(50)->get();
    $totalOrders = Order::count();
@endphp

<x-layouts.app>
    <div class="flex flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8">

        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-zinc-900 sm:text-3xl">Orders</h1>
                <p class="mt-1 text-sm text-zinc-500">{{ number_format($totalOrders) }} orders total. Showing the latest 50.</p>
            </div>
        </div>

        <div class="overflow-hidden rounded-[20px] bg-white shadow-sm shadow-zinc-900/5 ring-1 ring-zinc-100">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-[11px]">
                    <thead class="bg-zinc-50 text-[10px] uppercase tracking-wider text-zinc-500">
                        <tr>
                            <th class="px-5 py-3 font-semibold">Order #</th>
                            <th class="px-5 py-3 font-semibold">Customer</th>
                            <th class="px-5 py-3 font-semibold">Items</th>
                            <th class="px-5 py-3 font-semibold">Total</th>
                            <th class="px-5 py-3 font-semibold">Status</th>
                            <th class="px-5 py-3 font-semibold">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100">
                        @forelse ($orders as $order)
                            @php
                                $statusValue = $order->status->value ?? 'pending';
                                $statusClasses = match ($statusValue) {
                                    'completed' => 'bg-emerald-50 text-emerald-700',
                                    'failed', 'cancelled' => 'bg-red-50 text-red-700',
                                    'refunded' => 'bg-zinc-100 text-zinc-700',
                                    default => 'bg-amber-50 text-amber-700',
                                };
                            @endphp
                            <tr>
                                <td class="px-5 py-3 font-mono text-zinc-700">#{{ $order->order_number }}</td>
                                <td class="px-5 py-3 font-medium text-zinc-900">{{ $order->user?->name ?? '—' }}</td>
                                <td class="px-5 py-3 text-zinc-600">{{ $order->items->count() }}</td>
                                <td class="px-5 py-3 font-semibold text-zinc-900">${{ number_format((float) $order->total, 2) }}</td>
                                <td class="px-5 py-3">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $statusClasses }}">
                                        {{ $order->status->label() }}
                                    </span>
                                </td>
                                <td class="px-5 py-3 text-zinc-600">{{ $order->created_at->format('M j, Y') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-12 text-center text-sm text-zinc-500">No orders yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</x-layouts.app>
