@props([
    'action' => 'contact',
    'theme' => 'light',
    'context' => 'contact',
])

@php
    $enabled = config('services.turnstile.enabled');
    $enforceMap = [
        'auth' => config('services.turnstile.enforce_auth', true),
        'checkout' => config('services.turnstile.enforce_checkout', true),
        'contact' => config('services.turnstile.enforce_contact', true),
    ];
    $enforce = $enforceMap[$context] ?? true;
    $siteKey = config('services.turnstile.site_key');
@endphp

@if ($enabled && $enforce && $siteKey)
    {{-- The storefront layout loads api.js with ?render=explicit, so the
         widget will not auto-mount. We render it ourselves via Alpine once
         window.turnstile is available. Cloudflare auto-injects the
         "cf-turnstile-response" hidden input into the enclosing <form>,
         which VerifyTurnstile middleware reads. --}}
    <div
        {{ $attributes->merge(['class' => 'mt-1']) }}
        x-data="{
            widget: null,
            render(attempt = 0) {
                if (! window.turnstile) {
                    if (attempt < 50) setTimeout(() => this.render(attempt + 1), 100);
                    return;
                }
                if (this.widget) return;
                this.widget = window.turnstile.render(this.$refs.tw, {
                    sitekey: @js($siteKey),
                    theme: @js($theme),
                    action: @js($action),
                });
            },
        }"
        x-init="render()"
    >
        <div x-ref="tw"></div>
        @error('cf-turnstile-response')
            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
        @enderror
    </div>
@endif
