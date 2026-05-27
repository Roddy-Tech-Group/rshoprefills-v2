@props([
    'percent' => 0,         // 0-100, the slice to fill
    'color' => '#0044FF',   // foreground arc colour
    'track' => '#e5e7eb',   // background ring colour (light mode)
    'darkTrack' => '#26416b', // background ring colour (dark mode)
    'size' => 48,            // pixel size of the SVG
    'stroke' => 6,           // stroke thickness
    'label' => null,         // optional centre label (defaults to "{percent}%")
])

@php
    // Arc geometry — circumference drives the dasharray "wipe" animation.
    // Animation: stroke-dashoffset transitions from `circumference` (empty) to
    // `circumference × (1 − percent/100)` (filled) over ~900ms once visible.
    $radius = ($size - $stroke) / 2;
    $circumference = 2 * M_PI * $radius;
    $clamped = max(0, min(100, (float) $percent));
    $offset = $circumference * (1 - $clamped / 100);
    $centre = $size / 2;
    $displayLabel = $label ?? (round($clamped) . '%');
@endphp

<div
    x-data="{
        visible: false,
        offset: {{ $circumference }},
        init() {
            const target = {{ $offset }};
            const obs = new IntersectionObserver((entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        // Defer to next frame so the transition catches the
                        // change instead of jumping straight to the target.
                        requestAnimationFrame(() => { this.offset = target; });
                        this.visible = true;
                        obs.disconnect();
                    }
                });
            }, { threshold: 0.2 });
            obs.observe(this.$el);
        }
    }"
    class="relative inline-flex shrink-0 items-center justify-center"
    style="width: {{ $size }}px; height: {{ $size }}px;"
    aria-label="{{ $displayLabel }}"
    role="img"
>
    <svg
        width="{{ $size }}" height="{{ $size }}"
        viewBox="0 0 {{ $size }} {{ $size }}"
        class="-rotate-90"
        aria-hidden="true"
    >
        {{-- Background track --}}
        <circle
            cx="{{ $centre }}" cy="{{ $centre }}" r="{{ $radius }}"
            fill="none"
            stroke="{{ $track }}"
            stroke-width="{{ $stroke }}"
            class="dark:stroke-[{{ $darkTrack }}]"
        />
        {{-- Foreground arc (animated). stroke-dasharray = full circumference,
             dashoffset shrinks from full to (full × remainder) when visible. --}}
        <circle
            cx="{{ $centre }}" cy="{{ $centre }}" r="{{ $radius }}"
            fill="none"
            stroke="{{ $color }}"
            stroke-width="{{ $stroke }}"
            stroke-linecap="round"
            stroke-dasharray="{{ $circumference }}"
            :stroke-dashoffset="offset"
            style="transition: stroke-dashoffset 900ms cubic-bezier(0.22, 1, 0.36, 1);"
        />
    </svg>
    {{-- Centre label — small percent figure inside the ring. Hidden for tiny
         sizes where text would be illegible. --}}
    @if ($size >= 36)
        <span class="absolute inset-0 flex items-center justify-center text-[10px] font-bold tabular-nums text-zinc-900 dark:text-white">
            {{ $displayLabel }}
        </span>
    @endif
</div>
