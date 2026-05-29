@php
    /** @var \App\Models\User $suspendedUser */
    $suspendedUser = auth()->user();
    $reviewPending = $suspendedUser->hasRequestedSuspensionReview();
@endphp

<div class="mb-5 overflow-hidden rounded-2xl border border-amber-200 bg-amber-50 shadow-sm dark:border-amber-500/30 dark:bg-amber-500/10">
    <div class="flex flex-col gap-3 px-5 py-4 sm:flex-row sm:items-start sm:justify-between sm:gap-6">
        <div class="flex items-start gap-3">
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-200">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.732 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                </svg>
            </span>
            <div class="min-w-0">
                <p class="text-sm font-bold text-amber-900 dark:text-amber-100">Your account is suspended</p>
                <p class="mt-1 text-xs leading-relaxed text-amber-800 dark:text-amber-200/90">
                    @if ($suspendedUser->suspension_reason)
                        {{ $suspendedUser->suspension_reason }}
                    @else
                        Your account is on hold and cannot purchase, fund or check out right now. Our team is reviewing it.
                    @endif
                </p>
                @if ($reviewPending)
                    <p class="mt-2 inline-flex items-center gap-1 text-[11px] font-medium text-amber-900 dark:text-amber-100">
                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                        Review request received on {{ $suspendedUser->suspension_review_requested_at?->format('M j, Y · g:i A') }} — we'll be in touch soon.
                    </p>
                @endif
            </div>
        </div>

        @unless ($reviewPending)
            <form method="POST" action="{{ route('suspension.request-review') }}" class="shrink-0">
                @csrf
                <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-amber-600 px-4 py-2 text-xs font-semibold text-white shadow-sm transition-colors hover:bg-amber-700 sm:w-auto">
                    Request review
                </button>
            </form>
        @endunless
    </div>

    @if (session('status'))
        <div class="border-t border-amber-200 bg-amber-100/70 px-5 py-2 text-xs text-amber-900 dark:border-amber-500/30 dark:bg-amber-500/20 dark:text-amber-100">
            {{ session('status') }}
        </div>
    @endif
</div>
