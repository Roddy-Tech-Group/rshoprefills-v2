{{-- Animated eSIM scene: three people on their phones with rising signal
     waves and a floating eSIM chip. Inline SVG so it ships with the page,
     animates without JS, and honors prefers-reduced-motion. Size via the
     class attribute. IDs are instance-suffixed so the scene can render more
     than once per page without collisions. --}}
@php $uid = 'ep'.uniqid(); @endphp
<svg {{ $attributes }} viewBox="0 0 820 640" role="img" aria-label="Three people using their phones with eSIM connectivity">
  <style>
    .e1-ch { animation: e1-bob 4.2s ease-in-out infinite; }
    .e1-ch2 { animation-delay: 1.2s; }
    .e1-ch3 { animation-delay: 2.4s; }
    .e1-scr { animation: e1-scrP 2.8s ease-in-out infinite; }
    .e1-thumb { animation: e1-tap 1.1s ease-in-out infinite; }
    .e1-tb2 { animation-delay: 0.35s; }
    .e1-tb3 { animation-delay: 0.7s; }
    .e1-sg { opacity: 0; animation: e1-sig 2.2s ease-in-out infinite; }
    .e1-sgB { animation-delay: 0.22s; }
    .e1-sgC { animation-delay: 0.44s; }
    .e1-g2 .e1-sg { animation-delay: 0.5s; }
    .e1-g2 .e1-sgB { animation-delay: 0.72s; }
    .e1-g2 .e1-sgC { animation-delay: 0.94s; }
    .e1-g3 .e1-sg { animation-delay: 1s; }
    .e1-g3 .e1-sgB { animation-delay: 1.22s; }
    .e1-g3 .e1-sgC { animation-delay: 1.44s; }
    .e1-chipBob { animation: e1-bob 3.6s ease-in-out infinite; }
    .e1-halo { transform-box: fill-box; transform-origin: center; animation: e1-haloP 3.6s ease-in-out infinite; }
    @keyframes e1-bob { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-5px); } }
    @keyframes e1-scrP { 0%, 100% { opacity: 0.78; } 50% { opacity: 1; } }
    @keyframes e1-tap { 0%, 55%, 100% { transform: translateY(0); } 72% { transform: translateY(3px); } }
    @keyframes e1-sig { 0%, 8% { opacity: 0; } 22%, 78% { opacity: 1; } 92%, 100% { opacity: 0; } }
    @keyframes e1-haloP { 0%, 100% { transform: scale(1); opacity: 0.3; } 50% { transform: scale(1.18); opacity: 0.1; } }
    @media (prefers-reduced-motion: reduce) { .e1-ch, .e1-scr, .e1-thumb, .e1-sg, .e1-chipBob, .e1-halo { animation: none; } .e1-sg { opacity: 1; } }
  </style>
  <defs>
    <linearGradient id="p1Scr-{{ $uid }}" x1="0" y1="0" x2="0" y2="1">
      <stop offset="0" stop-color="#7DD3FC"/>
      <stop offset="1" stop-color="#3B82F6"/>
    </linearGradient>
  </defs>

  {{-- Background card --}}
  <rect width="820" height="640" rx="28" fill="#070D1A"/>

  {{-- Decor --}}
  <circle cx="104" cy="108" r="3.5" fill="#3B82F6" opacity="0.22"/>
  <circle cx="146" cy="78" r="2.5" fill="#60A5FA" opacity="0.18"/>
  <circle cx="752" cy="420" r="3" fill="#3B82F6" opacity="0.2"/>
  <path d="M88 392 v14 M81 399 h14" stroke="#3B82F6" stroke-width="2.5" stroke-linecap="round" opacity="0.22"/>
  <path d="M696 540 v14 M689 547 h14" stroke="#60A5FA" stroke-width="2.5" stroke-linecap="round" opacity="0.18"/>
  <path d="M580 74 Q608 54 636 74" stroke="#1E3A8A" stroke-width="3" stroke-linecap="round" fill="none" opacity="0.6"/>
  <path d="M70 250 Q94 234 118 250" stroke="#1E3A8A" stroke-width="3" stroke-linecap="round" fill="none" opacity="0.5"/>

  {{-- Ground shadows --}}
  <ellipse cx="170" cy="576" rx="92" ry="13" fill="#03060D" opacity="0.65"/>
  <ellipse cx="410" cy="576" rx="92" ry="13" fill="#03060D" opacity="0.65"/>
  <ellipse cx="650" cy="576" rx="92" ry="13" fill="#03060D" opacity="0.65"/>

  {{-- Character 1 --}}
  <g class="e1-g1">
    <g class="e1-ch">
      <line x1="158" y1="432" x2="150" y2="548" stroke="#16243F" stroke-width="24" stroke-linecap="round"/>
      <line x1="182" y1="432" x2="190" y2="548" stroke="#16243F" stroke-width="24" stroke-linecap="round"/>
      <ellipse cx="148" cy="556" rx="16" ry="9" fill="#0B1322"/>
      <ellipse cx="192" cy="556" rx="16" ry="9" fill="#0B1322"/>
      <rect x="112" y="250" width="116" height="185" rx="54" fill="#60A5FA"/>
      <circle cx="140" cy="166" r="30" fill="#0D1117"/>
      <circle cx="170" cy="150" r="34" fill="#0D1117"/>
      <circle cx="200" cy="166" r="30" fill="#0D1117"/>
      <circle cx="126" cy="194" r="22" fill="#0D1117"/>
      <circle cx="214" cy="194" r="22" fill="#0D1117"/>
      <circle cx="170" cy="200" r="44" fill="#7C4A21"/>
      <circle cx="156" cy="196" r="4" fill="#0B1322"/>
      <circle cx="184" cy="196" r="4" fill="#0B1322"/>
      <path d="M158 214 Q170 222 182 214" stroke="#0B1322" stroke-width="4" stroke-linecap="round" fill="none"/>
      <rect x="142" y="296" width="56" height="88" rx="10" fill="#1B2334"/>
      <rect class="e1-scr" x="148" y="304" width="44" height="72" rx="7" fill="url(#p1Scr-{{ $uid }})"/>
      <path d="M118 284 Q92 334 142 352" stroke="#3B82F6" stroke-width="22" stroke-linecap="round" fill="none"/>
      <path d="M222 284 Q248 334 198 352" stroke="#3B82F6" stroke-width="22" stroke-linecap="round" fill="none"/>
      <circle cx="142" cy="352" r="11" fill="#7C4A21"/>
      <circle cx="198" cy="352" r="11" fill="#7C4A21"/>
      <circle class="e1-thumb" cx="158" cy="338" r="6.5" fill="#7C4A21"/>
      <circle cx="182" cy="346" r="6.5" fill="#7C4A21"/>
      <path class="e1-sg"  d="M156 288 A14 14 0 0 1 184 288" stroke="#BFDBFE" stroke-width="5" stroke-linecap="round" fill="none"/>
      <path class="e1-sg e1-sgB" d="M146 288 A24 24 0 0 1 194 288" stroke="#BFDBFE" stroke-width="5" stroke-linecap="round" fill="none"/>
      <path class="e1-sg e1-sgC" d="M136 288 A34 34 0 0 1 204 288" stroke="#BFDBFE" stroke-width="5" stroke-linecap="round" fill="none"/>
    </g>
  </g>

  {{-- Character 2 --}}
  <g class="e1-g2">
    <g class="e1-ch e1-ch2">
      <line x1="398" y1="432" x2="390" y2="548" stroke="#233459" stroke-width="24" stroke-linecap="round"/>
      <line x1="422" y1="432" x2="430" y2="548" stroke="#233459" stroke-width="24" stroke-linecap="round"/>
      <ellipse cx="388" cy="556" rx="16" ry="9" fill="#0B1322"/>
      <ellipse cx="432" cy="556" rx="16" ry="9" fill="#0B1322"/>
      <rect x="352" y="250" width="116" height="185" rx="54" fill="#1D4ED8"/>
      <circle cx="410" cy="200" r="44" fill="#F3C99F"/>
      <path d="M364 192 A46 46 0 0 1 456 192 Z" fill="#2563EB"/>
      <rect x="362" y="184" width="96" height="16" rx="8" fill="#3B82F6"/>
      <circle cx="410" cy="144" r="11" fill="#93C5FD"/>
      <circle cx="396" cy="200" r="4" fill="#0B1322"/>
      <circle cx="424" cy="200" r="4" fill="#0B1322"/>
      <path d="M398 216 Q410 224 422 216" stroke="#0B1322" stroke-width="4" stroke-linecap="round" fill="none"/>
      <rect x="382" y="296" width="56" height="88" rx="10" fill="#1B2334"/>
      <rect class="e1-scr" x="388" y="304" width="44" height="72" rx="7" fill="url(#p1Scr-{{ $uid }})"/>
      <path d="M358 284 Q332 334 382 352" stroke="#1638A8" stroke-width="22" stroke-linecap="round" fill="none"/>
      <path d="M462 284 Q488 334 438 352" stroke="#1638A8" stroke-width="22" stroke-linecap="round" fill="none"/>
      <circle cx="382" cy="352" r="11" fill="#F3C99F"/>
      <circle cx="438" cy="352" r="11" fill="#F3C99F"/>
      <circle class="e1-thumb e1-tb2" cx="398" cy="338" r="6.5" fill="#F3C99F"/>
      <circle cx="422" cy="346" r="6.5" fill="#F3C99F"/>
      <path class="e1-sg"  d="M396 288 A14 14 0 0 1 424 288" stroke="#BFDBFE" stroke-width="5" stroke-linecap="round" fill="none"/>
      <path class="e1-sg e1-sgB" d="M386 288 A24 24 0 0 1 434 288" stroke="#BFDBFE" stroke-width="5" stroke-linecap="round" fill="none"/>
      <path class="e1-sg e1-sgC" d="M376 288 A34 34 0 0 1 444 288" stroke="#BFDBFE" stroke-width="5" stroke-linecap="round" fill="none"/>
    </g>
  </g>

  {{-- Character 3 --}}
  <g class="e1-g3">
    <g class="e1-ch e1-ch3">
      <line x1="638" y1="432" x2="630" y2="548" stroke="#1B2742" stroke-width="24" stroke-linecap="round"/>
      <line x1="662" y1="432" x2="670" y2="548" stroke="#1B2742" stroke-width="24" stroke-linecap="round"/>
      <ellipse cx="628" cy="556" rx="16" ry="9" fill="#0B1322"/>
      <ellipse cx="672" cy="556" rx="16" ry="9" fill="#0B1322"/>
      <rect x="592" y="250" width="116" height="185" rx="54" fill="#93C5FD"/>
      <ellipse cx="650" cy="158" rx="38" ry="24" fill="#A65A2E"/>
      <ellipse cx="624" cy="172" rx="16" ry="12" fill="#A65A2E"/>
      <ellipse cx="676" cy="172" rx="16" ry="12" fill="#A65A2E"/>
      <circle cx="650" cy="200" r="44" fill="#C68642"/>
      <circle cx="636" cy="196" r="4" fill="#0B1322"/>
      <circle cx="664" cy="196" r="4" fill="#0B1322"/>
      <path d="M638 214 Q650 222 662 214" stroke="#0B1322" stroke-width="4" stroke-linecap="round" fill="none"/>
      <rect x="622" y="296" width="56" height="88" rx="10" fill="#1B2334"/>
      <rect class="e1-scr" x="628" y="304" width="44" height="72" rx="7" fill="url(#p1Scr-{{ $uid }})"/>
      <path d="M598 284 Q572 334 622 352" stroke="#60A5FA" stroke-width="22" stroke-linecap="round" fill="none"/>
      <path d="M702 284 Q728 334 678 352" stroke="#60A5FA" stroke-width="22" stroke-linecap="round" fill="none"/>
      <circle cx="622" cy="352" r="11" fill="#C68642"/>
      <circle cx="678" cy="352" r="11" fill="#C68642"/>
      <circle class="e1-thumb e1-tb3" cx="638" cy="338" r="6.5" fill="#C68642"/>
      <circle cx="662" cy="346" r="6.5" fill="#C68642"/>
      <path class="e1-sg"  d="M636 288 A14 14 0 0 1 664 288" stroke="#BFDBFE" stroke-width="5" stroke-linecap="round" fill="none"/>
      <path class="e1-sg e1-sgB" d="M626 288 A24 24 0 0 1 674 288" stroke="#BFDBFE" stroke-width="5" stroke-linecap="round" fill="none"/>
      <path class="e1-sg e1-sgC" d="M616 288 A34 34 0 0 1 684 288" stroke="#BFDBFE" stroke-width="5" stroke-linecap="round" fill="none"/>
    </g>
  </g>

  {{-- Floating eSIM chip --}}
  <g transform="translate(488,60)">
    <circle class="e1-halo" cx="38" cy="28" r="48" stroke="#3B82F6" stroke-width="2.5" fill="none" opacity="0.3"/>
    <g class="e1-chipBob">
      <rect width="76" height="56" rx="12" fill="#3B82F6"/>
      <path d="M58 0 L76 0 L76 18 Z" fill="#070D1A"/>
      <rect x="20" y="14" width="36" height="28" rx="6" fill="none" stroke="#DBEAFE" stroke-width="2.5"/>
      <path d="M20 28 H56 M38 14 V42" stroke="#DBEAFE" stroke-width="2.5"/>
    </g>
  </g>
</svg>
