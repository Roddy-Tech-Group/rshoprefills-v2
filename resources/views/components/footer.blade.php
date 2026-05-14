{{-- Storefront footer. Used inside the <x-layouts.app.header> shell. --}}
<footer class="border-t border-zinc-200 bg-white text-zinc-900">
    <div class="mx-auto max-w-screen-xl px-4 sm:px-6 lg:px-8 py-12 lg:py-16">

        {{-- Top: brand + link columns --}}
        <div class="grid grid-cols-2 gap-x-6 gap-y-10 lg:grid-cols-12 lg:gap-10">

            {{-- Brand column --}}
            <div class="col-span-2 lg:col-span-4">
                <a href="{{ route('home') }}" wire:navigate class="-ml-3 flex flex-col rounded-md group focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/40">
                    <span class="flex h-11 items-center overflow-hidden">
                        <img
                            src="{{ asset('assets/Rshoprefillslogo.png') }}"
                            alt="RshopRefills"
                            class="h-[230px] w-auto max-w-none object-contain saturate-[1.25] transition-opacity duration-200 group-hover:opacity-90"
                        />
                    </span>
                    <span class="pl-8 mt-1 text-[11px] font-medium leading-none text-zinc-500">Digital Marketplace</span>
                </a>

                <p class="mt-6 max-w-sm text-sm leading-relaxed text-zinc-500">
                    Your digital world, all in one place. Gift cards, eSIMs, top-ups, flights and more. Instant, secure, worldwide.
                </p>

                {{-- Social links --}}
                <div class="mt-6 flex flex-wrap items-center gap-2">
                    <a href="https://facebook.com/rshoprefills" target="_blank" rel="noopener noreferrer" aria-label="Visit our Facebook page" class="flex h-10 w-10 items-center justify-center rounded-lg bg-zinc-100 text-zinc-700 transition-colors hover:bg-zinc-200 hover:text-zinc-900">
                        <svg viewBox="0 0 24 24" class="h-[18px] w-[18px]" fill="currentColor" aria-hidden="true">
                            <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                        </svg>
                    </a>
                    <a href="https://x.com/rshoprefills" target="_blank" rel="noopener noreferrer" aria-label="Follow us on X" class="flex h-10 w-10 items-center justify-center rounded-lg bg-zinc-100 text-zinc-700 transition-colors hover:bg-zinc-200 hover:text-zinc-900">
                        <svg viewBox="0 0 24 24" class="h-4 w-4" fill="currentColor" aria-hidden="true">
                            <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117L17.083 19.77z"/>
                        </svg>
                    </a>
                    <a href="https://tiktok.com/@rshoprefills" target="_blank" rel="noopener noreferrer" aria-label="Follow us on TikTok" class="flex h-10 w-10 items-center justify-center rounded-lg bg-zinc-100 text-zinc-700 transition-colors hover:bg-zinc-200 hover:text-zinc-900">
                        <svg viewBox="0 0 24 24" class="h-[18px] w-[18px]" fill="currentColor" aria-hidden="true">
                            <path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/>
                        </svg>
                    </a>
                    <a href="https://instagram.com/rshoprefills" target="_blank" rel="noopener noreferrer" aria-label="Follow us on Instagram" class="flex h-10 w-10 items-center justify-center rounded-lg bg-zinc-100 text-zinc-700 transition-colors hover:bg-zinc-200 hover:text-zinc-900">
                        <svg viewBox="0 0 24 24" class="h-[18px] w-[18px]" fill="currentColor" aria-hidden="true">
                            <path d="M12 2.163c3.204 0 3.584.012 4.849.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.07 1.644.07 4.849 0 3.205-.012 3.584-.07 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.849.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/>
                        </svg>
                    </a>
                </div>
            </div>

            {{-- Shop --}}
            <nav class="lg:col-span-2" aria-label="Shop">
                <h3 class="text-sm font-semibold text-zinc-900">Shop</h3>
                <ul class="mt-4 space-y-2.5 text-sm">
                    <li><a href="#" class="text-zinc-500 transition-colors hover:text-zinc-900">Gift Cards</a></li>
                    <li><a href="#" class="text-zinc-500 transition-colors hover:text-zinc-900">eSIMs</a></li>
                    <li><a href="#" class="text-zinc-500 transition-colors hover:text-zinc-900">Mobile top up</a></li>
                    <li><a href="#" class="text-zinc-500 transition-colors hover:text-zinc-900">Bill payments</a></li>
                    <li><a href="#" class="text-zinc-500 transition-colors hover:text-zinc-900">Flights</a></li>
                    <li><a href="#" class="text-zinc-500 transition-colors hover:text-zinc-900">Stays</a></li>
                </ul>
            </nav>

            {{-- Help --}}
            <nav class="lg:col-span-2" aria-label="Help">
                <h3 class="text-sm font-semibold text-zinc-900">Help</h3>
                <ul class="mt-4 space-y-2.5 text-sm">
                    <li><a href="#" class="text-zinc-500 transition-colors hover:text-zinc-900">Help center</a></li>
                    <li><a href="#" class="text-zinc-500 transition-colors hover:text-zinc-900">Contact us</a></li>
                    <li><a href="#" class="text-zinc-500 transition-colors hover:text-zinc-900">How it works</a></li>
                    <li><a href="#" class="text-zinc-500 transition-colors hover:text-zinc-900">Order status</a></li>
                    <li><a href="#" class="text-zinc-500 transition-colors hover:text-zinc-900">Refund policy</a></li>
                </ul>
            </nav>

            {{-- Company --}}
            <nav class="lg:col-span-2" aria-label="Company">
                <h3 class="text-sm font-semibold text-zinc-900">Company</h3>
                <ul class="mt-4 space-y-2.5 text-sm">
                    <li><a href="#" class="text-zinc-500 transition-colors hover:text-zinc-900">About us</a></li>
                    <li><a href="#" class="text-zinc-500 transition-colors hover:text-zinc-900">Blog</a></li>
                    <li><a href="#" class="text-zinc-500 transition-colors hover:text-zinc-900">Careers</a></li>
                    <li><a href="#" class="text-zinc-500 transition-colors hover:text-zinc-900">Press</a></li>
                </ul>
            </nav>

            {{-- Legal --}}
            <nav class="lg:col-span-2" aria-label="Legal">
                <h3 class="text-sm font-semibold text-zinc-900">Legal</h3>
                <ul class="mt-4 space-y-2.5 text-sm">
                    <li><a href="#" class="text-zinc-500 transition-colors hover:text-zinc-900">Privacy</a></li>
                    <li><a href="#" class="text-zinc-500 transition-colors hover:text-zinc-900">Terms of Service</a></li>
                    <li><a href="#" class="text-zinc-500 transition-colors hover:text-zinc-900">Cookie Policy</a></li>
                    <li><a href="#" class="text-zinc-500 transition-colors hover:text-zinc-900">Compliance</a></li>
                </ul>
            </nav>

        </div>

        {{-- Divider --}}
        <div class="my-10 h-px bg-zinc-200" aria-hidden="true"></div>

        {{-- Bottom bar --}}
        <div class="flex flex-col gap-4 text-xs text-zinc-500 sm:flex-row sm:items-center sm:justify-between">
            <p>&copy; 2026 RshopRefills. All rights reserved.</p>

            <div class="flex flex-wrap items-center gap-x-6 gap-y-2">
                {{-- Locale chip: opens the same modal the nav uses --}}
                <button type="button" @click="localeModalOpen = true" class="flex items-center gap-2 transition-colors hover:text-zinc-900 focus:outline-none focus-visible:text-zinc-900">
                    <span class="text-base leading-none" aria-hidden="true" x-text="countryFlag">🇨🇲</span>
                    <span x-text="country">Cameroon</span>
                    <span class="text-zinc-300" aria-hidden="true">/</span>
                    <span x-text="language">English</span>
                </button>
                <a href="#" class="transition-colors hover:text-zinc-900">Sitemap</a>
                <a href="#" class="transition-colors hover:text-zinc-900">Accessibility</a>
            </div>
        </div>

    </div>
</footer>
