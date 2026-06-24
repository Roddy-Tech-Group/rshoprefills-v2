@props(['options', 'placeholder' => 'Select an option...', 'label' => null, 'searchable' => false, 'required' => false])

@php
// Validate options format
$formattedOptions = collect($options)->map(function ($value, $key) {
    if (is_array($value) && isset($value['value']) && isset($value['label'])) {
        return $value;
    }
    return ['value' => $key, 'label' => $value];
})->values()->all();
@endphp

<div 
    data-options="{{ json_encode($formattedOptions) }}"
    x-data="{
        search: '',
        open: false,
        options: [],
        selectedValue: @entangle($attributes->wire('model')),
        init() {
            this.options = JSON.parse(this.$el.dataset.options);
            this.$watch('search', () => {});
            
            // Watch for DOM changes (Livewire updates)
            let observer = new MutationObserver(() => {
                this.options = JSON.parse(this.$el.dataset.options);
            });
            observer.observe(this.$el, { attributes: true, attributeFilter: ['data-options'] });
        },
        get filteredOptions() {
        if (this.search === '') return this.options;
        return this.options.filter(opt => opt.label && String(opt.label).toLowerCase().includes(this.search.toLowerCase()));
    },
    get selectedLabel() {
        if (!this.selectedValue) return '{{ $placeholder }}';
        const opt = this.options.find(o => o.value == this.selectedValue);
        return opt ? opt.label : '{{ $placeholder }}';
    },
    selectOption(val) {
        this.selectedValue = val;
        this.search = '';
        this.open = false;
    }
}" @click.away="open = false" class="relative w-full {{ $attributes->get('class') }}">
    @if($label)
        <flux:label class="mb-2">{{ $label }} @if($required) <span class="text-red-500">*</span> @endif</flux:label>
    @endif
    
    <button type="button" @click="open = !open" class="w-full flex items-center justify-between px-3 py-2 border rounded-lg text-left shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white">
        <span x-text="selectedLabel" :class="!selectedValue ? 'text-zinc-500 dark:text-zinc-400' : ''"></span>
        <svg class="h-5 w-5 text-zinc-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
        </svg>
    </button>
    
    <div x-show="open" x-transition.opacity class="absolute z-50 w-full mt-1 bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-lg shadow-lg" style="display: none;">
        @if($searchable)
            <div class="p-2 border-b border-zinc-200 dark:border-zinc-700">
                <input type="text" x-model="search" placeholder="Search..." class="w-full px-3 py-1.5 text-sm border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white">
            </div>
        @endif
        <ul class="max-h-60 overflow-y-auto py-1 text-sm text-zinc-700 dark:text-zinc-300">
            <template x-for="opt in filteredOptions" :key="opt.value">
                <li @click="selectOption(opt.value)" class="px-3 py-2 cursor-pointer hover:bg-zinc-100 dark:hover:bg-zinc-700" :class="{ 'bg-zinc-100 dark:bg-zinc-700 font-semibold': selectedValue == opt.value }">
                    <span x-text="opt.label"></span>
                </li>
            </template>
            <template x-if="filteredOptions.length === 0">
                <li class="px-3 py-2 text-zinc-500 italic">No options found.</li>
            </template>
        </ul>
    </div>
</div>
