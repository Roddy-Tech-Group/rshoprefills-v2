<?php

use App\Models\CurrencyRate;
use App\Models\Product;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;

new
#[Layout('components.layouts.admin')]
#[Title('Rate Management')]
class extends Component {
    /** Row currently being edited (id) or null. */
    public ?int $editingId = null;

    /** Editable form bound to the row in edit mode (or to the "add new" form). */
    public string $code = '';
    public string $name = '';
    public string $type = 'fiat';
    public string $rate_per_usd = '';
    public ?string $icon_path = null;
    public int $sort_order = 0;
    public bool $is_active = true;

    /** Toggles the "Add new currency" inline form. */
    public bool $creating = false;

    /**
     * ISO country override for fiat codes whose first two letters aren't a
     * country code. Everything else derives the flag from the code prefix
     * (USD -> US, GBP -> GB, EUR -> EU, ...).
     */
    private const FLAG_OVERRIDES = [
        'XAF' => 'CM', // Central African CFA franc
        'XOF' => 'SN', // West African CFA franc
        'XCD' => 'AG', // East Caribbean dollar
    ];

    /** Country flag for a fiat currency code, or null. */
    public function flagUrl(string $code): ?string
    {
        $code = strtoupper($code);

        return Product::flagUrl(self::FLAG_OVERRIDES[$code] ?? substr($code, 0, 2));
    }

    protected function rules(): array
    {
        return [
            'code' => [
                'required',
                'string',
                'max:10',
                \Illuminate\Validation\Rule::unique('currency_rates', 'code')->ignore($this->editingId),
            ],
            'name' => 'required|string|max:60',
            'type' => 'required|in:fiat,crypto',
            'rate_per_usd' => 'required|numeric|min:0',
            'icon_path' => 'nullable|string|max:120',
            'sort_order' => 'integer|min:0',
            'is_active' => 'boolean',
        ];
    }

    #[Computed]
    public function rates()
    {
        return CurrencyRate::orderBy('type')->orderBy('sort_order')->orderBy('code')->get();
    }

    public function startEdit(int $id): void
    {
        $rate = CurrencyRate::findOrFail($id);
        $this->editingId   = $rate->id;
        $this->code        = $rate->code;
        $this->name        = $rate->name;
        $this->type        = $rate->type;
        $this->rate_per_usd = (string) $rate->rate_per_usd;
        $this->icon_path   = $rate->icon_path;
        $this->sort_order  = $rate->sort_order;
        $this->is_active   = $rate->is_active;
        $this->creating    = false;
    }

    public function cancelEdit(): void
    {
        $this->reset(['editingId', 'code', 'name', 'type', 'rate_per_usd', 'icon_path', 'sort_order', 'is_active', 'creating']);
        $this->type = 'fiat';
        $this->is_active = true;
    }

    public function save(): void
    {
        $data = $this->validate();

        if ($this->editingId) {
            CurrencyRate::findOrFail($this->editingId)->update($data);
        } else {
            CurrencyRate::create($data);
        }

        $this->cancelEdit();
        unset($this->rates); // bust the computed cache
    }

    public function startCreate(): void
    {
        $this->cancelEdit();
        $this->creating = true;
    }

    public function delete(int $id): void
    {
        CurrencyRate::findOrFail($id)->delete();
        if ($this->editingId === $id) {
            $this->cancelEdit();
        }
        unset($this->rates);
    }

    public function toggleActive(int $id): void
    {
        $rate = CurrencyRate::findOrFail($id);
        $rate->update(['is_active' => ! $rate->is_active]);
        unset($this->rates);
    }
}; ?>

