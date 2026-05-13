{{--
    Top utility bar — locale + help. Desktop only; the mobile drawer carries
    these controls on small screens. The country/language buttons open the
    shared <x-nav.locale-modal /> popup.
--}}
<div class="bg-zinc-50 border-b border-zinc-100">
    <div class="max-w-screen-xl mx-auto px-4 sm:px-6 lg:px-8">
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
                <img src="{{ asset('assets/World%20Global.png') }}" alt="" class="w-4 h-4 shrink-0 opacity-80" />
                <span x-text="language">English</span>
                <svg class="w-3 h-3 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            {{-- Help --}}
            <a href="#" class="flex items-center gap-1.5 px-2.5 py-1 rounded-md text-[13px] font-medium text-zinc-900 hover:bg-zinc-100 transition-colors duration-150 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/40">
                <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9 5.25h.008v.008H12v-.008z" />
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
