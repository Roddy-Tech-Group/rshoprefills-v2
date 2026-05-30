{{-- FILE: resources/views/livewire/admin/newsletter.blade.php --}}
<?php

use App\Models\NewsletterSubscriber;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new
#[Layout('components.layouts.admin')]
#[Title('Newsletter')]
class extends Component {
    use WithPagination;

    #[Url(except: '')]
    public string $search = '';

    #[Url(as: 'status', except: 'all')]
    public string $statusFilter = 'all';

    #[Computed]
    public function counts(): array
    {
        return [
            'total'        => NewsletterSubscriber::count(),
            'active'       => NewsletterSubscriber::where('status', 'active')->count(),
            'unsubscribed' => NewsletterSubscriber::where('status', 'unsubscribed')->count(),
        ];
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function unsubscribe(int $id): void
    {
        NewsletterSubscriber::findOrFail($id)->update([
            'status'            => 'unsubscribed',
            'unsubscribed_at'   => now(),
        ]);
        session()->flash('status', 'Subscriber removed from list.');
        unset($this->counts);
    }

    public function resubscribe(int $id): void
    {
        NewsletterSubscriber::findOrFail($id)->update([
            'status'            => 'active',
            'unsubscribed_at'   => null,
        ]);
        session()->flash('status', 'Subscriber re-activated.');
        unset($this->counts);
    }

    public function with(): array
    {
        $query = NewsletterSubscriber::query()->latest('subscribed_at');

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        if ($this->search !== '') {
            $query->where('email', 'like', '%' . trim($this->search) . '%');
        }

        return [
            'subscribers' => $query->paginate(25),
        ];
    }
}; ?>

<div>
    <x-slot:heading>Newsletter</x-slot:heading>
    <x-slot:subheading>Manage email subscribers who opted in at checkout or via the sign-up form.</x-slot:subheading>

    <div class="flex flex-col gap-6">

        @if (session('status'))
            <div class="rounded-[10px] bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 ring-1 ring-emerald-200 dark:bg-emerald-500/15 dark:text-emerald-300 dark:ring-emerald-500/30">{{ session('status') }}</div>
        @endif

        {{-- KPI strip --}}
        <div class="grid grid-cols-3 gap-3">
            @foreach ([
                ['label' => 'Total',        'value' => $this->counts['total'],        'dot' => 'bg-blue-500'],
                ['label' => 'Active',        'value' => $this->counts['active'],       'dot' => 'bg-emerald-500'],
                ['label' => 'Unsubscribed', 'value' => $this->counts['unsubscribed'], 'dot' => 'bg-zinc-400'],
            ] as $stat)
                <div class="rounded-[10px] bg-white p-4 shadow-sm shadow-zinc-900/[0.04] ring-1 ring-zinc-100 dark:bg-[#1d3252] dark:ring-zinc-700/60">
                    <p class="flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-300">
                        <span class="inline-block h-1.5 w-1.5 rounded-full {{ $stat['dot'] }}"></span>
                        {{ $stat['label'] }}
                    </p>
                    <p class="mt-2 text-3xl font-bold tracking-tight text-zinc-900 dark:text-white">{{ number_format($stat['value']) }}</p>
                </div>
            @endforeach
        </div>

        {{-- Search + filter row --}}
        <div class="flex flex-col items-stretch gap-3 sm:flex-row sm:items-center">
            <div class="relative flex-1">
                <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-zinc-500 dark:text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input
                    wire:model.live.debounce.250ms="search"
                    type="search"
                    placeholder="Search by email address..."
                    class="w-full rounded-[10px] border border-zinc-200 bg-white py-2.5 pl-10 pr-3 text-sm text-zinc-900 outline-none transition-colors focus:border-blue-500 focus:ring-2 focus:ring-blue-500/15 dark:border-zinc-700/60 dark:bg-[#1d3252] dark:text-white"
                />
            </div>

