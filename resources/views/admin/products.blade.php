{{--
    All Products page.

    Backend hook: when the Product model + admin routes ship, replace the placeholder grid with the real product list
    (filter by $activeCategory) and swap href="#" on the category pills for route('admin.products', ['category' => ...]).

    Until then this is pure UI — Alpine handles the active pill state locally so the design renders without backend.
--}}
<x-layouts.app>
    <div class="flex flex-1 flex-col gap-6">

        {{-- Heading row --}}
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-zinc-900 sm:text-3xl">All Products</h1>
                <p class="mt-1 text-sm text-zinc-500">Manage products and services across every category.</p>
            </div>

            <button type="button" class="inline-flex items-center gap-2 self-start rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-blue-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/40 sm:self-auto">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M12 5v14M5 12h14"/>
                </svg>
                Add product
            </button>
        </div>

        {{-- Category filter pills (Alpine state for now; backend swaps to wire:click when filter logic ships) --}}
        <div
            x-data="{
                active: 'All',
                categories: ['All', 'Gift Cards', 'eSIMs', 'Mobile Top-ups', 'Bill Payments', 'Flights', 'Stays', 'Other']
            }"
            class="-mx-1 overflow-x-auto px-1 pb-1 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
        >
            <div class="flex w-max items-center gap-2">
                <template x-for="cat in categories" :key="cat">
                    <button
                        type="button"
                        @click="active = cat"
                        :class="active === cat
                            ? 'bg-zinc-900 text-white ring-zinc-900'
                            : 'bg-white text-zinc-800 ring-zinc-200 hover:bg-zinc-50'"
                        class="inline-flex items-center rounded-full px-5 py-2 text-sm font-semibold ring-1 transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/40"
                    >
                        <span x-text="cat"></span>
                    </button>
                </template>
            </div>
        </div>

        {{-- Product grid placeholder. Backend will loop $products->where('category', $activeCategory) inside this container. --}}
        <div class="rounded-[20px] bg-white shadow-sm shadow-zinc-900/5 ring-1 ring-zinc-100">
            <div class="flex flex-col items-center justify-center px-6 py-20 text-center">
                <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-blue-50">
                    <svg class="h-7 w-7 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007zM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0z"/>
                    </svg>
                </span>
                <h3 class="mt-4 text-base font-semibold text-zinc-900">No products yet</h3>
                <p class="mt-1 max-w-sm text-sm text-zinc-500">
                    Products will appear here once the Product model and admin endpoints ship.
                    Use the category pills above to filter by type.
                </p>
                <button type="button" class="mt-5 inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-blue-700">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M12 5v14M5 12h14"/>
                    </svg>
                    Add your first product
                </button>
            </div>
        </div>

    </div>
</x-layouts.app>
