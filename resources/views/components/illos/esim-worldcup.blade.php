{{-- Animated eSIM scene: a floodlit football stadium with a bouncing ball, a
     golden trophy and a floating eSIM chip beaming signal - for the "travel
     eSIM for the World Cup" advert. Inline SVG, no JS, honors
     prefers-reduced-motion. Size via the class attribute. IDs are
     instance-suffixed so the scene can render more than once per page. --}}
@php $uid = 'ew'.uniqid(); @endphp
<svg {{ $attributes }} viewBox="0 0 820 640" role="img" aria-label="A floodlit football stadium with a trophy and eSIM connectivity for World Cup travel">
  <style>
    .e3-ball  { transform-box: fill-box; transform-origin: center; animation: e3-bounce 2.2s ease-in-out infinite; }
    .e3-spin  { transform-box: fill-box; transform-origin: center; animation: e3-spin 6s linear infinite; }
    .e3-tro   { animation: e3-bob 4.2s ease-in-out infinite; }
    .e3-chip  { animation: e3-bob 3.6s ease-in-out infinite 1s; }
    .e3-halo  { transform-box: fill-box; transform-origin: center; animation: e3-halo 3.6s ease-in-out infinite 1s; }
    .e3-sg    { opacity: 0; animation: e3-sig 2.2s ease-in-out infinite; }
    .e3-sgB   { animation-delay: 0.22s; }
    .e3-sgC   { animation-delay: 0.44s; }
    .e3-conf  { animation: e3-fall 3.4s linear infinite; }
    .e3-conf2 { animation: e3-fall 4.2s linear infinite 0.8s; }
    .e3-conf3 { animation: e3-fall 3s linear infinite 1.6s; }
    .e3-glow  { animation: e3-glowK 3s ease-in-out infinite; }
    @keyframes e3-bounce { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-26px); } }
    @keyframes e3-spin { to { transform: rotate(360deg); } }
    @keyframes e3-bob { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-6px); } }
    @keyframes e3-halo { 0%, 100% { transform: scale(1); opacity: 0.3; } 50% { transform: scale(1.18); opacity: 0.1; } }
    @keyframes e3-sig { 0%, 8% { opacity: 0; } 22%, 78% { opacity: 1; } 92%, 100% { opacity: 0; } }
    @keyframes e3-fall { 0% { transform: translateY(-10px) rotate(0); opacity: 0; } 12% { opacity: 1; } 100% { transform: translateY(130px) rotate(220deg); opacity: 0; } }
    @keyframes e3-glowK { 0%, 100% { opacity: 0.5; } 50% { opacity: 1; } }
    @media (prefers-reduced-motion: reduce) {
      .e3-ball, .e3-spin, .e3-tro, .e3-chip, .e3-halo, .e3-sg, .e3-conf, .e3-conf2, .e3-conf3, .e3-glow { animation: none; }
      .e3-sg { opacity: 1; }
    }
  </style>
  <defs>
    <linearGradient id="e3pitch-{{ $uid }}" x1="0" y1="0" x2="0" y2="1">
      <stop offset="0" stop-color="#1E6F3C"/>
      <stop offset="1" stop-color="#0F4A26"/>
    </linearGradient>
    <linearGradient id="e3gold-{{ $uid }}" x1="0" y1="0" x2="0" y2="1">
      <stop offset="0" stop-color="#FDE68A"/>
      <stop offset="1" stop-color="#F59E0B"/>
    </linearGradient>
    <radialGradient id="e3sky-{{ $uid }}" cx="0.5" cy="0.1" r="0.95">
      <stop offset="0" stop-color="#13233F"/>
      <stop offset="1" stop-color="#070D1A"/>
    </radialGradient>
  </defs>

  {{-- Transparent background: the parent card provides the dark surface. --}}

  {{-- Decor --}}
  <circle cx="90" cy="92" r="3" fill="#3B82F6" opacity="0.22"/>
  <circle cx="742" cy="120" r="3" fill="#60A5FA" opacity="0.2"/>
  <path d="M86 250 v14 M79 257 h14" stroke="#3B82F6" stroke-width="2.5" stroke-linecap="round" opacity="0.2"/>
  <path d="M744 540 v14 M737 547 h14" stroke="#60A5FA" stroke-width="2.5" stroke-linecap="round" opacity="0.18"/>

  {{-- Floodlight towers --}}
  <g class="e3-glow">
    <rect x="150" y="150" width="8" height="120" rx="4" fill="#1E3A8A"/>
    <rect x="120" y="118" width="68" height="36" rx="6" fill="#1E3A8A"/>
    <g fill="#BFDBFE"><circle cx="134" cy="130" r="4"/><circle cx="148" cy="130" r="4"/><circle cx="162" cy="130" r="4"/><circle cx="176" cy="130" r="4"/><circle cx="134" cy="143" r="4"/><circle cx="148" cy="143" r="4"/><circle cx="162" cy="143" r="4"/><circle cx="176" cy="143" r="4"/></g>
  </g>
  <g class="e3-glow" style="animation-delay: 1.2s">
    <rect x="662" y="150" width="8" height="120" rx="4" fill="#1E3A8A"/>
    <rect x="632" y="118" width="68" height="36" rx="6" fill="#1E3A8A"/>
    <g fill="#BFDBFE"><circle cx="646" cy="130" r="4"/><circle cx="660" cy="130" r="4"/><circle cx="674" cy="130" r="4"/><circle cx="688" cy="130" r="4"/><circle cx="646" cy="143" r="4"/><circle cx="660" cy="143" r="4"/><circle cx="674" cy="143" r="4"/><circle cx="688" cy="143" r="4"/></g>
  </g>

  {{-- Stadium stands --}}
  <ellipse cx="410" cy="430" rx="356" ry="150" fill="#0F1B30"/>
  <ellipse cx="410" cy="430" rx="356" ry="150" fill="none" stroke="#1E3A8A" stroke-width="3" opacity="0.6"/>
  <ellipse cx="410" cy="430" rx="300" ry="120" fill="#16243F"/>
  <g fill="#3B82F6" opacity="0.5">
    <circle cx="180" cy="400" r="3"/><circle cx="210" cy="392" r="3"/><circle cx="240" cy="386" r="3"/>
    <circle cx="600" cy="392" r="3"/><circle cx="630" cy="400" r="3"/><circle cx="660" cy="410" r="3"/>
    <circle cx="410" cy="330" r="3"/><circle cx="380" cy="332" r="3"/><circle cx="440" cy="332" r="3"/>
  </g>

  {{-- Pitch --}}
  <ellipse cx="410" cy="446" rx="250" ry="92" fill="url(#e3pitch-{{ $uid }})"/>
  <ellipse cx="410" cy="446" rx="250" ry="92" fill="none" stroke="#BBF7D0" stroke-width="2.5" opacity="0.6"/>
  <ellipse cx="410" cy="446" rx="60" ry="24" fill="none" stroke="#BBF7D0" stroke-width="2.5" opacity="0.6"/>
  <line x1="410" y1="354" x2="410" y2="538" stroke="#BBF7D0" stroke-width="2.5" opacity="0.6"/>

  {{-- Trophy --}}
  <g class="e3-tro" transform="translate(250,328)">
    <ellipse cx="26" cy="120" rx="34" ry="8" fill="#03060D" opacity="0.5"/>
    <path d="M6 8 H46 L42 46 Q26 64 10 46 Z" fill="url(#e3gold-{{ $uid }})"/>
    <path d="M6 8 Q-18 8 -14 30 Q-10 48 8 44" fill="none" stroke="#F59E0B" stroke-width="5"/>
    <path d="M46 8 Q70 8 66 30 Q62 48 44 44" fill="none" stroke="#F59E0B" stroke-width="5"/>
    <rect x="20" y="60" width="12" height="26" fill="#F59E0B"/>
    <rect x="6" y="86" width="40" height="12" rx="3" fill="url(#e3gold-{{ $uid }})"/>
    <rect x="2" y="98" width="48" height="14" rx="3" fill="#D97706"/>
    <path class="e3-sg" d="M22 -6 A14 14 0 0 1 50 -6" stroke="#FDE68A" stroke-width="4" stroke-linecap="round" fill="none"/>
  </g>

  {{-- Football --}}
  <g transform="translate(520,372)">
    <ellipse cx="0" cy="74" rx="26" ry="7" fill="#03060D" opacity="0.45"/>
    <g class="e3-ball">
      <g class="e3-spin">
        <circle r="30" fill="#F8FAFC"/>
        <circle r="30" fill="none" stroke="#CBD5E1" stroke-width="2"/>
        <path d="M0 -12 L11 -4 L7 9 L-7 9 L-11 -4 Z" fill="#0B1322"/>
        <path d="M0 -30 L0 -12 M11 -4 L26 -10 M7 9 L18 22 M-7 9 L-18 22 M-11 -4 L-26 -10" stroke="#0B1322" stroke-width="2.5"/>
      </g>
    </g>
  </g>

  {{-- Floating eSIM chip --}}
  <g transform="translate(648,300)">
    <circle class="e3-halo" cx="30" cy="22" r="38" stroke="#3B82F6" stroke-width="2.5" fill="none" opacity="0.3"/>
    <g class="e3-chip">
      <rect width="60" height="44" rx="10" fill="#3B82F6"/>
      <path d="M46 0 L60 0 L60 14 Z" fill="#070D1A"/>
      <rect x="15" y="11" width="30" height="22" rx="5" fill="none" stroke="#DBEAFE" stroke-width="2"/>
      <path d="M15 22 H45 M30 11 V33" stroke="#DBEAFE" stroke-width="2"/>
    </g>
    <path class="e3-sg" d="M70 6 A14 14 0 0 1 70 38" stroke="#BFDBFE" stroke-width="5" stroke-linecap="round" fill="none"/>
    <path class="e3-sg e3-sgB" d="M80 -2 A24 24 0 0 1 80 46" stroke="#BFDBFE" stroke-width="5" stroke-linecap="round" fill="none"/>
    <path class="e3-sg e3-sgC" d="M90 -10 A34 34 0 0 1 90 54" stroke="#BFDBFE" stroke-width="5" stroke-linecap="round" fill="none"/>
  </g>

  {{-- Confetti --}}
  <g transform="translate(300,178)">
    <rect class="e3-conf" x="0" y="0" width="10" height="6" rx="2" fill="#60A5FA"/>
    <rect class="e3-conf2" x="60" y="0" width="8" height="8" rx="2" fill="#FDE68A"/>
    <rect class="e3-conf3" x="130" y="0" width="10" height="6" rx="2" fill="#93C5FD"/>
    <rect class="e3-conf2" x="200" y="0" width="8" height="8" rx="2" fill="#F59E0B"/>
    <rect class="e3-conf" x="270" y="0" width="10" height="6" rx="2" fill="#BFDBFE"/>
  </g>
</svg>
