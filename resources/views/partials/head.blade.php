<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta name="csrf-token" content="{{ csrf_token() }}" />

@php
    /*
     * Resolve the user's effective theme (dark | light) on the server so
     * the pre-paint frame uses the right colour scheme and bg, eliminating
     * the dark-on-light flash on hard refresh:
     *
     *   - Account holders: their saved DB preference is authoritative.
     *   - Guests + 'system': fall back to a cookie that mirrors the JS
     *     decision from the last visit. First-ever visit defaults to LIGHT
     *     (better trade-off than reading OS pref into a dark blank state).
     *
     * The theme-engine script below still re-asserts at runtime, but by
     * then HTML + CSS already painted the correct colour.
     */
    $themeIsAdminAreaHead = request()->is('admin*');
    $themeAccountHead = $themeIsAdminAreaHead
        ? auth('admin')->user()
        : auth('web')->user();
    $themeServerChoiceHead = $themeAccountHead?->theme;
    $themeCookieHead = $themeIsAdminAreaHead
        ? request()->cookie('theme_admin_dark')
        : request()->cookie('theme_web_dark');
    $themeIsDarkInitial = match (true) {
        $themeServerChoiceHead === 'dark' => true,
        $themeServerChoiceHead === 'light' => false,
        // 'system' or null falls through to the cookie hint.
        default => $themeCookieHead === '1',
    };
@endphp

{{-- Tell the browser the resolved scheme so the OS-default blank-page bg
     during a full refresh matches the page that's about to paint. Setting
     only the resolved value (not "light dark") stops the browser from
     using OS preference for the pre-CSS frame. --}}
<meta name="color-scheme" content="{{ $themeIsDarkInitial ? 'dark' : 'light' }}">

{{-- Apply .dark to <html> server-side AND prime Flux UI's own theme
     storage key in the SAME synchronous block. Flux ships its own
     appearance directive that reads `flux.appearance` from localStorage
     and falls back to OS pref - if it loads before our value is written,
     it can flip the page based on OS pref alone (the dark-then-light
     blink the user kept hitting on light-mode accounts whose OS is in
     dark mode). Writing the synced value here means even the very first
     paint after Flux's script sees the correct preference. --}}
<script>
    (function () {
        @if ($themeIsDarkInitial)
            document.documentElement.classList.add('dark');
        @endif
        try {
            localStorage.setItem(
                'flux.appearance',
                @json($themeServerChoiceHead ?? ($themeIsDarkInitial ? 'dark' : 'light'))
            );
        } catch (_) {}
    })();
</script>

{{-- Pin Flux to manual mode so its appearance script reads our value
     from localStorage instead of treating an unset key as "follow OS".
     Combined with the localStorage write above, this guarantees Flux
     and our theme engine always agree. --}}
<script>window.flux = window.flux || {}; window.flux.appearance = @json($themeServerChoiceHead ?? ($themeIsDarkInitial ? 'dark' : 'light'));</script>

{{-- THEME ENGINE MUST RUN BEFORE the Vite directive. The script inside
     reconciles the .dark class on <html> against the account's saved
     theme + localStorage + OS preference. The server-side hints above
     mean it usually has nothing to change, so no flash. --}}
@include('partials.theme-engine')

{{-- Match the blank-page bg to whatever the body bg is, so the moment the
     browser shows the new request before our full CSS parses doesn't flash
     a different colour. .dark is on <html> from the script above so the
     correct rule wins from the first paint. --}}
<style>
    html { background-color: #ffffff; }
    html.dark { background-color: #0c1a36; }
</style>

<title>{{ $title ?? 'RshopRefills' }}</title>

<link rel="icon" type="image/x-icon" href="{{ asset('assets/favicon.ico') }}">
<link rel="apple-touch-icon" href="{{ asset('assets/PWAicon.webp') }}">

<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

{{-- Flag CDN used by the locale modal, top-bar country chip, and admin
     country pickers. Pre-resolving the DNS shaves ~50-100ms off the first
     flag render without forcing any image load up front. --}}
<link rel="dns-prefetch" href="https://flagcdn.com">
<link rel="preconnect" href="https://flagcdn.com" crossorigin>

@vite(['resources/css/app.css', 'resources/js/app.js'])
@fluxAppearance

@include('partials.scroll-lock')

{{-- Page transition - the incoming page slides up from the bottom on every
     navigation (full page load + wire:navigate SPA swap). Driven entirely
     by JS class-swap so initial load and SPA swaps use the same single
     mechanism (previous mix of CSS keyframe + JS was racing on `transform`
     and producing a blink). Lives in the shared head partial so storefront,
     dashboard, admin, and auth layouts all get the same motion treatment. --}}
<style>
    /* Pure-CSS slide-in. Runs automatically whenever a <main> element is
       inserted into the DOM (initial page load AND every wire:navigate
       morph - Livewire replaces the <main> on navigation so the animation
       re-fires naturally). No JS class-swap means no snap-down-then-up
       jitter and no race between the keyframe and the JS handler. This is
       the same pattern the user dashboard uses smoothly. `backwards` so
       the starting state is held before the first frame paints. */
    @keyframes pageSlideIn {
        from { transform: translateY(32px); }
        to   { transform: translateY(0); }
    }
    main {
        animation: pageSlideIn 500ms cubic-bezier(0.22, 1, 0.36, 1) backwards;
        will-change: transform;
    }

    /* Micro-Fade Shift: scoped to layouts that opt in via
       <body data-page-transition="micro-fade-shift">. Replaces the slide
       with a very short opacity DIP + tiny upward nudge. Admin uses this
       so the dense data tables don't appear to drop into place on every
       navigation - feels more like a desktop app surface refresh. The
       opacity dips to 0.4 (not 0) so the body bg never gets fully exposed
       through a transparent main. */
    @keyframes microFadeShift {
        from { opacity: 0.4; transform: translateY(6px); }
        to   { opacity: 1;   transform: translateY(0); }
    }
    body[data-page-transition="micro-fade-shift"] main {
        animation: microFadeShift 240ms cubic-bezier(0.22, 1, 0.36, 1) backwards;
    }

    /* Modals/dialogs rise instead of flashing open. */
    @keyframes modalRise { from { opacity: 0; transform: translateY(24px); } to { opacity: 1; transform: translateY(0); } }
    [role="dialog"]:not(.modal-norise) { animation: modalRise 240ms cubic-bezier(0.22, 1, 0.36, 1) backwards; }

    @media (prefers-reduced-motion: reduce) {
        main { animation: none; }
        [role="dialog"] { animation: none; }
    }
</style>
