{{--
    Facebook/Instagram-style verification seal: a blue circular seal with a thin
    white outline and a white check in the centre. Inline
    SVG so it stays crisp at any size and pops on any background. Render only when
    the user's KYC is verified - gate at the call site. Size via the class
    attribute, e.g. <x-ui.verified-badge class="h-4 w-4" />.
--}}
@props(['title' => 'Identity verified', 'color' => '#2563eb'])
<svg
    {{ $attributes->merge(['class' => 'inline-block h-5 w-5 shrink-0']) }}
    viewBox="0 0 24 24"
    role="img"
    aria-label="{{ $title }}"
>
    <title>{{ $title }}</title>
    {{-- Perfect circle seal filled with `color`, a thin white edge, and a clean
         white check on top. Colour via the `color` prop (blue for identity,
         green for email, etc.). --}}
    <circle
        cx="12"
        cy="12"
        r="9.75"
        fill="{{ $color }}"
        stroke="#ffffff"
        stroke-width="1"
    />
    <path d="M8.7 12.5l2.2 2.2 4.6-5.2" fill="none" stroke="#ffffff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
</svg>
