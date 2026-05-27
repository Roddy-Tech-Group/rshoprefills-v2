{{-- Modern theme picker — the single light / dark / system control used
     site-wide (nav, footer, ...). A sun/moon trigger button that reveals a
     hover- and click-opened dropdown with Light / Dark / System options.
     Drives window.setTheme from the theme engine (partials/theme-engine) and
     stays in sync via the `theme-changed` window event.

     Props:
       drop = 'down' (default) | 'up'  — which way the panel opens (footer = up).
     Pass trigger button classes via attributes. --}}
@props(['drop' => 'down'])

@php
    $themeChoices = [
        ['value' => 'light',  'label' => 'Light',  'icon' => 'M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z'],
        ['value' => 'dark',   'label' => 'Dark',   'icon' => 'M21.752 15.002A9.72 9.72 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z'],
        ['value' => 'system', 'label' => 'Auto', 'icon' => 'M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0V12a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 12V5.25'],
    ];
    $panelPos = $drop === 'up' ? 'bottom-full mb-2' : 'top-full mt-2';
    $offStart = $drop === 'up' ? 'translate-y-1' : '-translate-y-1';
@endphp

<div
    x-data="{
        choice: (window.themeChoice ? window.themeChoice() : (localStorage.getItem('theme') || 'system')),
        dark: (window.themeIsDark ? window.themeIsDark() : document.documentElement.classList.contains('dark')),
        open: false,
        locked: false,
        pick(v) {
            this.choice = v;
            if (window.setTheme) window.setTheme(v);
            this.open = false;
            this.locked = false;
        },
    }"
    x-on:theme-changed.window="dark = $event.detail.dark; choice = (window.themeChoice ? window.themeChoice() : (localStorage.getItem('theme') || 'system'))"
    @mouseenter="open = true"
    @mouseleave="if (! locked) open = false"
    @click.outside="open = false; locked = false"
    @keydown.escape.window="open = false; locked = false"
    class="relative"
>
    <button
        type="button"
        @click="locked = ! locked; open = locked"
        :aria-expanded="open.toString()"
        aria-label="Theme"
        title="Theme"
        {{ $attributes->class('inline-flex items-center justify-center transition-colors') }}
    >
        {{-- Sun — light resolved --}}
        <svg x-show="! dark" class="h-[22px] w-[22px] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z"/>
        </svg>
        {{-- Moon — dark resolved --}}
        <svg x-show="dark" x-cloak class="h-[22px] w-[22px] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z"/>
        </svg>
    </button>

    <div
        x-show="open"
        x-cloak
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 {{ $offStart }}"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 {{ $offStart }}"
        class="absolute right-0 {{ $panelPos }} z-50 w-44 overflow-hidden rounded-[10px] bg-white shadow-xl shadow-zinc-900/10 ring-1 ring-zinc-200"
        role="menu"
        aria-label="Theme"
    >
        <div class="p-1.5">
            @foreach ($themeChoices as $opt)
                <button
                    type="button"
                    @click="pick('{{ $opt['value'] }}')"
                    :class="choice === '{{ $opt['value'] }}' ? 'bg-blue-50 text-blue-700' : 'text-zinc-700 hover:bg-zinc-100'"
                    class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-left text-sm font-medium transition-colors"
                    role="menuitemradio"
                    :aria-checked="(choice === '{{ $opt['value'] }}').toString()"
                >
                    <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $opt['icon'] }}"/>
                    </svg>
                    <span class="flex-1">{{ $opt['label'] }}</span>
                    <svg x-show="choice === '{{ $opt['value'] }}'" class="h-4 w-4 shrink-0 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                    </svg>
                </button>
            @endforeach
        </div>
    </div>
</div>
