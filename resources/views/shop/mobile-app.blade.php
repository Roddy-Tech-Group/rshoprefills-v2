@php
    // Mobile app "in development" page. Dark-mode safe (bg-blue-50 -> navy).
    $img = fn (string $file) => asset('assets/'.rawurlencode($file));
@endphp

<x-layouts.app.header :title="'Mobile App | RshopRefills'">

    <section class="mx-auto w-full max-w-[880px] px-4 py-16 text-center sm:px-6 sm:py-24">
        <span class="inline-flex items-center gap-2 rounded-[5px] bg-blue-100 px-3 py-1.5 text-[11px] font-bold uppercase tracking-[0.14em] text-blue-700">In development</span>
        <h1 class="mt-5 text-3xl font-bold tracking-tight text-zinc-900 sm:text-4xl">Our mobile app is on the way</h1>
        <p class="mx-auto mt-3 max-w-xl text-sm leading-relaxed text-zinc-600 sm:text-base">
            We are building the RshopRefills app for iOS and Android. While we put on the finishing touches, you can do
            everything right here on the web, including your wallet, orders and rewards.
        </p>

        <img src="{{ $img('Development Mood.png') }}" alt="Our mobile app is in development" class="mx-auto mt-10 w-full max-w-md" loading="lazy">

        {{-- Coming-soon store badges (visual) --}}
        <p class="mt-10 text-xs font-semibold uppercase tracking-wider text-zinc-500">Coming soon to</p>
        <div class="mt-3 flex flex-wrap items-center justify-center gap-3">
            <span class="inline-flex items-center gap-2 rounded-xl px-4 py-2 text-white opacity-90" style="background-color: #18181b;">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M17.05 20.28c-.98.95-2.05.8-3.08.35-1.09-.46-2.09-.48-3.24 0-1.44.62-2.2.44-3.06-.35C2.79 15.25 3.51 7.59 9.05 7.31c1.35.07 2.29.74 3.08.8 1.18-.24 2.31-.93 3.57-.84 1.51.12 2.65.72 3.4 1.8-3.12 1.87-2.38 5.98.48 7.13-.57 1.5-1.31 2.99-2.54 4.09zM12.03 7.25c-.15-2.23 1.66-4.07 3.74-4.25.29 2.58-2.34 4.5-3.74 4.25z"/></svg>
                <span class="text-left leading-none">
                    <span class="block text-xs">Get it on</span>
                    <span class="block text-sm font-semibold">App Store</span>
                </span>
            </span>
            <span class="inline-flex items-center gap-2 rounded-xl px-4 py-2 text-white opacity-90" style="background-color: #18181b;">
                <img src="{{ asset('assets/'.rawurlencode('Playstore 2.png')) }}" alt="" width="24" height="24" class="block h-6 w-6 shrink-0 object-contain" loading="lazy">
                <span class="text-left leading-none">
                    <span class="block text-xs">Download on</span>
                    <span class="block text-sm font-semibold">Google Play</span>
                </span>
            </span>
        </div>

        {{-- Web CTAs --}}
        <div class="mt-10 flex flex-col gap-3 sm:flex-row sm:justify-center">
            <a href="{{ route('shop.gift-cards') }}" wire:navigate class="inline-flex items-center justify-center gap-2 rounded-[6px] bg-blue-600 px-6 py-3 text-sm font-semibold text-white transition-colors hover:bg-blue-700">
                Continue on the web
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
            </a>
            <a href="{{ route('dashboard') }}" wire:navigate class="inline-flex items-center justify-center gap-2 rounded-[6px] border border-zinc-200 bg-white px-6 py-3 text-sm font-semibold text-zinc-700 transition-colors hover:bg-zinc-50">
                Go to dashboard
            </a>
        </div>
    </section>

</x-layouts.app.header>
