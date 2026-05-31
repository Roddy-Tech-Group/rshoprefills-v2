{{--
    Facebook/Instagram-style verification seal: a blue scalloped "star" seal with
    a rounded white outline on its edges and a white check in the centre. Inline
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
    {{-- Symmetric scalloped seal (Heroicons check-badge silhouette) filled with
         `color`, a thin white edge, and a clean white check on top. Colour via
         the `color` prop (blue for identity, green for email, etc.). --}}
    <path
        d="M8.603 3.799A4.49 4.49 0 0 1 12 2.25c1.357 0 2.573.6 3.397 1.549a4.49 4.49 0 0 1 3.498 1.307 4.491 4.491 0 0 1 1.307 3.497A4.49 4.49 0 0 1 21.75 12a4.49 4.49 0 0 1-1.549 3.397 4.491 4.491 0 0 1-1.307 3.497 4.491 4.491 0 0 1-3.497 1.307A4.49 4.49 0 0 1 12 21.75a4.49 4.49 0 0 1-3.397-1.549 4.49 4.49 0 0 1-3.498-1.306 4.491 4.491 0 0 1-1.307-3.498A4.49 4.49 0 0 1 2.25 12c0-1.357.6-2.573 1.549-3.397a4.49 4.49 0 0 1 1.307-3.497 4.49 4.49 0 0 1 3.497-1.307Z"
        fill="{{ $color }}"
        stroke="#ffffff"
        stroke-width="1"
        stroke-linejoin="round"
    />
    <path d="M8.7 12.5l2.2 2.2 4.6-5.2" fill="none" stroke="#ffffff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
</svg>
