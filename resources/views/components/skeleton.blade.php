@props([
    // Shape: line | circle | rect (default rect — generic block)
    'shape' => 'rect',
    // Width / height — accept Tailwind utility strings (e.g. "w-32", "h-4") or raw values.
    // Provide either as props or stack your own Tailwind classes via $attributes.
    'w' => null,
    'h' => null,
    // Roundness override. Defaults match shape.
    'rounded' => null,
])

@php
    // Default radius is a uniform 6px for line/rect skeletons. Circles stay fully round.
    // Shaped placeholders (logo tiles, card art) override `rounded` per-instance to mirror
    // the radius of the real content they stand in for.
    $shapeClasses = match ($shape) {
        'circle' => 'rounded-full',
        default  => 'rounded-[6px]',
    };
    $roundedClass = $rounded ?? $shapeClasses;
    $sizeClasses  = trim(($w ? $w.' ' : '').($h ? $h : ''));
@endphp

<span
    {{ $attributes->class([
        'skeleton block',
        $roundedClass,
        $sizeClasses,
    ]) }}
    aria-hidden="true"
></span>
