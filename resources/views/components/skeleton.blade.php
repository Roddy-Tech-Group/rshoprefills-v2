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
    $shapeClasses = match ($shape) {
        'circle' => 'rounded-full',
        'line'   => 'rounded',
        default  => 'rounded-lg',
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
