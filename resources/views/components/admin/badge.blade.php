{{--
    Canonical badge for the admin surface. ONE component, six tones, used by
    every status / type chip across the system so the dashboard, list pages,
    detail pages, content CMS and rates pages never disagree on what
    "PENDING" or "PAID" looks like.

    Visual reference: the "Latest Users" + "Latest Transactions" rows on the
    admin dashboard (resources/views/admin/dashboard.blade.php).

      <x-admin.badge tone="emerald">Active</x-admin.badge>
      <x-admin.badge tone="amber">Pending</x-admin.badge>
      <x-admin.badge tone="red">Banned</x-admin.badge>
      <x-admin.badge tone="blue">Payment - Flutterwave</x-admin.badge>
      <x-admin.badge tone="purple">Wallet - Credit</x-admin.badge>
      <x-admin.badge tone="zinc">Inactive</x-admin.badge>

    The default `tone` is `zinc` so a typo lands on the neutral look instead of
    silently rendering an untoned chip.
--}}
@props([
    'tone' => 'zinc',
])

@php
    $palette = [
        'emerald' => 'bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-500/15 dark:text-emerald-300 dark:ring-emerald-500/30',
        'amber'   => 'bg-amber-50 text-amber-700 ring-amber-200 dark:bg-amber-500/15 dark:text-amber-300 dark:ring-amber-500/30',
        'red'     => 'bg-red-50 text-red-700 ring-red-200 dark:bg-red-500/15 dark:text-red-300 dark:ring-red-500/30',
        'blue'    => 'bg-blue-50 text-blue-700 ring-blue-200 dark:bg-blue-500/15 dark:text-blue-300 dark:ring-blue-500/30',
        'purple'  => 'bg-purple-50 text-purple-700 ring-purple-200 dark:bg-purple-500/15 dark:text-purple-300 dark:ring-purple-500/30',
        'zinc'    => 'bg-zinc-100 text-zinc-700 ring-zinc-200 dark:bg-zinc-500/15 dark:text-zinc-300 dark:ring-zinc-500/30',
    ];
    $toneClasses = $palette[$tone] ?? $palette['zinc'];
@endphp

<span {{ $attributes->class([
    'inline-flex w-fit items-center whitespace-nowrap rounded-[5px] px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide ring-1',
    $toneClasses,
]) }}>{{ $slot }}</span>
