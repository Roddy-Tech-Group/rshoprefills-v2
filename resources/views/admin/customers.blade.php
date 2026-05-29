@php
    use App\Models\User;

    $customers = User::with('wallet')->latest()->limit(50)->get();
    $totalCustomers = User::count();

    // Status pill tone — drives the right-side chip per row. Active means the
    // email is verified AND the account isn't banned/suspended.
    $statusFor = function (User $user): array {
        if ($user->banned_at !== null) {
            return ['label' => 'Banned', 'class' => 'bg-red-50 text-red-700 ring-red-200 dark:bg-red-500/15 dark:text-red-300 dark:ring-red-500/30'];
        }
        if ($user->suspended_at !== null) {
            return ['label' => 'Suspended', 'class' => 'bg-amber-50 text-amber-700 ring-amber-200 dark:bg-amber-500/15 dark:text-amber-300 dark:ring-amber-500/30'];
        }
        if ($user->email_verified_at === null) {
            return ['label' => 'Pending', 'class' => 'bg-amber-50 text-amber-700 ring-amber-200 dark:bg-amber-500/15 dark:text-amber-300 dark:ring-amber-500/30'];
        }

        return ['label' => 'Active', 'class' => 'bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-500/15 dark:text-emerald-300 dark:ring-emerald-500/30'];
    };
@endphp

<x-layouts.admin>
    <x-slot:heading>Customers</x-slot:heading>
    <x-slot:subheading>{{ number_format($totalCustomers) }} users total. Showing the latest 50.</x-slot:subheading>

    {{-- Shared grid template between header pill + each customer row so columns
         line up exactly. Same pattern as the Transactions / Products pages. --}}
    <style>
        .cust-row {
            display: grid;
            grid-template-columns:
                minmax(220px, 1.8fr)   /* Avatar + name + email */
                minmax(110px, 0.7fr)   /* Status pill */
                minmax(160px, 1.1fr)   /* Wallet balance */
                minmax(120px, 0.9fr)   /* Joined date */
                minmax(90px,  0.6fr);  /* View action */
            gap: 1.25rem;
            align-items: center;
        }
        @media (max-width: 1024px) {
            .cust-row { grid-template-columns: minmax(200px, 1.7fr) minmax(100px, 0.8fr) minmax(80px, 0.6fr); }
            .cust-row > *:not(.col-user):not(.col-status):not(.col-action) { display: none; }
        }
    </style>

    <div class="flex flex-col gap-2">
        {{-- Header pill — light-blue bg, 2px blue ring, matches the Products
             filter bar styling. --}}
        <div class="cust-row hidden rounded-[10px] bg-blue-50 px-6 py-3 text-[10px] font-bold uppercase tracking-wider text-blue-700 shadow-sm shadow-zinc-900/5 ring-2 ring-blue-500 dark:bg-blue-600/15 dark:text-blue-300 dark:ring-blue-400 md:grid">
            <span class="col-user">User</span>
            <span class="col-status">Status</span>
            <span>Wallet Balance</span>
            <span>Joined</span>
            <span class="col-action text-right">Actions</span>
        </div>

        @forelse ($customers as $user)
            @php
                $status = $statusFor($user);
                $rowAvatar = $user->avatar_url ?: asset('assets/' . rawurlencode(match (strtolower($user->gender ?? '')) {
                    'female', 'f' => 'New Female Account Avatar.png',
                    default       => 'New male account avatar.png',
                }));
            @endphp
            <a
                href="{{ route('admin.customer', $user) }}"
                wire:navigate
                class="cust-row group cursor-pointer rounded-[10px] border border-zinc-100 bg-white px-6 py-3 shadow-sm shadow-zinc-900/5 transition-colors hover:border-blue-600 hover:bg-blue-50 dark:border-zinc-700/60 dark:bg-[#1d3252] dark:hover:border-blue-400 dark:hover:bg-blue-600/15"
            >
                {{-- User — avatar + name + email stacked. --}}
                <div class="col-user flex min-w-0 items-center gap-3">
                    <img src="{{ $rowAvatar }}" alt="" class="h-9 w-9 shrink-0 rounded-[10px] object-cover ring-1 ring-blue-100 dark:ring-blue-500/30" loading="lazy">
                    <div class="min-w-0 leading-tight">
                        <p class="truncate text-[13px] font-semibold text-zinc-900 dark:text-white">{{ $user->name }}</p>
                        <p class="truncate text-[11px] text-zinc-500 dark:text-zinc-400">{{ $user->email }}</p>
                    </div>
                </div>

                {{-- Status pill --}}
                <span class="col-status">
                    <span class="inline-flex w-fit items-center whitespace-nowrap rounded-[5px] px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide ring-1 {{ $status['class'] }}">
                        {{ $status['label'] }}
                    </span>
                </span>

                {{-- Wallet balance --}}
                <span class="truncate text-[13px] font-bold tabular-nums text-zinc-900 dark:text-white">
                    @if ($user->wallet)
                        {{ \App\Models\Product::currencySymbol($user->wallet->currency->value) }}{{ number_format((float) $user->wallet->balance, 2) }} {{ $user->wallet->currency->value }}
                    @else
                        <span class="font-medium text-zinc-500 dark:text-zinc-500">No wallet</span>
                    @endif
                </span>

                {{-- Joined --}}
                <span class="truncate text-[12px] text-zinc-600 dark:text-zinc-400">{{ $user->created_at->format('M j, Y') }}</span>

                {{-- View action — kept as a visual chip on the right; the
                     entire row is the link, so this is just an affordance. --}}
                <span class="col-action text-right">
                    <span class="inline-flex w-fit items-center rounded-[10px] border border-zinc-200 bg-white px-3 py-1 text-[11px] font-medium text-zinc-600 transition-colors group-hover:border-blue-600 group-hover:bg-white group-hover:text-blue-700 dark:border-zinc-700/60 dark:bg-[#26416b] dark:text-zinc-200 dark:group-hover:border-blue-400 dark:group-hover:text-blue-300">
                        View
                    </span>
                </span>
            </a>
        @empty
            <div class="rounded-[10px] bg-white px-5 py-12 text-center text-sm text-zinc-600 shadow-sm ring-1 ring-zinc-100 dark:bg-[#1d3252] dark:text-zinc-400 dark:ring-zinc-700/60">
                No customers yet.
            </div>
        @endforelse
    </div>
</x-layouts.admin>
