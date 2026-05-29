<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new
#[Layout('components.layouts.dashboard')]
#[Title('Notifications')]
class extends Component {
    use WithPagination;

    /** Mark a single notification as read. */
    public function markRead(string $id): void
    {
        $notification = Auth::user()->notifications()->whereKey($id)->first();

        if ($notification && ! $notification->read_at) {
            $notification->markAsRead();
        }
    }

    /** Mark every unread notification as read. */
    public function markAllRead(): void
    {
        Auth::user()->notifications()->whereNull('read_at')->update(['read_at' => now()]);
    }

    public function with(): array
    {
        return [
            'notifications' => Auth::user()->notifications()->latest()->paginate(15),
            'unreadCount' => Auth::user()->notifications()->whereNull('read_at')->count(),
        ];
    }
}; ?>

@php
    // Priority -> accent colour for the notification icon tile.
    $toneColor = fn ($priority) => match (is_object($priority) ? $priority->value : $priority) {
        'critical' => 'bg-red-500',
        'high'     => 'bg-amber-500',
        default    => 'bg-blue-600',
    };
@endphp

<div class="flex w-full flex-col gap-5">

    {{-- Heading — H1 desktop only (mobile uses the layout's slim top bar); the
         "Mark all read" button keeps showing on mobile, pushed right via ml-auto. --}}
    <div class="flex items-center justify-between gap-3">
        <h1 class="hidden text-xl font-bold tracking-tight text-zinc-900 sm:text-3xl lg:block">Notifications</h1>
        @if ($unreadCount > 0)
            <button
                type="button"
                wire:click="markAllRead"
                class="ml-auto shrink-0 rounded-[6px] bg-blue-600 px-3.5 py-2 text-xs font-semibold text-white transition-colors hover:bg-blue-700"
            >
                Mark all read
            </button>
        @endif
    </div>

    @if ($notifications->isNotEmpty())
        <div class="divide-y divide-zinc-200 overflow-hidden rounded-[10px] border-2 border-zinc-100 bg-white">
            @foreach ($notifications as $note)
                <button
                    type="button"
                    @if (! $note->read_at) wire:click="markRead('{{ $note->id }}')" @endif
                    wire:key="note-{{ $note->id }}"
                    class="flex w-full items-start gap-3 px-4 py-3.5 text-left transition-colors hover:bg-zinc-50 {{ $note->read_at ? '' : 'bg-blue-50/50' }}"
                >
                    {{-- Priority icon tile --}}
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-[8px] {{ $toneColor($note->priority) }}">
                        <img src="{{ asset('assets/' . rawurlencode('notification 2.svg')) }}" alt="" class="h-5 w-5 brightness-0 invert" loading="lazy">
                    </span>

                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            <p class="truncate text-sm font-bold text-zinc-900">{{ $note->title }}</p>
                            @unless ($note->read_at)
                                <span class="h-2 w-2 shrink-0 rounded-[10px] bg-blue-600" aria-label="Unread"></span>
                            @endunless
                        </div>
                        <p class="mt-0.5 text-xs leading-relaxed text-zinc-600">{{ $note->message }}</p>
                        <p class="mt-1 text-[11px] text-zinc-400">{{ $note->created_at->diffForHumans() }}</p>
                    </div>
                </button>
            @endforeach
        </div>

        @if ($notifications->hasPages())
            <div>{{ $notifications->links() }}</div>
        @endif
    @else
        {{-- Empty state --}}
        <div class="rounded-[10px] bg-white px-6 py-16 text-center ring-1 ring-zinc-200">
            <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-[10px] bg-blue-50">
                <img src="{{ asset('assets/' . rawurlencode('notification 2.svg')) }}" alt="" class="h-7 w-7" style="filter: brightness(0) saturate(100%) invert(40%) sepia(95%) saturate(1500%) hue-rotate(205deg);" loading="lazy">
            </span>
            <p class="mt-4 text-base font-semibold text-zinc-900">You're all caught up</p>
            <p class="mt-1 text-sm text-zinc-600">Order updates, wallet activity and security alerts will show up here.</p>
        </div>
    @endif
</div>
