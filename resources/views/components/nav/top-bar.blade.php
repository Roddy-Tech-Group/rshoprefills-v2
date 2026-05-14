{{--
    Top utility bar — locale + help. Desktop only; the mobile drawer carries
    these controls on small screens. The country/language buttons open the
    shared <x-nav.locale-modal /> popup.
--}}
<div class="bg-zinc-50 border-b border-zinc-100">
    <div class="max-w-[1350px] mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-end h-9 gap-1">

            {{-- Country --}}
            <button
                type="button"
                @click="localeModalOpen = true"
                class="flex items-center gap-1.5 px-2.5 py-1 rounded-md text-[13px] font-medium text-zinc-900 hover:bg-zinc-100 transition-colors duration-150 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/40"
            >
                <span class="text-base leading-none" aria-hidden="true" x-text="countryFlag">🇨🇲</span>
                <span x-text="country">Cameroon</span>
                <svg class="w-3 h-3 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            {{-- Language --}}
            <button
                type="button"
                @click="localeModalOpen = true"
                class="flex items-center gap-1.5 px-2.5 py-1 rounded-md text-[13px] font-medium text-zinc-900 hover:bg-zinc-100 transition-colors duration-150 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/40"
            >
                <svg viewBox="0 0 24 24" class="w-4 h-4 shrink-0" fill="currentColor" aria-hidden="true">
                    <path d="M22.55 18.71a1 1 0 0 0 -1.24 0.61 4 4 0 0 1 -6.6 1.58 0.51 0.51 0 0 1 -0.15 -0.35 0.47 0.47 0 0 1 0.15 -0.35l1.51 -1.52a0.48 0.48 0 0 0 0.11 -0.53 0.49 0.49 0 0 0 -0.45 -0.3h-4.37a0.49 0.49 0 0 0 -0.49 0.49v4.37a0.5 0.5 0 0 0 0.3 0.45 0.51 0.51 0 0 0 0.54 -0.11l0.77 -0.77a0.5 0.5 0 0 1 0.69 0A6 6 0 0 0 23.16 20a1 1 0 0 0 -0.61 -1.29Z"/>
                    <path d="M9 23.13a0.45 0.45 0 0 0 0.37 -0.08 0.42 0.42 0 0 0 0.17 -0.34v-1.1a0.48 0.48 0 0 0 -0.35 -0.47 9.82 9.82 0 0 1 -1.61 -0.64v-1.28a2.47 2.47 0 0 1 0.87 -1.88 4.4 4.4 0 0 0 -2.82 -7.78H2.46A9.81 9.81 0 0 1 12 2a9.69 9.69 0 0 1 5.53 1.72h-3.32a2.7 2.7 0 1 0 0 5.39 2.52 2.52 0 0 1 1.84 0.82 6.75 6.75 0 0 1 1.19 -0.1 7.39 7.39 0 0 1 4.23 1.33 0.32 0.32 0 0 0 0.41 0 2 2 0 0 1 1.39 -0.57 0.37 0.37 0 0 0 0.28 -0.13 0.36 0.36 0 0 0 0.09 -0.3 11.76 11.76 0 0 0 -23.4 1.6C0.24 13 0.76 21 9 23.13Z"/>
                    <path d="M23.76 12.47a0.48 0.48 0 0 0 -0.3 -0.45 0.49 0.49 0 0 0 -0.54 0.1l-0.83 0.83a0.49 0.49 0 0 1 -0.69 0 6 6 0 0 0 -9.82 2.35 1 1 0 0 0 0.61 1.24 1 1 0 0 0 1.24 -0.61 4 4 0 0 1 6.57 -1.6 0.49 0.49 0 0 1 0.15 0.35 0.52 0.52 0 0 1 -0.15 0.32l-1.45 1.45a0.49 0.49 0 0 0 0.34 0.84h4.37a0.49 0.49 0 0 0 0.49 -0.49Z"/>
                </svg>
                <span x-text="language">English</span>
                <svg class="w-3 h-3 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            {{-- Help --}}
            <a href="#" class="flex items-center gap-1.5 px-2.5 py-1 rounded-md text-[13px] font-medium text-zinc-900 hover:bg-zinc-100 transition-colors duration-150 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/40">
                <svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M12 2c5.5228 0 10 4.47715 10 10 0 5.5228 -4.4772 10 -10 10 -5.52285 0 -10 -4.4772 -10 -10C2 6.47715 6.47715 2 12 2m-2.5 9v2H11v2H9.5v2h5v-2H13v-3c0 -0.5523 -0.4477 -1 -1 -1zm2.25 -4c-0.6904 0 -1.25 0.55964 -1.25 1.25s0.5596 1.25 1.25 1.25S13 8.94036 13 8.25 12.4404 7 11.75 7"/>
                </svg>
                <span>Help</span>
            </a>

            {{-- Theme toggle (UI only — not wired up yet) --}}
            <button
                type="button"
                class="flex h-9 w-9 items-center justify-center rounded-md text-zinc-900 transition-colors duration-150 hover:bg-zinc-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/40"
                aria-label="Toggle theme"
            >
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" />
                </svg>
            </button>

        </div>
    </div>
</div>
