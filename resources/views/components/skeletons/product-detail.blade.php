{{--
    Skeleton for a product / refill detail page.
    Zero-CLS: mirrors the two-column detail layout — a hero plate on the left and
    the buy panel (title, description lines, denomination chips, CTA, trust row)
    on the right. See resources/views/shop/product.blade.php.
--}}
<div {{ $attributes->class('grid grid-cols-1 gap-8 lg:grid-cols-2') }} aria-hidden="true">

    {{-- Hero plate. --}}
    <x-skeleton class="aspect-[16/10] w-full ring-1 ring-zinc-200" rounded-[10px]="rounded-[20px]" />

    {{-- Buy panel. --}}
    <div class="flex flex-col gap-4">
        {{-- Title. --}}
        <x-skeleton class="h-7 w-2/3" />

        {{-- Description lines — varying widths. --}}
        <div class="space-y-2">
            <x-skeleton class="h-[14px] w-full" />
            <x-skeleton class="h-[14px] w-11/12" />
            <x-skeleton class="h-[14px] w-4/5" />
        </div>

        {{-- Denomination chips. --}}
        <div class="mt-1 flex flex-wrap gap-2.5">
            @for ($i = 0; $i < 4; $i++)
                <x-skeleton class="h-10 w-20" rounded-[10px]="rounded-[8px]" />
            @endfor
        </div>

        {{-- Buy CTA. --}}
        <x-skeleton class="mt-2 h-[52px] w-full" rounded-[10px]="rounded-[10px]" />

        {{-- Trust row. --}}
        <div class="flex flex-wrap gap-x-6 gap-y-2">
            @for ($i = 0; $i < 3; $i++)
                <x-skeleton class="h-3 w-28" />
            @endfor
        </div>
    </div>
</div>
