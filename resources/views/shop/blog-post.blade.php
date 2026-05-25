@php
    // Single blog article. Dark-mode safe (bg-white/bg-zinc-50/bg-blue-50 -> navy).
    $img = fn (string $file) => asset('assets/'.rawurlencode($file));
@endphp

<x-layouts.app.header :title="$post['title'].' | RshopRefills'">

    <article class="mx-auto w-full max-w-[820px] px-4 py-12 sm:px-6 sm:py-16">
        <a href="{{ route('shop.blog') }}" wire:navigate class="inline-flex items-center gap-2 text-sm font-medium text-blue-600 hover:underline">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
            Back to blog
        </a>

        <p class="mt-6 text-xs font-bold uppercase tracking-wider text-blue-600">{{ $post['category'] }}</p>
        <h1 class="mt-2 text-3xl font-bold tracking-tight text-zinc-900 sm:text-4xl">{{ $post['title'] }}</h1>
        <p class="mt-3 text-sm text-zinc-500">
            {{ $post['author'] ?? 'RshopRefills Team' }} &middot; {{ \Illuminate\Support\Carbon::parse($post['date'])->format('M j, Y') }}@isset($post['read']) &middot; {{ $post['read'] }}@endisset
        </p>

        <div class="mt-8 flex items-center justify-center overflow-hidden rounded-2xl bg-blue-50 p-8 ring-1 ring-zinc-100" style="height: 18rem;">
            <img src="{{ $img($post['image']) }}" alt="" class="max-h-full w-auto object-contain" loading="lazy">
        </div>

        <div class="mt-8 space-y-4 text-sm leading-relaxed text-zinc-600 sm:text-base">
            @foreach ($post['body'] as $paragraph)
                <p>{{ $paragraph }}</p>
            @endforeach
        </div>

        <div class="mt-10 rounded-2xl bg-blue-600 p-6 text-center">
            <p class="text-base font-bold text-white">Ready to put this into practice?</p>
            <a href="{{ route('shop.gift-cards') }}" wire:navigate class="mt-4 inline-flex items-center justify-center gap-2 rounded-[6px] bg-white px-5 py-2.5 text-sm font-semibold text-blue-700 transition-colors hover:bg-blue-50">
                Start shopping
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
            </a>
        </div>
    </article>

    @if (count($related))
        <section class="border-t border-zinc-100 bg-zinc-50">
            <div class="mx-auto w-full max-w-[1140px] px-4 py-12 sm:px-6 sm:py-16">
                <h2 class="text-xl font-bold tracking-tight text-zinc-900">More from the blog</h2>
                <div class="mt-6 grid grid-cols-1 gap-x-6 gap-y-10 sm:grid-cols-3">
                    @foreach ($related as $item)
                        <a href="{{ route('shop.blog.show', $item['slug']) }}" wire:navigate class="group block">
                            <div class="flex items-center justify-center overflow-hidden rounded-2xl bg-white p-6 ring-1 ring-zinc-100" style="height: 10rem;">
                                <img src="{{ $img($item['image']) }}" alt="" class="max-h-full w-auto object-contain" loading="lazy">
                            </div>
                            <p class="mt-3 text-xs font-bold uppercase tracking-wider text-blue-600">{{ $item['category'] }}</p>
                            <h3 class="mt-1 text-base font-bold leading-snug text-zinc-900 transition-colors group-hover:text-blue-600">{{ $item['title'] }}</h3>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

</x-layouts.app.header>
