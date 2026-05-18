@props([
    // How many placeholder cards to render. Match a typical first page of results.
    'count' => 10,
])

{{--
    A full responsive grid of product-card skeletons. Drop this in behind a
    wire:loading toggle or an x-show="navigating" overlay on any catalog grid
    (gift cards / eSIMs / top-ups / bills).

    Zero-CLS: the grid columns + gaps mirror the real catalog grid exactly.
    skeleton-stagger cascades each card in (style="--i") for a smooth reveal.
--}}
<div
    {{ $attributes->class('skeleton-stagger grid grid-cols-2 gap-x-4 gap-y-6 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5') }}
    aria-hidden="true"
>
    @for ($i = 0; $i < $count; $i++)
        <x-skeletons.product-card style="--i: {{ $i }}" />
    @endfor
</div>
