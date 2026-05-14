@props([
    'name',
    'priceRange' => null,
    'href' => '#',
    'cardClass' => 'bg-white',
])

{{--
    Brand card for the home rows. The slot is the visual that fills the card
    (logo, wordmark, etc.). Name + price range render below.
--}}
<li data-reveal-item class="w-44 shrink-0 sm:w-auto">
    <a href="{{ $href }}" class="group block focus:outline-none">
        <div {{ $attributes->merge(['class' => 'relative flex aspect-[16/10] items-center justify-center overflow-hidden rounded-2xl ring-1 ring-zinc-200 shadow-sm transition-all group-hover:-translate-y-0.5 group-hover:shadow-md group-hover:ring-zinc-300 group-focus-visible:ring-2 group-focus-visible:ring-blue-500/40 '.$cardClass]) }}>
            {{ $slot }}
        </div>
        <div class="mt-2.5">
            <p class="truncate text-base font-semibold text-zinc-900">{{ $name }}</p>
            @if ($priceRange)
                <p class="mt-0.5 text-base text-zinc-500">{{ $priceRange }}</p>
            @endif
        </div>
    </a>
</li>
