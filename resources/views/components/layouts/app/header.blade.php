<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <meta name="csrf-token" content="{{ csrf_token() }}" />
        <title>{{ $title ?? 'RshopRefills' }}</title>
        <meta name="description" content="Browse GiftCards, Esims, Topups, Book Flights and Stays from the comfort of your Home less stress Reliable trusted and world wide">

        <link rel="icon" type="image/x-icon" href="{{ asset('assets/favicon.ico') }}">
        <link rel="apple-touch-icon" href="{{ asset('assets/PWAicon.png') }}">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700|urbanist:800" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        {{-- Page transition — the incoming page slides up from the bottom on navigation. --}}
        <style>
            main { transition: opacity 700ms ease, transform 1200ms cubic-bezier(0.22, 1, 0.36, 1); }
            main.page-entering { opacity: 0; transform: translateY(40px); transition: none; }

            /* Every page rises into view on load; page-entering handles SPA swaps. */
            @keyframes pageRise { from { opacity: 0; transform: translateY(40px); } to { opacity: 1; transform: translateY(0); } }
            main { animation: pageRise 600ms cubic-bezier(0.22, 1, 0.36, 1) backwards; }

            /* Modals/dialogs rise instead of flashing open. */
            @keyframes modalRise { from { opacity: 0; transform: translateY(24px); } to { opacity: 1; transform: translateY(0); } }
            [role="dialog"]:not(.modal-norise) { animation: modalRise 240ms cubic-bezier(0.22, 1, 0.36, 1) backwards; }

            @media (prefers-reduced-motion: reduce) {
                main, [role="dialog"] { animation: none; }
            }
        </style>

        {{-- Theme engine (light / dark / system) — same as admin/dashboard so dark mode works storefront-wide. --}}
        @include('partials.theme-engine')
    </head>
    <body class="flex min-h-screen flex-col bg-white text-zinc-900 antialiased">

        <div
            x-data="storefrontLocale()"
            x-init="init()"
            @keydown.escape.window="localeModalOpen = false"
            x-effect="document.body.classList.toggle('overflow-hidden', localeModalOpen)"
            class="flex flex-1 flex-col"
        >
            <header class="sticky top-0 z-50 w-full">
                <x-nav.top-bar />
                <x-nav.main-nav />
            </header>

            <main class="flex-1 bg-zinc-100">
                {{ $slot }}
            </main>

            <x-footer />

            <x-nav.locale-modal />
        </div>

        @fluxScripts

        <script>
            (function () {
                let firstLoad = true;
                function playPageTransition() {
                    const main = document.querySelector('main');
                    if (! main) return;
                    main.classList.add('page-entering');
                    void main.offsetWidth; // commit the offset before transitioning back
                    main.classList.remove('page-entering');
                }
                document.addEventListener('livewire:navigated', () => {
                    if (firstLoad) { firstLoad = false; return; }
                    playPageTransition();
                });
            })();
        </script>
    </body>
</html>
