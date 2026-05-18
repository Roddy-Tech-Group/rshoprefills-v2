<?php

use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new
#[Layout('components.layouts.dashboard')]
#[Title('Order history')]
class extends Component {
    use WithPagination;

    /** Search by order id / order number. */
    public string $search = '';

    /** Show terminal (failed / cancelled) orders instead of active ones. */
    public bool $expired = false;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedExpired(): void
    {
        $this->resetPage();
    }

    public function with(): array
    {
        $query = Auth::user()->orders()->with('items')->latest();

        if (($term = trim($this->search)) !== '') {
            $query->where(fn ($q) => $q->where('id', 'like', "%{$term}%")
                ->orWhere('order_number', 'like', "%{$term}%"));
        }

        // No backend "expired" field exists — failed/cancelled orders are the
        // closest meaningful split, so the toggle flips between the two sets.
        if ($this->expired) {
            $query->whereIn('order_status', ['failed', 'cancelled']);
        } else {
            $query->whereNotIn('order_status', ['failed', 'cancelled']);
        }

        return ['orders' => $query->paginate(10)];
    }
}; ?>

@php
    $countryNames = array_flip(config('countries.codes', [])); // ISO -> name

    // Status word + colour for the order summary line.
    $statusUi = [
        'completed'           => ['Succeeded', 'text-emerald-600'],
        'partially_completed' => ['Partially complete', 'text-amber-600'],
        'processing'          => ['Processing', 'text-blue-600'],
        'pending'             => ['Pending', 'text-amber-600'],
        'failed'              => ['Failed', 'text-red-600'],
        'cancelled'           => ['Cancelled', 'text-zinc-500'],
        'requires_attention'  => ['Needs review', 'text-amber-600'],
    ];

    // Generic payment label — the gateway/provider name is never shown.
    $methodLabels = ['wallet' => 'Wallet', 'crypto' => 'Crypto', 'flutterwave' => 'Card'];

    // Pull a copyable redemption code out of an order item's fulfillment payload.
    $extractCode = function ($item): ?string {
        $payload = (array) ($item->fulfillment_payload ?? []);
        foreach (['pin', 'code', 'redeem_code', 'voucher_code', 'card_number'] as $key) {
            if (! empty($payload[$key]) && is_scalar($payload[$key])) {
                return (string) $payload[$key];
            }
        }
        foreach ($payload as $value) {
            if (is_scalar($value) && $value !== '') {
                return (string) $value;
            }
        }

        return null;
    };
@endphp

