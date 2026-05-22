@php
    use App\Models\User;

    $customers = User::with('wallet')->latest()->limit(50)->get();
    $totalCustomers = User::count();
@endphp

<x-layouts.admin>
    <x-slot:heading>Customers</x-slot:heading>
    <x-slot:subheading>{{ number_format($totalCustomers) }} users total. Showing the latest 50.</x-slot:subheading>

    <div class="flex flex-1 flex-col gap-6">

        <div class="overflow-hidden rounded-[20px] bg-white shadow-sm shadow-zinc-900/5 ring-1 ring-zinc-100">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-[11px]">
                    <thead class="bg-zinc-50 text-[10px] uppercase tracking-wider text-zinc-600">
                        <tr>
                            <th class="px-5 py-3 font-semibold">User</th>
                            <th class="px-5 py-3 font-semibold">Status</th>
                            <th class="px-5 py-3 font-semibold">Wallet Balance</th>
                            <th class="px-5 py-3 font-semibold">Joined</th>
                            <th class="px-5 py-3 text-right font-semibold">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100">
                        @forelse ($customers as $user)
                            <tr>
                                <td class="px-5 py-3">
                                    <div class="flex items-center gap-3">
                                        @php
                                            $rowAvatar = $user->avatar_url ?: asset('assets/' . rawurlencode(match (strtolower($user->gender ?? '')) {
                                                'female', 'f' => 'New Female Account Avatar.png',
                                                default       => 'New male account avatar.png',
                                            }));
                                        @endphp
                                        <img src="{{ $rowAvatar }}" alt="" class="h-9 w-9 shrink-0 rounded-full object-cover ring-1 ring-blue-100">
                                        <div class="leading-tight">
                                            <p class="text-[11px] font-semibold text-zinc-900">{{ $user->name }}</p>
                                            <p class="text-[10px] text-zinc-600">{{ $user->email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-3">
                                    @if ($user->email_verified_at)
                                        <span class="inline-flex items-center rounded-[5px] bg-emerald-400 px-2.5 py-0.5 text-xs font-semibold text-white">Active</span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-semibold text-amber-700">Pending</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3 font-semibold text-zinc-900">
                                    @if ($user->wallet)
                                        ${{ number_format((float) $user->wallet->balance, 2) }} {{ $user->wallet->currency }}
                                    @else
                                        <span class="text-zinc-600">No wallet</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3 text-zinc-600">{{ $user->created_at->format('M j, Y') }}</td>
                                <td class="px-5 py-3 text-right">
                                    <a href="{{ route('admin.customer', $user) }}" class="inline-flex items-center rounded-lg border border-zinc-200 bg-white px-3 py-1 text-xs font-medium text-zinc-600 hover:bg-zinc-50">View</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-12 text-center text-sm text-zinc-600">No customers yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</x-layouts.admin>
