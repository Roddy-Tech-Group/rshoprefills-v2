@props([
    'name',
    'priceRange' => null,
    'href' => '#',
    'cardClass' => 'bg-[#ffffff]',
])

{{--
    Brand card for the home rows. The slot is the visual that fills the card
    (logo, wordmark, etc.). Name + price range render below. The tile carries a
    pointer-tracked 3D tilt + glare (cardTilt() in app.js) for a premium feel.
--}}
<li data-reveal-item class="w-32 shrink-0 sm:w-auto">
    <a href="{{ $href }}" class="group block focus:outline-none">
        <div
            {{ $attributes->merge(['class' => 'relative flex aspect-[16/10] items-center justify-center overflow-hidden rounded-[15px] ring-1 ring-zinc-200 shadow-sm group-focus-visible:ring-2 group-focus-visible:ring-blue-500/40 '.$cardClass]) }}
        >
            {{ $slot }}
        </div>
        <div class="mt-2">
            <p class="truncate text-sm font-semibold text-zinc-900 sm:text-base">{{ $name }}</p>
            @if ($priceRange)
                <p class="mt-0.5 text-xs text-zinc-600">{{ $priceRange }}</p>
            @endif
        </div>
    </a>
</li>
