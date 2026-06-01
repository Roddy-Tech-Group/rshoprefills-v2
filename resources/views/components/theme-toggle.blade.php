{{-- Global theme picker - two variants, one component, one source of truth.

       variant="dropdown" (default): sun/moon icon trigger that pops a panel
         with Light / Dark / Auto options. Use in nav bars and other tight
         spaces where an inline segmented control would crowd the layout.
         Supports `drop="down"` (default) or `drop="up"` for the panel side.

       variant="segmented": always-visible inline pill with Light / Dark /
         Auto as three segments inside a blue-bordered chip. Use in the
         footer and any surface that has room for a labelled control.

     Both share the same window.setTheme backing and stay in sync via the
     `theme-changed` window event, so picking a theme from one updates any
     other instance on the page instantly. --}}
@props([
    'variant' => 'dropdown',
    'drop'    => 'down',
])

@php
    // Each mode maps to its hero PNG. Filenames carry the raw asset names so
    // rawurlencode handles the spaces / mixed casing at render time. The
    // segmented + dropdown variants both consume this array.
    $themeChoices = [
        ['value' => 'light',  'label' => 'Light', 'image' => 'Light mode respects theme.webp'],
        ['value' => 'dark',   'label' => 'Dark',  'image' => 'Dark mode respects light and dark mode.webp'],
        ['value' => 'system', 'label' => 'Auto',  'image' => 'Auto Mode.webp'],
    ];
@endphp

@if ($variant === 'segmented')
    {{-- ── Segmented chip (Light / Dark / Auto) ─────────────────────── --}}
    <div
        x-data="{
            choice: (window.themeChoice ? window.themeChoice() : (localStorage.getItem('theme') || 'system')),
            pick(v) {
                this.choice = v;
                if (window.setTheme) { window.setTheme(v); }
            },
        }"
        x-on:theme-changed.window="choice = (window.themeChoice ? window.themeChoice() : (localStorage.getItem('theme') || 'system'))"
        {{ $attributes->class('inline-flex w-fit items-center gap-1 rounded-[10px] border-2 border-blue-400 p-0.5 dark:border-blue-500/60') }}
        role="radiogroup"
        aria-label="Theme"
    >
        @foreach ($themeChoices as $opt)
            <button
                type="button"
                @click="pick('{{ $opt['value'] }}')"
                :class="choice === '{{ $opt['value'] }}'
                    ? 'bg-zinc-200 dark:bg-black dark:ring-1 dark:ring-white/10'
                    : 'hover:bg-zinc-100 dark:hover:bg-white/5'"
                class="flex h-8 w-8 items-center justify-center rounded-[7px] transition-colors duration-150"
                :aria-checked="(choice === '{{ $opt['value'] }}').toString()"
                role="radio"
                aria-label="{{ $opt['label'] }}"
                title="{{ $opt['label'] }}"
            >
                {{-- brightness-0 strips the PNG's own colour to pure black for
                     light mode; dark:invert then flips it to pure white in
                     dark mode. That's the "respects theme" behaviour the
                     filename promises - icons stay legible on both surfaces
                     without needing two separate assets. --}}
                <img src="{{ asset('assets/' . rawurlencode($opt['image'])) }}" alt="" class="h-5 w-5 object-contain brightness-0 dark:invert" loading="lazy">
            </button>
        @endforeach
    </div>
@else
    {{-- ── Dropdown (sun/moon trigger + popup panel) ─────────────────── --}}
    @php
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
                if (window.setTheme) { window.setTheme(v); }
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
            {{-- Trigger shows the icon that matches the user's CHOICE (not the
                 resolved theme), so the Auto icon is visible while in system
                 mode. brightness-0 + dark:invert keeps the monochrome webp
                 legible on whichever chrome it sits on. --}}
            <img
                x-show="choice === 'light'"
                src="{{ asset('assets/' . rawurlencode('Light mode respects theme.webp')) }}"
                alt=""
                class="h-7 w-7 shrink-0 object-contain brightness-0 dark:invert"
                loading="lazy"
            >
            <img
                x-show="choice === 'dark'"
                x-cloak
                src="{{ asset('assets/' . rawurlencode('Dark mode respects light and dark mode.webp')) }}"
                alt=""
                class="h-7 w-7 shrink-0 object-contain brightness-0 dark:invert"
                loading="lazy"
            >
            <img
                x-show="choice === 'system'"
                x-cloak
                src="{{ asset('assets/' . rawurlencode('Auto Mode.webp')) }}"
                alt=""
                class="h-7 w-7 shrink-0 object-contain brightness-0 dark:invert"
                loading="lazy"
            >
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
            class="absolute right-0 {{ $panelPos }} z-50 overflow-hidden rounded-[5px] bg-white/70 backdrop-blur-md shadow-xl shadow-zinc-900/10 ring-2 ring-blue-400 dark:bg-[#1d3252]/70 dark:ring-blue-500/60"
            role="menu"
            aria-label="Theme"
        >
            <div class="p-1">
                @foreach ($themeChoices as $opt)
                    <button
                        type="button"
                        @click="pick('{{ $opt['value'] }}')"
                        :class="choice === '{{ $opt['value'] }}'
                            ? 'bg-blue-600 text-white'
                            : 'text-zinc-900 hover:bg-zinc-100 dark:text-zinc-100 dark:hover:bg-[#26416b]'"
                        class="flex w-full items-center justify-center gap-2.5 rounded-[5px] px-3 py-2 text-left text-sm font-medium transition-colors sm:justify-start sm:py-1.5"
                        role="menuitemradio"
                        :aria-checked="(choice === '{{ $opt['value'] }}').toString()"
                    >
                        <img src="{{ asset('assets/' . rawurlencode($opt['image'])) }}" alt="" class="h-7 w-7 shrink-0 object-contain brightness-0 dark:invert sm:h-6 sm:w-6" loading="lazy">
                        <span class="hidden sm:inline">{{ $opt['label'] }}</span>
                    </button>
                @endforeach
            </div>
        </div>
    </div>
@endif
