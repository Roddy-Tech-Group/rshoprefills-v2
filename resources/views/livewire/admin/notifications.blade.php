<?php

use App\Domain\Notification\Enums\DeliveryStatus;
use App\Domain\Notification\Jobs\SendAsynchronousNotificationJob;
use App\Domain\Notification\Services\NotificationAuditService;
use App\Models\Notification;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new
#[Layout('components.layouts.admin')]
#[Title('Notifications')]
class extends Component {
    use WithPagination;

    /** Status filter: all | pending | sent | failed. */
    public string $filter = 'all';

    /** System-wide delivery metrics (NotificationAuditService). */
    #[Computed]
    public function metrics(): array
    {
        return app(NotificationAuditService::class)->getSystemMetrics();
    }

    public function setFilter(string $filter): void
    {
        $this->filter = $filter;
        $this->resetPage();
    }

    /**
     * Re-queue a failed/pending notification for delivery. Mirrors
     * NotificationAdminApiController::retry so both entry points behave the same.
     */
    public function retry(string $id): void
    {
        $notification = Notification::with('user')->find($id);

        if (! $notification || $notification->status === DeliveryStatus::Sent) {
            return;
        }

        $notification->update([
            'status' => DeliveryStatus::Pending,
            'failed_at' => null,
        ]);

        // Rebuild the original mailable when `type` holds a mailable class name.
        $mailable = null;
        if ($notification->type && class_exists($notification->type)) {
            $mailable = new $notification->type($notification->user);
        }

        SendAsynchronousNotificationJob::dispatch(
            user: $notification->user,
            title: $notification->title,
            message: $notification->message,
            mailable: $mailable,
            priority: $notification->priority,
            metadata: $notification->metadata ?? [],
            channels: [$notification->channel],
        );

        $this->dispatch('notification-retried');
    }

    public function with(): array
    {
        $query = Notification::with('user')->latest();

        if (in_array($this->filter, ['pending', 'sent', 'failed'], true)) {
            $query->where('status', $this->filter);
        }

        return [
            'notifications' => $query->paginate(20),
        ];
    }
}; ?>

@php
    // Status badge tone.
    $statusTone = [
        'sent'    => 'bg-emerald-500',
        'failed'  => 'bg-red-500',
        'pending' => 'bg-amber-500',
    ];
    $priorityTone = [
        'critical' => 'bg-red-500',
        'high'     => 'bg-amber-500',
        'normal'   => 'bg-zinc-400',
    ];
@endphp

