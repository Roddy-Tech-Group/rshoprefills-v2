@php
    // Admin customer detail — a full picture of one user: profile, wallet(s),
    // lifetime stats, and recent commerce activity (orders + wallet movements).
    // Read-only; mirrors the admin order detail page's layout conventions.

    use App\Domain\Order\Enums\OrderStatus;

    $fmtDate = fn ($d) => $d ? $d->format('M j, Y · g:i A') : null;

    // Status -> badge classes, shared across order / fulfilment / payment enums.
    $toneFor = function (?string $v): string {
        return match ($v) {
            'completed', 'paid', 'fulfilled' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
            'partially_completed', 'partially_fulfilled', 'partially_paid', 'processing' => 'bg-blue-50 text-blue-700 ring-blue-200',
            'failed', 'cancelled', 'requires_attention', 'expired' => 'bg-red-50 text-red-700 ring-red-200',
            'refunded', 'partially_refunded' => 'bg-zinc-100 text-zinc-700 ring-zinc-200',
            default => 'bg-amber-50 text-amber-700 ring-amber-200',
        };
    };

    $avatar = $user->avatar_url ?: asset('assets/' . rawurlencode(match (strtolower($user->gender ?? '')) {
        'female', 'f' => 'New Female Account Avatar.png',
        default       => 'New male account avatar.png',
    }));

    $primaryWallet = $user->wallets->first(fn ($wallet) => $wallet->currency->value === 'USD')
        ?? $user->wallets->first();
@endphp

