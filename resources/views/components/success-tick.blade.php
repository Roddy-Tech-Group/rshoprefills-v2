{{-- Animated success tick: a bouncy emerald check that pops in with a confetti
     burst. Shared by the order-complete page and the wallet-funding / checkout
     payment-success modals so every "it worked" moment feels identical. Pure CSS
     + inline custom properties; the animation runs once whenever the element is
     shown (incl. when a modal flips its success state into view). --}}
<div {{ $attributes->merge(['class' => 'relative mx-auto inline-block']) }}>
    {{-- Confetti particles - 14 around the tick, angles spread evenly, mixed
         shapes + a festive colour palette. --}}
    <span class="rshop-confetti"          style="--rshop-angle:   0deg; --rshop-distance: 70px; --rshop-color:#ef4444;"></span>
    <span class="rshop-confetti is-round" style="--rshop-angle:  26deg; --rshop-distance: 80px; --rshop-color:#f97316;"></span>
    <span class="rshop-confetti is-bar"   style="--rshop-angle:  52deg; --rshop-distance: 65px; --rshop-color:#fbbf24;"></span>
    <span class="rshop-confetti"          style="--rshop-angle:  78deg; --rshop-distance: 85px; --rshop-color:#84cc16;"></span>
    <span class="rshop-confetti is-round" style="--rshop-angle: 104deg; --rshop-distance: 75px; --rshop-color:#10b981;"></span>
    <span class="rshop-confetti is-bar"   style="--rshop-angle: 130deg; --rshop-distance: 90px; --rshop-color:#06b6d4;"></span>
    <span class="rshop-confetti"          style="--rshop-angle: 156deg; --rshop-distance: 70px; --rshop-color:#3b82f6;"></span>
    <span class="rshop-confetti is-round" style="--rshop-angle: 182deg; --rshop-distance: 80px; --rshop-color:#8b5cf6;"></span>
    <span class="rshop-confetti is-bar"   style="--rshop-angle: 208deg; --rshop-distance: 65px; --rshop-color:#ec4899;"></span>
    <span class="rshop-confetti"          style="--rshop-angle: 234deg; --rshop-distance: 85px; --rshop-color:#f43f5e;"></span>
    <span class="rshop-confetti is-round" style="--rshop-angle: 260deg; --rshop-distance: 75px; --rshop-color:#fbbf24;"></span>
    <span class="rshop-confetti is-bar"   style="--rshop-angle: 286deg; --rshop-distance: 90px; --rshop-color:#22c55e;"></span>
    <span class="rshop-confetti"          style="--rshop-angle: 312deg; --rshop-distance: 70px; --rshop-color:#0ea5e9;"></span>
    <span class="rshop-confetti is-round" style="--rshop-angle: 338deg; --rshop-distance: 80px; --rshop-color:#a855f7;"></span>

    {{-- The tick itself --}}
    <span class="rshop-tick relative flex h-16 w-16 items-center justify-center rounded-full bg-emerald-500 shadow-lg shadow-emerald-500/30">
        <svg class="h-9 w-9 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
        </svg>
    </span>
</div>

<style>
    @keyframes rshop-tick-pop {
        0%   { transform: scale(0); }
        60%  { transform: scale(1.2); }
        100% { transform: scale(1); }
    }
    @keyframes rshop-confetti-burst {
        0%   { transform: translate(-50%, -50%) rotate(var(--rshop-angle, 0deg)) translateX(0) scale(1); opacity: 1; }
        80%  { opacity: 1; }
        100% { transform: translate(-50%, -50%) rotate(var(--rshop-angle, 0deg)) translateX(var(--rshop-distance, 70px)) scale(0.3); opacity: 0; }
    }
    .rshop-tick {
        animation: rshop-tick-pop 0.55s cubic-bezier(0.34, 1.56, 0.64, 1) both;
    }
    .rshop-confetti {
        position: absolute;
        top: 50%;
        left: 50%;
        width: 8px;
        height: 8px;
        background: var(--rshop-color, #fbbf24);
        border-radius: 2px;
        pointer-events: none;
        opacity: 0;
        animation: rshop-confetti-burst 1.1s ease-out 0.15s forwards;
    }
    .rshop-confetti.is-round { border-radius: 9999px; }
    .rshop-confetti.is-bar   { width: 10px; height: 3px; border-radius: 2px; }
    @media (prefers-reduced-motion: reduce) {
        .rshop-tick { animation: none; }
        .rshop-confetti { display: none; }
    }
</style>
