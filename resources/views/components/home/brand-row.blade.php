@props([
    'title',
    'subtitle' => null,
    'viewAllHref' => '#',
    'cols' => 6,
])

@php
    // Explicit class strings so Tailwind's JIT picks them up.
    $gridCols = match ((int) $cols) {
        5 => 'lg:grid-cols-5',
        default => 'lg:grid-cols-6',
    };
@endphp

{{-- Brand row section. Title + optional subtitle + view-all header, then a responsive grid for brand cards. --}}
<section aria-label="{{ $title }}">
    <div class="mb-4 flex items-end justify-between gap-4">
        <div class="min-w-0">
            <h2 class="text-lg font-bold text-zinc-900 sm:text-xl">{{ $title }}</h2>
            @if ($subtitle)
                <p class="mt-0.5 text-base text-zinc-500">{{ $subtitle }}</p>
            @endif
        </div>
        <a href="{{ $viewAllHref }}" class="shrink-0 text-base font-medium text-zinc-700 underline underline-offset-4 transition-colors hover:text-zinc-900">
            See all
        </a>
    </div>

    {{-- Horizontal scroll on mobile, grid on tablet+. Mobile py-2 keeps the card ring + shadow from being clipped by overflow-x. --}}
    <div class="-mx-4 overflow-x-auto px-4 py-2 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden sm:mx-0 sm:overflow-visible sm:px-0 sm:py-0">
        <ul data-reveal-group class="flex w-max gap-4 sm:grid sm:w-full sm:grid-cols-3 sm:gap-5 {{ $gridCols }}">
            {{ $slot }}
        </ul>
    </div>
</section>
