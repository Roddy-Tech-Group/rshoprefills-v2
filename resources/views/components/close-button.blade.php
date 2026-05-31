{{--
    Standard modal close button — glass-dark square with a white X, GREY bg on hover.
    The single site-wide close control for modals (top-right placement, industry standard).
    Pass the close handler + any positioning via attributes:
      <x-close-button @click="open = false" />
      <x-close-button wire:click="$set('open', false)" class="absolute right-3 top-3" />
--}}
<button {{ $attributes->merge(['type' => 'button', 'aria-label' => 'Close'])->class('flex h-8 w-8 shrink-0 items-center justify-center rounded-[6px] bg-[#0a1729] text-white transition-colors hover:bg-[#1d3252] focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/40 dark:bg-white/20 dark:hover:bg-zinc-500') }}>
    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
    </svg>
</button>
