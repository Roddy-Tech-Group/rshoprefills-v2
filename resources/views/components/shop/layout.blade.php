@props([
    'title' => null,
    'description' => null,
    'ogImage' => null,
    'ogType' => null,
    'keywords' => null,
])

@php
    // Auto-detects context: any URL under /dashboard/shop/* renders in the
    // dashboard chrome (sidebar, dashboard header). Everywhere else falls back
    // to the public storefront chrome. Same body, two presentations - so the
    // catalog views below stay layout-agnostic.
    $inDashboard = request()->is('dashboard/shop*') && auth()->check();

    // When inside the dashboard, build the matching storefront URL so the
    // escape-hatch link can hop the user out to the marketing view. Preserve
    // any active query string (search, sort, region) on the hop.
    $storefrontUrl = null;
    if ($inDashboard) {
        $publicPath = preg_replace('#^dashboard/shop/?#', '', request()->path());
        $qs = request()->getQueryString();
        $storefrontUrl = url($publicPath).($qs ? '?'.$qs : '');
    }
@endphp

@if ($inDashboard)
    <x-layouts.dashboard>
        {{-- Subtle escape hatch back to the public storefront. Sits above the
             catalog body so it's discoverable without crowding the page header. --}}
        <div class="mx-auto w-full max-w-[1400px] px-4 pt-3 sm:px-6 lg:px-8">
            <a
                href="{{ $storefrontUrl }}"
                class="inline-flex items-center rounded-[10px] bg-white/70 px-3 py-1.5 text-xs font-semibold text-blue-700 ring-1 ring-blue-200 backdrop-blur transition-colors hover:bg-white hover:text-blue-800 dark:bg-white/10 dark:text-blue-300 dark:ring-white/15 dark:hover:bg-white/15"
            >
                View in storefront
            </a>
        </div>

        {{ $slot }}
    </x-layouts.dashboard>
@else
    <x-layouts.app.header :title="$title" :description="$description" :og-image="$ogImage" :og-type="$ogType" :keywords="$keywords">
        {{ $slot }}
    </x-layouts.app.header>
@endif
