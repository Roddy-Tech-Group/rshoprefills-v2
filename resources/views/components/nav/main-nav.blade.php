{{--
    Primary navigation row — logo · search · account/cart — plus a category
    shortcut bar that collapses on scroll. The row is a 3-column grid so the
    search bar is page-centred, aligned with the centred category bar below.
--}}
<div class="bg-white/75 backdrop-blur-xl">

    {{-- Nav row --}}
    <div class="max-w-screen-xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-[1fr_auto_1fr] items-center h-[64px] gap-4">

            {{-- Logo (the source PNG is square with whitespace padding, so the
                 image is scaled up and clipped to its wordmark band) --}}
            <a
                href="{{ route('home') }}"
                wire:navigate
                class="col-start-1 justify-self-start -ml-3 relative flex items-center rounded-md group focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/40"
                aria-label="RshopRefills — Home"
            >
                <span class="flex items-center h-9 md:h-11 overflow-hidden">
                    <img
                        src="{{ asset('assets/Rshoprefillslogo.png') }}"
                        alt="RshopRefills"
                        class="h-[190px] md:h-[230px] w-auto max-w-none object-contain saturate-[1.25] group-hover:opacity-90 transition-opacity duration-200"
                    />
                </span>
                <span class="absolute left-1 top-full -mt-0.5 text-[10px] font-medium italic leading-none text-zinc-400" aria-hidden="true">Est. 2024</span>
            </a>

            {{-- Search (desktop) --}}
            <div
                role="search"
                @click="$refs.search.focus()"
                class="col-start-2 group hidden md:flex w-[420px] max-w-full items-center gap-3 cursor-text rounded-2xl border-2 border-zinc-400 bg-white px-4 py-2 transition-all duration-200 hover:border-zinc-500 focus-within:border-blue-500 focus-within:ring-2 focus-within:ring-blue-500/15"
            >
                <svg class="w-5 h-5 shrink-0 text-zinc-900" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <input
                    x-ref="search"
                    type="search"
                    placeholder="Search for brands or categories"
                    aria-label="Search for brands or categories"
                    autocomplete="off"
                    spellcheck="false"
                    class="flex-1 min-w-0 bg-transparent text-sm text-zinc-800 placeholder:text-zinc-400 outline-none"
                />
            </div>

            {{-- Right actions --}}
            <div class="col-start-3 justify-self-end flex items-center gap-2">

                {{-- Mobile search trigger (icon-only on small screens) --}}
                <button
                    type="button"
                    class="md:hidden flex h-9 w-9 items-center justify-center rounded-md bg-zinc-100 text-zinc-900 transition-colors duration-150 hover:bg-zinc-200/70 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/50"
                    aria-label="Search"
                >
                    <svg class="h-[22px] w-[22px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </button>

                {{-- Account --}}
                @auth
                    <a
                        href="{{ route('settings.profile') }}"
                        wire:navigate
                        class="flex items-center justify-center w-9 h-9 md:w-10 md:h-10 rounded-md bg-zinc-100 text-zinc-700 hover:bg-zinc-200/70 transition-colors duration-150 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/50 select-none"
                        aria-label="Your account"
                    >
                        <span class="text-[12px] font-bold text-blue-600 leading-none">{{ auth()->user()->initials() }}</span>
                    </a>
                @else
                    <a
                        href="{{ route('login') }}"
                        wire:navigate
                        class="flex items-center justify-center w-9 h-9 md:w-10 md:h-10 rounded-md bg-zinc-100 text-zinc-900 hover:bg-zinc-200/70 transition-colors duration-150 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/50"
                        aria-label="Sign in"
                    >
                        <svg class="h-[22px] w-[22px] md:h-6 md:w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                        </svg>
                    </a>
                @endauth

                {{-- Cart --}}
                <a
                    href="#"
                    class="relative flex items-center justify-center w-9 h-9 md:w-10 md:h-10 rounded-md bg-zinc-100 text-zinc-900 hover:bg-zinc-200/70 transition-colors duration-150 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/50"
                    aria-label="Shopping cart"
                >
                    <svg class="h-[22px] w-[22px] md:h-6 md:w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="8" cy="21" r="1" />
                        <circle cx="19" cy="21" r="1" />
                        <path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12" />
                    </svg>
                    @if(($cartCount ?? 0) > 0)
                        <span class="absolute -top-1 -right-1 inline-flex items-center justify-center min-w-[18px] h-[18px] px-1 rounded-full bg-blue-600 text-[10px] font-bold text-white leading-none">{{ $cartCount }}</span>
                    @endif
                </a>

            </div>

        </div>
    </div>

    {{-- Category shortcut bar — visible on all devices --}}
    <div>
        <nav aria-label="Product categories" class="max-w-screen-xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex h-[40px] gap-1 overflow-x-auto justify-start md:justify-center [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                @foreach([
                    ['Gift Cards',    'M21 11.25v8.25a1.5 1.5 0 0 1-1.5 1.5H5.25a1.5 1.5 0 0 1-1.5-1.5v-8.25M12 4.875A2.625 2.625 0 1 0 9.375 7.5H12m0-2.625V7.5m0-2.625A2.625 2.625 0 1 1 14.625 7.5H12m0 0V21m-8.625-9.75h18.375a1.5 1.5 0 0 0 0-3H3.375a1.5 1.5 0 0 0 0 3Z'],
                    ['eSIMs',            'M7.5 3h7L18 6.5V19.5A1.5 1.5 0 0 1 16.5 21h-9A1.5 1.5 0 0 1 6 19.5V4.5A1.5 1.5 0 0 1 7.5 3ZM9.75 11.5h4.5v6h-4.5zM12 11.5v6M9.75 14.5h4.5'],
                    ['Mobile top up',    'M10.5 1.5H8.25A2.25 2.25 0 0 0 6 3.75v16.5a2.25 2.25 0 0 0 2.25 2.25h7.5A2.25 2.25 0 0 0 18 20.25V3.75a2.25 2.25 0 0 0-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 8.25h3'],
                    ['Bill payments',    'M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z'],
                    ['Flights',          'M17.8 19.2 16 11l3.5-3.5C21 6 21.5 4 21 3c-1-.5-3 0-4.5 1.5L13 8 4.8 6.2c-.5-.1-.9.1-1.1.5l-.3.5c-.2.5-.1 1 .3 1.3L9 12l-2 3H4l-1 1 3 2 2 3 1-1v-3l3-2 3.5 5.3c.3.4.8.5 1.3.3l.5-.2c.4-.3.6-.7.5-1.2z'],
                    ['Stays',            'M2 20v-8a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v8M4 10V6a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v4M12 4v6M2 18h20'],
                ] as [$label, $d])
                    <a
                        href="#"
                        @click.prevent="activeCategory = '{{ $label }}'"
                        :class="activeCategory === '{{ $label }}' ? 'text-zinc-900 font-semibold after:bg-zinc-900' : 'text-zinc-500 hover:text-zinc-800 after:bg-transparent'"
                        class="group relative flex h-full shrink-0 items-center gap-2 px-3 text-sm font-medium transition-colors duration-150 whitespace-nowrap focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/30 after:content-[''] after:absolute after:bottom-0 after:left-1/2 after:-translate-x-1/2 after:h-0.5 after:w-[0.5cm] after:rounded-full"
                    >
                        <svg class="w-5 h-5 shrink-0 text-zinc-900" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="{{ $d }}" />
                        </svg>
                        {{ $label }}
                    </a>
                @endforeach
            </div>
        </nav>
    </div>

</div>
