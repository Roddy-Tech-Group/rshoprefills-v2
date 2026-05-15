@props([
    'title',
    'subtitle' => null,
    'viewAllHref' => '#',
    'cols' => 6,
    // When true, render skeleton cards instead of the slot content. Lets pages flip rows into a loading state
    // (e.g. while a backend query runs) without rebuilding the whole row markup.
    'loading' => false,
])

@php
    // Explicit class strings so Tailwind's JIT picks them up.
    $gridCols = match ((int) $cols) {
        5 => 'lg:grid-cols-5',
        7 => 'lg:grid-cols-7',
        default => 'lg:grid-cols-6',
    };
@endphp

{{-- Brand row section. Title + optional subtitle + view-all header, then a responsive grid for brand cards.
     During navigation (livewire:navigating window event) the slot is overlaid with a skeleton grid. --}}
<section
    aria-label="{{ $title }}"
    x-data="{ navigating: false }"
    x-on:livewire:navigate.window="navigating = true"
    x-on:livewire:navigated.window="navigating = false"
    class="relative"
>
    <div class="mb-4 flex items-end justify-between gap-4">
        <div class="min-w-0">
            <h2 class="text-lg font-bold text-zinc-900 sm:text-xl">{{ $title }}</h2>
            @if ($subtitle)
                <p class="mt-0.5 text-base text-zinc-600">{{ $subtitle }}</p>
            @endif
        </div>
        <a href="{{ $viewAllHref }}" class="shrink-0 text-base font-medium text-zinc-700 underline underline-offset-4 transition-colors hover:text-zinc-900">
            See all
        </a>
    </div>

    {{-- Horizontal scroll on mobile, grid on tablet+. Mobile py-2 keeps the card ring + shadow from being clipped by overflow-x. --}}
    <div class="-mx-4 overflow-x-auto px-4 py-2 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden sm:mx-0 sm:overflow-visible sm:px-0 sm:py-0">
        @if ($loading)
            <ul class="skeleton-stagger-fast flex w-max gap-4 sm:grid sm:w-full sm:grid-cols-3 sm:gap-5 {{ $gridCols }}">
                @for ($i = 0; $i < (int) $cols; $i++)
                    <li class="rounded-2xl bg-white p-3 shadow-sm shadow-zinc-900/[0.04] ring-1 ring-zinc-100" style="--i: {{ $i }}">
                        <x-skeleton class="aspect-square w-full rounded-xl" />
                        <x-skeleton class="mt-3 h-3 w-3/4" />
                    </li>
                @endfor
            </ul>
        @else
            <ul data-reveal-group class="flex w-max gap-4 sm:grid sm:w-full sm:grid-cols-3 sm:gap-5 {{ $gridCols }}">
                {{ $slot }}
            </ul>
        @endif

        {{-- Skeleton overlay during page navigation --}}
        <ul x-show="navigating" x-cloak class="skeleton-stagger-fast pointer-events-none absolute inset-0 z-10 mt-12 flex w-max gap-4 bg-transparent sm:grid sm:w-full sm:grid-cols-3 sm:gap-5 {{ $gridCols }}" aria-hidden="true">
            @for ($i = 0; $i < (int) $cols; $i++)
                <li class="rounded-2xl bg-white p-3 shadow-sm shadow-zinc-900/[0.04] ring-1 ring-zinc-100" style="--i: {{ $i }}">
                    <x-skeleton class="aspect-square w-full rounded-xl" />
                    <x-skeleton class="mt-3 h-3 w-3/4" />
                </li>
            @endfor
        </ul>
    </div>
</section>
