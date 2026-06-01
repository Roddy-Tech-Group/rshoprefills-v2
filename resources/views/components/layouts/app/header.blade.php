@props([
    'title' => null,
    'description' => null,
    'ogImage' => null,
    'ogType' => null,
    'keywords' => null,
])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')

        {{-- Storefront-only head additions (extra display font, Turnstile loader).
             All SEO (title, description, OG, Twitter, JSON-LD) lives in one place
             in partials/head, overridable per page via the props above. --}}
        <link href="https://fonts.bunny.net/css?family=instrument-sans:700|urbanist:800" rel="stylesheet" />

        @if(config('services.turnstile.enabled') && (config('services.turnstile.enforce_auth') || config('services.turnstile.enforce_contact') || config('services.turnstile.enforce_checkout')))
            {{-- Cloudflare Turnstile (explicit render mode) - loaded once per
                 page so any form that needs the widget can call into
                 window.turnstile.render(...). Auth modal, contact forms,
                 feedback widget, and checkout all share this single loader. --}}
            <script src="https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit" async defer></script>
        @endif
    </head>
    <body class="flex min-h-screen flex-col bg-white text-zinc-900 antialiased">

        {{-- Translation engine (auto-detect + manual switching from the locale modal) --}}
        @include('partials.translate-engine')

        <div
            x-data="storefrontLocale()"
            x-init="init()"
            @keydown.escape.window="localeModalOpen = false"
            x-effect="localeModalOpen ? window.rshopScrollLock?.lock() : window.rshopScrollLock?.unlock()"
            class="flex flex-1 flex-col"
        >
            {{-- Maintenance banner - amber strip when system.maintenance_mode is on.
                 Sits above the sticky header so it announces itself first. --}}
            @include('partials.maintenance-banner')

            {{-- The whole header is sticky as one block (sticky needs a tall
                 containing block, the body provides that here). main-nav's
                 own Alpine then collapses the primary row on scroll, leaving
                 just the top bar + category strip pinned. --}}
            <header class="sticky top-0 z-50 w-full">
                <x-nav.top-bar />
                <x-nav.main-nav />
            </header>

            {{-- overflow-x-clip prevents the carousel full-bleed (mx-[calc(50%-50vw)]
                 w-screen) from creating a horizontal scrollbar: 100vw includes the
                 vertical scrollbar width, so the breakout would otherwise be ~15px
                 wider than the body. `clip` (not `hidden`) doesn't create a new
                 scroll container, so sticky positioning still works. --}}
            <main class="flex-1 overflow-x-clip bg-zinc-100">
                {{ $slot }}
            </main>

            <x-footer />

            <x-back-to-top />

            <x-cookie-consent />

            <x-nav.locale-modal />

            {{-- Global confirm modal — intercepts any form/button with `data-confirm`. --}}
            <x-confirm-modal />

            {{-- Global auth modal — login slides in from the right, register from
                 the left. Any `<button @click="$dispatch('open-auth-modal', { mode: '…' })">`
                 across the storefront pops this open instead of redirecting. --}}
            @guest
                <livewire:auth.auth-modal />
            @endguest

            {{-- Storefront feedback tab. Gated by the features.feedback_widget_enabled
                 admin toggle so it can be hidden without redeploying. --}}
            @feature('feedback_widget')
                <livewire:feedback-widget />
            @endfeature
        </div>

        @fluxScripts
    </body>
</html>
