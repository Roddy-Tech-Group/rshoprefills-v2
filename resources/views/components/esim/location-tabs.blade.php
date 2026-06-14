@php
    // eSIM location tabs (Popular / Local / Regional / Global / All) with icons,
    // spread evenly across the full width. Relies on Alpine `locTab` in the parent
    // scope. Shared by the eSIM landing and the country store so they're identical.
    $tabs = [
        'popular'  => ['label' => 'Popular',  'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M15.59 14.37a6 6 0 01-5.84 7.38v-4.8m5.84-2.58a14.98 14.98 0 006.16-12.12A14.98 14.98 0 009.631 8.41m5.96 5.96a14.926 14.926 0 01-5.841 2.58m-.119-8.54a6 6 0 00-7.381 5.84h4.8m2.581-5.84a14.927 14.927 0 00-2.58 5.84m2.699 2.7c-.103.021-.207.041-.311.06a15.09 15.09 0 01-2.448-2.448 14.9 14.9 0 01.06-.312m-2.24 2.39a4.493 4.493 0 00-1.757 4.306 4.493 4.493 0 004.306-1.758M16.5 9a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z"/>'],
        'local'    => ['label' => 'Local',    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/>'],
        'regional' => ['label' => 'Regional', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 6.75V15m6-6v8.25m.503 3.498l4.875-2.437c.381-.19.622-.58.622-1.006V4.82c0-.836-.88-1.38-1.628-1.006l-3.869 1.934c-.317.159-.69.159-1.006 0L9.503 3.252a1.125 1.125 0 00-1.006 0L3.622 5.689C3.24 5.88 3 6.27 3 6.695V19.18c0 .836.88 1.38 1.628 1.006l3.869-1.934c.317-.159.69-.159 1.006 0l4.994 2.497c.317.158.69.158 1.006 0z"/>'],
        'global'   => ['label' => 'Global',   'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9 9 0 100-18 9 9 0 000 18zm0 0c2.5 0 4.5-4.03 4.5-9S14.5 3 12 3 7.5 7.03 7.5 12s2 9 4.5 9zM3.6 9h16.8M3.6 15h16.8"/>'],
        'all'      => ['label' => 'All',      'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/>'],
    ];
@endphp

{{-- Equal-width tabs (flex-1) so all five always fit on one line - no
     horizontal scroll, no layout shift - at a compact 12px. --}}
<div class="flex items-stretch border-b-2 border-zinc-900 dark:border-white/20" role="tablist">
    @foreach ($tabs as $key => $tab)
        <button
            type="button"
            @click="locTab = '{{ $key }}'"
            :class="locTab === '{{ $key }}' ? 'border-blue-600 text-zinc-900 dark:text-white' : 'border-transparent text-zinc-500 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white'"
            class="-mb-0.5 flex flex-1 min-w-0 items-center justify-center gap-1.5 border-b-4 px-1 py-2.5 text-[12px] font-bold transition-colors focus:outline-none"
        >
            <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">{!! $tab['icon'] !!}</svg>
            <span class="truncate">{{ $tab['label'] }}</span>
        </button>
    @endforeach
</div>