<x-layouts.admin>
    <x-slot:heading>{{ $user->name }}</x-slot:heading>
    <x-slot:subheading>Customer #{{ $user->id }} · Joined {{ $user->created_at->format('M j, Y') }}</x-slot:subheading>

    <div class="flex flex-1 flex-col gap-6">

        @if (session('status'))
            <div class="flex items-center gap-2 rounded-xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 ring-1 ring-emerald-200">
                <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                </svg>
                {{ session('status') }}
            </div>
        @endif

        {{-- Back link --}}
        <a href="{{ route('admin.customers') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-blue-600 hover:text-blue-700">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/>
            </svg>
            All customers
        </a>

        {{-- Profile header --}}
        <div class="rounded-[10px] bg-white p-5 shadow-sm shadow-zinc-900/5 ring-1 ring-zinc-100">
            <div class="flex flex-wrap items-center gap-4">
                <img src="{{ $avatar }}" alt="" class="h-16 w-16 shrink-0 rounded-full object-cover ring-1 ring-blue-100">
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="text-lg font-bold text-zinc-900">{{ $user->name }}</h2>
                        @if ($user->email_verified_at)
                            <span class="inline-flex items-center rounded-[5px] bg-emerald-400 px-2.5 py-0.5 text-xs font-semibold text-white">Active</span>
                        @else
                            <span class="inline-flex items-center rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-semibold text-amber-700">Pending</span>
                        @endif
                    </div>
                    <p class="mt-0.5 text-sm text-zinc-600">{{ $user->email }}</p>
                </div>
            </div>

            <dl class="mt-5 grid grid-cols-2 gap-x-6 gap-y-3 text-xs sm:grid-cols-4">
                <div>
                    <dt class="text-[10px] font-semibold uppercase tracking-wider text-zinc-800">Phone</dt>
                    <dd class="mt-1 font-medium text-zinc-700">{{ $user->phone ?: '-' }}</dd>
                </div>
                <div>
                    <dt class="text-[10px] font-semibold uppercase tracking-wider text-zinc-800">Gender</dt>
                    <dd class="mt-1 font-medium capitalize text-zinc-700">{{ $user->gender ?: '-' }}</dd>
                </div>
                <div>
                    <dt class="text-[10px] font-semibold uppercase tracking-wider text-zinc-800">Sign-in</dt>
                    <dd class="mt-1 font-medium text-zinc-700">{{ $user->google_id ? 'Google' : 'Email & password' }}</dd>
                </div>
                <div>
                    <dt class="text-[10px] font-semibold uppercase tracking-wider text-zinc-800">Email verified</dt>
                    <dd class="mt-1 font-medium text-zinc-700">{{ $fmtDate($user->email_verified_at) ?? 'Not verified' }}</dd>
                </div>
            </dl>
        </div>

        {{-- Admin actions: edit, notify, verify email/KYC, suspend, hold funds, ban --}}
        @php
            $isBanned = $user->banned_at !== null;
            $isSuspended = $user->isSuspended();
            $fundsHeld = $user->wallets->isNotEmpty() && $user->wallets->where('is_active', true)->isEmpty();
            $emailVerified = $user->email_verified_at !== null;
            // Normalised KYC string, used by both the dropdown above AND the badge
            // block lower down. Computed once here so the dropdown doesn't 500
            // when no status badges happen to render.
            $kycRaw = strtolower((string) ($user->kyc_status ?? ''));
            $kycVerified = $kycRaw === 'verified';
        @endphp
        <div x-data="{ editing: @js($errors->hasAny(['name', 'email', 'phone', 'gender'])), warning: @js($errors->hasAny(['type', 'body'])), suspending: @js($errors->hasAny(['reason'])) }" class="rounded-[10px] bg-white shadow-sm shadow-zinc-900/5 ring-1 ring-zinc-100">
            @php
                // Shared button shape. Compact + responsive:
                //   - Mobile: full-width grid cells (two per row) so labels never truncate
                //   - sm+:    inline auto-width chips that flex-wrap on overflow
                // Toned variants are appended per-button.
                $btn = 'inline-flex w-full sm:w-auto items-center justify-center gap-1 rounded-lg px-2.5 py-1.5 text-[11px] font-semibold transition-colors';
                $btnIcon = 'h-3 w-3 shrink-0';
            @endphp
            <div class="flex flex-col gap-3 border-b border-zinc-100 px-5 py-4 sm:flex-row sm:flex-wrap sm:items-center sm:justify-between">
                <h3 class="text-sm font-bold text-zinc-900">Actions</h3>
                <div class="grid w-full grid-cols-2 gap-1.5 sm:flex sm:w-auto sm:flex-wrap sm:items-center sm:gap-2">
                    <button type="button" @click="editing = ! editing" class="{{ $btn }} bg-zinc-100 text-zinc-700 hover:bg-zinc-200">
                        <svg class="{{ $btnIcon }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/></svg>
                        Edit
                    </button>

                    <button type="button" @click="warning = true" class="{{ $btn }} bg-blue-50 text-blue-700 ring-1 ring-blue-200 hover:bg-blue-100 dark:bg-blue-600/15 dark:text-blue-300 dark:ring-blue-500/30 dark:hover:bg-blue-600/25">
                        <svg class="{{ $btnIcon }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                        Notify
                    </button>

                    {{-- Verify email — toggles email_verified_at on/off. Confirm only on removal. --}}
                    <form method="POST" action="{{ route('admin.customer.verify-email', $user) }}"
                          @if ($emailVerified)
                              data-confirm="Remove email verification for this customer? They may be re-prompted to verify."
                              data-confirm-title="Remove email verification"
                              data-confirm-text="Remove"
                              data-confirm-tone="warning"
                          @endif>
                        @csrf
                        <button type="submit" class="{{ $btn }} {{ $emailVerified ? 'bg-zinc-100 text-zinc-700 ring-1 ring-zinc-200 hover:bg-zinc-200' : 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200 hover:bg-emerald-100' }}">
                            <svg class="{{ $btnIcon }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0-.621.504-1.125 1.125-1.125h17.25c.621 0 1.125.504 1.125 1.125v10.5c0 .621-.504 1.125-1.125 1.125H3.375a1.125 1.125 0 01-1.125-1.125V6.75zM2.25 6.75l9.75 7.5 9.75-7.5"/></svg>
                            {{ $emailVerified ? 'Email ✓' : 'Verify email' }}
                        </button>
                    </form>

                    {{-- KYC status dropdown — admin can flip between Under review,
                         Verified, and Rejected. Each option goes through the global
                         confirm modal so a mis-click never lands a status change. --}}
                    <div x-data="{ open: false }" @click.outside="open = false" @keydown.escape.window="open = false" class="relative">
                        <button type="button" @click="open = ! open" :aria-expanded="open.toString()" class="{{ $btn }} bg-zinc-100 text-zinc-700 ring-1 ring-zinc-200 hover:bg-zinc-200 dark:bg-white/5 dark:text-zinc-200 dark:ring-zinc-700/60 dark:hover:bg-white/10">
                            <svg class="{{ $btnIcon }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Set KYC
                            <svg class="h-3 w-3 transition-transform" :class="open && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="open" x-transition style="display:none;" class="absolute right-0 z-30 mt-1.5 w-56 overflow-hidden rounded-xl bg-white p-1.5 shadow-xl shadow-zinc-900/10 ring-1 ring-zinc-200 dark:bg-[#1d3252] dark:ring-zinc-700/60" role="menu">
                            @php
                                // Each row is a single-field POST. The blade renders an
                                // unstyled <button> inside a <form> with data-confirm so
                                // the global modal handles approval.
                                $kycOptions = [
                                    'pending' => [
                                        'label' => 'Mark under review',
                                        'description' => 'Awaiting documents or follow-up.',
                                        'confirm' => 'Mark this customer as KYC under review?',
                                        'tone' => 'primary',
                                        'classes' => 'text-blue-700 hover:bg-blue-50 dark:text-blue-300 dark:hover:bg-blue-600/15',
                                    ],
                                    'verified' => [
                                        'label' => 'Mark verified',
                                        'description' => 'Use only when you hold the proof out-of-band.',
                                        'confirm' => 'Manually mark this customer as KYC-verified? Use only when you hold the proof out-of-band.',
                                        'tone' => 'success',
                                        'classes' => 'text-emerald-700 hover:bg-emerald-50 dark:text-emerald-300 dark:hover:bg-emerald-500/15',
                                    ],
                                    'rejected' => [
                                        'label' => 'Mark rejected',
                                        'description' => 'Customer will need to resubmit.',
                                        'confirm' => 'Reject this customer\'s KYC? They will need to resubmit their documents.',
                                        'tone' => 'danger',
                                        'classes' => 'text-red-700 hover:bg-red-50 dark:text-red-300 dark:hover:bg-red-500/15',
                                    ],
                                ];
                            @endphp
                            @foreach ($kycOptions as $status => $opt)
                                @continue($kycRaw === $status)
                                <form method="POST" action="{{ route('admin.customer.kyc-status', $user) }}"
                                      data-confirm="{{ $opt['confirm'] }}"
                                      data-confirm-title="{{ $opt['label'] }}"
                                      data-confirm-text="{{ $opt['label'] }}"
                                      data-confirm-tone="{{ $opt['tone'] }}">
                                    @csrf
                                    <input type="hidden" name="status" value="{{ $status }}">
                                    <button type="submit" class="flex w-full flex-col items-start rounded-lg px-3 py-2 text-left text-xs font-medium transition-colors {{ $opt['classes'] }}">
                                        <span class="font-semibold">{{ $opt['label'] }}</span>
                                        <span class="mt-0.5 text-[10px] text-zinc-500 dark:text-zinc-400">{{ $opt['description'] }}</span>
                                    </button>
                                </form>
                            @endforeach
                            @if (count(array_filter($kycOptions, fn ($_, $s) => $s !== $kycRaw, ARRAY_FILTER_USE_BOTH)) === 0)
                                <p class="px-3 py-2 text-[11px] text-zinc-500 dark:text-zinc-400">No other states to set.</p>
                            @endif
                        </div>
                    </div>

                    {{-- Suspend / Lift. Opens a modal for the reason on first click; lifting is one-click. --}}
                    @if ($isSuspended)
                        <form method="POST" action="{{ route('admin.customer.suspend', $user) }}"
                              data-confirm="Lift the suspension on this account? The customer will regain full access."
                              data-confirm-title="Lift suspension"
                              data-confirm-text="Lift suspension"
                              data-confirm-tone="success">
                            @csrf
                            <button type="submit" class="{{ $btn }} bg-emerald-600 text-white hover:bg-emerald-700">
                                <svg class="{{ $btnIcon }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5V6.75a4.5 4.5 0 119 0v3.75M3.75 21.75h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H3.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/></svg>
                                Lift suspension
                            </button>
                        </form>
                    @else
                        <button type="button" @click="suspending = true" class="{{ $btn }} bg-amber-50 text-amber-700 ring-1 ring-amber-200 hover:bg-amber-100">
                            <svg class="{{ $btnIcon }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.732 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
                            Suspend
                        </button>
                    @endif

                    <form method="POST" action="{{ route('admin.customer.funds', $user) }}"
                          data-confirm="{{ $fundsHeld ? 'Release this customer\'s funds so they can spend from their wallet again?' : 'Place this customer\'s funds on hold? Their wallet will be frozen until released.' }}"
                          data-confirm-title="{{ $fundsHeld ? 'Release funds' : 'Hold funds' }}"
                          data-confirm-text="{{ $fundsHeld ? 'Release' : 'Hold' }}"
                          data-confirm-tone="{{ $fundsHeld ? 'success' : 'warning' }}">
                        @csrf
                        <button type="submit" class="{{ $btn }} {{ $fundsHeld ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200 hover:bg-emerald-100' : 'bg-amber-50 text-amber-700 ring-1 ring-amber-200 hover:bg-amber-100' }}">
                            {{ $fundsHeld ? 'Release funds' : 'Hold funds' }}
                        </button>
                    </form>

                    <form method="POST" action="{{ route('admin.customer.ban', $user) }}"
                          data-confirm="{{ $isBanned ? 'Unban this customer? They will be able to sign in again.' : 'Ban this customer? They will be signed out immediately and blocked from signing in.' }}"
                          data-confirm-title="{{ $isBanned ? 'Unban customer' : 'Ban customer' }}"
                          data-confirm-text="{{ $isBanned ? 'Unban' : 'Ban' }}"
                          data-confirm-tone="{{ $isBanned ? 'success' : 'danger' }}">
                        @csrf
                        <button type="submit" class="{{ $btn }} text-white {{ $isBanned ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-red-600 hover:bg-red-700' }}">
                            {{ $isBanned ? 'Unban' : 'Ban' }}
                        </button>
                    </form>
                </div>
            </div>

            {{-- Suspend modal — captures an optional reason shown to the customer. --}}
            <div x-show="suspending" x-cloak @keydown.escape.window="suspending = false" class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true">
                <div x-show="suspending" @click="suspending = false" x-transition.opacity class="absolute inset-0 bg-zinc-900/40 dark:bg-zinc-950/70"></div>
                <form x-show="suspending" x-transition method="POST" action="{{ route('admin.customer.suspend', $user) }}" class="relative w-full max-w-lg overflow-hidden rounded-2xl bg-white shadow-2xl dark:bg-[#1d3252] dark:ring-1 dark:ring-zinc-700/60">
                    @csrf
                    <div class="flex items-start justify-between gap-4 border-b border-zinc-100 px-5 py-4 dark:border-zinc-700/60">
                        <div>
                            <h3 class="text-sm font-bold text-zinc-900 dark:text-white">Suspend customer</h3>
                            <p class="mt-0.5 text-xs text-zinc-600 dark:text-zinc-300">They will stay signed in but cannot purchase, fund, or check out. They can request a review.</p>
                        </div>
                        <button type="button" @click="suspending = false" aria-label="Close" class="flex h-8 w-8 items-center justify-center rounded-full bg-zinc-100 text-zinc-600 transition-colors hover:bg-zinc-200 dark:bg-[#26416b] dark:text-zinc-300 dark:hover:bg-[#34507a]">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <div class="space-y-4 px-5 py-4">
                        <div>
                            <label class="text-[10px] font-semibold uppercase tracking-wider text-zinc-800 dark:text-zinc-400">Reason (optional, shown to the customer)</label>
                            <textarea name="reason" rows="4" maxlength="500" placeholder="e.g. We detected unusual activity on your account and need to review it before unlocking further purchases." class="mt-1.5 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 outline-none transition-colors focus:border-amber-500 focus:ring-2 focus:ring-amber-500/15 dark:border-zinc-700/60 dark:bg-[#26416b] dark:text-white dark:placeholder:text-zinc-400">{{ old('reason') }}</textarea>
                            @error('reason') <p class="mt-1 text-[11px] font-medium text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div class="flex items-center justify-end gap-2 border-t border-zinc-100 bg-zinc-50 px-5 py-3 dark:border-zinc-700/60 dark:bg-[#162a4a]">
                        <button type="button" @click="suspending = false" class="inline-flex items-center rounded-xl px-3.5 py-2 text-xs font-semibold text-zinc-700 transition-colors hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-[#26416b]">Cancel</button>
                        <button type="submit" class="inline-flex items-center rounded-xl bg-amber-600 px-4 py-2 text-xs font-semibold text-white transition-colors hover:bg-amber-700">Suspend</button>
                    </div>
                </form>
            </div>

            {{-- Notify / message modal --}}
            <div x-show="warning" x-cloak @keydown.escape.window="warning = false" class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true">
                <div x-show="warning" @click="warning = false" x-transition.opacity class="absolute inset-0 bg-zinc-900/40 dark:bg-zinc-950/70"></div>
                <form x-show="warning" x-transition method="POST" action="{{ route('admin.customer.message', $user) }}" class="relative w-full max-w-lg overflow-hidden rounded-2xl bg-white shadow-2xl dark:bg-[#1d3252] dark:ring-1 dark:ring-zinc-700/60">
                    @csrf
                    <div class="flex items-start justify-between gap-4 border-b border-zinc-100 px-5 py-4 dark:border-zinc-700/60">
                        <div>
                            <h3 class="text-sm font-bold text-zinc-900 dark:text-white">Send a message</h3>
                            <p class="mt-0.5 text-xs text-zinc-600 dark:text-zinc-300">Emails {{ $user->email }} and pushes a notification to their dashboard.</p>
                        </div>
                        <button type="button" @click="warning = false" aria-label="Close" class="flex h-8 w-8 items-center justify-center rounded-full bg-zinc-100 text-zinc-600 transition-colors hover:bg-zinc-200 dark:bg-[#26416b] dark:text-zinc-300 dark:hover:bg-[#34507a]">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <div class="space-y-4 px-5 py-4">
                        <div>
                            <p class="text-[10px] font-semibold uppercase tracking-wider text-zinc-800 dark:text-zinc-400">Type</p>
                            <div class="mt-2 grid grid-cols-2 gap-2">
                                @php
                                    // Each option carries both light + dark "checked" styling so the active
                                    // card reads correctly in either theme without the white-on-dark blowout
                                    // the previous single-mode styling produced.
                                    $typeOptions = [
                                        'notification' => [
                                            'label' => 'Notification',
                                            'checked' => 'has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50 has-[:checked]:text-blue-700 has-[:checked]:ring-blue-500/20 dark:has-[:checked]:bg-blue-600/15 dark:has-[:checked]:text-blue-300',
                                        ],
                                        'warning' => [
                                            'label' => 'Warning',
                                            'checked' => 'has-[:checked]:border-amber-500 has-[:checked]:bg-amber-50 has-[:checked]:text-amber-700 has-[:checked]:ring-amber-500/20 dark:has-[:checked]:bg-amber-500/15 dark:has-[:checked]:text-amber-300',
                                        ],
                                    ];
                                @endphp
                                @foreach ($typeOptions as $value => $opt)
                                    <label class="relative flex cursor-pointer items-center gap-2 rounded-lg border-2 border-zinc-200 px-3 py-2 text-sm font-semibold text-zinc-700 transition-colors dark:border-zinc-700/60 dark:text-zinc-200 {{ $opt['checked'] }}">
                                        <input type="radio" name="type" value="{{ $value }}" @checked(old('type', 'notification') === $value) class="h-4 w-4 cursor-pointer accent-blue-600">
                                        {{ $opt['label'] }}
                                    </label>
                                @endforeach
                            </div>
                            @error('type') <p class="mt-1 text-[11px] font-medium text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="text-[10px] font-semibold uppercase tracking-wider text-zinc-800 dark:text-zinc-400">Message</label>
                            <textarea name="body" rows="6" required minlength="5" maxlength="2000" placeholder="Write the message the customer will see in their email and dashboard…" class="mt-1.5 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 outline-none transition-colors focus:border-blue-500 focus:ring-2 focus:ring-blue-500/15 dark:border-zinc-700/60 dark:bg-[#26416b] dark:text-white dark:placeholder:text-zinc-400">{{ old('body') }}</textarea>
                            @error('body') <p class="mt-1 text-[11px] font-medium text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div class="flex items-center justify-end gap-2 border-t border-zinc-100 bg-zinc-50 px-5 py-3 dark:border-zinc-700/60 dark:bg-[#162a4a]">
                        <button type="button" @click="warning = false" class="inline-flex items-center rounded-xl px-3.5 py-2 text-xs font-semibold text-zinc-700 transition-colors hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-[#26416b]">Cancel</button>
                        <button type="submit" class="inline-flex items-center rounded-xl bg-blue-600 px-4 py-2 text-xs font-semibold text-white transition-colors hover:bg-blue-700">Send</button>
                    </div>
                </form>
            </div>

            @php
                // KYC label + tone, keyed off the raw status string. `$kycRaw`
                // is computed up top alongside the other status flags. Falls
                // back to a neutral "Not started" so an unknown value still
                // renders sensibly rather than leaking the raw enum to the admin.
                // NOTE: avoid `$kyc` — the controller passes in a KycSubmission
                // model on that name (used further down for the documents grid).
                $kycMap = [
                    'verified' => ['label' => 'KYC verified',    'tone' => 'bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-500/15 dark:text-emerald-300 dark:ring-emerald-500/30'],
                    'pending'  => ['label' => 'KYC under review','tone' => 'bg-blue-50 text-blue-700 ring-blue-200 dark:bg-blue-600/15 dark:text-blue-300 dark:ring-blue-500/30'],
                    'rejected' => ['label' => 'KYC rejected',    'tone' => 'bg-red-50 text-red-700 ring-red-200 dark:bg-red-500/15 dark:text-red-300 dark:ring-red-500/30'],
                    'unsubmitted' => ['label' => 'KYC not started', 'tone' => 'bg-zinc-100 text-zinc-700 ring-zinc-200 dark:bg-[#26416b] dark:text-zinc-200 dark:ring-zinc-700/60'],
                ];
                $kycBadge = $kycMap[$kycRaw] ?? ['label' => 'KYC not started', 'tone' => 'bg-zinc-100 text-zinc-700 ring-zinc-200 dark:bg-[#26416b] dark:text-zinc-200 dark:ring-zinc-700/60'];
                // Compact pill: smaller padding + tighter text so the row tucks
                // close to the divider above instead of floating in dead space.
                $badgeBase = 'inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-[11px] font-medium ring-1';
            @endphp
            @if ($isBanned || $isSuspended || $fundsHeld || ! $emailVerified || ! $kycVerified)
                <div class="flex flex-wrap gap-1.5 px-5 py-2">
                    @if ($isBanned)
                        <span class="{{ $badgeBase }} bg-red-50 text-red-700 ring-red-200 dark:bg-red-500/15 dark:text-red-300 dark:ring-red-500/30">Banned{{ $user->banned_at ? ' on '.$fmtDate($user->banned_at) : '' }}</span>
                    @endif
                    @if ($isSuspended)
                        <span class="{{ $badgeBase }} bg-amber-50 text-amber-700 ring-amber-200 dark:bg-amber-500/15 dark:text-amber-300 dark:ring-amber-500/30">
                            Suspended{{ $user->suspended_at ? ' on '.$fmtDate($user->suspended_at) : '' }}
                        </span>
                        @if ($user->hasRequestedSuspensionReview())
                            <span class="{{ $badgeBase }} bg-blue-50 text-blue-700 ring-blue-200 dark:bg-blue-600/15 dark:text-blue-300 dark:ring-blue-500/30">
                                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21 11.25v8.25a1.5 1.5 0 01-1.5 1.5H5.25a1.5 1.5 0 01-1.5-1.5v-8.25M12 4.875A2.625 2.625 0 109.375 7.5H12m0-2.625V7.5m0-2.625A2.625 2.625 0 1114.625 7.5H12"/></svg>
                                Review requested {{ $fmtDate($user->suspension_review_requested_at) }}
                            </span>
                        @endif
                    @endif
                    @if ($fundsHeld)
                        <span class="{{ $badgeBase }} bg-amber-50 text-amber-700 ring-amber-200 dark:bg-amber-500/15 dark:text-amber-300 dark:ring-amber-500/30">Funds on hold</span>
                    @endif
                    @unless ($emailVerified)
                        <span class="{{ $badgeBase }} bg-zinc-100 text-zinc-700 ring-zinc-200 dark:bg-[#26416b] dark:text-zinc-200 dark:ring-zinc-700/60">Email unverified</span>
                    @endunless
                    @unless ($kycVerified)
                        <span class="{{ $badgeBase }} {{ $kycBadge['tone'] }}">{{ $kycBadge['label'] }}</span>
                    @endunless
                </div>
                @if ($isSuspended && $user->suspension_reason)
                    <p class="px-5 pt-3 text-xs text-zinc-700 dark:text-zinc-300"><span class="font-semibold">Reason shown to customer:</span> {{ $user->suspension_reason }}</p>
                @endif
            @endif

            {{-- Edit form --}}
            <form x-show="editing" x-cloak method="POST" action="{{ route('admin.customer.update', $user) }}" class="px-5 py-4">
                @csrf
                @method('PATCH')
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="text-[10px] font-semibold uppercase tracking-wider text-zinc-800">Name</label>
                        <input name="name" value="{{ old('name', $user->name) }}" class="mt-1.5 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 outline-none transition-colors focus:border-blue-500 focus:ring-2 focus:ring-blue-500/15">
                        @error('name') <p class="mt-1 text-[11px] font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="text-[10px] font-semibold uppercase tracking-wider text-zinc-800">Email</label>
                        <input name="email" type="email" value="{{ old('email', $user->email) }}" class="mt-1.5 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 outline-none transition-colors focus:border-blue-500 focus:ring-2 focus:ring-blue-500/15">
                        @error('email') <p class="mt-1 text-[11px] font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="text-[10px] font-semibold uppercase tracking-wider text-zinc-800">Phone</label>
                        <input name="phone" value="{{ old('phone', $user->phone) }}" class="mt-1.5 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 outline-none transition-colors focus:border-blue-500 focus:ring-2 focus:ring-blue-500/15">
                        @error('phone') <p class="mt-1 text-[11px] font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="text-[10px] font-semibold uppercase tracking-wider text-zinc-800">Gender</label>
                        {{-- Native <select> kept for accessibility + form submission; the OS arrow
                             is hidden with appearance-none and replaced by a centred chevron SVG. --}}
                        <div class="relative mt-1.5">
                            <select name="gender" class="w-full appearance-none rounded-lg border border-zinc-200 bg-white px-3 py-2 pr-9 text-sm text-zinc-900 outline-none transition-colors focus:border-blue-500 focus:ring-2 focus:ring-blue-500/15">
                                <option value="">Not set</option>
                                @foreach (['male', 'female', 'other'] as $g)
                                    <option value="{{ $g }}" @selected(old('gender', $user->gender) === $g)>{{ ucfirst($g) }}</option>
                                @endforeach
                            </select>
                            <svg class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-zinc-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </div>
                        @error('gender') <p class="mt-1 text-[11px] font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
                <button type="submit" class="mt-4 inline-flex items-center rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-blue-700">Save changes</button>
            </form>
        </div>

        {{-- Identity verification (KYC) --}}
        @php
            $kycTone = match ($user->kyc_status) {
                'verified' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
                'pending'  => 'bg-amber-50 text-amber-700 ring-amber-200',
                'rejected' => 'bg-red-50 text-red-700 ring-red-200',
                default    => 'bg-zinc-100 text-zinc-600 ring-zinc-200',
            };
        @endphp
        <div class="rounded-[10px] bg-white shadow-sm shadow-zinc-900/5 ring-1 ring-zinc-100">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-zinc-100 px-5 py-4">
                <h3 class="text-sm font-bold text-zinc-900">Identity verification (KYC)</h3>
                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-semibold uppercase tracking-wide ring-1 {{ $kycTone }}">{{ $user->kyc_status }}</span>
            </div>

            @if ($kyc)
                <div class="px-5 py-4">
                    <dl class="grid grid-cols-2 gap-x-6 gap-y-3 text-xs sm:grid-cols-3">
                        <div>
                            <dt class="text-[10px] font-semibold uppercase tracking-wider text-zinc-800">Full name</dt>
                            <dd class="mt-1 font-medium text-zinc-700">{{ $kyc->full_name }}</dd>
                        </div>
                        <div>
                            <dt class="text-[10px] font-semibold uppercase tracking-wider text-zinc-800">Date of birth</dt>
                            <dd class="mt-1 font-medium text-zinc-700">{{ optional($kyc->date_of_birth)->format('M j, Y') ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-[10px] font-semibold uppercase tracking-wider text-zinc-800">Country</dt>
                            <dd class="mt-1 font-medium text-zinc-700">{{ $kyc->country ?: '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-[10px] font-semibold uppercase tracking-wider text-zinc-800">Document type</dt>
                            <dd class="mt-1 font-medium capitalize text-zinc-700">{{ str_replace('_', ' ', $kyc->document_type) }}</dd>
                        </div>
                        <div>
                            <dt class="text-[10px] font-semibold uppercase tracking-wider text-zinc-800">Document number</dt>
                            <dd class="mt-1 font-medium text-zinc-700">{{ $kyc->document_number ?: '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-[10px] font-semibold uppercase tracking-wider text-zinc-800">Submitted</dt>
                            <dd class="mt-1 font-medium text-zinc-700">{{ $fmtDate($kyc->created_at) }}</dd>
                        </div>
                    </dl>

                    {{-- Documents — streamed from the private disk, admin-only. --}}
                    <p class="mt-5 text-[10px] font-semibold uppercase tracking-wider text-zinc-800">Documents</p>
                    <div class="mt-2 grid grid-cols-2 gap-3 sm:grid-cols-3">
                        @foreach (['front' => 'Document front', 'back' => 'Document back', 'selfie' => 'Selfie'] as $docType => $docLabel)
                            @php
                                $docPath = match ($docType) {
                                    'front' => $kyc->document_front_path,
                                    'back' => $kyc->document_back_path,
                                    default => $kyc->selfie_path,
                                };
                            @endphp
                            @if ($docPath)
                                <a href="{{ route('admin.kyc.document', [$kyc, $docType]) }}" target="_blank" rel="noopener" class="group block overflow-hidden rounded-xl border border-zinc-200">
                                    <img src="{{ route('admin.kyc.document', [$kyc, $docType]) }}" alt="{{ $docLabel }}" class="h-32 w-full bg-zinc-50 object-cover transition-transform duration-200 group-hover:scale-105">
                                    <p class="px-3 py-1.5 text-[11px] font-medium text-zinc-600">{{ $docLabel }}</p>
                                </a>
                            @endif
                        @endforeach
                    </div>

                    {{-- Review actions / outcome --}}
                    @if ($kyc->status === 'pending')
                        <div x-data="{ rejecting: @js($errors->has('reason')) }" class="mt-5 border-t border-zinc-100 pt-4">
                            <div class="flex flex-wrap items-center gap-2">
                                <form method="POST" action="{{ route('admin.kyc.approve', $kyc) }}">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-emerald-700">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                                        Approve
                                    </button>
                                </form>
                                <button type="button" @click="rejecting = ! rejecting" class="inline-flex items-center gap-1.5 rounded-xl bg-red-50 px-4 py-2 text-sm font-semibold text-red-700 ring-1 ring-red-200 transition-colors hover:bg-red-100">Reject</button>
                            </div>

                            <form x-show="rejecting" x-cloak method="POST" action="{{ route('admin.kyc.reject', $kyc) }}" class="mt-3">
                                @csrf
                                <textarea name="reason" rows="2" required placeholder="Reason for rejection (the customer will see this)" class="w-full rounded-xl border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 outline-none transition-colors focus:border-red-500 focus:ring-2 focus:ring-red-500/15">{{ old('reason') }}</textarea>
                                @error('reason') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                                <button type="submit" class="mt-2 inline-flex items-center rounded-xl bg-red-600 px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-red-700">Confirm rejection</button>
                            </form>
                        </div>
                    @else
                        <div class="mt-5 border-t border-zinc-100 pt-4 text-xs text-zinc-600">
                            <p><span class="font-semibold capitalize">{{ $kyc->status }}</span>{{ $kyc->reviewed_at ? ' on '.$fmtDate($kyc->reviewed_at) : '' }}{{ $kyc->reviewer ? ' by '.$kyc->reviewer->name : '' }}.</p>
                            @if ($kyc->status === 'rejected' && $kyc->rejection_reason)
                                <p class="mt-1 text-red-600">Reason: {{ $kyc->rejection_reason }}</p>
                            @endif
                        </div>
                    @endif
                </div>
            @else
                <div class="px-5 py-10 text-center text-sm text-zinc-600">This customer has not submitted KYC documents.</div>
            @endif
        </div>

        {{-- Lifetime stats. Both currency-denominated stats are pre-converted to
             USD per row so we never sum across currencies (the original
             "$X USD" total just added NGN + USD + GHS values as if they were
             the same unit — bogus accounting). --}}
        @php
            $rateService = app(\App\Domain\Wallet\Services\CurrencyRateService::class);

            // Wallet headline: convert each wallet's balance into USD using
            // its native currency, then sum.
            $walletTotalUsd = $user->wallets->sum(
                fn ($w) => $rateService->convert((float) $w->balance, $w->currency->value, 'USD')
            );

            // Total spent: prefer the Order helper (which understands the rate
            // snapshot + falls back honestly). Sum its USD output per order
            // across all completed / partially-completed orders.
            $totalSpentUsd = $user->orders()
                ->whereIn('order_status', ['completed', 'partially_completed'])
                ->get()
                ->sum(fn ($o) => $o->usdTotal());
        @endphp
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            @foreach ([
                ['Total orders', number_format($ordersCount)],
                ['Total spent', \App\Domain\Shared\Services\Money::codeAmount((float) $totalSpentUsd, 'USD')],
                ['Wallet balance', \App\Domain\Shared\Services\Money::codeAmount((float) $walletTotalUsd, 'USD')],
                ['Unread alerts', number_format($unreadNotifications)],
            ] as [$label, $value])
                <div class="rounded-[16px] bg-white p-4 shadow-sm shadow-zinc-900/5 ring-1 ring-zinc-100">
                    <p class="text-[10px] font-semibold uppercase tracking-wider text-zinc-800">{{ $label }}</p>
                    <p class="mt-1.5 text-lg font-bold text-zinc-900">{{ $value }}</p>
                </div>
            @endforeach
        </div>

        {{-- Wallets --}}
        <div class="rounded-[10px] bg-white shadow-sm shadow-zinc-900/5 ring-1 ring-zinc-100">
            <div class="border-b border-zinc-100 px-5 py-4">
                <h3 class="text-sm font-bold text-zinc-900">Wallets ({{ $user->wallets->count() }})</h3>
            </div>
            <div class="divide-y divide-zinc-100">
                @forelse ($user->wallets as $wallet)
                    <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4">
                        <div>
                            <p class="text-sm font-bold text-zinc-900">@money((float) $wallet->balance, $wallet->currency) {{ $wallet->currency->value }}</p>
                            <p class="mt-0.5 text-[11px] text-zinc-800">
                                Available @money((float) $wallet->availableBalance(), $wallet->currency)
                                · Locked @money((float) $wallet->locked_balance, $wallet->currency)
                            </p>
                        </div>
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-semibold ring-1 {{ $wallet->is_active ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : 'bg-zinc-100 text-zinc-700 ring-zinc-200' }}">
                            {{ $wallet->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                @empty
                    <div class="px-5 py-10 text-center text-sm text-zinc-600">This customer has no wallet yet.</div>
                @endforelse
            </div>
        </div>

        {{-- Recent orders --}}
        <div class="rounded-[10px] bg-white shadow-sm shadow-zinc-900/5 ring-1 ring-zinc-100">
            <div class="border-b border-zinc-100 px-5 py-4">
                <h3 class="text-sm font-bold text-zinc-900">Recent orders</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-[11px]">
                    <thead class="bg-zinc-50 text-[10px] uppercase tracking-wider text-zinc-800">
                        <tr>
                            <th class="px-5 py-3 font-semibold">Order</th>
                            <th class="px-5 py-3 font-semibold">Status</th>
                            <th class="px-5 py-3 font-semibold">Total</th>
                            <th class="px-5 py-3 font-semibold">Placed</th>
                            <th class="px-5 py-3 text-right font-semibold">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100">
                        @forelse ($user->orders as $order)
                            <tr>
                                <td class="px-5 py-3 font-mono font-semibold text-zinc-900">#{{ $order->order_number }}</td>
                                <td class="px-5 py-3">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-semibold ring-1 {{ $toneFor($order->order_status?->value) }}">
                                        {{ $order->order_status?->label() ?? 'Pending' }}
                                    </span>
                                </td>
                                <td class="px-5 py-3 font-semibold text-zinc-900">
                                    @moneyCode($order->usdTotal(), 'USD')
                                    @if (! $order->hasSuspectPricing() && strtoupper((string) $order->display_currency) !== 'USD')
                                        <p class="text-[10px] font-normal text-zinc-500">@moneyCode((float) $order->total_amount, $order->display_currency)</p>
                                    @endif
                                </td>
                                <td class="px-5 py-3 text-zinc-600">{{ $fmtDate($order->placed_at ?? $order->created_at) }}</td>
                                <td class="px-5 py-3 text-right">
                                    <a href="{{ route('admin.order', $order) }}" class="inline-flex items-center rounded-[10px] border border-zinc-200 bg-white px-3 py-1 text-xs font-medium text-zinc-600 hover:bg-zinc-50">View</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-10 text-center text-sm text-zinc-600">No orders placed yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Recent wallet activity --}}
        <div class="rounded-[10px] bg-white shadow-sm shadow-zinc-900/5 ring-1 ring-zinc-100">
            <div class="border-b border-zinc-100 px-5 py-4">
                <h3 class="text-sm font-bold text-zinc-900">Recent wallet activity</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-[11px]">
                    <thead class="bg-zinc-50 text-[10px] uppercase tracking-wider text-zinc-800">
                        <tr>
                            <th class="px-5 py-3 font-semibold">Type</th>
                            <th class="px-5 py-3 font-semibold">Amount</th>
                            <th class="px-5 py-3 font-semibold">Description</th>
                            <th class="px-5 py-3 font-semibold">Balance after</th>
                            <th class="px-5 py-3 font-semibold">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100">
                        @forelse ($user->walletTransactions as $tx)
                            @php $isCredit = $tx->type === \App\Domain\Shared\Enums\WalletTransactionType::Credit; @endphp
                            <tr>
                                <td class="px-5 py-3">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-semibold ring-1 {{ $isCredit ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : 'bg-zinc-100 text-zinc-700 ring-zinc-200' }}">
                                        {{ $tx->type->label() }}
                                    </span>
                                </td>
                                <td class="px-5 py-3 font-semibold {{ $isCredit ? 'text-emerald-700' : 'text-zinc-900' }}">
                                    {{ $isCredit ? '+' : '-' }}{{ $tx->currency->value }} {{ number_format((float) $tx->amount, 2) }}
                                </td>
                                <td class="px-5 py-3 text-zinc-600">{{ $tx->description ?: '-' }}</td>
                                <td class="px-5 py-3 text-zinc-600">{{ $tx->currency->value }} {{ number_format((float) $tx->balance_after, 2) }}</td>
                                <td class="px-5 py-3 text-zinc-600">{{ $fmtDate($tx->created_at) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-10 text-center text-sm text-zinc-600">No wallet activity yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</x-layouts.admin>
