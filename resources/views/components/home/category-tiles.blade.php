{{--
    Quick-access category tiles. Pill-shaped, content-sized, single-active state.
    Default: Gift Cards (index 0) is black/active. Hover any tile to activate it;
    leaving the row reverts to the first tile.
--}}
<section aria-label="Browse categories">
    <div class="-mx-4 overflow-x-auto px-4 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden sm:mx-0 sm:overflow-visible sm:px-0">
        <ul
            x-data="{ active: -1 }"
            @mouseleave="active = -1"
            data-reveal-group
            class="flex w-max gap-3 sm:w-full sm:flex-wrap sm:justify-center sm:gap-3 lg:gap-4"
        >

            {{-- Gift Cards (always black, acts as the persistent active/default state) --}}
            <li data-reveal-item class="shrink-0">
                <a
                    href="#"
                    class="inline-flex items-center gap-3 rounded-[25px] bg-zinc-900 px-6 py-2 ring-1 ring-zinc-900 transition-all duration-300 ease-out will-change-transform hover:-translate-y-0.5 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/40"
                >
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-white/10 text-white ring-1 ring-white/20">
                        <svg viewBox="0 0 24 24" class="h-[25px] w-[25px]" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M3 8a3 3 0 0 1 3 -3h12a3 3 0 0 1 3 3v8a3 3 0 0 1 -3 3H6a3 3 0 0 1 -3 -3z"/>
                            <path d="m7 16 3 -3 3 3"/>
                            <path d="M8 13c-0.789 0 -2 -0.672 -2 -1.5S6.711 10 7.5 10c1.128 -0.02 2.077 1.17 2.5 3 0.423 -1.83 1.372 -3.02 2.5 -3 0.789 0 1.5 0.672 1.5 1.5S12.789 13 12 13H8z"/>
                        </svg>
                    </span>
                    <span class="text-base font-semibold text-white">Gift Cards</span>
                </a>
            </li>

            {{-- eSIMs --}}
            <li data-reveal-item class="shrink-0">
                <a
                    href="#"
                    @mouseenter="active = 1"
                    :class="active === 1 ? 'bg-zinc-900 ring-zinc-900' : 'bg-white ring-zinc-200 hover:ring-zinc-300'"
                    class="inline-flex items-center gap-3 rounded-[25px] px-6 py-2 ring-1 transition-all duration-300 ease-out will-change-transform hover:-translate-y-0.5 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/40"
                >
                    <span
                        :class="active === 1 ? 'bg-white/10 text-white ring-white/20' : 'bg-blue-50 text-blue-600 ring-blue-100'"
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl ring-1 transition-colors duration-300 ease-out"
                    >
                        <svg viewBox="0 0 24 24" class="h-[25px] w-[25px] transition-colors duration-300 ease-out" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M6 3h8.5L19 7.5V20a1 1 0 0 1 -1 1H6a1 1 0 0 1 -1 -1V4a1 1 0 0 1 1 -1z"/>
                            <path d="M9 11h3v6"/>
                            <path d="M15 17v0.01"/>
                            <path d="M15 14v0.01"/>
                            <path d="M15 11v0.01"/>
                            <path d="M9 14v0.01"/>
                            <path d="M9 17v0.01"/>
                        </svg>
                    </span>
                    <span :class="active === 1 ? 'text-white' : 'text-zinc-800'" class="text-base font-semibold transition-colors duration-300 ease-out">eSIMs</span>
                </a>
            </li>

            {{-- Mobile Top-ups --}}
            <li data-reveal-item class="shrink-0">
                <a
                    href="#"
                    @mouseenter="active = 2"
                    :class="active === 2 ? 'bg-zinc-900 ring-zinc-900' : 'bg-white ring-zinc-200 hover:ring-zinc-300'"
                    class="inline-flex items-center gap-3 rounded-[25px] px-6 py-2 ring-1 transition-all duration-300 ease-out will-change-transform hover:-translate-y-0.5 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/40"
                >
                    <span
                        :class="active === 2 ? 'bg-white/10 text-white ring-white/20' : 'bg-blue-50 text-blue-600 ring-blue-100'"
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl ring-1 transition-colors duration-300 ease-out"
                    >
                        <svg viewBox="0 0 14 14" class="h-[25px] w-[25px] transition-colors duration-300 ease-out" fill="none" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M10.5 0.5h-7c-0.55228 0 -1 0.447715 -1 1v11c0 0.5523 0.44772 1 1 1h7c0.5523 0 1 -0.4477 1 -1v-11c0 -0.552285 -0.4477 -1 -1 -1Z"/>
                            <path d="M6.5 11h1"/>
                        </svg>
                    </span>
                    <span :class="active === 2 ? 'text-white' : 'text-zinc-800'" class="text-base font-semibold transition-colors duration-300 ease-out">Mobile Top-ups</span>
                </a>
            </li>

            {{-- Bill Payments --}}
            <li data-reveal-item class="shrink-0">
                <a
                    href="#"
                    @mouseenter="active = 3"
                    :class="active === 3 ? 'bg-zinc-900 ring-zinc-900' : 'bg-white ring-zinc-200 hover:ring-zinc-300'"
                    class="inline-flex items-center gap-3 rounded-[25px] px-6 py-2 ring-1 transition-all duration-300 ease-out will-change-transform hover:-translate-y-0.5 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/40"
                >
                    <span
                        :class="active === 3 ? 'bg-white/10 text-white ring-white/20' : 'bg-blue-50 text-blue-600 ring-blue-100'"
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl ring-1 transition-colors duration-300 ease-out"
                    >
                        <svg viewBox="0 0 24 24" class="h-[25px] w-[25px] transition-colors duration-300 ease-out" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M5 21V5a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2v16l-3 -2 -2 2 -2 -2 -2 2 -2 -2 -3 2"/>
                            <path d="M14.8 8A2 2 0 0 0 13 7h-2a2 2 0 1 0 0 4h2a2 2 0 1 1 0 4h-2a2 2 0 0 1 -1.8 -1"/>
                            <path d="M12 6v10"/>
                        </svg>
                    </span>
                    <span :class="active === 3 ? 'text-white' : 'text-zinc-800'" class="text-base font-semibold transition-colors duration-300 ease-out">Bill Payments</span>
                </a>
            </li>

            {{-- Flights --}}
            <li data-reveal-item class="shrink-0">
                <a
                    href="#"
                    @mouseenter="active = 4"
                    :class="active === 4 ? 'bg-zinc-900 ring-zinc-900' : 'bg-white ring-zinc-200 hover:ring-zinc-300'"
                    class="inline-flex items-center gap-3 rounded-[25px] px-6 py-2 ring-1 transition-all duration-300 ease-out will-change-transform hover:-translate-y-0.5 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/40"
                >
                    <span
                        :class="active === 4 ? 'bg-white/10 text-white ring-white/20' : 'bg-blue-50 text-blue-600 ring-blue-100'"
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl ring-1 transition-colors duration-300 ease-out"
                    >
                        <svg viewBox="0 0 24 24" class="h-[25px] w-[25px] transition-colors duration-300 ease-out" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="m14.5 6.5 3 -2.9a2.05 2.05 0 0 1 2.9 2.9l-2.9 3L20 17l-2.5 2.55L14 13l-3 3v3l-2 2 -1.5 -4.5L3 15l2 -2h3l3 -3 -6.5 -3.5L7 4l7.5 2.5z"/>
                        </svg>
                    </span>
                    <span :class="active === 4 ? 'text-white' : 'text-zinc-800'" class="text-base font-semibold transition-colors duration-300 ease-out">Flights</span>
                </a>
            </li>

            {{-- Stays --}}
            <li data-reveal-item class="shrink-0">
                <a
                    href="#"
                    @mouseenter="active = 5"
                    :class="active === 5 ? 'bg-zinc-900 ring-zinc-900' : 'bg-white ring-zinc-200 hover:ring-zinc-300'"
                    class="inline-flex items-center gap-3 rounded-[25px] px-6 py-2 ring-1 transition-all duration-300 ease-out will-change-transform hover:-translate-y-0.5 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/40"
                >
                    <span
                        :class="active === 5 ? 'bg-white/10 text-white ring-white/20' : 'bg-blue-50 text-blue-600 ring-blue-100'"
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl ring-1 transition-colors duration-300 ease-out"
                    >
                        <svg viewBox="0 0 24 24" class="h-[25px] w-[25px] transition-colors duration-300 ease-out" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M5 9a2 2 0 1 0 4 0 2 2 0 1 0 -4 0"/>
                            <path d="M22 17v-3H2"/>
                            <path d="M2 8v9"/>
                            <path d="M12 14h10v-2a3 3 0 0 0 -3 -3h-7v5z"/>
                        </svg>
                    </span>
                    <span :class="active === 5 ? 'text-white' : 'text-zinc-800'" class="text-base font-semibold transition-colors duration-300 ease-out">Stays</span>
                </a>
            </li>

        </ul>
    </div>
</section>
