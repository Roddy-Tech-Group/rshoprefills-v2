<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>{{ $title ?? config('app.name', 'RshopRefills') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        {{-- Slide-up entrance animation for the auth card --}}
        <style>
            @keyframes authSlideUp {
                from { transform: translateY(60px); opacity: 0; }
                to   { transform: translateY(0);    opacity: 1; }
            }
            .auth-slide-up { animation: authSlideUp 1000ms cubic-bezier(0.22, 1, 0.36, 1); }
        </style>
    </head>
    <body class="min-h-screen bg-zinc-100 text-zinc-900 antialiased">

        <div class="flex min-h-screen p-3 sm:p-6 lg:p-[60px]">
            <div class="auth-slide-up grid w-full overflow-hidden rounded-2xl lg:grid-cols-2">

            {{-- Left panel: marketing / phone mockup --}}
            <aside class="relative hidden flex-col overflow-hidden bg-blue-950 p-10 text-white lg:flex">

                {{-- Brand --}}
                <a href="{{ route('home') }}" wire:navigate class="relative z-10 flex flex-col rounded-md group focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-300/40">
                    <span class="flex h-11 items-center overflow-hidden">
                        <img
                            src="{{ asset('assets/Rshoprefillslogo.png') }}"
                            alt="RshopRefills"
                            class="h-[230px] w-auto max-w-none object-contain brightness-0 invert transition-opacity duration-200 group-hover:opacity-90"
                        />
                    </span>
                    <span class="pl-8 mt-1 text-[11px] font-medium leading-none text-blue-200/80">Digital Marketplace</span>
                </a>

                {{-- Middle: copy + phone --}}
                <div class="relative z-10 mt-12 flex flex-1 items-center gap-8">
                    <div class="flex-1">
                        <h1 class="text-3xl font-bold leading-tight tracking-tight xl:text-4xl">
                            Your digital world,<br />
                            <span class="text-blue-300">all in one place.</span>
                        </h1>
                        <p class="mt-4 max-w-md text-sm leading-relaxed text-blue-100/85">
                            Buy gift cards, eSIMs, top-ups, flights and more. Instantly and securely, at the best rates.
                        </p>

                        {{-- Feature bullets --}}
                        <ul class="mt-8 space-y-4">
                            @foreach([
                                ['Secure & Trusted',  'Your data and transactions are 100% secure', 'M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152A11.959 11.959 0 0 1 12 2.714Z'],
                                ['Instant Delivery',  'Get your digital products in seconds',       'M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z'],
                                ['Global Access',     'Access products and services worldwide',     'M12 21a9 9 0 100-18 9 9 0 000 18zm0 0c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3M3 12h18'],
                                ['24/7 Support',      'We\'re here to help anytime, anywhere',      'M3 14h3a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2H4a1 1 0 0 1-1-1v-6a9 9 0 0 1 18 0v6a1 1 0 0 1-1 1h-2a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3'],
                            ] as [$title, $desc, $d])
                                <li class="flex items-center gap-3">
                                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/10 ring-1 ring-white/15">
                                        <svg class="h-5 w-5 text-blue-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $d }}" />
                                        </svg>
                                    </span>
                                    <div class="leading-tight">
                                        <p class="text-sm font-semibold text-white">{{ $title }}</p>
                                        <p class="text-[12px] text-blue-100/70">{{ $desc }}</p>
                                    </div>
                                </li>
                            @endforeach
                        </ul>

                        {{-- Trust pill --}}
                        <div class="mt-8 inline-flex items-center gap-3 rounded-xl border border-white/10 bg-white/5 px-4 py-2.5">
                            <div class="flex -space-x-2">
                                @foreach(['bg-amber-400','bg-pink-400','bg-emerald-400'] as $c)
                                    <span class="inline-flex h-7 w-7 items-center justify-center rounded-full border-2 border-blue-950 {{ $c }}"></span>
                                @endforeach
                            </div>
                            <div class="leading-tight">
                                <p class="text-[12px] font-semibold text-white">Trusted by 150+ users</p>
                                <div class="mt-0.5 flex gap-0.5 text-amber-400">
                                    @for($i = 0; $i < 5; $i++)
                                        <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path d="M10 .587l3.09 6.26 6.91 1.005-5 4.873 1.18 6.875L10 16.327l-6.18 3.273L5 13.725 0 8.852l6.91-1.005L10 .587z"/></svg>
                                    @endfor
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Phone mockup --}}
                    <img
                        src="{{ asset('assets/graphic_rshoprefill.png') }}"
                        alt=""
                        class="relative z-10 max-h-[560px] w-auto select-none drop-shadow-2xl"
                    />
                </div>

                {{-- Footer --}}
                <div class="relative z-10 mt-6 flex flex-wrap items-center justify-between gap-4 text-[12px] text-blue-200/70">
                    <p>&copy; 2026 RshopRefills. All rights reserved.</p>
                    <div class="flex flex-wrap gap-6">
                        <a href="#" class="transition-colors hover:text-white">Privacy Policy</a>
                        <a href="#" class="transition-colors hover:text-white">Terms of Service</a>
                        <a href="#" class="transition-colors hover:text-white">Help Center</a>
                    </div>
                </div>
            </aside>

            {{-- Right panel: auth form --}}
            <main class="relative flex flex-col bg-white px-6 py-10 sm:px-10 lg:px-16">
                {{-- Mobile brand (visible only when left panel is hidden) --}}
                <a href="{{ route('home') }}" wire:navigate class="flex flex-col rounded-md group focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/40 lg:hidden">
                    <span class="flex h-9 items-center overflow-hidden">
                        <img
                            src="{{ asset('assets/Rshoprefillslogo.png') }}"
                            alt="RshopRefills"
                            class="h-[170px] w-auto max-w-none object-contain saturate-[1.25] transition-opacity duration-200 group-hover:opacity-90"
                        />
                    </span>
                    <span class="pl-1 mt-0.5 text-[10px] font-medium leading-none text-zinc-500">Digital Marketplace</span>
                </a>

                {{ $slot }}
            </main>

            </div>
        </div>

        @fluxScripts
    </body>
</html>
