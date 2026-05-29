<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>{{ $title ?? config('app.name', 'RshopRefills') }}</title>
        <meta name="description" content="Browse GiftCards, Esims, Topups, Book Flights and Stays from the comfort of your Home less stress Reliable trusted and world wide">

        <link rel="icon" type="image/x-icon" href="{{ asset('assets/favicon.ico') }}">
        <link rel="apple-touch-icon" href="{{ asset('assets/PWAicon.png') }}">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        {{-- App-style slide-up sheet entrance for the auth card.
             Starts further below + slight scale-down for a true modal-sheet feel.
             Replays on Livewire SPA navigation via livewire:navigated. --}}
        <style>
            @keyframes authSlideUp {
                from { transform: translateY(110px) scale(0.97); opacity: 0; }
                to   { transform: translateY(0)     scale(1);    opacity: 1; }
            }
            .auth-slide-up {
                animation: authSlideUp 600ms cubic-bezier(0.16, 1, 0.3, 1);
                will-change: transform, opacity;
            }
        </style>
        <script>
            document.addEventListener('livewire:navigated', () => {
                document.querySelectorAll('.auth-slide-up').forEach((el) => {
                    el.classList.remove('auth-slide-up');
                    void el.offsetWidth;
                    el.classList.add('auth-slide-up');
                });
            });
        </script>
        @if(config('services.turnstile.enabled') && config('services.turnstile.enforce_auth'))
            <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
        @endif
    </head>
    <body class="min-h-screen bg-zinc-100 text-zinc-900 antialiased">

        <div class="flex min-h-screen items-start p-4 sm:items-stretch sm:p-6 lg:p-[60px]">
            <div class="auth-slide-up my-auto grid w-full overflow-hidden rounded-[10px] shadow-xl shadow-zinc-900/10 ring-1 ring-zinc-900/5 sm:my-0 lg:grid-cols-2 lg:shadow-2xl lg:shadow-zinc-900/15">

            {{-- Left panel: marketing / phone mockup --}}
            <aside class="relative hidden flex-col overflow-hidden bg-blue-950 p-10 text-white lg:flex">

                {{-- Brand --}}
                <a href="{{ route('home') }}" wire:navigate class="relative z-10 flex flex-col rounded-[10px] group focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-300/40">
                    <span class="flex h-12 items-center">
                        <img
                            src="{{ asset('assets/Rshoprefillslogo.png') }}"
                            alt="RshopRefills"
                            fetchpriority="high"
                            class="h-full w-auto object-contain brightness-0 invert transition-opacity duration-200 group-hover:opacity-90"
                        />
                    </span>
                    <span class="mt-1 pl-1 text-[11px] font-medium leading-none text-blue-200/80">Digital Marketplace</span>
                </a>

                {{-- Middle: copy + phone --}}
                <div class="relative z-10 mt-12 flex flex-1 items-center gap-8">
                    <div class="flex-1">
                        <h1 class="text-3xl font-bold leading-tight tracking-tight xl:text-4xl">
                            Your digital world,<br />
                            <span class="text-blue-300">all in one place.</span>
                        </h1>
                        <p class="mt-4 max-w-md text-base leading-relaxed text-blue-100/85">
                            Buy gift cards, eSIMs, top-ups, flights and more. Instantly and securely, at the best rates.
                        </p>

                        {{-- Feature bullets --}}
                        <ul class="mt-8 space-y-4">
                            @foreach([
                                ['Secure & Trusted', 'Your data and transactions are 100% secure', 'secure payments.svg'],
                                ['Instant Delivery', 'Get your digital products in seconds',       'fast.png'],
                                ['Global Access',    'Access products and services worldwide',    'global svg.svg'],
                                ['24/7 Support',     'We\'re here to help anytime, anywhere',     'support.svg'],
                            ] as [$title, $desc, $icon])
                                <li class="flex items-center gap-3">
                                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-[10px] bg-white/10 ring-1 ring-white/15">
                                        <img
                                            src="{{ asset('assets/' . rawurlencode($icon)) }}"
                                            alt=""
                                            class="h-5 w-5 shrink-0 brightness-0 invert"
                                            loading="lazy"
                                        >
                                    </span>
                                    <div class="leading-tight">
                                        <p class="text-base font-semibold text-white">{{ $title }}</p>
                                        <p class="text-[12px] text-blue-100/70">{{ $desc }}</p>
                                    </div>
                                </li>
                            @endforeach
                        </ul>

                        {{-- Trust pill --}}
                        <div class="mt-8 inline-flex items-center gap-3 rounded-[10px] border border-white/10 bg-white/5 px-4 py-2.5">
                            <div class="flex -space-x-2">
                                @foreach(['bg-amber-400','bg-pink-400','bg-emerald-400'] as $c)
                                    <span class="inline-flex h-7 w-7 items-center justify-center rounded-[10px] border-2 border-blue-950 {{ $c }}"></span>
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
                        fetchpriority="high"
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
            <main class="relative flex flex-col bg-white px-6 py-[50px] sm:px-10 sm:py-10 lg:px-16">
                {{-- Mobile brand (inside the card) --}}
                <a href="{{ route('home') }}" wire:navigate class="flex shrink-0 flex-col items-center rounded-[10px] group focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/40 lg:hidden">
                    <span class="flex h-10 items-center">
                        <img
                            src="{{ asset('assets/Rshoprefillslogo.png') }}"
                            alt="RshopRefills"
                            class="h-full w-auto object-contain transition-opacity duration-200 group-hover:opacity-90"
                        />
                    </span>
                    <span class="mt-0.5 text-[10px] font-medium leading-none text-zinc-600">Digital Marketplace</span>
                </a>

                {{ $slot }}
            </main>

            </div>
        </div>

        @fluxScripts
    </body>
</html>
