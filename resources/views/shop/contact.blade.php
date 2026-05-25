@php
    $supportEmail = 'info@rshoprefill.com';
    $field = 'w-full rounded-xl border border-zinc-300 bg-white px-3.5 py-2.5 text-sm text-black placeholder:text-zinc-500 outline-none transition-colors focus:border-blue-500 focus:ring-2 focus:ring-blue-500/15';

    $subjects = [
        'General enquiry',
        'Order not delivered',
        'Issue redeeming gift card',
        'Missing barcode',
        'Refund request / change order',
        'Paid with the wrong coin or network',
        'KYC approval',
        'Out of stock',
        'Flight or stay booking',
        'Brand / product request',
        'Feature request',
        'Other reason',
    ];

    $channels = [
        ['label' => 'Email us',      'value' => $supportEmail,        'href' => 'mailto:'.$supportEmail, 'path' => 'M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75'],
        ['label' => 'Response time', 'value' => 'Within 24 hours',    'href' => null,                    'path' => 'M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z'],
        ['label' => 'Availability',  'value' => '7 days a week',       'href' => null,                    'path' => 'M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5'],
    ];
@endphp

<x-layouts.app.header :title="'Contact Us | RshopRefills'">

    {{-- ── Hero ──────────────────────────────────────────────── --}}
    <section class="border-b border-zinc-100 bg-blue-50">
        <div class="mx-auto w-full max-w-[1000px] px-4 py-14 text-center sm:px-6 sm:py-20">
            <span class="inline-flex items-center gap-2 rounded-[5px] bg-blue-100 px-3 py-1.5 text-[11px] font-bold uppercase tracking-[0.14em] text-blue-700">Contact</span>
            <h1 class="mt-5 text-3xl font-bold tracking-tight text-zinc-900 sm:text-4xl lg:text-5xl">Get in touch</h1>
            <p class="mx-auto mt-3 max-w-xl text-sm leading-relaxed text-zinc-600 sm:text-base">
                Questions about an order, your wallet or your account? Send us a message and our team will get back to you, usually within a day.
            </p>
        </div>
    </section>

    {{-- ── Form + channels ───────────────────────────────────── --}}
    <section class="mx-auto w-full max-w-[1140px] px-4 py-12 sm:px-6 sm:py-16">
        <div class="grid grid-cols-1 gap-8 lg:grid-cols-2 lg:gap-10">

            {{-- Form --}}
            <div>
                <div class="rounded-[24px] bg-white p-6 shadow-sm shadow-zinc-900/5 ring-1 ring-zinc-100 sm:p-8">
                    @if (session('contact_sent'))
                        {{-- Success state --}}
                        <div class="flex flex-col items-center py-10 text-center">
                            <span class="flex h-14 w-14 items-center justify-center rounded-full bg-emerald-100">
                                <svg class="h-7 w-7 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                                </svg>
                            </span>
                            <h2 class="mt-4 text-xl font-bold text-zinc-900">Message sent</h2>
                            <p class="mt-1.5 max-w-sm text-sm leading-relaxed text-zinc-600">
                                Thanks for reaching out. We have received your message and will reply to your email within 24 hours.
                            </p>
                            <a href="{{ route('shop.contact') }}" wire:navigate class="mt-6 inline-flex items-center gap-2 rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-blue-700">
                                Send another message
                            </a>
                        </div>
                    @else
                        <div class="mb-6">
                            <h2 class="text-lg font-bold text-zinc-900">Send us a message</h2>
                            <p class="mt-0.5 text-sm text-zinc-600">We read every message. Fields marked with an asterisk are required.</p>
                        </div>

                        <form method="POST" action="{{ route('contact.send') }}" class="space-y-5">
                            @csrf

                            {{-- Honeypot — hidden from humans, bots tend to fill it. --}}
                            <div class="hidden" aria-hidden="true">
                                <label>Website<input type="text" name="website" tabindex="-1" autocomplete="off"></label>
                            </div>

                            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                                <div>
                                    <label for="name" class="mb-1.5 block text-sm font-medium text-zinc-700">Full name <span class="text-blue-600">*</span></label>
                                    <input id="name" name="name" type="text" required value="{{ old('name', auth()->user()?->name) }}" placeholder="Your name" class="{{ $field }}">
                                    @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label for="email" class="mb-1.5 block text-sm font-medium text-zinc-700">Email <span class="text-blue-600">*</span></label>
                                    <input id="email" name="email" type="email" required value="{{ old('email', auth()->user()?->email) }}" placeholder="you@example.com" class="{{ $field }}">
                                    @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                                {{-- Subject — modern custom dropdown (styled chevron, not the native arrow) --}}
                                <div x-data="{ open: false, subject: @js(old('subject', $subjects[0])) }" @click.outside="open = false" @keydown.escape="open = false" class="relative">
                                    <label class="mb-1.5 block text-sm font-medium text-zinc-700">Subject</label>
                                    <input type="hidden" name="subject" :value="subject">
                                    <button
                                        type="button"
                                        @click="open = ! open"
                                        :aria-expanded="open.toString()"
                                        class="flex w-full items-center justify-between gap-2 rounded-xl border border-zinc-300 bg-white px-3.5 py-2.5 text-left text-sm font-medium text-zinc-900 outline-none transition-colors hover:border-zinc-400 focus:border-blue-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/15"
                                    >
                                        <span x-text="subject" class="truncate"></span>
                                        <svg class="h-4 w-4 shrink-0 text-zinc-500 transition-transform duration-150" :class="open && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                                    </button>
                                    <div
                                        x-show="open"
                                        x-cloak
                                        x-transition:enter="transition ease-out duration-150"
                                        x-transition:enter-start="opacity-0 -translate-y-1"
                                        x-transition:enter-end="opacity-100 translate-y-0"
                                        class="absolute left-0 right-0 z-20 mt-1.5 max-h-60 overflow-y-auto rounded-xl border border-zinc-200 bg-white p-1 shadow-xl shadow-zinc-900/10"
                                        role="listbox"
                                    >
                                        @foreach ($subjects as $s)
                                            <button
                                                type="button"
                                                @click="subject = @js($s); open = false"
                                                role="option"
                                                class="flex w-full items-center justify-between gap-2 rounded-lg px-3 py-2 text-left text-sm font-medium transition-colors"
                                                :class="subject === @js($s) ? 'bg-blue-50 text-blue-700' : 'text-zinc-700 hover:bg-zinc-100'"
                                            >
                                                <span>{{ $s }}</span>
                                                <svg x-show="subject === @js($s)" class="h-4 w-4 shrink-0 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                                            </button>
                                        @endforeach
                                    </div>
                                    @error('subject') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>

                                {{-- Order ID (optional) --}}
                                <div>
                                    <label for="order_id" class="mb-1.5 block text-sm font-medium text-zinc-700">Order ID <span class="font-normal text-zinc-500">(optional)</span></label>
                                    <input id="order_id" name="order_id" type="text" value="{{ old('order_id') }}" placeholder="e.g. ORD-AB12CD34" class="{{ $field }}">
                                    @error('order_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                            </div>

                            <div>
                                <label for="message" class="mb-1.5 block text-sm font-medium text-zinc-700">Message <span class="text-blue-600">*</span></label>
                                <textarea id="message" name="message" rows="6" required placeholder="How can we help?" class="{{ $field }} resize-y">{{ old('message') }}</textarea>
                                @error('message') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div class="flex items-center gap-3 pt-1">
                                <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm shadow-blue-600/25 transition-colors hover:bg-blue-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/50">
                                    Send message
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5"/></svg>
                                </button>
                                <p class="text-xs text-zinc-500">We never share your details.</p>
                            </div>
                        </form>
                    @endif
                </div>
            </div>

            {{-- Channels --}}
            <div>
                <div class="space-y-4">
                    @foreach ($channels as $channel)
                        <div class="flex items-start gap-4 rounded-2xl bg-white p-5 ring-1 ring-zinc-100">
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $channel['path'] }}"/></svg>
                            </span>
                            <div class="min-w-0">
                                <p class="text-sm font-bold text-zinc-900">{{ $channel['label'] }}</p>
                                @if ($channel['href'])
                                    <a href="{{ $channel['href'] }}" class="mt-0.5 block break-words text-sm font-medium text-blue-600 hover:underline">{{ $channel['value'] }}</a>
                                @else
                                    <p class="mt-0.5 text-sm text-zinc-600">{{ $channel['value'] }}</p>
                                @endif
                            </div>
                        </div>
                    @endforeach

                    {{-- Self-service + socials --}}
                    <div class="rounded-2xl bg-blue-600 p-6">
                        <p class="text-sm font-bold text-white">Prefer to help yourself?</p>
                        <p class="mt-1 text-xs leading-relaxed text-blue-100">Browse common answers in our Help Center, available any time.</p>
                        <a href="{{ route('shop.help') }}" wire:navigate class="mt-4 inline-flex items-center gap-2 rounded-[6px] bg-white px-4 py-2.5 text-sm font-semibold text-blue-700 transition-colors hover:bg-blue-50">
                            Visit Help Center
                        </a>
                        <div class="mt-5 flex items-center gap-2 border-t border-white/15 pt-5">
                            <a href="https://facebook.com/rshoprefills" target="_blank" rel="noopener noreferrer" aria-label="Facebook" class="flex h-9 w-9 items-center justify-center rounded-lg bg-white/10 text-white transition-colors hover:bg-white/20">
                                <svg viewBox="0 0 24 24" class="h-4 w-4" fill="currentColor" aria-hidden="true"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                            </a>
                            <a href="https://x.com/rshoprefills" target="_blank" rel="noopener noreferrer" aria-label="X" class="flex h-9 w-9 items-center justify-center rounded-lg bg-white/10 text-white transition-colors hover:bg-white/20">
                                <svg viewBox="0 0 24 24" class="h-3.5 w-3.5" fill="currentColor" aria-hidden="true"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117L17.083 19.77z"/></svg>
                            </a>
                            <a href="https://instagram.com/rshoprefills" target="_blank" rel="noopener noreferrer" aria-label="Instagram" class="flex h-9 w-9 items-center justify-center rounded-lg bg-white/10 text-white transition-colors hover:bg-white/20">
                                <svg viewBox="0 0 24 24" class="h-4 w-4" fill="currentColor" aria-hidden="true"><path d="M12 2.163c3.204 0 3.584.012 4.849.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.07 1.644.07 4.849 0 3.205-.012 3.584-.07 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.849.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

</x-layouts.app.header>
