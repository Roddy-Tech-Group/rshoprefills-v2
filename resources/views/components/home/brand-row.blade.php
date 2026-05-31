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
        cardW: 280,
        gap: 20,
        padLeft: 0,
        tx: 0,
        maxTx: 0,
        savedDuration: '0.6s',
        // dragArmed: pointerdown fired but threshold not yet crossed.
        // dragging:  threshold crossed - we own the gesture, click is killed.
        dragArmed: false,
        dragging: false,
        dragTarget: null,
        dragPointerId: null,
        dragStartX: 0,
        dragStartTx: 0,
        setup() {
            const viewport = this.$refs.track;
            const list     = this.$refs.list;
            if (! viewport || ! list) { return; }
            this.cardW   = window.innerWidth >= 1024 ? 280 : window.innerWidth >= 640 ? 200 : 160;
            this.gap     = window.innerWidth >= 640 ? 20 : 16;
            this.padLeft = Math.round(this.$el.getBoundingClientRect().left);
            list.style.paddingLeft = this.padLeft + 'px';
            this.tx = 0;
            this.maxTx = Math.min(0, viewport.clientWidth - list.scrollWidth);
            list.style.transform = 'translate3d(0, 0, 0)';
            // Slow the slide down when the row is long so a 20-card carousel
            // doesn't feel hurried. Base 0.6s, +0.04s per item, capped at 1.2s.
            const itemCount = list.children.length;
            const duration  = Math.min(1.2, 0.6 + 0.04 * Math.max(0, itemCount - 5));
            this.savedDuration = duration.toFixed(2) + 's';
            list.style.transitionDuration = this.savedDuration;
            this.canPrev = false;
            this.canNext = this.maxTx < 0;
        },
        scroll(dir) {
            const list = this.$refs.list;
            if (! list) { return; }
            // Shift by exactly 2 cards per click (1 on mobile). Keeps the slide
            // short so longer rows take several clicks to traverse instead of
            // flying past in one or two presses.
            const cardsPerStep = window.innerWidth >= 640 ? 2 : 1;
            const step = cardsPerStep * (this.cardW + this.gap);
            this.tx = Math.max(this.maxTx, Math.min(0, this.tx - dir * step));
            list.style.transform = `translate3d(${this.tx}px, 0, 0)`;
            this.canPrev = this.tx < 0;
            this.canNext = this.tx > this.maxTx;
        },
        // Touch / pointer drag for mobile + trackpad. Disables the CSS
        // transition while dragging so the finger tracks 1:1, then re-enables
        // it on release and snaps to the nearest card boundary.
        //
        // A movement THRESHOLD (8px) gates the actual drag. Below that, the
        // gesture is treated as a click - we do not capture the pointer,
        // do not move the list, and do not snap on release. This is what
        // makes the brand card links inside the carousel actually clickable
        // (previously a 1-2px finger wiggle would shift the carousel and
        // the click would land on a different element or be swallowed
        // entirely by the pointer-capture call).
        onDragStart(e) {
            const list = this.$refs.list;
            if (! list) { return; }
            // Don't engage drag on right-clicks or middle-clicks.
            if (e.button !== undefined && e.button !== 0) { return; }
            this.dragArmed = true;
            this.dragging = false;
            this.dragTarget = e.currentTarget;
            this.dragPointerId = e.pointerId;
            this.dragStartX = e.clientX;
            this.dragStartTx = this.tx;
        },
        onDragMove(e) {
            if (! this.dragArmed) { return; }
            const list = this.$refs.list;
            if (! list) { return; }
            const delta = e.clientX - this.dragStartX;

            // Below the threshold this is still a click candidate - bail out
            // without touching the list so the underlying <a> can receive
            // the click cleanly on pointerup.
            if (! this.dragging && Math.abs(delta) < 8) { return; }

            // Crossed the threshold: commit to a drag from here on.
            if (! this.dragging) {
                this.dragging = true;
                list.style.transitionDuration = '0s';
                try { this.dragTarget?.setPointerCapture(this.dragPointerId); } catch (_) {}
            }

            const raw = this.dragStartTx + delta;
            // Soft clamp so the user feels the edges instead of hitting a wall.
            const next = raw > 0 ? raw * 0.35 : (raw < this.maxTx ? this.maxTx + (raw - this.maxTx) * 0.35 : raw);
            list.style.transform = `translate3d(${next}px, 0, 0)`;
        },
        onDragEnd(e) {
            if (! this.dragArmed) { return; }
            const wasDragging = this.dragging;
            this.dragArmed = false;
            this.dragging = false;

            // No drag actually happened - leave everything alone so the
            // click event that follows pointerup hits its <a> target.
            if (! wasDragging) { return; }

            const list = this.$refs.list;
            if (! list) { return; }
            list.style.transitionDuration = this.savedDuration;
            const delta = e.clientX - this.dragStartX;
            const step  = this.cardW + this.gap;
            // Snap to nearest card boundary, biased toward swipe direction.
            const projected = this.dragStartTx + delta;
            const snapped   = Math.round(projected / step) * step;
            this.tx = Math.max(this.maxTx, Math.min(0, snapped));
            list.style.transform = `translate3d(${this.tx}px, 0, 0)`;
            this.canPrev = this.tx < 0;
            this.canNext = this.tx > this.maxTx;
        },
    }"
    x-on:livewire:navigate.window="navigating = true"
    x-on:livewire:navigated.window="navigating = false; $nextTick(() => setup())"
    x-init="$nextTick(() => setup())"
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
                    @click="scroll(-1)"
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
                    @click="scroll(1)"
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
            @resize.window.debounce.200ms="setup()"
            @pointerdown="onDragStart($event)"
            @pointermove="onDragMove($event)"
            @pointerup="onDragEnd($event)"
            @pointercancel="onDragEnd($event)"
            class="mx-[calc(50%-50vw)] w-screen overflow-hidden py-4 touch-pan-y select-none"
            style="cursor: grab;"
        >
            <ul
                x-ref="list"
                data-reveal-group
                :style="`--card-w: ${cardW}px`"
                class="carousel-list flex w-max gap-4 pl-4 pr-4 sm:gap-5 sm:pl-6 sm:pr-6 lg:pl-8 lg:pr-8 [&>*]:shrink-0"
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
