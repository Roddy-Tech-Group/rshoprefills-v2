@props([
    'title',
    'subtitle' => null,
    'viewAllHref' => '#',
    'cols' => 6,
    // When true, render skeleton cards instead of the slot content. Lets pages flip rows into a loading state
    // (e.g. while a backend query runs) without rebuilding the whole row markup.
    'loading' => false,
    // When true, render the children as a horizontal scrollable carousel with a
    // single toggle button (next at start -> back once scrolled). Use for rows
    // that have more brands than fit in a single grid view.
    'carousel' => false,
    // 'link' (default) renders an underlined "See all" text link. 'plus' swaps it
    // for a small rounded "+" pill that matches the dashboard's see-more pattern.
    'viewAllVariant' => 'link',
])

@php
    // Explicit class strings so Tailwind's JIT picks them up.
    $gridCols = match ((int) $cols) {
        5 => 'lg:grid-cols-5',
        7 => 'lg:grid-cols-7',
        default => 'lg:grid-cols-6',
    };
@endphp

{{-- Brand row section. Header (title + view-all) and content share the SAME
     parent width - the carousel does NOT use a full-bleed negative-margin
     trick, so titles, buttons and "See all" all line up with the card edges. --}}
<section
    aria-label="{{ $title }}"
    x-data="{
        navigating: false,
        canPrev: false,
        canNext: true,
        // Native horizontal scroll (no JS hand-drag). Touch + trackpad get the
        // browser's own smooth momentum scrolling, and it never fights vertical
        // page scroll. The arrow buttons (desktop only) drive scrollBy().
        refresh() {
            const t = this.$refs.track;
            if (! t) { return; }
            this.canPrev = t.scrollLeft > 4;
            this.canNext = t.scrollLeft + t.clientWidth < t.scrollWidth - 4;
        },
        nudge(dir) {
            const t = this.$refs.track;
            if (! t) { return; }
            const first   = t.querySelector('.carousel-list > *');
            const perStep = window.innerWidth >= 640 ? 2 : 1;
            const gap     = window.innerWidth >= 640 ? 20 : 16;
            const step    = first ? (first.getBoundingClientRect().width + gap) * perStep : t.clientWidth * 0.8;
            t.scrollBy({ left: dir * step, behavior: 'smooth' });
        },
    }"
    x-on:livewire:navigate.window="navigating = true"
    x-on:livewire:navigated.window="navigating = false; $nextTick(() => refresh())"
    x-init="$nextTick(() => refresh())"
    class="relative"
>
    <div class="mb-4 flex items-end justify-between gap-4">
        <div class="min-w-0">
            <h2 class="text-lg font-bold text-zinc-900 sm:text-xl dark:text-white">{{ $title }}</h2>
            @if ($subtitle)
                <p class="mt-0.5 text-base text-zinc-600 dark:text-zinc-400">{{ $subtitle }}</p>
            @endif
        </div>

        <div class="flex shrink-0 items-center gap-2">
            @if ($viewAllVariant === 'plus')
                <a href="{{ $viewAllHref }}" wire:navigate aria-label="See more {{ $title }}" class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-blue-50 text-blue-700 ring-1 ring-blue-200 transition-colors hover:bg-blue-100">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                    </svg>
                </a>
            @else
                <a href="{{ $viewAllHref }}" class="shrink-0 text-base font-medium text-zinc-700 underline underline-offset-4 transition-colors hover:text-zinc-900 dark:text-zinc-300 dark:hover:text-white">
                    See all
                </a>
            @endif

            @if ($carousel)
                <button
                    type="button"
                    @click="nudge(-1)"
                    :disabled="! canPrev"
                    aria-label="Previous"
                    class="hidden h-9 w-9 items-center justify-center rounded-full bg-white text-zinc-700 ring-1 ring-zinc-200 transition-colors hover:bg-zinc-50 hover:text-zinc-900 disabled:opacity-30 disabled:cursor-not-allowed sm:flex dark:bg-[#1d3252] dark:text-zinc-200 dark:ring-zinc-700/60 dark:hover:bg-[#26416b]"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/>
                    </svg>
                </button>
                <button
                    type="button"
                    @click="nudge(1)"
                    :disabled="! canNext"
                    aria-label="Next"
                    class="hidden h-9 w-9 items-center justify-center rounded-full bg-white text-zinc-700 ring-1 ring-zinc-200 transition-colors hover:bg-zinc-50 hover:text-zinc-900 disabled:opacity-30 disabled:cursor-not-allowed sm:flex dark:bg-[#1d3252] dark:text-zinc-200 dark:ring-zinc-700/60 dark:hover:bg-[#26416b]"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
                    </svg>
                </button>
            @endif
        </div>
    </div>

    @if ($carousel)
        {{-- Full-bleed carousel: the track spans 100vw via mx-[calc(50%-50vw)],
             breaking out of the max-w container. JS sets padding-left = the
             section's distance from the viewport left, so the first card lines
             up with the content column on load. On scroll, cards pass through
             that padding all the way to x=0 (the absolute viewport left edge).
             Native overflow-x-auto preserves trackpad/touch swipe momentum. --}}
        <div
            x-ref="track"
            @scroll.passive="refresh()"
            @resize.window.debounce.200ms="refresh()"
            class="mx-[calc(50%-50vw)] w-screen overflow-x-auto overflow-y-hidden py-4 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden [-webkit-overflow-scrolling:touch] [scroll-snap-type:x_proximity] scroll-pl-4 sm:scroll-pl-6 lg:scroll-pl-8"
            style="scroll-behavior: smooth;"
        >
            <ul
                data-reveal-group
                class="carousel-list flex w-max gap-4 pl-4 pr-4 sm:gap-5 sm:pl-6 sm:pr-6 lg:pl-8 lg:pr-8 [&>*]:shrink-0 [&>*]:snap-start"
            >
                {{ $slot }}
            </ul>
        </div>
    @else
        {{-- Grid mode (default): horizontal scroll on mobile, responsive grid on tablet+. --}}
        <div class="-mx-4 overflow-x-auto px-4 py-2 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden sm:mx-0 sm:overflow-visible sm:px-0 sm:py-0">
            @if ($loading)
                <ul class="skeleton-stagger-fast flex w-max gap-4 sm:grid sm:w-full sm:grid-cols-3 sm:gap-5 {{ $gridCols }}">
                    @for ($i = 0; $i < (int) $cols; $i++)
                        <li class="rounded-[10px] bg-white p-3 shadow-sm shadow-zinc-900/[0.04] ring-1 ring-zinc-100" style="--i: {{ $i }}">
                            <x-skeleton class="aspect-[16/10] w-full rounded-[15px]" />
                            <x-skeleton class="mt-3 h-4 w-3/4" />
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
                    <li class="rounded-[10px] bg-white p-3 shadow-sm shadow-zinc-900/[0.04] ring-1 ring-zinc-100" style="--i: {{ $i }}">
                        <x-skeleton class="aspect-[16/10] w-full rounded-[15px]" />
                        <x-skeleton class="mt-3 h-4 w-3/4" />
                    </li>
                @endfor
            </ul>
        </div>
    @endif
</section>
