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
        <div class="rounded-[20px] bg-white p-5 shadow-sm shadow-zinc-900/5 ring-1 ring-zinc-100">
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

        {{-- Admin actions: edit, ban, hold funds --}}
        @php
            $isBanned = $user->banned_at !== null;
            $fundsHeld = $user->wallets->isNotEmpty() && $user->wallets->where('is_active', true)->isEmpty();
        @endphp
        <div x-data="{ editing: @js($errors->hasAny(['name', 'email', 'phone', 'gender'])) }" class="rounded-[20px] bg-white shadow-sm shadow-zinc-900/5 ring-1 ring-zinc-100">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-zinc-100 px-5 py-4">
                <h3 class="text-sm font-bold text-zinc-900">Actions</h3>
                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" @click="editing = ! editing" class="inline-flex items-center gap-1.5 rounded-xl bg-zinc-100 px-3.5 py-2 text-xs font-semibold text-zinc-700 transition-colors hover:bg-zinc-200">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/></svg>
                        Edit
                    </button>

                    <form method="POST" action="{{ route('admin.customer.funds', $user) }}" onsubmit="return confirm('{{ $fundsHeld ? 'Release funds for this customer?' : 'Place funds on hold for this customer?' }}')">
                        @csrf
                        <button type="submit" class="inline-flex items-center gap-1.5 rounded-xl px-3.5 py-2 text-xs font-semibold transition-colors {{ $fundsHeld ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200 hover:bg-emerald-100' : 'bg-amber-50 text-amber-700 ring-1 ring-amber-200 hover:bg-amber-100' }}">
                            {{ $fundsHeld ? 'Release funds' : 'Hold funds' }}
                        </button>
                    </form>

                    <form method="POST" action="{{ route('admin.customer.ban', $user) }}" onsubmit="return confirm('{{ $isBanned ? 'Unban this customer?' : 'Ban this customer? They will be signed out and blocked from signing in.' }}')">
                        @csrf
                        <button type="submit" class="inline-flex items-center gap-1.5 rounded-xl px-3.5 py-2 text-xs font-semibold text-white transition-colors {{ $isBanned ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-red-600 hover:bg-red-700' }}">
                            {{ $isBanned ? 'Unban' : 'Ban' }}
                        </button>
                    </form>
                </div>
            </div>

            @if ($isBanned || $fundsHeld)
                <div class="flex flex-wrap gap-2 px-5 pt-4">
                    @if ($isBanned)
                        <span class="inline-flex items-center rounded-lg bg-red-50 px-3 py-1.5 text-xs font-medium text-red-700 ring-1 ring-red-200">Suspended{{ $user->banned_at ? ' on '.$fmtDate($user->banned_at) : '' }}</span>
                    @endif
                    @if ($fundsHeld)
                        <span class="inline-flex items-center rounded-lg bg-amber-50 px-3 py-1.5 text-xs font-medium text-amber-700 ring-1 ring-amber-200">Funds on hold</span>
                    @endif
                </div>
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
                        <select name="gender" class="mt-1.5 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 outline-none transition-colors focus:border-blue-500 focus:ring-2 focus:ring-blue-500/15">
                            <option value="">Not set</option>
                            @foreach (['male', 'female', 'other'] as $g)
                                <option value="{{ $g }}" @selected(old('gender', $user->gender) === $g)>{{ ucfirst($g) }}</option>
                            @endforeach
                        </select>
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
        <div class="rounded-[20px] bg-white shadow-sm shadow-zinc-900/5 ring-1 ring-zinc-100">
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

        {{-- Lifetime stats --}}
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            @php
                // Headline balance: every wallet summed and converted to USD.
                $rateService = app(\App\Domain\Wallet\Services\CurrencyRateService::class);
                $walletTotalUsd = $user->wallets->sum(fn ($w) => $rateService->convert((float) $w->balance, $w->currency->value, 'USD'));
                $walletLabel = '$' . number_format($walletTotalUsd, 2) . ' USD';
            @endphp
            @foreach ([
                ['Total orders', number_format($ordersCount)],
                ['Total spent', '$' . number_format($totalSpent, 2)],
                ['Wallet balance', $walletLabel],
                ['Unread alerts', number_format($unreadNotifications)],
            ] as [$label, $value])
                <div class="rounded-[16px] bg-white p-4 shadow-sm shadow-zinc-900/5 ring-1 ring-zinc-100">
                    <p class="text-[10px] font-semibold uppercase tracking-wider text-zinc-800">{{ $label }}</p>
                    <p class="mt-1.5 text-lg font-bold text-zinc-900">{{ $value }}</p>
                </div>
            @endforeach
        </div>

        {{-- Wallets --}}
        <div class="rounded-[20px] bg-white shadow-sm shadow-zinc-900/5 ring-1 ring-zinc-100">
            <div class="border-b border-zinc-100 px-5 py-4">
                <h3 class="text-sm font-bold text-zinc-900">Wallets ({{ $user->wallets->count() }})</h3>
            </div>
            <div class="divide-y divide-zinc-100">
                @forelse ($user->wallets as $wallet)
                    <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4">
                        <div>
                            <p class="text-sm font-bold text-zinc-900">{{ \App\Models\Product::currencySymbol($wallet->currency->value) }}{{ number_format((float) $wallet->balance, 2) }} {{ $wallet->currency->value }}</p>
                            <p class="mt-0.5 text-[11px] text-zinc-800">
                                Available {{ \App\Models\Product::currencySymbol($wallet->currency->value) }}{{ number_format($wallet->availableBalance(), 2) }}
                                · Locked {{ \App\Models\Product::currencySymbol($wallet->currency->value) }}{{ number_format((float) $wallet->locked_balance, 2) }}
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
        <div class="rounded-[20px] bg-white shadow-sm shadow-zinc-900/5 ring-1 ring-zinc-100">
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
                                <td class="px-5 py-3 font-semibold text-zinc-900">{{ $order->display_currency }} {{ number_format((float) $order->total_amount, 2) }}</td>
                                <td class="px-5 py-3 text-zinc-600">{{ $fmtDate($order->placed_at ?? $order->created_at) }}</td>
                                <td class="px-5 py-3 text-right">
                                    <a href="{{ route('admin.order', $order) }}" class="inline-flex items-center rounded-lg border border-zinc-200 bg-white px-3 py-1 text-xs font-medium text-zinc-600 hover:bg-zinc-50">View</a>
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
        <div class="rounded-[20px] bg-white shadow-sm shadow-zinc-900/5 ring-1 ring-zinc-100">
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
