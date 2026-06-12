{{-- Animated debit (money-out) transaction icon: red outgoing arrow with a
     soft ping ring. Inline SVG, honors prefers-reduced-motion. Size via the
     class attribute (e.g. class="h-10 w-10"). --}}
<svg {{ $attributes->merge(['class' => 'shrink-0']) }} viewBox="0 0 48 48" role="img" aria-label="Debit transaction">
    <style>
        .td-arrow { animation: td-nudge 2.4s ease-in-out infinite; }
        .td-ping { transform-box: fill-box; transform-origin: center; opacity: 0; animation: td-ping 2.4s ease-out infinite; }
        @keyframes td-nudge { 0%, 100% { transform: translate(0, 0); } 50% { transform: translate(2.5px, -2.5px); } }
        @keyframes td-ping { 0% { transform: scale(0.8); opacity: 0.5; } 70%, 100% { transform: scale(1.18); opacity: 0; } }
        @media (prefers-reduced-motion: reduce) { .td-arrow, .td-ping { animation: none; } }
    </style>
    <circle cx="24" cy="24" r="21" fill="#FF5C5C" opacity="0.14"/>
    <circle class="td-ping" cx="24" cy="24" r="21" fill="none" stroke="#FF5C5C" stroke-width="2"/>
    <path class="td-arrow" d="M17.5 30.5 L29 19 M21.5 19 H29 V26.5" stroke="#FF5C5C" stroke-width="3.6" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
</svg>
