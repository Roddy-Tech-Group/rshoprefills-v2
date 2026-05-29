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

        {{-- App-style slide-up sheet entrance — same animation pattern as the split auth layout. --}}
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
    </head>
    <body class="min-h-screen bg-zinc-100 text-zinc-900 antialiased">

        <div class="flex min-h-screen items-start justify-center p-4 sm:items-center sm:p-6 lg:p-[60px]">
            <div class="auth-slide-up my-auto w-full max-w-xl overflow-hidden rounded-[10px] bg-white shadow-xl shadow-zinc-900/10 ring-1 ring-zinc-900/5 sm:my-0 lg:shadow-2xl lg:shadow-zinc-900/15">

                <main class="relative flex flex-col bg-white px-6 py-[50px] sm:px-10 sm:py-10">
                    {{-- Brand (centered at the top) --}}
                    <a href="{{ route('home') }}" wire:navigate class="flex shrink-0 flex-col items-center rounded-[10px] group focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/40">
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
