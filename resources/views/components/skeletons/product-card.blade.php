{{--
    Skeleton placeholder for a catalog product card (gift card / eSIM / top-up).
    Zero-CLS: matches the real card exactly — a 16:10 rounded-[15px] tile + a two-line
    caption in a mt-2 block. See resources/views/shop/gift-cards.blade.php.
--}}
<div {{ $attributes }} aria-hidden="true">
    <x-skeleton class="aspect-[16/10] w-full ring-1 ring-zinc-200" rounded="rounded-[15px]" />
    <div class="mt-2 px-0.5">
        {{-- Brand name — a wide bar. --}}
        <x-skeleton class="h-[15px] w-3/4" />
        {{-- Denomination range — a shorter bar, mimics real content density. --}}
        <x-skeleton class="mt-1.5 h-[14px] w-2/5" />
    </div>
</div>
