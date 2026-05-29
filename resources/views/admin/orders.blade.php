@php
    use App\Models\Order;

    // Hide never-paid orders (abandoned / failed checkouts) so the list shows real orders only.
    $hiddenStatuses = ['pending', 'cancelled', 'failed'];
    $orders = Order::with(['user', 'items'])->whereNotIn('order_status', $hiddenStatuses)->latest()->limit(50)->get();
    $totalOrders = Order::whereNotIn('order_status', $hiddenStatuses)->count();

    // Status pill tone — matches the transactions/customers ring pattern so
    // every list page reads the same. Keys are OrderStatus values.
    $statusPillFor = function (?string $status): array {
        return match ($status) {
            'completed' => ['label' => 'Completed', 'class' => 'bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-500/15 dark:text-emerald-300 dark:ring-emerald-500/30'],
            'partially_completed' => ['label' => 'Partial', 'class' => 'bg-blue-50 text-blue-700 ring-blue-200 dark:bg-blue-600/15 dark:text-blue-300 dark:ring-blue-500/30'],
            'failed', 'cancelled' => ['label' => 'Failed', 'class' => 'bg-red-50 text-red-700 ring-red-200 dark:bg-red-500/15 dark:text-red-300 dark:ring-red-500/30'],
            'requires_attention' => ['label' => 'Attention', 'class' => 'bg-red-50 text-red-700 ring-red-200 dark:bg-red-500/15 dark:text-red-300 dark:ring-red-500/30'],
            'processing' => ['label' => 'Processing', 'class' => 'bg-blue-50 text-blue-700 ring-blue-200 dark:bg-blue-600/15 dark:text-blue-300 dark:ring-blue-500/30'],
            default => ['label' => 'Pending', 'class' => 'bg-amber-50 text-amber-700 ring-amber-200 dark:bg-amber-500/15 dark:text-amber-300 dark:ring-amber-500/30'],
        };
    };
@endphp

<x-layouts.admin>
    <x-slot:heading>Orders</x-slot:heading>
    <x-slot:subheading>{{ number_format($totalOrders) }} orders total. Showing the latest 50.</x-slot:subheading>

    {{-- Shared grid template — same pattern as Customers / Transactions /
         Products pages so every list reads the same. --}}
    <style>
        .order-row {
            display: grid;
            grid-template-columns:
                minmax(170px, 1.2fr)   /* Order # (copyable) */
                minmax(170px, 1.4fr)   /* Customer */
                minmax(60px,  0.4fr)   /* Items count */
                minmax(140px, 1fr)     /* Total */
                minmax(110px, 0.8fr)   /* Status */
                minmax(120px, 0.9fr);  /* Date */
            gap: 1.25rem;
            align-items: center;
        }
        @media (max-width: 1024px) {
            .order-row { grid-template-columns: minmax(170px, 1.4fr) minmax(140px, 1fr) minmax(100px, 0.8fr); }
            .order-row > *:not(.col-order):not(.col-total):not(.col-status) { display: none; }
        }
    </style>

    <div class="flex flex-col gap-2">
        {{-- Header pill — light-blue bg, 2px blue ring. --}}
        <div class="order-row hidden rounded-[10px] bg-blue-50 px-6 py-3 text-[10px] font-bold uppercase tracking-wider text-blue-700 shadow-sm shadow-zinc-900/5 ring-2 ring-blue-500 dark:bg-blue-600/15 dark:text-blue-300 dark:ring-blue-400 md:grid">
            <span class="col-order">Order #</span>
            <span>Customer</span>
            <span>Items</span>
            <span class="col-total">Total</span>
            <span class="col-status">Status</span>
            <span>Date</span>
        </div>

        @forelse ($orders as $order)
            @php
                $statusValue = $order->order_status?->value ?? 'pending';
                $status = $statusPillFor($statusValue);
                $orderNumber = '#'.$order->order_number;
            @endphp
            <a
                href="{{ route('admin.order', $order) }}"
                wire:navigate
                class="order-row group cursor-pointer rounded-[10px] border border-zinc-100 bg-white px-6 py-3 shadow-sm shadow-zinc-900/5 transition-colors hover:border-blue-600 hover:bg-blue-50 dark:border-zinc-700/60 dark:bg-[#1d3252] dark:hover:border-blue-400 dark:hover:bg-blue-600/15"
            >
                {{-- Order # — click-to-copy chip. Stops the link nav so a
                     copy click doesn't open the order page. --}}
                <span class="col-order min-w-0">
                    <button
                        type="button"
                        x-data="{ copied: false }"
                        @click.stop.prevent="navigator.clipboard.writeText(@js($orderNumber)); copied = true; setTimeout(() => copied = false, 1500)"
                        :aria-label="copied ? 'Copied' : 'Copy order id'"
                        class="group/copy inline-flex max-w-full items-center gap-1 rounded-[10px] px-1.5 py-0.5 font-mono text-[12px] font-semibold text-blue-600 transition-colors hover:bg-blue-100 dark:text-blue-400 dark:hover:bg-blue-500/15"
                    >
                        <span class="truncate">{{ $orderNumber }}</span>
                        <svg x-show="! copied" class="h-3 w-3 shrink-0 opacity-0 transition-opacity group-hover/copy:opacity-100" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3"/>
                        </svg>
                        <svg x-show="copied" x-cloak class="h-3 w-3 shrink-0 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                    </button>
                    @if ($order->hasSuspectPricing())
                        <span class="ml-1 inline-flex items-center rounded-[10px] bg-amber-50 px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wide text-amber-700 ring-1 ring-amber-200" title="No exchange rate snapshot — display amount may be raw USD mis-labelled">!</span>
                    @endif
                </span>

                {{-- Customer --}}
                <span class="truncate text-[13px] font-medium text-zinc-900 dark:text-white">{{ $order->user?->name ?? 'Unknown' }}</span>

                {{-- Items count --}}
                <span class="truncate text-[13px] text-zinc-600 dark:text-zinc-400">{{ $order->items->count() }}</span>

                {{-- Total — USD primary, display currency secondary. --}}
                <span class="col-total truncate text-[13px]">
                    <span class="block font-bold tabular-nums text-zinc-900 dark:text-white">@moneyCode($order->usdTotal(), 'USD')</span>
                    @if (! $order->hasSuspectPricing() && strtoupper((string) $order->display_currency) !== 'USD')
                        <span class="block text-[10px] text-zinc-500 dark:text-zinc-400">@moneyCode((float) $order->total_amount, $order->display_currency)</span>
                    @endif
                </span>

                {{-- Status pill --}}
                <span class="col-status">
                    <span class="inline-flex w-fit items-center whitespace-nowrap rounded-[5px] px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide ring-1 {{ $status['class'] }}">
                        {{ $status['label'] }}
                    </span>
                </span>

                {{-- Date --}}
                <span class="truncate text-[12px] text-zinc-600 dark:text-zinc-400">{{ $order->created_at->format('M j, Y') }}</span>
            </a>
        @empty
            <div class="rounded-[10px] bg-white px-5 py-12 text-center text-sm text-zinc-600 shadow-sm ring-1 ring-zinc-100 dark:bg-[#1d3252] dark:text-zinc-400 dark:ring-zinc-700/60">
                No orders yet.
            </div>
        @endforelse
    </div>
</x-layouts.admin>
