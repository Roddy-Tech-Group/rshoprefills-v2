<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;

/**
 * Customer notification bell + dropdown panel. Embedded in the dashboard layout
 * via <livewire:notifications-menu />. Session-authenticated (web guard) — it
 * queries the Notification model directly, no API token needed.
 *
 * `tone` adapts the bell colour to its background: 'light' for the blue mobile
 * hero, 'dark' for the white inner top bar.
 */
new class extends Component {
    /** 'dark' (on white) or 'light' (on the blue hero). */
    public string $tone = 'dark';

    /** Most recent notifications shown in the panel. */
    #[Computed]
    public function items()
    {
        return Auth::user()
            ->notifications()
            ->latest()
            ->limit(20)
            ->get();
    }

    /** Unread badge count. */
    #[Computed]
    public function unreadCount(): int
    {
        return Auth::user()->notifications()->whereNull('read_at')->count();
    }

    public function markRead(string $id): void
    {
        $notification = Auth::user()->notifications()->whereKey($id)->first();

        if ($notification && ! $notification->read_at) {
            $notification->markAsRead();
            unset($this->items, $this->unreadCount);
        }
    }

    public function markAllRead(): void
    {
        Auth::user()->notifications()->whereNull('read_at')->update(['read_at' => now()]);
        unset($this->items, $this->unreadCount);
    }
}; ?>

@php
    // Priority -> accent colour for the notification icon tile.
    $toneColor = fn ($priority) => match ($priority?->value) {
        'critical' => 'bg-red-500',
        'high'     => 'bg-amber-500',
        default    => 'bg-blue-600',
    };
@endphp

<div
    x-data="{ open: false }"
    @click.outside="open = false"
    @keydown.escape.window="open = false"
    class="relative"
>
    {{-- Bell button --}}
    <button
        type="button"
        @click="open = !open"
        :aria-expanded="open.toString()"
        aria-label="Notifications"
        @class([
            'relative flex h-10 w-10 shrink-0 items-center justify-center rounded-full transition-colors active:scale-95',
            'text-white hover:bg-white/10' => $tone === 'light',
            'text-zinc-700 hover:bg-zinc-100' => $tone === 'dark',
        ])
    >
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"/>
        </svg>
        @if ($this->unreadCount > 0)
            <span class="absolute -top-0.5 -right-0.5 inline-flex h-5 min-w-[20px] items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-bold text-white ring-2 {{ $tone === 'light' ? 'ring-blue-600' : 'ring-white' }}">
                {{ $this->unreadCount > 99 ? '99+' : $this->unreadCount }}
            </span>
        @endif
    </button>

    {{-- Dropdown panel --}}
    <div
        x-show="open"
        x-cloak
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 -translate-y-1"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-end="opacity-0 -translate-y-1"
        class="absolute right-0 top-full z-50 mt-2 w-[360px] max-w-[calc(100vw-2rem)] overflow-hidden rounded-2xl bg-white shadow-xl shadow-zinc-900/10 ring-1 ring-zinc-200"
        role="menu"
    >
        {{-- Header --}}
        <div class="flex items-center justify-between gap-3 border-b border-zinc-100 px-4 py-3">
            <div class="flex items-center gap-2">
                <p class="text-sm font-bold text-zinc-900">Notifications</p>
                @if ($this->unreadCount > 0)
                    <span class="inline-flex items-center rounded-[5px] bg-blue-600 px-1.5 py-0.5 text-[10px] font-bold text-white">{{ $this->unreadCount }} new</span>
                @endif
            </div>
            @if ($this->unreadCount > 0)
                <button type="button" wire:click="markAllRead" class="text-xs font-semibold text-blue-600 transition-colors hover:text-blue-700">
                    Mark all read
                </button>
            @endif
        </div>

        {{-- List --}}
        <div class="max-h-[60vh] overflow-y-auto [scrollbar-width:thin]">
            @forelse ($this->items as $n)
                <button
                    type="button"
                    @if (! $n->read_at) wire:click="markRead('{{ $n->id }}')" @endif
                    class="flex w-full items-start gap-3 px-4 py-3 text-left transition-colors hover:bg-zinc-50 {{ $n->read_at ? '' : 'bg-blue-50/60' }}"
                    role="menuitem"
                >
                    <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-xl {{ $toneColor($n->priority) }}">
                        <svg class="h-4.5 w-4.5 text-white" style="height:1.125rem;width:1.125rem;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"/>
                        </svg>
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="flex items-start justify-between gap-2">
                            <span class="truncate text-sm font-semibold text-zinc-900">{{ $n->title }}</span>
                            @unless ($n->read_at)
                                <span class="mt-1 h-2 w-2 shrink-0 rounded-full bg-blue-600"></span>
                            @endunless
                        </span>
                        <span class="mt-0.5 block text-xs leading-snug text-zinc-600 line-clamp-2">{{ $n->message }}</span>
                        <span class="mt-1 block text-[11px] text-zinc-400">{{ $n->created_at->diffForHumans() }}</span>
                    </span>
                </button>
            @empty
                <div class="px-4 py-12 text-center">
                    <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-zinc-100 text-zinc-400">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"/>
                        </svg>
                    </span>
                    <p class="mt-3 text-sm font-semibold text-zinc-900">You're all caught up</p>
                    <p class="mt-0.5 text-xs text-zinc-600">New notifications will appear here.</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
