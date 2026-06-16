@props([
    'title' => null,
    'description' => null,
    'ogImage' => null,
    'ogType' => null,
    'keywords' => null,
    'jsonLd' => null,
])

@php
    // Auto-detects context: any URL under /dashboard/shop/* renders in the
    // dashboard chrome (sidebar, dashboard header). Everywhere else falls back
    // to the public storefront chrome. Same body, two presentations - so the
    // catalog views below stay layout-agnostic.
    $inDashboard = request()->is('dashboard/shop*') && auth()->check();
@endphp

@if ($inDashboard)
    <x-layouts.dashboard>
        {{ $slot }}
    </x-layouts.dashboard>
@else
    <x-layouts.app.header :title="$title" :description="$description" :og-image="$ogImage" :og-type="$ogType" :keywords="$keywords" :json-ld="$jsonLd">
        {{ $slot }}
    </x-layouts.app.header>
@endif
