{{-- Animated failure cross: a red X that pops in with a small wobble - the
     negative counterpart to <x-success-tick>. Shared by the checkout and
     wallet-funding payment modals so failures read as clearly as successes.
     Pure CSS; runs once whenever the element is shown. --}}
<div {{ $attributes->merge(['class' => 'relative mx-auto inline-block']) }}>
    <span class="rshop-cross flex h-16 w-16 items-center justify-center rounded-full bg-red-500 shadow-lg shadow-red-500/30">
        <svg class="h-9 w-9 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
        </svg>
    </span>
</div>

<style>
    @keyframes rshop-cross-pop {
        0%   { transform: scale(0) rotate(-8deg); }
        60%  { transform: scale(1.15) rotate(4deg); }
        100% { transform: scale(1) rotate(0); }
    }
    .rshop-cross {
        animation: rshop-cross-pop 0.5s cubic-bezier(0.34, 1.56, 0.64, 1) both;
    }
    @media (prefers-reduced-motion: reduce) {
        .rshop-cross { animation: none; }
    }
</style>
