@php
    use App\Models\PaymentAttempt;

    // The pre-merge `payments` table was dropped and replaced by `payment_attempts`
    // (polymorphic — an attempt belongs to an Order or a WalletFunding).
    $payments = PaymentAttempt::with(['user', 'order'])->latest()->limit(50)->get();
    $totalPayments = PaymentAttempt::count();

    // Status pill tone — maps PaymentAttempt status onto the canonical
    // x-admin.badge tone palette.
    $statusPillFor = function (?string $status): string {
        return match ($status) {
            'paid' => 'emerald',
            'failed', 'expired' => 'red',
            'refunded', 'partially_refunded' => 'zinc',
            'processing', 'reserved' => 'blue',
            default => 'amber',
        };
    };
@endphp

<x-layouts.admin>
    <x-slot:heading>
        <div class="flex items-end justify-between gap-3">
            <span>Transactions</span>
            {{-- CSV export. Forwards any active filter query params so an admin
                 can scope the file to whatever they were just looking at. --}}
            <a
                href="{{ route('admin.transactions.export', request()->query()) }}"
                class="inline-flex items-center gap-1.5 rounded-[10px] border border-zinc-200 bg-white px-3 py-2 text-xs font-semibold text-zinc-700 transition-colors hover:bg-zinc-50 dark:border-zinc-700/60 dark:bg-[#1d3252] dark:text-zinc-300 dark:hover:bg-[#26416b]"
            >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                </svg>
                Export CSV
            </a>
        </div>
    </x-slot:heading>
    <x-slot:subheading>{{ number_format($totalPayments) }} payment attempts total. Showing the latest 50.</x-slot:subheading>

    {{-- Grid template shared between the header pill and every data row so
         columns line up exactly. Same pattern as the Products page's
         .variant-row — single source of truth for column widths. --}}
    <style>
        .txn-row {
            display: grid;
            grid-template-columns:
                minmax(180px, 1.6fr)   /* Customer + reference (stacked) */
                minmax(110px, 0.9fr)   /* Gateway */
                minmax(120px, 1fr)     /* Amount */
                minmax(140px, 1fr)     /* Date */
                minmax(90px,  0.7fr);  /* Status pill */
            gap: 1.25rem;
            align-items: center;
            min-width: 820px;
        }
        .txn-body:not(:last-of-type)::after {
            content: '';
            position: absolute;
            left: 1.5rem;
            right: 1.5rem;
            bottom: 0;
            height: 1px;
            background-color: rgb(244 244 245);
            pointer-events: none;
        }
        html.dark .txn-body:not(:last-of-type)::after {
            background-color: rgb(255 255 255 / 0.08);
        }
        .txn-body:hover::after { display: none; }
        .txn-body:hover { border-radius: 10px; }
    </style>

    <div class="overflow-hidden rounded-[10px] border-[1.5px] border-white bg-white shadow-sm shadow-zinc-900/[0.04] dark:border-white dark:bg-[#1d3252]">
        <div class="overflow-x-auto [scrollbar-width:thin] [&::-webkit-scrollbar]:h-1.5 [&::-webkit-scrollbar-thumb]:rounded-full [&::-webkit-scrollbar-thumb]:bg-zinc-300 dark:[&::-webkit-scrollbar-thumb]:bg-zinc-600">

        {{-- Header pill --}}
        <div class="txn-row grid mx-3 my-3 rounded-[10px] bg-blue-50 px-6 py-3 text-[10px] font-bold uppercase tracking-wider text-blue-700 ring-2 ring-blue-500 dark:bg-blue-600/15 dark:text-blue-300 dark:ring-blue-400">
            <span class="col-customer">Customer</span>
            <span>Gateway</span>
            <span>Amount</span>
            <span>Date</span>
            <span class="col-status">Status</span>
        </div>

        @forelse ($payments as $payment)
            @php
                $statusValue = $payment->payment_status?->value ?? 'pending';
                $pillTone = $statusPillFor($statusValue);
                $reference = $payment->gateway_reference ?: $payment->idempotency_key;
                $isWalletFunding = ! $payment->order;
            @endphp
            <article class="txn-row txn-body group relative mx-3 cursor-pointer bg-white px-6 py-3 transition-all hover:bg-blue-50 hover:ring-1 hover:ring-inset hover:ring-blue-500 dark:bg-[#1d3252] dark:hover:bg-blue-600/10 dark:hover:ring-blue-400">

                {{-- Customer — name + reference / order link stacked. The
                     reference line is a click-to-copy chip; clicking it puts
                     the full transaction id on the clipboard and flashes a
                     checkmark for ~1.5s as confirmation. --}}
                <div class="col-customer min-w-0">
                    <div class="flex flex-wrap items-baseline gap-x-2 gap-y-0.5">
                        <p class="truncate text-[13px] font-semibold text-zinc-900 dark:text-white">{{ $payment->user?->name ?? 'Unknown customer' }}</p>
                        @if ($isWalletFunding)
                            <p class="truncate font-mono text-[11px] text-zinc-500 dark:text-zinc-400">Wallet funding</p>
                        @else
                            {{-- Order number — click-to-copy chip, same pattern as
                                 the reference line below. --}}
                            <button
                                type="button"
                                x-data="{ copied: false }"
                                @click.stop="navigator.clipboard.writeText(@js('#'.$payment->order->order_number)); copied = true; setTimeout(() => copied = false, 1500)"
                                :aria-label="copied ? 'Copied' : 'Copy order id'"
                                class="group/copy inline-flex max-w-full items-center gap-1 rounded-[10px] px-1.5 py-0.5 font-mono text-[11px] text-zinc-500 transition-colors hover:bg-blue-100 hover:text-blue-700 dark:text-zinc-400 dark:hover:bg-blue-500/15 dark:hover:text-blue-300"
                            >
                                <span class="truncate">#{{ $payment->order->order_number }}</span>
                                <svg x-show="! copied" class="h-3 w-3 shrink-0 opacity-0 transition-opacity group-hover/copy:opacity-100" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3"/>
                                </svg>
                                <svg x-show="copied" x-cloak class="h-3 w-3 shrink-0 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                </svg>
                            </button>
                        @endif
                    </div>
                    <button
                        type="button"
                        x-data="{ copied: false }"
                        @click.stop="navigator.clipboard.writeText(@js($reference)); copied = true; setTimeout(() => copied = false, 1500)"
                        :aria-label="copied ? 'Copied' : 'Copy transaction id'"
                        class="mt-0.5 group/copy inline-flex max-w-full items-center gap-1 rounded-[10px] px-1.5 py-0.5 font-mono text-[10px] text-zinc-500 transition-colors hover:bg-blue-100 hover:text-blue-700 dark:text-zinc-500 dark:hover:bg-blue-500/15 dark:hover:text-blue-300"
                    >
                        <span class="truncate">{{ $reference }}</span>
                        {{-- Copy / check icon swap on copy. --}}
                        <svg x-show="! copied" class="h-3 w-3 shrink-0 opacity-0 transition-opacity group-hover/copy:opacity-100" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3"/>
                        </svg>
                        <svg x-show="copied" x-cloak class="h-3 w-3 shrink-0 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                    </button>
                </div>

                {{-- Gateway --}}
                <span class="truncate text-[13px] font-medium capitalize text-zinc-700 dark:text-zinc-200">{{ $payment->gateway }}</span>

                {{-- Amount --}}
                <span class="col-amount truncate text-[13px] font-bold tabular-nums text-zinc-900 dark:text-white">@moneyCode((float) $payment->amount, $payment->currency)</span>

                {{-- Date --}}
                <span class="truncate text-[12px] text-zinc-600 dark:text-zinc-400">{{ $payment->created_at->format('M j, Y · g:i A') }}</span>

                {{-- Status pill --}}
                <span class="col-status">
                    <x-admin.badge :tone="$pillTone">{{ $payment->payment_status?->label() ?? 'Pending' }}</x-admin.badge>
                </span>
            </article>
        @empty
            <div class="px-5 py-12 text-center text-sm text-zinc-600 dark:text-zinc-400">
                No transactions yet.
            </div>
        @endforelse

        </div>
    </div>
</x-layouts.admin>
