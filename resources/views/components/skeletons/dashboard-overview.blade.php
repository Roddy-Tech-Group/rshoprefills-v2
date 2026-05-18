{{--
    Zero-CLS skeleton for the customer dashboard overview (livewire/dashboard/overview).
    Shown as the #[Lazy] placeholder while the real component boots. Mirrors the real
    layout — same card shells, grid columns and gaps — so nothing jumps when the data
    arrives. Single root element (Livewire placeholder requirement).
--}}
<div aria-hidden="true">

    {{-- ── MOBILE (< lg) ───────────────────────────────────────── --}}
    <div class="flex flex-col gap-5 lg:hidden">

        {{-- Quick Actions card --}}
        <div class="skeleton-stagger-fast rounded-2xl bg-white p-5 shadow-sm shadow-zinc-900/5 ring-1 ring-zinc-100">
            <x-skeleton class="h-5 w-32" style="--i: 0" />
            <div class="mt-4 grid grid-cols-3 gap-3">
                @for ($i = 0; $i < 6; $i++)
                    <div class="flex flex-col items-center gap-1.5" style="--i: {{ $i }}">
                        <x-skeleton shape="circle" class="h-12 w-12" />
                        <x-skeleton class="h-3 w-12" />
                    </div>
                @endfor
            </div>
        </div>

        {{-- Promo block --}}
        <x-skeleton class="h-[148px] w-full" rounded="rounded-2xl" />
    </div>

    {{-- ── DESKTOP (lg+) ───────────────────────────────────────── --}}
    <div class="hidden lg:block">
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">

            {{-- LEFT column --}}
            <div class="flex flex-col gap-6 lg:col-span-8">

                {{-- Heading row --}}
                <div class="flex items-start justify-between">
                    <div class="space-y-2">
                        <x-skeleton class="h-8 w-72" />
                        <x-skeleton class="h-4 w-60" />
                    </div>
                    <x-skeleton class="h-9 w-28" rounded="rounded-xl" />
                </div>

                {{-- Wallet / Quick Actions / Recent Order row --}}
                <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
                    @for ($i = 0; $i < 3; $i++)
                        <div class="rounded-2xl bg-white p-5 shadow-sm shadow-zinc-900/5 ring-1 ring-zinc-100">
                            <x-skeleton class="h-4 w-24" />
                            <x-skeleton class="mt-4 h-7 w-32" />
                            <div class="mt-5 space-y-2.5">
                                <x-skeleton class="h-3.5 w-full" />
                                <x-skeleton class="h-3.5 w-4/5" />
                                <x-skeleton class="h-3.5 w-2/3" />
                            </div>
                        </div>
                    @endfor
                </div>

                {{-- Trust strip --}}
                <div class="flex items-center gap-4 rounded-2xl bg-white p-5 shadow-sm shadow-zinc-900/5 ring-1 ring-zinc-100">
                    <x-skeleton class="h-12 w-12 shrink-0" rounded="rounded-2xl" />
                    <div class="flex-1 space-y-2">
                        <x-skeleton class="h-4 w-48" />
                        <x-skeleton class="h-3 w-72" />
                    </div>
                    <x-skeleton class="h-4 w-20 shrink-0" />
                </div>

                {{-- Shop by Category + Recommended card --}}
                <div class="rounded-2xl bg-white shadow-sm shadow-zinc-900/5 ring-1 ring-zinc-100">
                    <div class="p-6">
                        <x-skeleton class="h-5 w-40" />
                        <div class="mt-4 grid grid-cols-8 gap-3">
                            @for ($i = 0; $i < 8; $i++)
                                <div class="flex flex-col items-center gap-2">
                                    <x-skeleton shape="circle" class="h-14 w-14" />
                                    <x-skeleton class="h-3 w-12" />
                                </div>
                            @endfor
                        </div>
                    </div>
                    <div class="border-t border-zinc-100"></div>
                    <div class="p-6">
                        <x-skeleton class="h-5 w-48" />
                        <div class="mt-4 grid grid-cols-4 gap-3">
                            @for ($i = 0; $i < 4; $i++)
                                <x-skeleton class="h-32 w-full" rounded="rounded-2xl" />
                            @endfor
                        </div>
                    </div>
                </div>
            </div>

            {{-- RIGHT rail --}}
            <div class="flex flex-col gap-6 lg:col-span-4">

                {{-- Rcoin card --}}
                <div class="rounded-2xl bg-white p-5 shadow-sm shadow-zinc-900/5 ring-1 ring-zinc-100">
                    <div class="flex items-start gap-3">
                        <x-skeleton class="h-10 w-10 shrink-0" rounded="rounded-xl" />
                        <div class="flex-1 space-y-2">
                            <x-skeleton class="h-3.5 w-24" />
                            <x-skeleton class="h-6 w-28" />
                        </div>
                    </div>
                    <x-skeleton class="mt-4 h-3 w-56" />
                    <x-skeleton class="mt-2 h-2 w-full" rounded="rounded-full" />
                    <x-skeleton class="mt-4 h-10 w-full" rounded="rounded-xl" />
                </div>

                {{-- Promo block --}}
                <x-skeleton class="h-44 w-full" rounded="rounded-2xl" />

                {{-- Recent Transactions card --}}
                <div class="rounded-2xl bg-white p-5 shadow-sm shadow-zinc-900/5 ring-1 ring-zinc-100">
                    <div class="flex items-center justify-between">
                        <x-skeleton class="h-5 w-36" />
                        <x-skeleton class="h-3.5 w-12" />
                    </div>
                    <div class="skeleton-stagger-fast mt-4 space-y-3">
                        @for ($i = 0; $i < 4; $i++)
                            <div class="flex items-center gap-3" style="--i: {{ $i }}">
                                <x-skeleton class="h-11 w-11 shrink-0" rounded="rounded-xl" />
                                <div class="min-w-0 flex-1 space-y-2">
                                    <x-skeleton class="h-3.5 w-36 max-w-[70%]" />
                                    <x-skeleton class="h-3 w-20 max-w-[45%]" />
                                </div>
                                <div class="flex shrink-0 flex-col items-end gap-2">
                                    <x-skeleton class="h-3.5 w-14" />
                                    <x-skeleton class="h-3 w-10" />
                                </div>
                            </div>
                        @endfor
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
