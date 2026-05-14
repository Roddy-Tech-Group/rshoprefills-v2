<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<title>{{ $title ?? 'RshopRefills' }}</title>

<link rel="icon" type="image/x-icon" href="{{ asset('assets/favicon.ico') }}">
<link rel="apple-touch-icon" href="{{ asset('assets/PWAicon.png') }}">

<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

@vite(['resources/css/app.css', 'resources/js/app.js'])
@fluxAppearance

{{-- Force admin/settings into light mode. Dark mode wasn't fully designed yet — Flux components read the .dark
     class set by @fluxAppearance based on the user's OS preference, which flips text colors and breaks contrast.
     This script strips it on every load. Remove this script when a proper dark mode pass ships. --}}
<script>
    document.documentElement.classList.remove('dark');
    document.addEventListener('livewire:navigated', () => {
        document.documentElement.classList.remove('dark');
    });
</script>