            <div class="flex flex-wrap items-center gap-1.5">
                @foreach (['all' => 'All', 'active' => 'Active', 'unsubscribed' => 'Unsubscribed'] as $value => $label)
                    <button
                        type="button"
                        wire:click="$set('statusFilter', '{{ $value }}')"
                        @class([
                            'rounded-[10px] px-3 py-1.5 text-xs font-semibold transition-colors',
                            'bg-blue-600 text-white' => $statusFilter === $value,
                            'bg-white text-zinc-700 ring-1 ring-zinc-200 hover:bg-zinc-50 dark:bg-[#1d3252] dark:text-zinc-300 dark:ring-zinc-700/60 dark:hover:bg-[#26416b]' => $statusFilter !== $value,
                        ])
                    >{{ $label }}</button>
                @endforeach
            </div>
        </div>

        {{-- Table --}}
        <div class="overflow-hidden rounded-[10px] border-[1.5px] border-white bg-white shadow-sm shadow-zinc-900/[0.04] dark:border-white dark:bg-[#1d3252]">
            <div class="overflow-x-auto p-3">
                <table class="admin-table w-full text-left text-sm">
                    <thead class="bg-zinc-50 text-[11px] uppercase tracking-wider text-zinc-600 dark:bg-[#0c1a36] dark:text-zinc-400">
                        <tr>
                            <th class="px-5 py-3 font-semibold">Email</th>
                            <th class="hidden px-5 py-3 font-semibold sm:table-cell">Source</th>
                            <th class="px-5 py-3 font-semibold">Status</th>
                            <th class="hidden px-5 py-3 font-semibold md:table-cell">Subscribed</th>
                            <th class="px-5 py-3 text-right font-semibold">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-inset">
                        @forelse ($subscribers as $sub)
                            <tr class="transition-colors hover:bg-zinc-50 dark:hover:bg-[#26416b]/40">
                                <td class="px-5 py-3 font-medium text-zinc-900 dark:text-white">{{ $sub->email }}</td>
                                <td class="hidden px-5 py-3 sm:table-cell">
                                    @if ($sub->source)
                                        <x-admin.badge tone="blue">{{ $sub->source }}</x-admin.badge>
                                    @else
                                        <span class="text-xs text-zinc-400 dark:text-zinc-600">-</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3">
                                    <x-admin.badge :tone="$sub->status === 'active' ? 'emerald' : 'zinc'">
                                        {{ $sub->status }}
                                    </x-admin.badge>
                                </td>
                                <td class="hidden whitespace-nowrap px-5 py-3 text-xs text-zinc-500 dark:text-zinc-400 md:table-cell">
                                    {{ $sub->subscribed_at->format('M j, Y') }}
                                </td>
                                <td class="whitespace-nowrap px-5 py-3 text-right">
                                    @if ($sub->status === 'active')
                                        <button
                                            wire:click="unsubscribe({{ $sub->id }})"
                                            wire:confirm="Remove this subscriber from the list?"
                                            type="button"
                                            class="rounded-[10px] bg-red-50 px-2.5 py-1 text-[11px] font-semibold text-red-700 transition-colors hover:bg-red-100 dark:bg-red-500/15 dark:text-red-300 dark:hover:bg-red-500/25"
                                        >Unsubscribe</button>
                                    @else
                                        <button
                                            wire:click="resubscribe({{ $sub->id }})"
                                            type="button"
                                            class="rounded-[10px] bg-emerald-50 px-2.5 py-1 text-[11px] font-semibold text-emerald-700 transition-colors hover:bg-emerald-100 dark:bg-emerald-500/15 dark:text-emerald-300 dark:hover:bg-emerald-500/25"
                                        >Re-subscribe</button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-16 text-center">
                                    <p class="text-base font-semibold text-zinc-900 dark:text-white">No subscribers match those filters</p>
                                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Try adjusting the search or status filter.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($subscribers->hasPages())
                <div class="border-t border-zinc-100 px-5 py-3 dark:border-zinc-700/60">
                    {{ $subscribers->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
