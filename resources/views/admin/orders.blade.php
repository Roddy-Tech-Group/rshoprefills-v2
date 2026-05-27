@php
    use App\Models\Order;

    // Hide never-paid orders (abandoned / failed checkouts) so the list shows real orders only.
    $hiddenStatuses = ['pending', 'cancelled', 'failed'];
    $orders = Order::with(['user', 'items'])->whereNotIn('order_status', $hiddenStatuses)->latest()->limit(50)->get();
    $totalOrders = Order::whereNotIn('order_status', $hiddenStatuses)->count();
@endphp

<x-layouts.admin>
    <x-slot:heading>Orders</x-slot:heading>
    <x-slot:subheading>{{ number_format($totalOrders) }} orders total. Showing the latest 50.</x-slot:subheading>

    <div class="flex flex-1 flex-col gap-6">

        <div class="overflow-hidden rounded-[10px] bg-white shadow-sm shadow-zinc-900/5 ring-1 ring-zinc-100">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-[11px]">
                    <thead class="bg-zinc-50 text-[10px] uppercase tracking-wider text-zinc-600">
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
                                $statusValue = $order->order_status?->value ?? 'pending';
                                $statusClasses = match ($statusValue) {
                                    'completed' => 'bg-emerald-50 text-emerald-700',
                                    'partially_completed' => 'bg-blue-50 text-blue-700',
                                    'failed', 'cancelled', 'requires_attention' => 'bg-red-50 text-red-700',
                                    default => 'bg-amber-50 text-amber-700',
                                };
                            @endphp
                            <tr
                                onclick="window.location='{{ route('admin.order', $order) }}'"
                                class="cursor-pointer transition-colors hover:bg-blue-50/40"
                            >
                                <td class="px-5 py-3">
                                    <a href="{{ route('admin.order', $order) }}" class="font-mono font-semibold text-blue-600 hover:text-blue-700">#{{ $order->order_number }}</a>
                                    @if ($order->hasSuspectPricing())
                                        <span class="ml-1 inline-flex items-center rounded-full bg-amber-50 px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wide text-amber-700 ring-1 ring-amber-200" title="No exchange rate snapshot — display amount may be raw USD mis-labelled">!</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3 font-medium text-zinc-900">{{ $order->user?->name ?? 'Unknown' }}</td>
                                <td class="px-5 py-3 text-zinc-600">{{ $order->items->count() }}</td>
                                <td class="px-5 py-3">
                                    {{-- USD is the truth; customer's display amount sits below when it differs. --}}
                                    <p class="font-semibold text-zinc-900">@moneyCode($order->usdTotal(), 'USD')</p>
                                    @if (! $order->hasSuspectPricing() && strtoupper((string) $order->display_currency) !== 'USD')
                                        <p class="text-[10px] text-zinc-500">@moneyCode((float) $order->total_amount, $order->display_currency)</p>
                                    @endif
                                </td>
                                <td class="px-5 py-3">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $statusClasses }}">
                                        {{ $order->order_status?->label() ?? 'Pending' }}
                                    </span>
                                </td>
                                <td class="px-5 py-3 text-zinc-600">{{ $order->created_at->format('M j, Y') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-12 text-center text-sm text-zinc-600">No orders yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</x-layouts.admin>
