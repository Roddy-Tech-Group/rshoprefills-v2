@php
    use App\Models\PaymentAttempt;

    // The pre-merge `payments` table was dropped and replaced by `payment_attempts`
    // (polymorphic — an attempt belongs to an Order or a WalletFunding).
    $payments = PaymentAttempt::with(['user', 'order'])->latest()->limit(50)->get();
    $totalPayments = PaymentAttempt::count();

    // Status tone — one set of classes per known status. Used for the per-card
    // accent bar + the right-side status pill.
    $statusToneFor = function (?string $status): array {
        return match ($status) {
            'paid' => ['accent' => 'bg-emerald-500', 'pill' => 'bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-500/15 dark:text-emerald-300 dark:ring-emerald-500/30'],
            'failed', 'expired' => ['accent' => 'bg-red-500', 'pill' => 'bg-red-50 text-red-700 ring-red-200 dark:bg-red-500/15 dark:text-red-300 dark:ring-red-500/30'],
            'refunded', 'partially_refunded' => ['accent' => 'bg-zinc-400', 'pill' => 'bg-zinc-100 text-zinc-700 ring-zinc-200 dark:bg-white/5 dark:text-zinc-300 dark:ring-zinc-700/60'],
            'processing', 'reserved' => ['accent' => 'bg-blue-500', 'pill' => 'bg-blue-50 text-blue-700 ring-blue-200 dark:bg-blue-600/15 dark:text-blue-300 dark:ring-blue-500/30'],
            default => ['accent' => 'bg-amber-500', 'pill' => 'bg-amber-50 text-amber-700 ring-amber-200 dark:bg-amber-500/15 dark:text-amber-300 dark:ring-amber-500/30'],
        };
    };
@endphp

<x-layouts.admin>
    <x-slot:heading>Transactions</x-slot:heading>
    <x-slot:subheading>{{ number_format($totalPayments) }} payment attempts total. Showing the latest 50.</x-slot:subheading>

    {{-- Each payment attempt sits in its own pill card (10px radius per the
         project standard). A coloured accent strip on the left maps status to
         a quick visual signal. Cards stack vertically; on wide viewports the
         row content laps out horizontally so dense scanning is still cheap. --}}
    <div class="flex flex-col gap-2">
        @forelse ($payments as $payment)
            @php
                $statusValue = $payment->payment_status?->value ?? 'pending';
                $tone = $statusToneFor($statusValue);
                $reference = $payment->gateway_reference ?: $payment->idempotency_key;
                $isWalletFunding = ! $payment->order;
            @endphp
            <article class="group relative overflow-hidden rounded-[10px] bg-white shadow-sm shadow-zinc-900/5 ring-1 ring-zinc-100 transition-colors hover:bg-zinc-50 dark:bg-[#1d3252] dark:ring-zinc-700/60 dark:hover:bg-[#26416b]">
                {{-- Status accent strip on the left edge. --}}
                <span class="absolute inset-y-0 left-0 w-1 {{ $tone['accent'] }}"></span>

                <div class="flex flex-col gap-3 px-5 py-3 pl-6 sm:flex-row sm:items-center sm:justify-between">

                    {{-- Left: who + what (customer, reference, order link). --}}
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1">
                            <p class="text-sm font-semibold text-zinc-900 dark:text-white">{{ $payment->user?->name ?? 'Unknown customer' }}</p>
                            <p class="font-mono text-[11px] text-zinc-500 dark:text-zinc-400">
                                {{ $isWalletFunding ? 'Wallet funding' : '#'.$payment->order->order_number }}
                            </p>
                        </div>
                        <p class="mt-0.5 truncate font-mono text-[10px] text-zinc-500 dark:text-zinc-500">{{ $reference }}</p>
                    </div>

                    {{-- Middle: gateway. --}}
                    <div class="hidden text-[11px] text-zinc-600 sm:block dark:text-zinc-400">
                        <p class="text-[9px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">Gateway</p>
                        <p class="mt-0.5 font-medium capitalize text-zinc-700 dark:text-zinc-200">{{ $payment->gateway }}</p>
                    </div>

                    {{-- Right: amount, status, date. --}}
                    <div class="flex items-center gap-3 sm:gap-4">
                        <div class="text-right">
                            <p class="text-sm font-bold tabular-nums text-zinc-900 dark:text-white">@moneyCode((float) $payment->amount, $payment->currency)</p>
                            <p class="mt-0.5 text-[10px] text-zinc-500 dark:text-zinc-400">{{ $payment->created_at->format('M j, Y · g:i A') }}</p>
                        </div>
                        <span class="shrink-0 inline-flex items-center rounded-[10px] px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide ring-1 {{ $tone['pill'] }}">
                            {{ $payment->payment_status?->label() ?? 'Pending' }}
                        </span>
                    </div>
                </div>
            </article>
        @empty
            <div class="rounded-[10px] bg-white px-5 py-12 text-center text-sm text-zinc-600 shadow-sm ring-1 ring-zinc-100 dark:bg-[#1d3252] dark:text-zinc-400 dark:ring-zinc-700/60">
                No transactions yet.
            </div>
        @endforelse
    </div>
</x-layouts.admin>
