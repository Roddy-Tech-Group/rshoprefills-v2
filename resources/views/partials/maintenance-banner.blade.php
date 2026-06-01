{{-- Maintenance banner. Renders a sticky amber strip at the top of every
     storefront page when `system.maintenance_mode` is "on" so customers know
     about active work without redeploying. Toggle from the admin System
     Settings page; the banner copy lives in `system.maintenance_message`. --}}
@php
    $maintenanceOn = in_array(
        strtolower((string) \App\Models\SiteSetting::get('system.maintenance_mode', 'off')),
        ['on', 'true', '1'],
        true,
    );
    $maintenanceMsg = (string) \App\Models\SiteSetting::get(
        'system.maintenance_message',
        'We are running quick maintenance. Back shortly.',
    );
@endphp

@if ($maintenanceOn)
    <div
        x-data="{ open: true }"
        x-show="open"
        x-cloak
        role="status"
        aria-live="polite"
        class="relative z-[60] flex items-center justify-center gap-3 bg-amber-500 px-4 py-2.5 text-center text-sm font-semibold text-amber-950"
    >
        <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
        </svg>
        <span>{{ $maintenanceMsg }}</span>
        <button
            type="button"
            @click="open = false"
            aria-label="Dismiss"
            class="absolute right-3 top-1/2 -translate-y-1/2 flex h-7 w-7 items-center justify-center rounded-[6px] text-amber-950/70 transition-colors hover:bg-amber-600/30 hover:text-amber-950"
        >
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>
@endif
