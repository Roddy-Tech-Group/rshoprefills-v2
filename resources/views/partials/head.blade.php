<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta name="csrf-token" content="{{ csrf_token() }}" />

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

@include('partials.theme-engine')