<div>
    <x-slot:heading>Notifications</x-slot:heading>
    <x-slot:subheading>Delivery monitoring and audit log for every customer notification.</x-slot:subheading>

    <div class="flex flex-col gap-6">

        {{-- Delivery metrics --}}
        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            @php
                $m = $this->metrics;
                $cards = [
                    ['Delivery attempts', number_format($m['total_delivery_attempts']), 'bg-blue-100', 'text-blue-700'],
                    ['Delivered',         number_format($m['successful_deliveries']),  'bg-emerald-100', 'text-emerald-700'],
                    ['Failed',            number_format($m['failed_deliveries']),      'bg-red-100', 'text-red-700'],
                    ['Success rate',      $m['success_rate'].'%',                      'bg-zinc-100', 'text-zinc-700'],
                ];
            @endphp
            @foreach ($cards as [$label, $value, $bg, $fg])
                <div class="rounded-[20px] bg-white p-5 shadow-sm shadow-zinc-900/5 ring-1 ring-zinc-100">
                    <span class="inline-flex items-center rounded-[5px] {{ $bg }} {{ $fg }} px-2 py-0.5 text-[11px] font-bold uppercase tracking-wide">{{ $label }}</span>
                    <p class="mt-3 text-2xl font-extrabold tabular-nums text-zinc-900">{{ $value }}</p>
                </div>
            @endforeach
        </div>

        {{-- Filter pills --}}
        <div class="inline-flex w-max items-center rounded-[10px] bg-zinc-100 p-1" role="tablist" aria-label="Filter notifications">
            @foreach (['all' => 'All', 'pending' => 'Pending', 'sent' => 'Sent', 'failed' => 'Failed'] as $value => $label)
                <button
                    type="button"
                    wire:click="setFilter('{{ $value }}')"
                    @class([
                        'inline-flex items-center justify-center rounded-[10px] px-3.5 py-1.5 text-xs font-semibold transition-all',
                        'bg-white text-zinc-900 shadow-sm ring-1 ring-zinc-200' => $filter === $value,
                        'text-zinc-600 hover:text-zinc-900' => $filter !== $value,
                    ])
                >
                    {{ $label }}
                </button>
            @endforeach
        </div>

        {{-- Notifications table --}}
        <div class="overflow-hidden rounded-[20px] bg-white shadow-sm shadow-zinc-900/5 ring-1 ring-zinc-100">
            <div class="overflow-x-auto [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                <table class="w-full min-w-[720px] text-left text-sm">
                    <thead>
                        <tr class="border-b border-zinc-100 text-[11px] font-bold uppercase tracking-wide text-zinc-500">
                            <th class="px-5 py-3">Recipient</th>
                            <th class="px-5 py-3">Notification</th>
                            <th class="px-5 py-3">Channel</th>
                            <th class="px-5 py-3">Priority</th>
                            <th class="px-5 py-3">Status</th>
                            <th class="px-5 py-3">Created</th>
                            <th class="px-5 py-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100">
                        @forelse ($notifications as $n)
                            <tr class="transition-colors hover:bg-zinc-50">
                                <td class="px-5 py-3.5">
                                    <p class="font-semibold text-zinc-900">{{ $n->user?->name ?? 'Unknown' }}</p>
                                    <p class="text-xs text-zinc-500">{{ $n->user?->email }}</p>
                                </td>
                                <td class="px-5 py-3.5">
                                    <p class="font-medium text-zinc-900">{{ $n->title }}</p>
                                    <p class="max-w-[280px] truncate text-xs text-zinc-500">{{ $n->message }}</p>
                                </td>
                                <td class="px-5 py-3.5 capitalize text-zinc-700">{{ $n->channel->value }}</td>
                                <td class="px-5 py-3.5">
                                    <span class="inline-flex items-center rounded-[5px] {{ $priorityTone[$n->priority->value] ?? 'bg-zinc-400' }} px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-white">{{ $n->priority->value }}</span>
                                </td>
                                <td class="px-5 py-3.5">
                                    <span class="inline-flex items-center rounded-[5px] {{ $statusTone[$n->status->value] ?? 'bg-zinc-400' }} px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-white">{{ $n->status->value }}</span>
                                </td>
                                <td class="px-5 py-3.5 text-zinc-600">{{ $n->created_at->format('M j, Y g:i A') }}</td>
                                <td class="px-5 py-3.5 text-right">
                                    @if ($n->status !== \App\Domain\Notification\Enums\DeliveryStatus::Sent)
                                        <button
                                            type="button"
                                            wire:click="retry('{{ $n->id }}')"
                                            wire:loading.attr="disabled"
                                            wire:target="retry('{{ $n->id }}')"
                                            class="inline-flex items-center gap-1.5 rounded-[10px] bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white transition-colors hover:bg-blue-700 disabled:opacity-50"
                                        >
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"/>
                                            </svg>
                                            Retry
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-5 py-16 text-center">
                                    <p class="text-sm font-semibold text-zinc-900">No notifications</p>
                                    <p class="mt-1 text-xs text-zinc-500">Nothing matches this filter yet.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($notifications->hasPages())
                <div class="border-t border-zinc-100 px-5 py-3">
                    {{ $notifications->links() }}
                </div>
            @endif
        </div>

        <x-action-message on="notification-retried" class="text-sm font-medium text-emerald-600">
            Notification re-queued for delivery.
        </x-action-message>
    </div>
</div>
