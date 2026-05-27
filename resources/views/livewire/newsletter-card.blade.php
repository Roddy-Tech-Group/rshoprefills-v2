<?php

/**
 * Newsletter signup card — sits in the customer dashboard sidebar.
 *
 * One-tap subscribe for an authenticated user: the auth email is the address,
 * NotificationService writes to the newsletter_subscribers table. On mount we
 * check whether the user is already an active subscriber and render the
 * "already subscribed" state instead of the CTA so they aren't shown the prompt
 * again on every dashboard hit.
 */

use App\Domain\Notification\Services\NotificationService;
use App\Models\NewsletterSubscriber;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component
{
    public bool $subscribed = false;
    public bool $loading = false;
    public ?string $errorMessage = null;

    public function mount(): void
    {
        $user = Auth::user();
        if (! $user || ! $user->email) {
            return;
        }

        $existing = NewsletterSubscriber::where('email', $user->email)
            ->where('status', 'active')
            ->first();

        $this->subscribed = (bool) $existing;
    }

    public function subscribe(NotificationService $service): void
    {
        $this->errorMessage = null;
        $user = Auth::user();

        if (! $user || ! $user->email) {
            $this->errorMessage = 'Sign in to subscribe.';

            return;
        }

        $this->loading = true;

        try {
            $service->subscribeNewsletter($user->email, 'dashboard_sidebar');
            $this->subscribed = true;
        } catch (\Throwable $e) {
            $this->errorMessage = 'Subscription failed. Please try again.';
        } finally {
            $this->loading = false;
        }
    }
}; ?>

<div class="rounded-[10px] bg-white p-4 ring-1 ring-zinc-200">
    @if ($subscribed)
        {{-- Already subscribed state --}}
        <div class="flex items-start gap-3">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-[10px] bg-emerald-50">
                <svg class="h-5 w-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                </svg>
            </span>
            <div class="min-w-0 flex-1">
                <p class="text-sm font-semibold text-zinc-900">You're in</p>
                <p class="mt-0.5 text-[11px] text-zinc-600">Deals + new launches arrive in your inbox.</p>
            </div>
        </div>
    @else
        {{-- Subscribe CTA --}}
        <div class="flex items-start gap-3">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-[10px] bg-blue-100">
                <svg class="h-5 w-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/>
                </svg>
            </span>
            <div class="min-w-0 flex-1">
                <p class="text-sm font-semibold text-zinc-900">Stay in the loop</p>
                <p class="mt-0.5 text-[11px] leading-snug text-zinc-600">Deals, drops, and product launches in your inbox.</p>
            </div>
        </div>

        @if ($errorMessage)
            <p class="mt-2 text-[11px] font-medium text-red-600">{{ $errorMessage }}</p>
        @endif

        <button
            type="button"
            wire:click="subscribe"
            wire:target="subscribe"
            wire:loading.attr="disabled"
            class="mt-3 inline-flex w-full items-center justify-center gap-1.5 rounded-[10px] bg-blue-600 px-3 py-2 text-xs font-semibold text-white transition-colors hover:bg-blue-700 disabled:opacity-60"
        >
            <svg wire:loading wire:target="subscribe" class="h-3.5 w-3.5 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
            </svg>
            <span wire:loading.remove wire:target="subscribe">Subscribe</span>
            <span wire:loading wire:target="subscribe">Subscribing...</span>
        </button>
    @endif
</div>
