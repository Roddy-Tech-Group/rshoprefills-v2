{{-- Global pill / badge. The site-wide chip style: rounded-full, a neutral
     theme-aware surface with a 1px border, and an optional leading status dot
     that carries the colour (matching the reference badge design). Use it for
     tags, status chips and labels everywhere so they stay consistent.

     Usage:
       <x-ui.pill>Data only</x-ui.pill>
       <x-ui.pill dot="emerald">Completed</x-ui.pill>
       <x-ui.pill dot="blue" class="uppercase tracking-wider">Explorer</x-ui.pill>

     Props:
       dot - optional status dot colour: emerald|green, blue, amber, red,
             purple, zinc|grey (omit for no dot). --}}
@props([
    'dot' => null,
])

@php
    $dotColors = [
        'emerald' => 'bg-emerald-500',
        'green' => 'bg-emerald-500',
        'blue' => 'bg-blue-500',
        'amber' => 'bg-amber-500',
        'red' => 'bg-red-500',
        'purple' => 'bg-purple-500',
        'zinc' => 'bg-zinc-400',
        'grey' => 'bg-zinc-400',
        'gray' => 'bg-zinc-400',
    ];
    $dotClass = $dot ? ($dotColors[$dot] ?? $dotColors['zinc']) : null;
@endphp

<span {{ $attributes->class('inline-flex items-center gap-1.5 rounded-full bg-white px-2.5 py-1 text-[11px] font-semibold leading-none text-zinc-700 ring-1 ring-zinc-200 dark:text-zinc-200 dark:ring-[#24364f]') }}>
    @if ($dotClass)
        <span class="h-1.5 w-1.5 shrink-0 rounded-full {{ $dotClass }}" aria-hidden="true"></span>
    @endif
    {{ $slot }}
</span>
