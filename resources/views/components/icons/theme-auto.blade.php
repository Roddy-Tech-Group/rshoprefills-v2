{{-- Animated auto-theme icon: a small spinning/pulsing sun with an "A" beside
     it. Inline SVG so currentColor inherits the row text colour. Size it from
     the call site (class="h-4 w-4"). --}}
<svg {{ $attributes->merge(['class' => 'shrink-0']) }} viewBox="0 0 24 24" fill="none" role="img" aria-label="Auto theme">
    <style>
        .ta-rays { transform-origin: 9.5px 9.5px; animation: ta-spin 24s linear infinite; }
        .ta-core { transform-origin: 9.5px 9.5px; animation: ta-pulse 3s ease-in-out infinite; }
        @keyframes ta-spin { to { transform: rotate(360deg); } }
        @keyframes ta-pulse { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.12); } }
        @media (prefers-reduced-motion: reduce) { .ta-rays, .ta-core { animation: none; } }
    </style>
    <circle class="ta-core" cx="9.5" cy="9.5" r="2.6" fill="currentColor"/>
    <g class="ta-rays" stroke="currentColor" stroke-width="2" stroke-linecap="round">
        <line x1="14.5" y1="9.5" x2="16.7" y2="9.5"/>
        <line x1="13.04" y1="13.04" x2="14.59" y2="14.59"/>
        <line x1="9.5" y1="14.5" x2="9.5" y2="16.7"/>
        <line x1="5.96" y1="13.04" x2="4.41" y2="14.59"/>
        <line x1="4.5" y1="9.5" x2="2.3" y2="9.5"/>
        <line x1="5.96" y1="5.96" x2="4.41" y2="4.41"/>
        <line x1="9.5" y1="4.5" x2="9.5" y2="2.3"/>
        <line x1="13.04" y1="5.96" x2="14.59" y2="4.41"/>
    </g>
    <path d="M14.8 21 L17.6 13.8 L20.4 21 M15.95 18.5 L19.25 18.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
</svg>
