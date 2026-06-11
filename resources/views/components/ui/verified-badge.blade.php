{{--
    X/Twitter-style scalloped verification seal with a white check. Inline SVG so
    it stays crisp at any size and pops on any background. Render only when the
    relevant verification is in place - gate at the call site. Colour via the
    `color` prop (blue #1D9BF0 for identity by default, green for email). Size via
    the class attribute, e.g. <x-ui.verified-badge class="h-4 w-4" />.
--}}
@props(['title' => 'Identity verified', 'color' => '#1D9BF0'])
<svg
    {{ $attributes->merge(['class' => 'inline-block h-5 w-5 shrink-0']) }}
    viewBox="0 0 24 24"
    role="img"
    aria-label="{{ $title }}"
>
    <title>{{ $title }}</title>
    <polygon
        points="22,12 20.31,14.23 20.66,17 18.08,18.08 17,20.66 14.23,20.31 12,22 9.77,20.31 7,20.66 5.92,18.08 3.34,17 3.69,14.23 2,12 3.69,9.77 3.34,7 5.92,5.92 7,3.34 9.77,3.69 12,2 14.23,3.69 17,3.34 18.08,5.92 20.66,7 20.31,9.77"
        fill="{{ $color }}"
        stroke="{{ $color }}"
        stroke-width="1.8"
        stroke-linejoin="round"
    />
    {{-- Check colour follows the theme: white tick in light mode, black tick in
         dark mode (the seal stays blue in both). currentColor is driven by the
         text-* classes so it flips with the .dark root class. --}}
    <path d="M7.4 12.6l3.1 3.1L16.8 9.2" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" class="text-white dark:text-black"/>
</svg>