<div class="mx-auto flex w-full max-w-3xl flex-col gap-5">

    {{-- Heading --}}
    <h1 class="text-2xl font-bold tracking-tight text-zinc-900 sm:text-3xl">Order history</h1>

    {{-- Search + expired filter --}}
    <div>
        <label for="order-search" class="block text-sm font-bold text-zinc-900">Enter your order id</label>
        <div class="mt-2 flex items-center gap-3">
            <div class="relative flex-1">
                <input
                    id="order-search"
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="019e34c4-9b70-726f-96cc-cf42b222d88a"
                    class="w-full rounded-[10px] border-2 border-zinc-100 bg-white px-4 py-3 text-sm text-zinc-900 placeholder:text-zinc-400 outline-none transition-colors focus:border-blue-500 focus:ring-2 focus:ring-blue-500/15"
                >
                <div wire:loading wire:target="search" class="absolute right-3 top-1/2 -translate-y-1/2">
                    <svg class="h-4 w-4 animate-spin text-blue-600" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <circle class="opacity-30" cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3"/>
                        <path class="opacity-90" fill="currentColor" d="M12 3a9 9 0 0 1 9 9h-3a6 6 0 0 0-6-6V3z"/>
                    </svg>
                </div>
            </div>
            <label class="flex shrink-0 cursor-pointer items-center gap-2.5 rounded-xl px-1 py-2">
                <input type="checkbox" wire:model.live="expired" class="h-5 w-5 rounded-md border-2 border-zinc-300 text-blue-600 focus:ring-2 focus:ring-blue-500/30">
                <span class="text-sm font-semibold text-zinc-700">Expired orders</span>
            </label>
        </div>
    </div>

    @if ($orders->isNotEmpty())
        {{-- All orders live in ONE card; each order is a drop-down row. --}}
        <div class="divide-y divide-zinc-200 overflow-hidden rounded-[10px] border-2 border-zinc-100 bg-white">
            @foreach ($orders as $order)
                @php
                    [$statusLabel, $statusColor] = $statusUi[$order->order_status->value] ?? ['Placed', 'text-amber-600'];
                    $points = (int) floor((float) $order->total_amount * 0.5);
                @endphp
                <div x-data="{ open: false }" wire:key="order-{{ $order->id }}">
                    {{-- Header (always visible) --}}
                    <button type="button" @click="open = !open" class="flex w-full items-center gap-3 px-5 pt-4 text-left">
                        <span class="min-w-0 flex-1 truncate text-sm text-zinc-400">
                            {{ ($order->placed_at ?? $order->created_at)->format('d/m/Y') }}
                            <span class="px-1">|</span>
                            <span class="text-zinc-500">{{ $order->id }}</span>
                        </span>
                        <svg class="h-5 w-5 shrink-0 text-zinc-500 transition-transform duration-200" :class="open && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>

                    {{-- Summary (always visible) --}}
                    <div class="px-5 pb-4 pt-2.5">
                        <p class="text-sm font-semibold {{ $statusColor }}">{{ $statusLabel }}</p>
                        @foreach ($order->items as $item)
                            @php
                                $snap     = $item->product_snapshot ?? [];
                                $vsnap    = $item->variant_snapshot ?? [];
                                $brandKey = $snap['brand_key'] ?? null;
                                $name     = $brandKey ? Product::brandDisplayName($brandKey) : ($snap['name'] ?? 'Item');
                                $faceVal  = $vsnap['face_value'] ?? null;
                                $faceCur  = $vsnap['currency'] ?? ($snap['currency_code'] ?? 'USD');
                                $faceTxt  = $faceVal !== null
                                    ? Product::currencySymbol($faceCur).rtrim(rtrim(number_format((float) $faceVal, 2), '0'), '.')
                                    : null;
                                $country  = $countryNames[strtoupper((string) ($snap['country_code'] ?? ''))] ?? ($snap['country_code'] ?? null);
                            @endphp
                            <p class="mt-1 text-sm text-zinc-900">
                                <span class="font-bold">{{ $item->quantity }}</span>
                                <span class="px-1 text-zinc-400">x</span>
                                <span class="font-medium">{{ $name }}</span>
                                @if ($faceTxt)
                                    <span class="ml-1 font-bold">{{ $faceTxt }}</span>
                                @endif
                                @if ($country)
                                    @if (Product::flagUrl($snap['country_code'] ?? null))
                                        <img src="{{ Product::flagUrl($snap['country_code'] ?? null) }}" alt="" class="ml-1 inline-block h-3 w-[18px] rounded-[1px] object-cover align-[-1px] ring-1 ring-zinc-200">
                                    @endif
                                    <span class="ml-1 font-semibold text-zinc-700">{{ $country }}</span>
                                @endif
                            </p>
                        @endforeach
                    </div>

                    {{-- Expanded detail --}}
                    <div x-show="open" x-collapse x-cloak class="px-5 pb-5">

                        {{-- Meta rows --}}
                        <dl class="space-y-3 text-sm">
                            <div class="flex items-start justify-between gap-4">
                                <dt class="text-zinc-500">Order id</dt>
                                <dd class="flex items-center gap-2 text-right">
                                    <span class="break-all font-medium text-zinc-900">{{ $order->id }}</span>
                                    <button
                                        type="button"
                                        x-data="{ copied: false }"
                                        @click="navigator.clipboard.writeText(@js($order->id)); copied = true; setTimeout(() => copied = false, 1500)"
                                        class="shrink-0 text-zinc-400 transition-colors hover:text-blue-600"
                                        aria-label="Copy order id"
                                    >
                                        <svg x-show="!copied" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 01-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 011.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 00-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 01-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 00-3.375-3.375h-1.5a1.125 1.125 0 01-1.125-1.125v-1.5a3.375 3.375 0 00-3.375-3.375H9.75"/>
                                        </svg>
                                        <svg x-show="copied" x-cloak class="h-4 w-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                                        </svg>
                                    </button>
                                </dd>
                            </div>
                            <div class="flex items-center justify-between gap-4">
                                <dt class="text-zinc-500">Payment method</dt>
                                <dd class="font-semibold text-zinc-900">{{ $methodLabels[$order->payment_method] ?? 'Card' }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-4">
                                <dt class="text-zinc-500">Earned points</dt>
                                <dd class="flex items-center gap-1.5 font-semibold text-zinc-900">
                                    <img src="{{ asset('assets/favicon.ico') }}" alt="" class="h-5 w-5 object-contain">
                                    {{ number_format($points) }}
                                </dd>
                            </div>
                            <div class="flex items-center justify-between gap-4">
                                <dt class="text-zinc-500">Total amount</dt>
                                <dd class="font-bold text-zinc-900">{{ number_format((float) $order->total_amount, 2) }} {{ $order->display_currency }}</dd>
                            </div>
                        </dl>

                        <a href="#" class="mt-4 inline-flex items-center gap-2 text-sm text-zinc-600 underline underline-offset-2 transition-colors hover:text-blue-700">
                            <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9 5.25h.008v.008H12v-.008z"/>
                            </svg>
                            Need help with your order? Contact us!
                        </a>

                        {{-- Gift cards — each delivered item shows the card with its copyable code --}}
                        <div class="mt-5 space-y-4">
                            @foreach ($order->items as $item)
                                @php
                                    $snap     = $item->product_snapshot ?? [];
                                    $vsnap    = $item->variant_snapshot ?? [];
                                    $brandKey = $snap['brand_key'] ?? null;
                                    $name     = $brandKey ? Product::brandDisplayName($brandKey) : ($snap['name'] ?? 'Item');
                                    $logo     = Product::brandLogoUrl($brandKey, $snap['logo_url'] ?? null);
                                    $faceVal  = $vsnap['face_value'] ?? null;
                                    $faceCur  = $vsnap['currency'] ?? ($snap['currency_code'] ?? 'USD');
                                    $faceTxt  = $faceVal !== null
                                        ? Product::currencySymbol($faceCur).rtrim(rtrim(number_format((float) $faceVal, 2), '0'), '.')
                                        : null;
                                    $country  = $countryNames[strtoupper((string) ($snap['country_code'] ?? ''))] ?? ($snap['country_code'] ?? null);
                                    $code     = $extractCode($item);
                                @endphp
                                {{-- Gift card — brand logo + denomination, full width inside the order card.
                                     `theme-static`: resellers screenshot this to deliver to their own
                                     customers, so it must look identical in light and dark mode. --}}
                                <div class="theme-static max-w-[340px] rounded-[10px] border-2 border-zinc-100 bg-zinc-100 px-3 py-1.5">
                                    <div class="flex items-start justify-between gap-3 px-2 pt-2">
                                        <div class="flex min-w-0 flex-col items-start gap-2.5">
                                            @if ($logo)
                                                <img src="{{ $logo }}" alt="{{ $name }}" class="h-16 w-auto max-w-[130px] object-contain mix-blend-multiply" loading="lazy">
                                            @else
                                                <span class="text-3xl font-black uppercase text-zinc-400">{{ str($name)->substr(0, 2)->upper() }}</span>
                                            @endif
                                            <p class="truncate text-sm font-bold text-zinc-900">For {{ $name }}</p>
                                        </div>
                                        <div class="shrink-0 text-right">
                                            @if ($faceTxt)
                                                <p class="text-2xl font-extrabold leading-none text-zinc-900">{{ $faceTxt }}</p>
                                            @endif
                                            @if ($country)
                                                <p class="mt-1 flex items-center justify-end gap-1.5 text-sm text-zinc-600">
                                                    @if (Product::flagUrl($snap['country_code'] ?? null))
                                                        <img src="{{ Product::flagUrl($snap['country_code'] ?? null) }}" alt="" class="h-3 w-[18px] rounded-[1px] object-cover ring-1 ring-zinc-200">
                                                    @endif
                                                    {{ $country }}
                                                </p>
                                            @endif
                                        </div>
                                    </div>

                                    {{-- Card body space --}}
                                    <div class="h-14"></div>

                                    {{-- Pin / code bar --}}
                                    @if ($code)
                                        <div class="flex items-center gap-3 rounded-[10px] border-2 border-zinc-100 bg-white px-4 py-3.5">
                                            <span class="shrink-0 text-xs font-semibold uppercase tracking-wide text-zinc-500">Pin</span>
                                            <span class="min-w-0 flex-1 truncate text-base font-bold tracking-wider text-zinc-900">{{ $code }}</span>
                                            <button
                                                type="button"
                                                x-data="{ copied: false }"
                                                @click="navigator.clipboard.writeText(@js($code)); copied = true; setTimeout(() => copied = false, 1500)"
                                                class="shrink-0 text-zinc-400 transition-colors hover:text-blue-600"
                                                aria-label="Copy redemption code"
                                            >
                                                <svg x-show="!copied" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 01-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 011.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 00-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 01-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 00-3.375-3.375h-1.5a1.125 1.125 0 01-1.125-1.125v-1.5a3.375 3.375 0 00-3.375-3.375H9.75"/>
                                                </svg>
                                                <span x-show="copied" x-cloak class="text-xs font-bold text-emerald-600">Copied</span>
                                            </button>
                                        </div>
                                    @else
                                        <div class="flex items-center gap-2 rounded-[10px] border-2 border-zinc-100 bg-white px-4 py-3 text-xs font-medium text-zinc-500">
                                            <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2m6-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            Your code appears here once payment clears.
                                        </div>
                                    @endif
                                </div>

                                {{-- Redeem / Terms — centered below the card. --}}
                                <div class="mt-2.5 flex max-w-[340px] flex-wrap justify-center gap-x-8 gap-y-1 text-sm">
                                    <a href="#" class="font-medium text-blue-600 transition-colors hover:text-blue-700">Redeem instructions</a>
                                    <a href="#" class="font-medium text-zinc-600 transition-colors hover:text-zinc-900">Terms and conditions</a>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        {{-- Empty state --}}
        <div class="rounded-2xl bg-white px-6 py-16 text-center ring-1 ring-zinc-200">
            <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-blue-50 text-blue-600">
                <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 00-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm12.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z"/>
                </svg>
            </span>
            <p class="mt-4 text-base font-semibold text-zinc-900">
                @if (trim($search) !== '')
                    No orders match that id
                @elseif ($expired)
                    No expired orders
                @else
                    No orders yet
                @endif
            </p>
            <p class="mt-1 text-sm text-zinc-600">
                @if (trim($search) !== '' || $expired)
                    Try a different search or filter.
                @else
                    Your purchases will show up here with their redemption codes.
                @endif
            </p>
            @unless (trim($search) !== '' || $expired)
                <a href="{{ route('shop.gift-cards') }}" wire:navigate class="mt-4 inline-flex items-center gap-1.5 text-sm font-semibold text-blue-600 hover:text-blue-700">
                    Browse gift cards
                </a>
            @endunless
        </div>
    @endif

    @if ($orders->hasPages())
        <div>{{ $orders->links() }}</div>
    @endif
</div>
