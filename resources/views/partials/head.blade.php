<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<title>{{ $title ?? 'RshopRefills' }}</title>

<link rel="icon" type="image/x-icon" href="{{ asset('assets/favicon.ico') }}">
<link rel="apple-touch-icon" href="{{ asset('assets/PWAicon.png') }}">

<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

@vite(['resources/css/app.css', 'resources/js/app.js'])
@fluxAppearance

{{-- Page transition — the incoming page slides up from the bottom on navigation. --}}
<style>
    main { transition: opacity 700ms ease, transform 1200ms cubic-bezier(0.22, 1, 0.36, 1); }
    main.page-entering { opacity: 0; transform: translateY(40px); transition: none; }
</style>

{{-- Force admin/settings into light mode. Dark mode wasn't fully designed yet — Flux components read the .dark
     class set by @fluxAppearance based on the user's OS preference, which flips text colors and breaks contrast.
     This script strips it on every load. Remove this script when a proper dark mode pass ships. --}}
<script>
    document.documentElement.classList.remove('dark');

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
            document.documentElement.classList.remove('dark');
            if (firstLoad) { firstLoad = false; return; }
            playPageTransition();
        });
    })();
</script>
