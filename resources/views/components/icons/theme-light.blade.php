{{-- Animated light-theme icon: sun with slowly spinning rays and a gently
     pulsing core. Inline SVG so currentColor inherits the row text colour -
     the same icon recolors automatically on light, dark, and selected states.
     Size it from the call site (class="h-4 w-4"). --}}
<svg {{ $attributes->merge(['class' => 'shrink-0']) }} viewBox="0 0 24 24" fill="none" role="img" aria-label="Light theme">
    <style>
        .tl-rays { transform-origin: 12px 12px; animation: tl-spin 24s linear infinite; }
        .tl-core { transform-origin: 12px 12px; animation: tl-pulse 3s ease-in-out infinite; }
        @keyframes tl-spin { to { transform: rotate(360deg); } }
        @keyframes tl-pulse { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.12); } }
        @media (prefers-reduced-motion: reduce) { .tl-rays, .tl-core { animation: none; } }
    </style>
    <circle class="tl-core" cx="12" cy="12" r="3.4" fill="currentColor"/>
    <g class="tl-rays" stroke="currentColor" stroke-width="2" stroke-linecap="round">
        <line x1="18.8" y1="12" x2="21.8" y2="12"/>
        <line x1="16.81" y1="16.81" x2="18.93" y2="18.93"/>
        <line x1="12" y1="18.8" x2="12" y2="21.8"/>
        <line x1="7.19" y1="16.81" x2="5.07" y2="18.93"/>
        <line x1="5.2" y1="12" x2="2.2" y2="12"/>
        <line x1="7.19" y1="7.19" x2="5.07" y2="5.07"/>
        <line x1="12" y1="5.2" x2="12" y2="2.2"/>
        <line x1="16.81" y1="7.19" x2="18.93" y2="5.07"/>
    </g>
</svg>
