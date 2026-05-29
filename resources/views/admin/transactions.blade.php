@php
    use App\Models\PaymentAttempt;

    // The pre-merge `payments` table was dropped and replaced by `payment_attempts`
    // (polymorphic — an attempt belongs to an Order or a WalletFunding).
    $payments = PaymentAttempt::with(['user', 'order'])->latest()->limit(50)->get();
    $totalPayments = PaymentAttempt::count();

    // Status pill tone — one set of classes per known status. Drives the
    // right-side status chip on every row.
    $statusPillFor = function (?string $status): string {
        return match ($status) {
            'paid' => 'bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-500/15 dark:text-emerald-300 dark:ring-emerald-500/30',
            'failed', 'expired' => 'bg-red-50 text-red-700 ring-red-200 dark:bg-red-500/15 dark:text-red-300 dark:ring-red-500/30',
            'refunded', 'partially_refunded' => 'bg-zinc-100 text-zinc-700 ring-zinc-200 dark:bg-white/5 dark:text-zinc-300 dark:ring-zinc-700/60',
            'processing', 'reserved' => 'bg-blue-50 text-blue-700 ring-blue-200 dark:bg-blue-600/15 dark:text-blue-300 dark:ring-blue-500/30',
            default => 'bg-amber-50 text-amber-700 ring-amber-200 dark:bg-amber-500/15 dark:text-amber-300 dark:ring-amber-500/30',
        };
    };
@endphp

<x-layouts.admin>
    <x-slot:heading>Transactions</x-slot:heading>
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
        }
        @media (max-width: 1024px) {
            .txn-row { grid-template-columns: minmax(160px, 1.5fr) minmax(110px, 1fr) minmax(90px, 0.7fr); }
            .txn-row > *:not(.col-customer):not(.col-amount):not(.col-status) { display: none; }
        }
    </style>

    <div class="flex flex-col gap-2">
        {{-- Header pill — light-blue background, 2px blue ring, matches the
             Products filter bar styling. --}}
        <div class="txn-row hidden rounded-[10px] bg-blue-50 px-6 py-3 text-[10px] font-bold uppercase tracking-wider text-blue-700 shadow-sm shadow-zinc-900/5 ring-2 ring-blue-500 dark:bg-blue-600/15 dark:text-blue-300 dark:ring-blue-400 md:grid">
            <span class="col-customer">Customer</span>
            <span>Gateway</span>
            <span>Amount</span>
            <span>Date</span>
            <span class="col-status">Status</span>
        </div>

        @forelse ($payments as $payment)
            @php
                $statusValue = $payment->payment_status?->value ?? 'pending';
                $pillClass = $statusPillFor($statusValue);
                $reference = $payment->gateway_reference ?: $payment->idempotency_key;
                $isWalletFunding = ! $payment->order;
            @endphp
            <article class="txn-row group cursor-pointer rounded-[10px] border border-zinc-100 bg-white px-6 py-3 shadow-sm shadow-zinc-900/5 transition-colors hover:border-blue-600 hover:bg-blue-50 dark:border-zinc-700/60 dark:bg-[#1d3252] dark:hover:border-blue-400 dark:hover:bg-blue-600/15">

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
                    <span class="inline-flex w-fit items-center whitespace-nowrap rounded-[5px] px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide ring-1 {{ $pillClass }}">
                        {{ $payment->payment_status?->label() ?? 'Pending' }}
                    </span>
                </span>
            </article>
        @empty
            <div class="rounded-[10px] bg-white px-5 py-12 text-center text-sm text-zinc-600 shadow-sm ring-1 ring-zinc-100 dark:bg-[#1d3252] dark:text-zinc-400 dark:ring-zinc-700/60">
                No transactions yet.
            </div>
        @endforelse
    </div>
</x-layouts.admin>
