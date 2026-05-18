{{--
    Standard modal close button — a dark square with a white X. The single
    site-wide close control for modals (top-right placement, industry standard).
    Pass the close handler + any positioning via attributes:
      <x-close-button @click="open = false" />
      <x-close-button wire:click="$set('open', false)" class="absolute right-3 top-3" />
    Themes itself: dark button on light mode, a light-translucent button on dark.
--}}
<button {{ $attributes->merge(['type' => 'button', 'aria-label' => 'Close'])->class('flex h-8 w-8 shrink-0 items-center justify-center rounded-[6px] bg-zinc-900/55 text-white ring-1 ring-white/15 backdrop-blur-md transition-colors hover:bg-zinc-900/70 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/40 dark:bg-white/15 dark:hover:bg-white/25') }}>
    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
    </svg>
</button>
