{{-- Animated dark-theme icon: a circle whose filled half flips around every
     few seconds. Inline SVG so currentColor inherits the row text colour.
     Size it from the call site (class="h-4 w-4"). --}}
<svg {{ $attributes->merge(['class' => 'shrink-0']) }} viewBox="0 0 24 24" fill="none" role="img" aria-label="Dark theme">
    <style>
        .td-half {
            transform-origin: 12px 12px;
            animation: td-flip 6s cubic-bezier(0.45, 0, 0.25, 1) infinite;
        }
        @keyframes td-flip {
            0%, 42%  { transform: rotate(0deg); }
            50%, 92% { transform: rotate(180deg); }
            100%     { transform: rotate(360deg); }
        }
        @media (prefers-reduced-motion: reduce) { .td-half { animation: none; } }
    </style>
    <circle cx="12" cy="12" r="8.6" stroke="currentColor" stroke-width="2"/>
    <path class="td-half" d="M12 3.4 A8.6 8.6 0 0 0 12 20.6 Z" fill="currentColor"/>
</svg>
