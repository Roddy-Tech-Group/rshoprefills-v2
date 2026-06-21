{{-- Storefront announcement / coupon bar. A thin blue strip at the very top of
     every storefront page that rotates through up to three admin-set messages
     (announcement.promo_1..3 on the System Settings page). Blank slots are
     skipped; if all three are blank the bar renders nothing. Mirrors the
     maintenance banner pattern so admins can promote a coupon any time without
     a deploy ("Use LAUNCHV2 to get 9% off"). --}}
@php
    $promoTexts = collect([
        \App\Models\SiteSetting::get('announcement.promo_1', ''),
        \App\Models\SiteSetting::get('announcement.promo_2', ''),
        \App\Models\SiteSetting::get('announcement.promo_3', ''),
    ])->map(fn ($t) => trim((string) $t))->filter()->values();
@endphp

@if ($promoTexts->isNotEmpty())
    <div
        x-data="{
            open: true,
            show: true,
            i: 0,
            texts: @js($promoTexts->all()),
            init() {
                if (this.texts.length > 1) {
                    setInterval(() => {
                        this.show = false;
                        setTimeout(() => {
                            this.i = (this.i + 1) % this.texts.length;
                            this.show = true;
                        }, 320);
                    }, 5000);
                }
            },
        }"
        x-show="open"
        x-cloak
        id="rshop-promo-bar"
        role="status"
        aria-live="polite"
        class="relative z-[60] flex items-center justify-center gap-2.5 bg-blue-600 px-10 py-2.5 text-center text-sm font-semibold text-white"
    >
        <svg class="h-4 w-4 shrink-0 text-blue-100" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z"/>
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6Z"/>
        </svg>
        <span
            class="transition-opacity duration-300"
            :class="show ? 'opacity-100' : 'opacity-0'"
            x-text="texts[i]"
        >{{ $promoTexts->first() }}</span>
        <button
            type="button"
            @click="open = false"
            aria-label="Dismiss"
            class="absolute right-3 top-1/2 flex h-7 w-7 -translate-y-1/2 items-center justify-center rounded-[6px] text-blue-100/80 transition-colors hover:bg-blue-500/40 hover:text-white"
        >
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>
@endif
