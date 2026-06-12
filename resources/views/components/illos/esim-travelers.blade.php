{{-- Animated eSIM scene: two travelers at an airport gate - one on a laptop,
     one tapping a phone - with a plane crossing the window and a floating
     eSIM chip. Inline SVG, no JS, honors prefers-reduced-motion. Size via
     the class attribute. IDs are instance-suffixed so the scene can render
     more than once per page without collisions. --}}
@php $uid = 'et'.uniqid(); @endphp
<svg {{ $attributes }} viewBox="0 0 820 640" role="img" aria-label="Two travelers at an airport using a laptop and a phone with eSIM connectivity">
  <style>
    .e2-fly { animation: e2-flyK 13s linear infinite; }
    .e2-drift1 { animation: e2-driftK 9s ease-in-out infinite alternate; }
    .e2-drift2 { animation: e2-driftK 11s ease-in-out infinite alternate-reverse; }
    .e2-scr { animation: e2-scrP 2.8s ease-in-out infinite; }
    .e2-typeL { animation: e2-typeK 0.9s ease-in-out infinite; }
    .e2-typeR { animation: e2-typeK 0.9s ease-in-out infinite 0.45s; }
    .e2-thumb { animation: e2-tap 1.1s ease-in-out infinite; }
    .e2-sg { opacity: 0; animation: e2-sig 2.2s ease-in-out infinite; }
    .e2-sgB { animation-delay: 0.22s; }
    .e2-sgC { animation-delay: 0.44s; }
    .e2-breathA { animation: e2-bob 4.6s ease-in-out infinite; }
    .e2-breathB { animation: e2-bob 4.6s ease-in-out infinite 2s; }
    .e2-chipBob { animation: e2-bob 3.6s ease-in-out infinite 1s; }
    .e2-halo { transform-box: fill-box; transform-origin: center; animation: e2-haloP 3.6s ease-in-out infinite 1s; }
    @keyframes e2-flyK { 0% { transform: translate(40px, 156px); } 100% { transform: translate(780px, 118px); } }
    @keyframes e2-driftK { from { transform: translateX(0); } to { transform: translateX(16px); } }
    @keyframes e2-scrP { 0%, 100% { opacity: 0.78; } 50% { opacity: 1; } }
    @keyframes e2-typeK { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-3px); } }
    @keyframes e2-tap { 0%, 55%, 100% { transform: translateY(0); } 72% { transform: translateY(3px); } }
    @keyframes e2-sig { 0%, 8% { opacity: 0; } 22%, 78% { opacity: 1; } 92%, 100% { opacity: 0; } }
    @keyframes e2-bob { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-4px); } }
    @keyframes e2-haloP { 0%, 100% { transform: scale(1); opacity: 0.3; } 50% { transform: scale(1.18); opacity: 0.1; } }
    @media (prefers-reduced-motion: reduce) {
      .e2-fly, .e2-drift1, .e2-drift2, .e2-scr, .e2-typeL, .e2-typeR, .e2-thumb, .e2-sg, .e2-breathA, .e2-breathB, .e2-chipBob, .e2-halo { animation: none; }
      .e2-sg { opacity: 1; }
      .e2-fly { transform: translate(560px, 130px); }
    }
  </style>
  <defs>
    <linearGradient id="p2Scr-{{ $uid }}" x1="0" y1="0" x2="0" y2="1">
      <stop offset="0" stop-color="#7DD3FC"/>
      <stop offset="1" stop-color="#3B82F6"/>
    </linearGradient>
    <clipPath id="winClip-{{ $uid }}">
      <rect x="113" y="67" width="594" height="184" rx="12"/>
    </clipPath>
  </defs>

  {{-- Background card --}}
  <rect width="820" height="640" rx="28" fill="#070D1A"/>

  {{-- Decor --}}
  <circle cx="88" cy="96" r="3" fill="#3B82F6" opacity="0.22"/>
  <circle cx="756" cy="150" r="3" fill="#60A5FA" opacity="0.2"/>
  <path d="M84 296 v14 M77 303 h14" stroke="#3B82F6" stroke-width="2.5" stroke-linecap="round" opacity="0.22"/>
  <path d="M748 560 v14 M741 567 h14" stroke="#60A5FA" stroke-width="2.5" stroke-linecap="round" opacity="0.18"/>

  {{-- Window --}}
  <g clip-path="url(#winClip-{{ $uid }})">
    <g class="e2-drift1" fill="#7DD3FC" opacity="0.9">
      <circle cx="200" cy="130" r="16"/>
      <circle cx="224" cy="122" r="20"/>
      <circle cx="248" cy="132" r="13"/>
      <rect x="184" y="126" width="80" height="14" rx="7"/>
    </g>
    <g class="e2-drift2" fill="#7DD3FC" opacity="0.6">
      <circle cx="560" cy="170" r="12"/>
      <circle cx="578" cy="164" r="15"/>
      <circle cx="596" cy="172" r="10"/>
      <rect x="548" y="168" width="62" height="11" rx="5"/>
    </g>
    <g class="e2-fly">
      <ellipse cx="0" cy="0" rx="26" ry="7" fill="#BFDBFE"/>
      <path d="M-26 -2 L-38 -16 L-18 -4 Z" fill="#BFDBFE"/>
      <path d="M-4 2 L-18 17 L8 6 Z" fill="#93C5FD"/>
      <circle cx="-8" cy="-1" r="1.8" fill="#0A1322"/>
      <circle cx="0" cy="-1" r="1.8" fill="#0A1322"/>
      <circle cx="8" cy="-1" r="1.8" fill="#0A1322"/>
    </g>
  </g>
  <rect x="110" y="64" width="600" height="190" rx="14" fill="none" stroke="#7DD3FC" stroke-width="3" opacity="0.7"/>
  <line x1="410" y1="66" x2="410" y2="252" stroke="#7DD3FC" stroke-width="3" opacity="0.7"/>

  {{-- Floor --}}
  <path d="M70 510 H750" stroke="#15233F" stroke-width="4" stroke-linecap="round"/>

  {{-- Couch back --}}
  <rect x="150" y="320" width="540" height="120" rx="26" fill="#1E3A8A"/>

  {{-- Person A upper body --}}
  <g class="e2-breathA">
    <rect x="252" y="298" width="96" height="132" rx="44" fill="#3B82F6"/>
    <circle cx="300" cy="262" r="38" fill="#C68642"/>
    <path d="M262 262 A38 38 0 0 1 338 262 Z" fill="#0E1320"/>
    <circle cx="288" cy="268" r="3.5" fill="#0B1322"/>
    <circle cx="312" cy="268" r="3.5" fill="#0B1322"/>
    <circle cx="288" cy="268" r="10" fill="none" stroke="#0B1322" stroke-width="2.5"/>
    <circle cx="312" cy="268" r="10" fill="none" stroke="#0B1322" stroke-width="2.5"/>
    <path d="M298 268 H302" stroke="#0B1322" stroke-width="2.5"/>
    <path d="M291 282 Q300 288 309 282" stroke="#0B1322" stroke-width="3.5" stroke-linecap="round" fill="none"/>
  </g>

  {{-- Person B upper body --}}
  <g class="e2-breathB">
    <rect x="492" y="298" width="96" height="132" rx="44" fill="#93C5FD"/>
    <circle cx="540" cy="262" r="38" fill="#7C4A21"/>
    <path d="M502 262 A38 38 0 0 1 578 262 Z" fill="#0E1320"/>
    <circle cx="564" cy="222" r="14" fill="#0E1320"/>
    <circle cx="528" cy="268" r="3.5" fill="#0B1322"/>
    <circle cx="552" cy="268" r="3.5" fill="#0B1322"/>
    <path d="M531 282 Q540 288 549 282" stroke="#0B1322" stroke-width="3.5" stroke-linecap="round" fill="none"/>
    <rect x="580" y="294" width="44" height="70" rx="9" fill="#1B2334"/>
    <rect class="e2-scr" x="585" y="300" width="34" height="58" rx="6" fill="url(#p2Scr-{{ $uid }})"/>
    <path d="M578 318 Q606 336 598 352" stroke="#60A5FA" stroke-width="18" stroke-linecap="round" fill="none"/>
    <circle cx="598" cy="352" r="9" fill="#7C4A21"/>
    <circle class="e2-thumb" cx="596" cy="332" r="5.5" fill="#7C4A21"/>
    <path d="M504 318 Q490 372 510 408" stroke="#60A5FA" stroke-width="18" stroke-linecap="round" fill="none"/>
    <circle cx="510" cy="410" r="9" fill="#7C4A21"/>
    <path class="e2-sg"  d="M590 288 A12 12 0 0 1 614 288" stroke="#BFDBFE" stroke-width="5" stroke-linecap="round" fill="none"/>
    <path class="e2-sg e2-sgB" d="M582 288 A20 20 0 0 1 622 288" stroke="#BFDBFE" stroke-width="5" stroke-linecap="round" fill="none"/>
    <path class="e2-sg e2-sgC" d="M574 288 A28 28 0 0 1 630 288" stroke="#BFDBFE" stroke-width="5" stroke-linecap="round" fill="none"/>
  </g>

  {{-- Tote with envelope, between the two --}}
  <g>
    <g transform="rotate(-6 422 352)">
      <rect x="397" y="332" width="50" height="34" rx="4" fill="#E9EFFA"/>
      <path d="M399 336 L422 352 L445 336" stroke="#94A3B8" stroke-width="2.5" fill="none"/>
    </g>
    <rect x="384" y="360" width="76" height="64" rx="10" fill="#2563EB"/>
    <path d="M396 360 Q404 330 414 360" stroke="#1E40AF" stroke-width="5" fill="none"/>
    <path d="M430 360 Q438 330 448 360" stroke="#1E40AF" stroke-width="5" fill="none"/>
  </g>

  {{-- Seat + armrests --}}
  <rect x="140" y="420" width="560" height="64" rx="26" fill="#1D4ED8"/>
  <rect x="124" y="348" width="46" height="134" rx="22" fill="#2563EB"/>
  <rect x="670" y="348" width="46" height="134" rx="22" fill="#2563EB"/>
  <rect x="168" y="482" width="14" height="24" rx="5" fill="#0F1830"/>
  <rect x="638" y="482" width="14" height="24" rx="5" fill="#0F1830"/>

  {{-- Person A legs + laptop --}}
  <rect x="258" y="420" width="84" height="34" rx="16" fill="#16243F"/>
  <line x1="272" y1="466" x2="268" y2="508" stroke="#16243F" stroke-width="22" stroke-linecap="round"/>
  <line x1="328" y1="466" x2="332" y2="508" stroke="#16243F" stroke-width="22" stroke-linecap="round"/>
  <ellipse cx="264" cy="514" rx="15" ry="8" fill="#0B1322"/>
  <ellipse cx="338" cy="514" rx="15" ry="8" fill="#0B1322"/>
  <path d="M262 318 Q242 350 256 368" stroke="#2563EB" stroke-width="18" stroke-linecap="round" fill="none"/>
  <path d="M338 318 Q358 350 344 368" stroke="#2563EB" stroke-width="18" stroke-linecap="round" fill="none"/>
  <circle class="e2-typeL" cx="258" cy="370" r="9" fill="#C68642"/>
  <circle class="e2-typeR" cx="342" cy="370" r="9" fill="#C68642"/>
  <rect class="e2-scr" x="254" y="369" width="92" height="6" rx="3" fill="#7DD3FC"/>
  <rect x="246" y="372" width="108" height="74" rx="10" fill="#131D33"/>
  <circle cx="300" cy="409" r="6" fill="#3B82F6" opacity="0.9"/>
  <path d="M234 446 L366 446 L356 464 L244 464 Z" fill="#0C1626"/>

  {{-- Person B legs --}}
  <rect x="498" y="420" width="84" height="34" rx="16" fill="#233459"/>
  <line x1="522" y1="456" x2="518" y2="508" stroke="#233459" stroke-width="22" stroke-linecap="round"/>
  <line x1="558" y1="456" x2="562" y2="508" stroke="#233459" stroke-width="22" stroke-linecap="round"/>
  <ellipse cx="514" cy="514" rx="15" ry="8" fill="#0B1322"/>
  <ellipse cx="566" cy="514" rx="15" ry="8" fill="#0B1322"/>

  {{-- Suitcase --}}
  <ellipse cx="202" cy="520" rx="58" ry="8" fill="#03060D" opacity="0.55"/>
  <line x1="180" y1="398" x2="180" y2="356" stroke="#93C5FD" stroke-width="6"/>
  <line x1="224" y1="398" x2="224" y2="356" stroke="#93C5FD" stroke-width="6"/>
  <rect x="170" y="346" width="64" height="12" rx="6" fill="#93C5FD"/>
  <rect x="158" y="398" width="86" height="110" rx="14" fill="#60A5FA"/>
  <path d="M158 432 H244 M158 468 H244" stroke="#3B82F6" stroke-width="4" opacity="0.7"/>
  <circle cx="178" cy="516" r="9" fill="#0F1830"/>
  <circle cx="224" cy="516" r="9" fill="#0F1830"/>

  {{-- Floating eSIM chip --}}
  <g transform="translate(688,268)">
    <circle class="e2-halo" cx="30" cy="22" r="38" stroke="#3B82F6" stroke-width="2.5" fill="none" opacity="0.3"/>
    <g class="e2-chipBob">
      <rect width="60" height="44" rx="10" fill="#3B82F6"/>
      <path d="M46 0 L60 0 L60 14 Z" fill="#070D1A"/>
      <rect x="15" y="11" width="30" height="22" rx="5" fill="none" stroke="#DBEAFE" stroke-width="2"/>
      <path d="M15 22 H45 M30 11 V33" stroke="#DBEAFE" stroke-width="2"/>
    </g>
  </g>
</svg>
