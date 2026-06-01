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
    /* Kill the ~15px horizontal scrollbar from full-bleed sections
       (mx-[calc(50%-50vw)]) at the viewport root. `clip` keeps vertical scroll
       and, unlike doing this on an inner wrapper, never becomes a containing
       block/clip context for position:fixed modals + bars. */
    html { overflow-x: clip; }
</style>

{{-- ─────────────────────────────── SEO ───────────────────────────────────
     Single source of truth for the whole site. Pages override per-page by
     passing $title / $description / $ogImage / $ogType / $keywords through the
     layout (see x-shop.layout + x-layouts.app.header), e.g. product pages set
     the brand name + logo so a shared link previews as that product. --}}
@php
    $siteName = 'RshopRefills';

    $defaultDescription = 'Buy gift cards, eSIMs, mobile top-ups and bill payments worldwide. Built in Cameroon by CEO Divine Ofeh and CTO Johnpaul, RshopRefills is bringing Africa\'s digital ecosystem to the world - instant delivery, great prices and 24/7 support.';
    $defaultKeywords = 'RshopRefills, gift cards, buy gift cards online, eSIM, travel eSIM, mobile top up, airtime recharge, bill payments, crypto gift cards, Amazon gift card, Apple gift card, Google Play, Steam, Netflix, PlayStation, Xbox, Cameroon, Africa fintech, Divine Ofeh, Johnpaul';

    $seoTitle       = ! empty($title) ? $title : $siteName.' - Gift Cards, eSIMs, Top-ups & Bill Payments';
    $seoDescription = ! empty($description) ? $description : $defaultDescription;
    $seoKeywords    = ! empty($keywords) ? $keywords : $defaultKeywords;
    $seoType        = ! empty($ogType) ? $ogType : 'website';
    $seoImage       = ! empty($ogImage) ? $ogImage : asset('assets/og-image.png');
    $seoUrl         = url()->current();

    // Organization schema: the brand, the founders, and the mission - written so
    // search engines and AI crawlers can pick up who built RshopRefills and why.
    $orgJsonLd = [
        '@context' => 'https://schema.org',
        '@type' => 'Organization',
        'name' => $siteName,
        'url' => url('/'),
        'logo' => asset('assets/icon-512.png'),
        'image' => asset('assets/og-image.png'),
        'description' => 'RshopRefills is the first product of Roddy Technologies. It makes gift cards, eSIMs, mobile top-ups and bill payments instant and affordable. Founded and built in Cameroon by CEO Divine Ofeh and CTO Johnpaul, the team keeps shipping updates and solving problems for the community while building an Africa-to-international digital ecosystem - from Cameroon to the world.',
        'slogan' => 'From Cameroon to the world.',
        'foundingLocation' => [
            '@type' => 'Place',
            'address' => ['@type' => 'PostalAddress', 'addressCountry' => 'CM'],
        ],
        'areaServed' => 'Worldwide',
        'parentOrganization' => [
            '@type' => 'Organization',
            'name' => 'Roddy Technologies',
        ],
        'founder' => [
            ['@type' => 'Person', 'name' => 'Divine Ofeh', 'jobTitle' => 'Chief Executive Officer (CEO)'],
            ['@type' => 'Person', 'name' => 'Johnpaul', 'jobTitle' => 'Chief Technology Officer (CTO)'],
        ],
    ];
    $siteJsonLd = [
        '@context' => 'https://schema.org',
        '@type' => 'WebSite',
        'name' => $siteName,
        'url' => url('/'),
    ];
@endphp

<title>{{ $seoTitle }}</title>
<meta name="description" content="{{ $seoDescription }}">
<meta name="keywords" content="{{ $seoKeywords }}">
<meta name="author" content="Divine Ofeh (CEO) and Johnpaul (CTO) - RshopRefills">
<meta name="robots" content="index, follow">
<link rel="canonical" href="{{ $seoUrl }}">

{{-- Open Graph: link previews on Facebook, WhatsApp, LinkedIn, iMessage, etc. --}}
<meta property="og:site_name" content="{{ $siteName }}">
<meta property="og:type" content="{{ $seoType }}">
<meta property="og:title" content="{{ $seoTitle }}">
<meta property="og:description" content="{{ $seoDescription }}">
<meta property="og:url" content="{{ $seoUrl }}">
<meta property="og:image" content="{{ $seoImage }}">
<meta property="og:locale" content="{{ str_replace('-', '_', app()->getLocale()) }}">

{{-- Twitter / X card --}}
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $seoTitle }}">
<meta name="twitter:description" content="{{ $seoDescription }}">
<meta name="twitter:image" content="{{ $seoImage }}">

{{-- Structured data (founders + mission for bots/AI crawlers) --}}
<script type="application/ld+json">{!! json_encode($orgJsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
<script type="application/ld+json">{!! json_encode($siteJsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>

<link rel="icon" type="image/x-icon" href="{{ asset('assets/favicon.ico') }}">

{{-- ───────────────────────── PWA / installable app ─────────────────────────
     iOS does not read most manifest fields, so the Apple-specific meta + a
     square, OPAQUE apple-touch-icon (transparency would be black-filled on the
     home screen) are required for a clean "Add to Home Screen" result. --}}
<link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
<meta name="theme-color" content="#2563eb">
<link rel="apple-touch-icon" sizes="180x180" href="{{ asset('assets/apple-touch-icon-180.png') }}">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="apple-mobile-web-app-title" content="RshopRefills">
<meta name="application-name" content="RshopRefills">
<script>
    // Capture the install prompt as early as possible (it fires before Alpine
    // boots) and stash it so the floating Install button can replay it on tap.
    window.addEventListener('beforeinstallprompt', function (e) {
        e.preventDefault();
        window.__rshopInstallPrompt = e;
        window.dispatchEvent(new CustomEvent('rshop:installable'));
    });
    window.addEventListener('appinstalled', function () {
        window.__rshopInstallPrompt = null;
        window.dispatchEvent(new CustomEvent('rshop:installed'));
    });

    // Register the service worker once the page has loaded so it never competes
    // with first paint. Failures are swallowed - the site works without it.
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function () {
            navigator.serviceWorker.register('/sw.js').catch(function () {});
        });
    }
</script>

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
        /* NOTE: no `will-change: transform` here. It would make <main> a permanent
           containing block for position:fixed descendants, trapping fixed buy bars
           and modals inside <main> instead of pinning them to the viewport. The
           500ms slide runs fine without the hint. */
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
