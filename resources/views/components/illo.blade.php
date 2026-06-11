{{--
    ╔══════════════════════════════════════════════════════════════════════════╗
    ║  <x-illo> — reusable inline animated SVG illustrations                    ║
    ╚══════════════════════════════════════════════════════════════════════════╝

    WHY: the SVG ships inline in the HTML, so there is NO image request and it
    paints instantly (this is what replaced the slow .webp/.png assets). The GSAP
    engine in resources/js/app.js wires the motion off the `data-illo` attribute.

    USAGE:
        <x-illo name="gift" class="h-16 w-16" />          (size via the class)
        <x-illo name="payout" class="h-full w-full" />    (fills a sized box)

    AVAILABLE NAMES
        Hero chips (200x200, light circle bg — size square, e.g. h-16 w-16):
          · gift      — gift box with bow + tag
          · globe     — globe, location pin, orbiting plane
          · shield    — security shield + verified user
          · search    — shopping bags + magnifier
          · mobile    — phone with gift, "works on every device"
        Full scenes (size with max-w-* or h-full w-full):
          · notFound  — 420x200, the animated "404" characters (used on errors/404)
          · emptyCart — 520x400 dark panel, desert cart (cart page + nav popups)
          · cardFan   — 320x260 dark panel, fanned gift cards (how-it-works step 1)
          · payWeb    — 320x260 dark panel, payment network w/ travelling dots (step 2)
          · payout    — 320x260 dark panel, instant-delivery card (step 3)

    ADDING A NEW ONE (keep it reusable):
        1. Add a @case('myName') here with the inline <svg> (namespace every id/class,
           e.g. #imy-thing / .imy-bit, so multiple illustrations can share a page).
        2. Add a matching `myName` entry to the ILLOS registry in app.js with
           set(g, el) / build(tl) / idle(g, el). The engine scopes selectors per
           instance (gsap.context), plays on scroll-into-view, and replays on hover.

    BEHAVIOR: entrance plays when scrolled into view; idle loops forever; hovering
    replays it fast. Falls back to a static SVG if GSAP is missing or the user has
    prefers-reduced-motion. Safe to render the same illustration more than once on
    a page.
--}}
@props(['name'])

<div data-illo="{{ $name }}" {{ $attributes->merge(['class' => 'block']) }}>
    @switch($name)
        @case('gift')
            <svg viewBox="0 0 200 200" role="img" aria-label="Gift box" class="h-auto w-full">
                <title>Gift</title>
                <circle cx="100" cy="100" r="100" fill="#DBEAFE"/>
                <ellipse cx="100" cy="164" rx="46" ry="5" fill="#BFDBFE"/>
                <g id="igift-all">
                    <rect x="58" y="96" width="84" height="64" rx="6" fill="#BFDBFE" stroke="#0F172A" stroke-width="3"/>
                    <rect x="88" y="96" width="24" height="64" fill="#FFFFFF" stroke="#0F172A" stroke-width="3"/>
                    <rect x="50" y="76" width="100" height="24" rx="4" fill="#93C5FD" stroke="#0F172A" stroke-width="3"/>
                    <rect x="88" y="76" width="24" height="24" fill="#FFFFFF" stroke="#0F172A" stroke-width="3"/>
                    <path d="M100 76 C88 76 64 70 62 58 C60 46 74 40 84 48 C92 54 98 66 100 76 Z" fill="#FFFFFF" stroke="#0F172A" stroke-width="3" stroke-linejoin="round"/>
                    <path d="M100 76 C112 76 136 70 138 58 C140 46 126 40 116 48 C108 54 102 66 100 76 Z" fill="#FFFFFF" stroke="#0F172A" stroke-width="3" stroke-linejoin="round"/>
                    <path d="M78 54 C72 56 70 62 74 66" fill="none" stroke="#0F172A" stroke-width="2" stroke-linecap="round"/>
                    <path d="M122 54 C128 56 130 62 126 66" fill="none" stroke="#0F172A" stroke-width="2" stroke-linecap="round"/>
                    <rect x="94" y="68" width="12" height="10" rx="3" fill="#FFFFFF" stroke="#0F172A" stroke-width="3"/>
                    <g id="igift-tag">
                        <path d="M104 75 C116 84 127 96 134 114" fill="none" stroke="#0F172A" stroke-width="2.5"/>
                        <g transform="rotate(28 134 112)"><rect x="130" y="106" width="48" height="28" rx="6" fill="#FFFFFF" stroke="#0F172A" stroke-width="3"/><circle cx="138" cy="120" r="3" fill="#BFDBFE" stroke="#0F172A" stroke-width="2"/></g>
                    </g>
                </g>
                <path class="igift-spark" d="M44 56v10M39 61h10" stroke="#3B82F6" stroke-width="2.5" stroke-linecap="round"/>
                <circle class="igift-spark" cx="160" cy="52" r="3.5" fill="#3B82F6"/>
                <circle class="igift-spark" cx="50" cy="134" r="3" fill="#60A5FA"/>
            </svg>
            @break

        @case('globe')
            <svg viewBox="0 0 200 200" role="img" aria-label="Global coverage" class="h-auto w-full">
                <title>Global coverage</title>
                <circle cx="100" cy="100" r="100" fill="#EFF6FF"/>
                <g id="igc-globe">
                    <circle cx="95" cy="105" r="52" fill="#FFFFFF" stroke="#0F172A" stroke-width="3"/>
                    <path d="M58 92 C64 78 84 74 92 84 C100 80 108 88 102 96 C110 98 106 110 96 108 C88 116 66 112 64 102 C56 100 54 96 58 92 Z" fill="#93C5FD" stroke="#0F172A" stroke-width="2"/>
                    <path d="M104 124 C112 120 122 126 118 134 C110 138 102 132 104 124 Z" fill="#93C5FD" stroke="#0F172A" stroke-width="2"/>
                    <ellipse cx="74" cy="130" rx="8" ry="4" fill="#93C5FD" stroke="#0F172A" stroke-width="2"/>
                </g>
                <g transform="rotate(-18 100 105)"><path id="igc-orbit" d="M18 105 A82 30 0 1 1 182 105 A82 30 0 1 1 18 105" fill="none" stroke="#3B82F6" stroke-width="2" stroke-dasharray="5 7" stroke-linecap="round"/></g>
                <g id="igc-pin">
                    <path d="M70 26 C52 26 40 40 40 56 C40 74 70 96 70 96 C70 96 100 74 100 56 C100 40 88 26 70 26 Z" fill="#FFFFFF" stroke="#0F172A" stroke-width="3" stroke-linejoin="round"/>
                    <circle cx="70" cy="55" r="12" fill="#60A5FA" stroke="#0F172A" stroke-width="3"/>
                </g>
                <g id="igc-plane"><path d="M-11 -4 L11 0 L-7 6 L-4 1 Z" fill="#3B82F6" stroke="#0F172A" stroke-width="2" stroke-linejoin="round"/></g>
            </svg>
            @break

        @case('shield')
            <svg viewBox="0 0 200 200" role="img" aria-label="Secured users" class="h-auto w-full">
                <title>Secured users</title>
                <circle cx="100" cy="100" r="100" fill="#DBEAFE"/>
                <ellipse cx="106" cy="176" rx="44" ry="5" fill="#BFDBFE"/>
                <g id="ishield-shield">
                    <path d="M100 30 C118 44 142 52 158 56 C158 92 156 128 100 168 C44 128 42 92 42 56 C58 52 82 44 100 30 Z" fill="#FFFFFF" stroke="#0F172A" stroke-width="3" stroke-linejoin="round"/>
                    <path d="M100 44 C114 55 132 61 146 65 C146 94 143 122 100 154 C57 122 54 94 54 65 C68 61 86 55 100 44 Z" fill="#93C5FD" stroke="#0F172A" stroke-width="2.5" stroke-linejoin="round"/>
                    <path id="ishield-check" d="M76 96 L94 114 L128 78" fill="none" stroke="#FFFFFF" stroke-width="10" stroke-linecap="round" stroke-linejoin="round"/>
                </g>
                <g id="ishield-user">
                    <circle cx="144" cy="138" r="32" fill="#FFFFFF" stroke="#0F172A" stroke-width="3"/>
                    <clipPath id="ishield-clip"><circle cx="144" cy="138" r="30"/></clipPath>
                    <g clip-path="url(#ishield-clip)">
                        <path d="M116 164 C120 148 168 148 172 164 L172 172 L116 172 Z" fill="#93C5FD" stroke="#0F172A" stroke-width="2.5"/>
                        <circle cx="144" cy="130" r="10.5" fill="#BFDBFE" stroke="#0F172A" stroke-width="2.5"/>
                        <path d="M134 128 C136 120 152 120 154 128" fill="none" stroke="#0F172A" stroke-width="2.5"/>
                    </g>
                </g>
                <path class="ishield-spark" d="M40 44v10M35 49h10" stroke="#3B82F6" stroke-width="2.5" stroke-linecap="round"/>
                <circle class="ishield-spark" cx="166" cy="58" r="3.5" fill="#3B82F6"/>
            </svg>
            @break

        @case('search')
            <svg viewBox="0 0 200 200" role="img" aria-label="Search products" class="h-auto w-full">
                <title>Search products</title>
                <circle cx="100" cy="100" r="100" fill="#DBEAFE"/>
                <ellipse cx="96" cy="166" rx="40" ry="5" fill="#BFDBFE"/>
                <g class="isr-bag">
                    <path d="M126 70 C126 52 144 52 144 70" fill="none" stroke="#0F172A" stroke-width="3"/>
                    <path d="M118 70 L150 70 L158 142 L112 142 Z" fill="#93C5FD" stroke="#0F172A" stroke-width="3" stroke-linejoin="round"/>
                </g>
                <g class="isr-bag">
                    <path d="M66 78 C66 56 88 56 88 78" fill="none" stroke="#0F172A" stroke-width="3"/>
                    <path d="M80 78 C80 56 102 56 102 78" fill="none" stroke="#0F172A" stroke-width="3"/>
                    <path d="M52 78 L116 78 L124 156 L42 156 Z" fill="#FFFFFF" stroke="#0F172A" stroke-width="3" stroke-linejoin="round"/>
                    <circle cx="68" cy="84" r="4" fill="#BFDBFE" stroke="#0F172A" stroke-width="2.5"/>
                    <circle cx="100" cy="84" r="4" fill="#BFDBFE" stroke="#0F172A" stroke-width="2.5"/>
                </g>
                <g id="isr-glass">
                    <path d="M142 142 L170 170" stroke="#0F172A" stroke-width="14" stroke-linecap="round"/>
                    <path d="M142 142 L170 170" stroke="#60A5FA" stroke-width="7" stroke-linecap="round"/>
                    <circle cx="118" cy="118" r="36" fill="#FFFFFF" stroke="#0F172A" stroke-width="3"/>
                    <circle cx="118" cy="118" r="28" fill="#BFDBFE" stroke="#0F172A" stroke-width="2"/>
                    <path d="M102 134 L120 112 L138 134 Z" fill="#93C5FD" stroke="#0F172A" stroke-width="2.5" stroke-linejoin="round"/>
                    <path id="isr-glint" d="M104 100 L114 92" stroke="#FFFFFF" stroke-width="4" stroke-linecap="round"/>
                </g>
            </svg>
            @break

        @case('notFound')
            <svg viewBox="0 0 420 200" role="img" aria-label="404, page not found" class="h-auto w-full">
                <title>404</title>
                <g stroke="#0F172A" opacity="0.35" stroke-width="2.5" stroke-linecap="round"><path d="M40 194h24"/><path d="M140 196h16"/><path d="M236 194h26"/><path d="M330 196h20"/></g>
                <g class="i404-char">
                    <path d="M88 160 L85 182 L76 184" fill="none" stroke="#0F172A" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M108 160 L110 182 L119 184" fill="none" stroke="#0F172A" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M82 50 L116 50 L116 112 L128 112 L128 132 L116 132 L116 160 L94 160 L94 132 L42 132 L42 116 Z" fill="#3B82F6" stroke="#0F172A" stroke-width="3" stroke-linejoin="round"/>
                    <circle cx="100" cy="74" r="2" fill="#0F172A"/>
                    <path d="M70 16 Q78 12 86 16" fill="none" stroke="#0F172A" stroke-width="2.5" stroke-linecap="round"/>
                    <path d="M99 13 Q107 9 115 13" fill="none" stroke="#0F172A" stroke-width="2.5" stroke-linecap="round"/>
                    <g class="i404-eye"><circle cx="78" cy="30" r="7.5" fill="#FFFFFF" stroke="#0F172A" stroke-width="2.5"/><circle class="i404-pupil" cx="80" cy="31" r="2.6" fill="#0F172A"/></g>
                    <g class="i404-eye"><circle cx="106" cy="30" r="7.5" fill="#FFFFFF" stroke="#0F172A" stroke-width="2.5"/><circle class="i404-pupil" cx="108" cy="31" r="2.6" fill="#0F172A"/></g>
                </g>
                <g class="i404-char">
                    <path d="M198 167 L196 186 L188 188" fill="none" stroke="#0F172A" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M222 167 L224 186 L232 188" fill="none" stroke="#0F172A" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                    <ellipse cx="210" cy="112" rx="46" ry="56" fill="#BFDBFE" stroke="#0F172A" stroke-width="3"/>
                    <ellipse cx="210" cy="112" rx="24" ry="34" fill="#FFFFFF" stroke="#0F172A" stroke-width="3"/>
                    <path d="M166 84 C155 76 151 64 156 53" fill="none" stroke="#0F172A" stroke-width="3" stroke-linecap="round"/>
                    <path d="M254 84 C265 76 269 64 264 53" fill="none" stroke="#0F172A" stroke-width="3" stroke-linecap="round"/>
                    <path d="M187 16 L198 19" stroke="#0F172A" stroke-width="2.5" stroke-linecap="round"/>
                    <path d="M233 16 L222 19" stroke="#0F172A" stroke-width="2.5" stroke-linecap="round"/>
                    <path d="M202 44 Q210 38 218 44" fill="none" stroke="#0F172A" stroke-width="3" stroke-linecap="round"/>
                    <g class="i404-eye"><circle cx="194" cy="30" r="7.5" fill="#FFFFFF" stroke="#0F172A" stroke-width="2.5"/><circle class="i404-pupil" cx="194" cy="32" r="2.6" fill="#0F172A"/></g>
                    <g class="i404-eye"><circle cx="226" cy="30" r="7.5" fill="#FFFFFF" stroke="#0F172A" stroke-width="2.5"/><circle class="i404-pupil" cx="226" cy="32" r="2.6" fill="#0F172A"/></g>
                </g>
                <g class="i404-char">
                    <g transform="translate(252 0)">
                        <path d="M88 160 L85 182 L76 184" fill="none" stroke="#0F172A" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M108 160 L110 182 L119 184" fill="none" stroke="#0F172A" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M82 50 L116 50 L116 112 L128 112 L128 132 L116 132 L116 160 L94 160 L94 132 L42 132 L42 116 Z" fill="#3B82F6" stroke="#0F172A" stroke-width="3" stroke-linejoin="round"/>
                        <ellipse cx="112" cy="11" rx="8" ry="3.5" fill="none" stroke="#60A5FA" stroke-width="2.5"/>
                        <path d="M64 14 Q72 10 80 14" fill="none" stroke="#0F172A" stroke-width="2.5" stroke-linecap="round"/>
                        <g class="i404-eye"><circle cx="74" cy="30" r="7.5" fill="#FFFFFF" stroke="#0F172A" stroke-width="2.5"/><circle class="i404-pupil" cx="72" cy="31" r="2.6" fill="#0F172A"/></g>
                        <path d="M98 31 q7 5 14 0" fill="none" stroke="#0F172A" stroke-width="2.5" stroke-linecap="round"/>
                    </g>
                </g>
            </svg>
            @break

        @case('emptyCart')
            <svg viewBox="0 0 520 400" role="img" aria-label="Empty cart illustration" class="h-auto w-full">
                <title>Your cart is empty</title>
                <rect width="520" height="400" rx="24" fill="#0E1626"/>
                <g fill="#FFFFFF" opacity="0.28">
                    <circle cx="70" cy="56" r="2"/><circle cx="150" cy="34" r="1.5"/><circle cx="420" cy="52" r="2"/>
                    <circle cx="478" cy="142" r="1.5"/><circle cx="255" cy="38" r="1.5"/>
                </g>
                <g stroke="#2A3A5C" stroke-width="3" stroke-linecap="round">
                    <path d="M70 312h18"/><path d="M152 322h12"/><path d="M298 336h12"/><path d="M468 300h10"/>
                </g>
                <g fill="#1D4ED8">
                    <path d="M48 302 C44 288 50 276 57 270 C58 282 56 293 53 302 Z"/>
                    <path d="M58 302 C57 288 63 279 70 275 C68 286 65 295 62 302 Z"/>
                    <path d="M118 302 C115 290 120 280 127 276 C126 287 124 295 122 302 Z"/>
                    <path d="M128 302 C128 290 134 282 141 279 C138 289 134 296 131 302 Z"/>
                </g>
                <g id="ec-cactus">
                    <path d="M79 302 C74 262 72 204 80 174 C85 157 105 156 110 172 C117 198 114 262 110 302 Z" fill="#3B82F6"/>
                    <path d="M79 244 C60 240 49 226 51 203 C52 190 66 189 67 201 C69 214 71 224 81 228 Z" fill="#3B82F6"/>
                    <path d="M110 222 C129 218 139 202 137 180 C136 167 122 166 121 178 C119 192 118 204 108 208 Z" fill="#3B82F6"/>
                    <g stroke="#BFDBFE" stroke-width="2" stroke-linecap="round">
                        <path d="M88 195l-7 -3"/><path d="M101 215l7 -3"/><path d="M90 250l-7 -3"/><path d="M102 275l7 -3"/>
                        <path d="M58 214l-6 -4"/><path d="M129 190l6 -4"/><path d="M95 172l-2 -7"/>
                    </g>
                    <path d="M94 162 C88 158 86 150 90 144 C92 150 94 152 94 146 C95 151 97 150 99 144 C103 150 100 158 94 162 Z" fill="#FFFFFF"/>
                </g>
                <g id="ec-tumbleweed" fill="none" stroke="#BFDBFE" stroke-linecap="round">
                    <path d="M402 318 C398 297 426 285 445 295 C462 281 486 299 474 315 C487 329 463 344 446 336 C432 351 404 341 402 318 Z" stroke-width="3"/>
                    <path d="M417 312 C425 299 446 301 453 315" stroke-width="2.5"/>
                    <path d="M414 327 C428 338 450 333 459 319" stroke-width="2.5"/>
                    <path d="M432 304 C439 312 437 323 428 330" stroke-width="2.5"/>
                </g>
                <g id="ec-cart">
                    <g class="ec-bag">
                        <path d="M232 188 L266 188 L270 244 L228 244 Z" fill="#60A5FA"/>
                        <path d="M240 188 C240 174 258 174 258 188" stroke="#60A5FA" stroke-width="4.5" fill="none"/>
                    </g>
                    <g class="ec-bag">
                        <path d="M288 180 L322 180 L328 246 L283 246 Z" fill="#FFFFFF"/>
                        <path d="M296 180 C296 166 314 166 314 180" stroke="#FFFFFF" stroke-width="4.5" fill="none"/>
                    </g>
                    <g class="ec-bag">
                        <path d="M250 196 L286 196 L291 248 L246 248 Z" fill="#1D4ED8"/>
                        <path d="M259 196 C259 181 277 181 277 196" stroke="#1D4ED8" stroke-width="4.5" fill="none"/>
                    </g>
                    <g fill="none" stroke="#FFFFFF" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M223 216 L345 271" stroke-width="2.5" opacity="0.75"/>
                        <path d="M355 216 L233 271" stroke-width="2.5" opacity="0.75"/>
                        <path d="M220 243 C262 247 318 247 358 243" stroke-width="2.5" opacity="0.75"/>
                        <path d="M216 210 C258 205 322 205 362 210 L350 276 C312 281 266 281 228 276 Z" stroke-width="5"/>
                        <path d="M218 211 C202 199 192 191 180 186" stroke-width="5"/>
                        <path d="M240 278 L237 305" stroke-width="5"/>
                        <path d="M340 278 L337 305" stroke-width="5"/>
                    </g>
                    <circle cx="177" cy="184" r="4.5" fill="#FFFFFF"/>
                    <g id="ec-wheel-1" stroke="#FFFFFF" fill="none" stroke-linecap="round">
                        <circle cx="236" cy="316" r="10" stroke-width="5"/>
                        <path d="M236 309v14M229 316h14" stroke-width="2.5"/>
                    </g>
                    <g id="ec-wheel-2" stroke="#FFFFFF" fill="none" stroke-linecap="round">
                        <circle cx="336" cy="316" r="10" stroke-width="5"/>
                        <path d="M336 309v14M329 316h14" stroke-width="2.5"/>
                    </g>
                </g>
                <g id="ec-bubble">
                    <path d="M295 139 C293 151 287 158 276 164 C289 160 300 154 305 140 Z" fill="#93C5FD"/>
                    <path d="M264 106 C264 83 283 72 304 72 C327 72 346 85 344 109 C342 131 324 141 302 141 C281 141 264 129 264 106 Z" fill="#93C5FD"/>
                    <circle cx="289" cy="100" r="3.5" fill="#0E1626"/>
                    <circle cx="319" cy="100" r="3.5" fill="#0E1626"/>
                    <path d="M283 91 Q289 87 295 89" stroke="#0E1626" stroke-width="2.5" fill="none" stroke-linecap="round"/>
                    <path d="M313 89 Q319 87 325 91" stroke="#0E1626" stroke-width="2.5" fill="none" stroke-linecap="round"/>
                    <path d="M289 121 Q304 110 319 121" stroke="#0E1626" stroke-width="4" fill="none" stroke-linecap="round"/>
                </g>
            </svg>
            @break

        @case('mobile')
            <svg viewBox="0 0 200 200" role="img" aria-label="Works on every device" class="h-auto w-full">
                <title>Mobile compatibility</title>
                <circle cx="100" cy="100" r="100" fill="#DBEAFE"/>
                <ellipse cx="100" cy="172" rx="46" ry="5" fill="#BFDBFE"/>
                <g id="imob-phone">
                    <g transform="rotate(-14 80 118)">
                        <rect x="53" y="70" width="54" height="96" rx="10" fill="#FFFFFF" stroke="#0F172A" stroke-width="3"/>
                        <rect x="60" y="84" width="40" height="66" rx="4" fill="#EFF6FF" stroke="#0F172A" stroke-width="2"/>
                        <path d="M72 77h16" stroke="#0F172A" stroke-width="2.5" stroke-linecap="round"/>
                        <path d="M72 159h16" stroke="#0F172A" stroke-width="2.5" stroke-linecap="round"/>
                        <rect class="imob-ui" x="64" y="90" width="32" height="5" rx="2.5" fill="#93C5FD"/>
                        <rect class="imob-ui" x="64" y="100" width="24" height="5" rx="2.5" fill="#BFDBFE"/>
                        <rect class="imob-ui" x="64" y="110" width="28" height="5" rx="2.5" fill="#BFDBFE"/>
                        <g class="imob-ui">
                            <rect x="64" y="120" width="32" height="18" rx="3" fill="#2563EB"/>
                            <circle cx="71" cy="129" r="3" fill="#FFFFFF"/>
                            <rect x="77" y="126" width="13" height="4" rx="2" fill="#FFFFFF" opacity="0.75"/>
                        </g>
                    </g>
                </g>
                <g class="imob-trail" stroke="#2563EB" stroke-width="2.5" stroke-linecap="round"><path d="M164 74h12"/></g>
                <g class="imob-trail" stroke="#2563EB" stroke-width="2.5" stroke-linecap="round"><path d="M166 84h16"/></g>
                <g class="imob-trail" stroke="#2563EB" stroke-width="2.5" stroke-linecap="round"><path d="M164 94h10"/></g>
                <g id="imob-gift">
                    <g transform="rotate(10 142 86)">
                        <path d="M142 70 C137 70 130 67 130 62 C130 58 135 57 138 60 C140 62 141 66 142 70 Z" fill="#FFFFFF" stroke="#0F172A" stroke-width="2.5" stroke-linejoin="round"/>
                        <path d="M142 70 C147 70 154 67 154 62 C154 58 149 57 146 60 C144 62 143 66 142 70 Z" fill="#FFFFFF" stroke="#0F172A" stroke-width="2.5" stroke-linejoin="round"/>
                        <rect x="126" y="78" width="32" height="24" rx="3" fill="#93C5FD" stroke="#0F172A" stroke-width="2.5"/>
                        <rect x="138" y="78" width="8" height="24" fill="#FFFFFF" stroke="#0F172A" stroke-width="2.5"/>
                        <rect x="123" y="70" width="38" height="10" rx="2" fill="#BFDBFE" stroke="#0F172A" stroke-width="2.5"/>
                        <rect x="138" y="70" width="8" height="10" fill="#FFFFFF" stroke="#0F172A" stroke-width="2.5"/>
                    </g>
                </g>
                <path class="imob-star" d="M56 42 C57.5 47 59.5 49 64 50.5 C59.5 52 57.5 54 56 59 C54.5 54 52.5 52 48 50.5 C52.5 49 54.5 47 56 42 Z" fill="#FFFFFF" stroke="#0F172A" stroke-width="2.5"/>
                <path class="imob-star" d="M158 121 C159 125 161 126.5 164.5 127.5 C161 128.5 159 130 158 134 C157 130 155 128.5 151.5 127.5 C155 126.5 157 125 158 121 Z" fill="#FFFFFF" stroke="#0F172A" stroke-width="2.5"/>
                <circle class="imob-star" cx="148" cy="46" r="2.5" fill="#2563EB"/>
                <circle class="imob-star" cx="44" cy="118" r="3" fill="#60A5FA"/>
                <circle class="imob-star" cx="50" cy="86" r="2" fill="#60A5FA"/>
            </svg>
            @break

        @case('cardFan')
            <svg viewBox="0 0 320 260" role="img" aria-label="Choose your gift card" preserveAspectRatio="xMidYMid slice" class="h-full w-full">
                <title>Choose your gift card</title>
                <rect width="320" height="260" rx="18" fill="#0E1626"/>
                <path class="gc-spark" d="M52 56v10M47 61h10" stroke="#60A5FA" stroke-width="2.5" stroke-linecap="round"/>
                <circle class="gc-spark" cx="272" cy="208" r="3" fill="#2563EB"/>
                <circle class="gc-spark" cx="282" cy="62" r="2.5" fill="#60A5FA"/>
                <g class="gc-card" transform="rotate(-20 100 78)"><rect x="56" y="50" width="88" height="56" rx="8" fill="#1E3A8A"/><circle cx="78" cy="72" r="9" fill="#60A5FA"/><rect x="94" y="66" width="34" height="5" rx="2.5" fill="#FFFFFF" opacity="0.55"/><rect x="94" y="76" width="24" height="5" rx="2.5" fill="#FFFFFF" opacity="0.35"/></g>
                <g class="gc-card" transform="rotate(16 222 78)"><rect x="178" y="50" width="88" height="56" rx="8" fill="#2563EB"/><path d="M196 84 L206 64 L216 84 Z" fill="#FFFFFF"/><rect x="222" y="68" width="32" height="5" rx="2.5" fill="#FFFFFF" opacity="0.6"/><rect x="222" y="78" width="22" height="5" rx="2.5" fill="#FFFFFF" opacity="0.4"/></g>
                <g class="gc-card" transform="rotate(-28 78 140)"><rect x="34" y="112" width="88" height="56" rx="8" fill="#93C5FD"/><path d="M58 128l4 9 9 4-9 4-4 9-4-9-9-4 9-4z" fill="#0F172A"/><rect x="78" y="130" width="30" height="5" rx="2.5" fill="#0F172A" opacity="0.7"/><rect x="78" y="140" width="20" height="5" rx="2.5" fill="#0F172A" opacity="0.45"/></g>
                <g class="gc-card" transform="rotate(22 244 142)"><rect x="200" y="114" width="88" height="56" rx="8" fill="#FFFFFF"/><path d="M222 128l12 7v14l-12 7-12-7v-14z" fill="#2563EB"/><rect x="244" y="132" width="30" height="5" rx="2.5" fill="#0F172A" opacity="0.6"/><rect x="244" y="142" width="20" height="5" rx="2.5" fill="#0F172A" opacity="0.35"/></g>
                <g class="gc-card" transform="rotate(-12 110 196)"><rect x="66" y="168" width="88" height="56" rx="8" fill="#1D4ED8"/><path d="M92 180l-8 16h8l-6 16 16-20h-9l7-12z" fill="#FFFFFF"/><rect x="110" y="186" width="28" height="5" rx="2.5" fill="#FFFFFF" opacity="0.6"/><rect x="110" y="196" width="18" height="5" rx="2.5" fill="#FFFFFF" opacity="0.4"/></g>
                <g class="gc-card" transform="rotate(14 216 198)"><rect x="172" y="170" width="88" height="56" rx="8" fill="#60A5FA"/><circle cx="194" cy="198" r="9" fill="none" stroke="#0F172A" stroke-width="4"/><rect x="212" y="188" width="30" height="5" rx="2.5" fill="#0F172A" opacity="0.7"/><rect x="212" y="198" width="20" height="5" rx="2.5" fill="#0F172A" opacity="0.45"/></g>
                <g class="gc-card" transform="rotate(-3 161 140)"><rect x="113" y="110" width="96" height="60" rx="9" fill="#FFFFFF"/><path d="M141 140l12-14 12 14-12 14z" fill="#2563EB"/><rect x="168" y="128" width="32" height="6" rx="3" fill="#0F172A" opacity="0.85"/><rect x="168" y="140" width="24" height="5" rx="2.5" fill="#0F172A" opacity="0.45"/><rect x="125" y="158" width="70" height="4" rx="2" fill="#0F172A" opacity="0.25"/></g>
            </svg>
            @break

        @case('payWeb')
            <svg viewBox="0 0 320 260" role="img" aria-label="Pay your way" preserveAspectRatio="xMidYMid slice" class="h-full w-full">
                <title>Pay your way</title>
                <rect width="320" height="260" rx="18" fill="#0E1626"/>
                <g fill="none" stroke="#2563EB" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path class="ipay-link" d="M150 102 L150 78 L86 78 L86 69"/>
                    <path class="ipay-link" d="M160 102 L160 72 L152 72 L152 53"/>
                    <path class="ipay-link" d="M176 102 L176 80 L236 80 L236 69"/>
                    <path class="ipay-link" d="M188 122 L245 122"/>
                    <path class="ipay-link" d="M188 146 L214 146 L214 190 L231 190"/>
                    <path class="ipay-link" d="M168 158 L168 205"/>
                    <path class="ipay-link" d="M140 158 L140 200 L60 200 L60 205"/>
                    <path class="ipay-link" d="M132 130 L61 130"/>
                    <path class="ipay-link" d="M132 146 L110 146 L110 158"/>
                </g>
                <g class="ipay-node"><circle cx="70" cy="52" r="15" fill="#2563EB"/><path d="M66 44l-5 9h5l-4 9 10-12h-5l4-6z" fill="#FFFFFF"/></g>
                <g class="ipay-node"><circle cx="152" cy="36" r="15" fill="#FFFFFF"/><path d="M152 28l7 8-7 8-7-8z" fill="#0F172A"/></g>
                <g class="ipay-node"><circle cx="236" cy="52" r="15" fill="#1D4ED8"/><path d="M236 44l8 14h-16z" fill="#FFFFFF"/></g>
                <g class="ipay-node"><circle cx="262" cy="120" r="15" fill="#60A5FA"/><path d="M262 111l8 4.5v9l-8 4.5-8-4.5v-9z" fill="#0F172A"/></g>
                <g class="ipay-node"><circle cx="248" cy="196" r="15" fill="#FFFFFF"/><circle cx="248" cy="196" r="5.5" fill="none" stroke="#2563EB" stroke-width="3"/></g>
                <g class="ipay-node"><circle cx="168" cy="222" r="15" fill="#93C5FD"/><path d="M164 214l-5 9h5l-4 9 10-12h-5l4-6z" fill="#0F172A"/></g>
                <g class="ipay-node"><circle cx="60" cy="222" r="15" fill="#1E3A8A"/><path d="M60 213l2.5 6.5 6.5 2.5-6.5 2.5-2.5 6.5-2.5-6.5-6.5-2.5 6.5-2.5z" fill="#FFFFFF"/></g>
                <g class="ipay-node"><circle cx="44" cy="128" r="15" fill="#FFFFFF"/><path d="M36 128q4-5 8 0t8 0" fill="none" stroke="#0F172A" stroke-width="2.5" stroke-linecap="round"/></g>
                <g class="ipay-node"><rect x="84" y="160" width="52" height="34" rx="8" fill="#1D4ED8"/><path d="M99 176c0-5 8-5 8 0" fill="none" stroke="#FFFFFF" stroke-width="2"/><rect x="96" y="176" width="14" height="12" rx="2" fill="none" stroke="#FFFFFF" stroke-width="2"/><rect x="114" y="180" width="14" height="4" rx="2" fill="#FFFFFF" opacity="0.8"/></g>
                <g id="ipay-hub"><rect x="132" y="102" width="56" height="56" rx="14" fill="#0F172A" stroke="#2563EB" stroke-width="2"/><circle cx="146" cy="117" r="5" fill="none" stroke="#FFFFFF" stroke-width="3"/><rect x="158" y="112" width="10" height="10" rx="2" fill="#FFFFFF"/><circle cx="174" cy="117" r="5" fill="#2563EB"/><circle cx="147" cy="140" r="5" fill="#FFFFFF"/><rect x="159" y="135" width="10" height="10" rx="2" fill="none" stroke="#FFFFFF" stroke-width="2.5"/><circle cx="174" cy="140" r="4" fill="#60A5FA"/></g>
                <g fill="#60A5FA"><circle class="ipay-dot" cx="160" cy="130" r="2.5"/><circle class="ipay-dot" cx="160" cy="130" r="2.5"/><circle class="ipay-dot" cx="160" cy="130" r="2.5"/><circle class="ipay-dot" cx="160" cy="130" r="2.5"/><circle class="ipay-dot" cx="160" cy="130" r="2.5"/><circle class="ipay-dot" cx="160" cy="130" r="2.5"/><circle class="ipay-dot" cx="160" cy="130" r="2.5"/><circle class="ipay-dot" cx="160" cy="130" r="2.5"/><circle class="ipay-dot" cx="160" cy="130" r="2.5"/></g>
            </svg>
            @break

        @case('payout')
            <svg viewBox="0 0 320 260" role="img" aria-label="Receive instantly" preserveAspectRatio="xMidYMid slice" class="h-full w-full">
                <title>Receive instantly</title>
                <rect width="320" height="260" rx="18" fill="#0E1626"/>
                <g stroke="#22C55E" stroke-width="2.5" stroke-linecap="round">
                    <path class="isx-line-l" d="M48 56 L118 56"/>
                    <path class="isx-line-l" d="M58 70 L116 70"/>
                    <path class="isx-line-r" d="M202 56 L272 56"/>
                    <path class="isx-line-r" d="M204 70 L262 70"/>
                </g>
                <circle class="isx-dot" cx="84" cy="44" r="2.5" fill="#4ADE80"/>
                <circle class="isx-dot" cx="238" cy="84" r="2.5" fill="#4ADE80"/>
                <circle class="isx-dot" cx="262" cy="42" r="2" fill="#4ADE80"/>
                <circle class="isx-dot" cx="128" cy="92" r="1.8" fill="#4ADE80"/>
                <g id="isx-medal"><circle cx="160" cy="64" r="37" fill="none" stroke="#22C55E" stroke-width="2" opacity="0.35"/><circle cx="160" cy="64" r="30" fill="#22C55E"/><path id="isx-check" d="M147 65 L158 76 L176 52" fill="none" stroke="#FFFFFF" stroke-width="8" stroke-linecap="round" stroke-linejoin="round"/></g>
                <g id="isx-card">
                    <rect x="52" y="118" width="216" height="112" rx="14" fill="#FFFFFF"/>
                    <path d="M140 126 L108 162 L140 162 Z" fill="#93C5FD"/>
                    <path d="M140 126 L124 162 L140 162 Z" fill="#BFDBFE"/>
                    <path d="M140 126 L172 162 L140 162 Z" fill="#60A5FA"/>
                    <path d="M108 162 L140 196 L140 162 Z" fill="#2563EB"/>
                    <path d="M172 162 L140 196 L140 162 Z" fill="#1D4ED8"/>
                    <text x="68" y="216" font-size="14.5" font-weight="600" fill="#0F172A">Your Product</text>
                    <text x="252" y="170" font-size="28" font-weight="700" fill="#0F172A" text-anchor="end">$100</text>
                    <text x="252" y="188" font-size="10.5" fill="#64748B" text-anchor="end">Your Region</text>
                </g>
            </svg>
            @break
    @endswitch
</div>
