{{-- Animated credit (money-in) transaction icon: green incoming arrow with a
     soft ping ring. Inline SVG, honors prefers-reduced-motion. Size via the
     class attribute (e.g. class="h-10 w-10"). --}}
<svg {{ $attributes->merge(['class' => 'shrink-0']) }} viewBox="0 0 48 48" role="img" aria-label="Credited transaction">
    <style>
        .tc-arrow { animation: tc-nudge 2.4s ease-in-out infinite; }
        .tc-ping { transform-box: fill-box; transform-origin: center; opacity: 0; animation: tc-ping 2.4s ease-out infinite; }
        @keyframes tc-nudge { 0%, 100% { transform: translate(0, 0); } 50% { transform: translate(-2.5px, 2.5px); } }
        @keyframes tc-ping { 0% { transform: scale(0.8); opacity: 0.5; } 70%, 100% { transform: scale(1.18); opacity: 0; } }
        @media (prefers-reduced-motion: reduce) { .tc-arrow, .tc-ping { animation: none; } }
    </style>
    <circle cx="24" cy="24" r="21" fill="#2BD576" opacity="0.14"/>
    <circle class="tc-ping" cx="24" cy="24" r="21" fill="none" stroke="#2BD576" stroke-width="2"/>
    <path class="tc-arrow" d="M30.5 17.5 L19 29 M19 21.5 V29 H26.5" stroke="#2BD576" stroke-width="3.6" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
</svg>