<div>
    <x-slot:heading>Rate Management</x-slot:heading>
    <x-slot:subheading>Currencies and exchange rates accepted across the platform. Rates expressed as "1 USD = X of this currency".</x-slot:subheading>

    <div class="flex flex-1 flex-col gap-6">

        {{-- Header: centered counts line-up + a small right-aligned Add button. --}}
        <div class="flex items-center gap-3">
            <div class="flex flex-1 flex-wrap justify-center gap-x-5 gap-y-2 text-sm">
                <span class="flex items-center gap-2">
                    <span class="inline-block h-2 w-2 rounded-full bg-emerald-500"></span>
                    <span class="font-semibold text-zinc-900">{{ $this->rates->where('type', 'fiat')->count() }}</span>
                    <span class="text-zinc-600">fiat</span>
                </span>
                <span class="flex items-center gap-2">
                    <span class="inline-block h-2 w-2 rounded-full bg-amber-500"></span>
                    <span class="font-semibold text-zinc-900">{{ $this->rates->where('type', 'crypto')->count() }}</span>
                    <span class="text-zinc-600">crypto</span>
                </span>
                <span class="flex items-center gap-2">
                    <span class="inline-block h-2 w-2 rounded-full bg-zinc-400"></span>
                    <span class="font-semibold text-zinc-900">{{ $this->rates->where('is_active', false)->count() }}</span>
                    <span class="text-zinc-600">inactive</span>
                </span>
            </div>

            <button
                type="button"
                wire:click="startCreate"
                class="inline-flex shrink-0 items-center gap-1.5 rounded-lg bg-blue-600 px-3 py-2 text-xs font-semibold text-white shadow-sm transition-colors hover:bg-blue-700"
            >
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/>
                </svg>
                Add currency
            </button>
        </div>

        {{-- Table. On mobile: Name hidden below lg, Type hidden below md (folded into the
             Currency cell as a small badge), tighter padding, scrollbar hidden. --}}
        <div class="overflow-hidden rounded-[20px] bg-white shadow-sm shadow-zinc-900/5 ring-1 ring-zinc-100">
            <div class="overflow-x-auto [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                <table class="min-w-full text-sm">
                    <thead class="border-b border-zinc-100 bg-zinc-50/60">
                        <tr class="text-left text-[10px] font-semibold uppercase tracking-[0.1em] text-zinc-600">
                            <th class="px-3 py-3 font-semibold sm:px-5">Logo</th>
                            <th class="px-3 py-3 font-semibold sm:px-5">Currency</th>
                            <th class="hidden px-3 py-3 font-semibold sm:px-5 lg:table-cell">Name</th>
                            <th class="hidden px-3 py-3 font-semibold sm:px-5 md:table-cell">Type</th>
                            <th class="px-3 py-3 font-semibold sm:px-5">Rate</th>
                            <th class="px-3 py-3 font-semibold sm:px-5">Active</th>
                            <th class="px-3 py-3 text-right font-semibold sm:px-5">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100/80">

                        {{-- Inline create row --}}
                        @if ($creating)
                            <tr class="bg-blue-50/30">
                                <td class="px-3 py-3.5 sm:px-5">
                                    <input wire:model="icon_path" type="text" placeholder="BTC.svg" class="w-24 rounded-md border border-zinc-200 px-2 py-1 text-xs">
                                </td>
                                <td class="px-3 py-3.5 sm:px-5">
                                    <input wire:model="code" type="text" placeholder="USD" class="w-20 rounded-md border border-zinc-200 px-2 py-1 text-sm font-bold uppercase">
                                    @error('code') <p class="mt-1 text-[10px] text-red-600">{{ $message }}</p> @enderror
                                </td>
                                <td class="hidden px-3 py-3.5 sm:px-5 lg:table-cell">
                                    <input wire:model="name" type="text" placeholder="Currency name" class="w-44 rounded-md border border-zinc-200 px-2 py-1 text-sm">
                                </td>
                                <td class="hidden px-3 py-3.5 sm:px-5 md:table-cell">
                                    <select wire:model="type" class="rounded-md border border-zinc-200 px-2 py-1 text-sm">
                                        <option value="fiat">fiat</option>
                                        <option value="crypto">crypto</option>
                                    </select>
                                </td>
                                <td class="px-3 py-3.5 sm:px-5">
                                    <input wire:model="rate_per_usd" type="number" step="0.00000001" min="0" placeholder="0.00" class="w-32 rounded-md border border-zinc-200 px-2 py-1 text-sm tabular-nums sm:w-40">
                                    @error('rate_per_usd') <p class="mt-1 text-[10px] text-red-600">{{ $message }}</p> @enderror
                                </td>
                                <td class="px-3 py-3.5 sm:px-5">
                                    <label class="inline-flex items-center gap-2 cursor-pointer">
                                        <input wire:model="is_active" type="checkbox" class="rounded">
                                        <span class="text-xs text-zinc-600">Yes</span>
                                    </label>
                                </td>
                                <td class="px-3 py-3.5 text-right sm:px-5">
                                    <div class="flex justify-end gap-1.5">
                                        <button wire:click="save" class="inline-flex items-center gap-1 rounded-md bg-emerald-600 px-2.5 py-1 text-xs font-semibold text-white transition-colors hover:bg-emerald-700">Save</button>
                                        <button wire:click="cancelEdit" class="inline-flex items-center gap-1 rounded-md bg-zinc-200 px-2.5 py-1 text-xs font-semibold text-zinc-700 transition-colors hover:bg-zinc-300">Cancel</button>
                                    </div>
                                </td>
                            </tr>
                        @endif

                        @forelse ($this->rates as $rate)
                            @if ($editingId === $rate->id)
                                {{-- Edit row --}}
                                <tr class="bg-blue-50/30">
                                    <td class="px-3 py-3.5 sm:px-5">
                                        <input wire:model="icon_path" type="text" placeholder="BTC.svg" class="w-24 rounded-md border border-zinc-200 px-2 py-1 text-xs">
                                    </td>
                                    <td class="px-3 py-3.5 sm:px-5">
                                        <input wire:model="code" type="text" class="w-20 rounded-md border border-zinc-200 px-2 py-1 text-sm font-bold uppercase">
                                        @error('code') <p class="mt-1 text-[10px] text-red-600">{{ $message }}</p> @enderror
                                    </td>
                                    <td class="hidden px-3 py-3.5 sm:px-5 lg:table-cell">
                                        <input wire:model="name" type="text" class="w-44 rounded-md border border-zinc-200 px-2 py-1 text-sm">
                                    </td>
                                    <td class="hidden px-3 py-3.5 sm:px-5 md:table-cell">
                                        <select wire:model="type" class="rounded-md border border-zinc-200 px-2 py-1 text-sm">
                                            <option value="fiat">fiat</option>
                                            <option value="crypto">crypto</option>
                                        </select>
                                    </td>
                                    <td class="px-3 py-3.5 sm:px-5">
                                        <input wire:model="rate_per_usd" type="number" step="0.00000001" min="0" class="w-32 rounded-md border border-zinc-200 px-2 py-1 text-sm tabular-nums sm:w-40">
                                        @error('rate_per_usd') <p class="mt-1 text-[10px] text-red-600">{{ $message }}</p> @enderror
                                    </td>
                                    <td class="px-3 py-3.5 sm:px-5">
                                        <label class="inline-flex items-center gap-2 cursor-pointer">
                                            <input wire:model="is_active" type="checkbox" class="rounded">
                                            <span class="text-xs text-zinc-600">Yes</span>
                                        </label>
                                    </td>
                                    <td class="px-3 py-3.5 text-right sm:px-5">
                                        <div class="flex justify-end gap-1.5">
                                            <button wire:click="save" class="inline-flex items-center gap-1 rounded-md bg-emerald-600 px-2.5 py-1 text-xs font-semibold text-white transition-colors hover:bg-emerald-700">Save</button>
                                            <button wire:click="cancelEdit" class="inline-flex items-center gap-1 rounded-md bg-zinc-200 px-2.5 py-1 text-xs font-semibold text-zinc-700 transition-colors hover:bg-zinc-300">Cancel</button>
                                        </div>
                                    </td>
                                </tr>
                            @else
                                {{-- Read-only row --}}
                                <tr class="transition-colors duration-150 hover:bg-blue-50/40">
                                    <td class="px-3 py-3.5 sm:px-5">
                                        @if ($rate->icon_path)
                                            <img src="{{ asset('assets/' . $rate->icon_path) }}" alt="{{ $rate->code }}" class="h-9 w-9 rounded-full object-contain bg-white ring-1 ring-zinc-100" loading="lazy">
                                        @elseif ($rate->type === 'fiat' && $this->flagUrl($rate->code))
                                            <img src="{{ $this->flagUrl($rate->code) }}" alt="{{ $rate->code }}" class="h-9 w-9 rounded-full object-cover bg-white ring-1 ring-zinc-100" loading="lazy">
                                        @else
                                            <span class="flex h-9 w-9 items-center justify-center rounded-full {{ $rate->type === 'crypto' ? 'bg-amber-500' : 'bg-emerald-500' }} text-xs font-bold text-white">
                                                {{ substr($rate->code, 0, 1) }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-3.5 sm:px-5">
                                        <span class="font-bold tabular-nums text-zinc-900">{{ $rate->code }}</span>
                                        {{-- Type folded under the code on mobile where the Type column is hidden. --}}
                                        <span class="mt-0.5 block md:hidden">
                                            <span class="inline-flex items-center rounded-[5px] px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wide text-white {{ $rate->type === 'crypto' ? 'bg-amber-500' : 'bg-emerald-500' }}">{{ $rate->type }}</span>
                                        </span>
                                    </td>
                                    <td class="hidden px-3 py-3.5 text-zinc-700 sm:px-5 lg:table-cell">{{ $rate->name }}</td>
                                    <td class="hidden px-3 py-3.5 sm:px-5 md:table-cell">
                                        <span class="inline-flex items-center rounded-[5px] px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-white {{ $rate->type === 'crypto' ? 'bg-amber-500' : 'bg-emerald-500' }}">{{ $rate->type }}</span>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-3.5 tabular-nums text-zinc-900 sm:px-5">{{ rtrim(rtrim(number_format((float) $rate->rate_per_usd, 8), '0'), '.') }}</td>
                                    <td class="px-3 py-3.5 sm:px-5">
                                        <button wire:click="toggleActive({{ $rate->id }})" type="button" class="inline-flex items-center rounded-full {{ $rate->is_active ? 'bg-emerald-500 text-white' : 'bg-zinc-300 text-zinc-700' }} px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wide transition-colors">
                                            {{ $rate->is_active ? 'Active' : 'Inactive' }}
                                        </button>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-3.5 text-right sm:px-5">
                                        <button wire:click="startEdit({{ $rate->id }})" class="text-xs font-semibold text-blue-600 transition-colors hover:text-blue-700">Edit</button>
                                        <button wire:click="delete({{ $rate->id }})" wire:confirm="Delete {{ $rate->code }}? This can't be undone." class="ml-2 text-xs font-semibold text-red-600 transition-colors hover:text-red-700">Delete</button>
                                    </td>
                                </tr>
                            @endif
                        @empty
                            <tr>
                                <td colspan="7" class="px-5 py-16 text-center">
                                    <p class="text-base font-semibold text-zinc-900">No currencies yet</p>
                                    <p class="mt-1 text-sm text-zinc-600">Click "Add currency" to define your first rate.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
