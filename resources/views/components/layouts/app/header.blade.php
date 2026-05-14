<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>{{ $title ?? 'RshopRefills' }}</title>
        <meta name="description" content="Browse GiftCards, Esims, Topups, Book Flights and Stays from the comfort of your Home less stress Reliable trusted and world wide">

        <link rel="icon" type="image/x-icon" href="{{ asset('assets/favicon.ico') }}">
        <link rel="apple-touch-icon" href="{{ asset('assets/PWAicon.png') }}">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700|urbanist:800" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        {{-- @fluxAppearance intentionally omitted — the storefront is always light mode --}}

        {{-- Smooth fade between pages on wire:navigate --}}
        <style>
            main { transition: opacity 400ms ease-in-out; }
            body.is-navigating main { opacity: 0; }
        </style>
    </head>
    <body class="flex min-h-screen flex-col bg-white text-zinc-900 antialiased">

        <div
            x-data="{
                localeModalOpen: false,
                country: 'Cameroon',
                countryFlag: '🇨🇲',
                language: 'English',
                activeCategory: 'Gift Cards'
            }"
            @keydown.escape.window="localeModalOpen = false"
            x-effect="document.body.classList.toggle('overflow-hidden', localeModalOpen)"
            class="flex flex-1 flex-col"
        >
            <header class="sticky top-0 z-50 w-full">
                <x-nav.top-bar />
                <x-nav.main-nav />
            </header>

            <main class="flex-1">
                {{ $slot }}
            </main>

            <x-footer />

            <x-nav.locale-modal />
        </div>

        @fluxScripts

        <script>
            document.addEventListener('livewire:navigating', () => {
                document.body.classList.add('is-navigating');
            });
            document.addEventListener('livewire:navigated', () => {
                document.body.classList.remove('is-navigating');
            });
        </script>
    </body>
</html>
