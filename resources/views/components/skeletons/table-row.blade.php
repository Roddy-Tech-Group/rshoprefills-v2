{{--
    Skeleton row for a list / ledger (the wallet transactions page, order lists).
    Zero-CLS: matches a real transaction row exactly — flex items-center gap-3
    px-4 py-3.5, a h-10 w-10 rounded-[8px] leading icon, two stacked text lines,
    and a trailing amount + balance. See livewire/dashboard/transactions.blade.php.

    Varying bar widths mimic real content density so the eye reads it as "loading".
--}}
<div {{ $attributes->class('flex items-center gap-3 px-4 py-3.5') }} aria-hidden="true">
    {{-- Leading icon tile. --}}
    <x-skeleton class="h-10 w-10 shrink-0" rounded="rounded-[8px]" />

    {{-- Description + meta. --}}
    <div class="min-w-0 flex-1">
        <x-skeleton class="h-[14px] w-44 max-w-[60%]" />
        <x-skeleton class="mt-2 h-[12px] w-28 max-w-[42%]" />
    </div>

    {{-- Amount + running balance. --}}
    <div class="flex shrink-0 flex-col items-end">
        <x-skeleton class="h-[14px] w-16" />
        <x-skeleton class="mt-2 h-[12px] w-20" />
    </div>
</div>
