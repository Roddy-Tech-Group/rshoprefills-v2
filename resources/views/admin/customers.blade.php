@php
    use App\Models\User;

    $customers = User::with('wallet')->latest()->limit(50)->get();
    $totalCustomers = User::count();

    // Status pill tone — drives the right-side chip per row. Active means the
    // email is verified AND the account isn't banned/suspended. Tone names
    // map onto <x-admin.badge>'s canonical palette.
    $statusFor = function (User $user): array {
        if ($user->banned_at !== null) {
            return ['label' => 'Banned', 'tone' => 'red'];
        }
        if ($user->suspended_at !== null) {
            return ['label' => 'Suspended', 'tone' => 'amber'];
        }
        if ($user->email_verified_at === null) {
            return ['label' => 'Pending', 'tone' => 'amber'];
        }

        return ['label' => 'Active', 'tone' => 'emerald'];
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
            min-width: 900px;
        }
        .cust-body:not(:last-of-type)::after {
            content: '';
            position: absolute;
            left: 1.5rem;
            right: 1.5rem;
            bottom: 0;
            height: 1px;
            background-color: rgb(244 244 245);
            pointer-events: none;
        }
        html.dark .cust-body:not(:last-of-type)::after {
            background-color: rgb(255 255 255 / 0.08);
        }
        .cust-body:hover::after { display: none; }
        .cust-body:hover { border-radius: 10px; }
    </style>

    <div class="overflow-hidden rounded-[10px] border-[1.5px] border-white bg-white shadow-sm shadow-zinc-900/[0.04] dark:border-white dark:bg-[#1d3252]">
        <div class="overflow-x-auto [scrollbar-width:thin] [&::-webkit-scrollbar]:h-1.5 [&::-webkit-scrollbar-thumb]:rounded-full [&::-webkit-scrollbar-thumb]:bg-zinc-300 dark:[&::-webkit-scrollbar-thumb]:bg-zinc-600">

        {{-- Header pill --}}
        <div class="cust-row grid mx-3 my-3 rounded-[10px] bg-blue-50 px-6 py-3 text-[10px] font-bold uppercase tracking-wider text-blue-700 ring-2 ring-blue-500 dark:bg-blue-600/15 dark:text-blue-300 dark:ring-blue-400">
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
                class="cust-row cust-body group relative mx-3 cursor-pointer bg-white px-6 py-3 transition-all hover:bg-blue-50 hover:ring-1 hover:ring-inset hover:ring-blue-500 dark:bg-[#1d3252] dark:hover:bg-blue-600/10 dark:hover:ring-blue-400"
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
                    <x-admin.badge :tone="$status['tone']">{{ $status['label'] }}</x-admin.badge>
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
            <div class="px-5 py-12 text-center text-sm text-zinc-600 dark:text-zinc-400">
                No customers yet.
            </div>
        @endforelse

        </div>
    </div>
</x-layouts.admin>
