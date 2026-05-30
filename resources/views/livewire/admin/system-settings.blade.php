{{-- FILE: resources/views/livewire/admin/system-settings.blade.php --}}
<?php

use App\Models\SiteSetting;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new
#[Layout('components.layouts.admin')]
#[Title('System Settings')]
class extends Component {
    /**
     * Flash message for save feedback.
     * Keyed by setting key so each row can show its own confirmation.
     *
     * @var array<string, string>
     */
    public array $savedKeys = [];

    #[Computed]
    public function settings()
    {
        return SiteSetting::orderBy('group')->orderBy('key')->get()->groupBy('group');
    }

    /**
     * Persist a setting value. The value is stored as JSON in the database
     * (the SiteSetting model casts the `value` column as array), so we wrap
     * the raw string the admin typed in an associative wrapper for scalar
     * values, or decode it if it looks like valid JSON already.
     */
    public function updateSetting(string $key, string $value): void
    {
        $decoded = json_decode($value, true);
        $stored  = json_last_error() === JSON_ERROR_NONE ? $decoded : $value;

        SiteSetting::put($key, $stored);

        $this->savedKeys[$key] = now()->toTimeString();
        unset($this->settings);
    }

    /**
     * Return the scalar representation of a setting value for display
     * inside an <input>. Arrays and objects are re-encoded as JSON strings.
     */
    public function displayValue(mixed $value): string
    {
        if (is_array($value) || is_object($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return (string) ($value ?? '');
    }
}; ?>

<div>
    <x-slot:heading>System Settings</x-slot:heading>
    <x-slot:subheading>Site-wide configuration stored in the database. Changes take effect immediately; cached values refresh within 1 hour.</x-slot:subheading>

    <div class="flex flex-col gap-6">

        @forelse ($this->settings as $group => $groupSettings)
            <div class="overflow-hidden rounded-[10px] border-[1.5px] border-white bg-white shadow-sm shadow-zinc-900/[0.04] dark:border-white dark:bg-[#1d3252]">
                {{-- Group header pill --}}
                <div class="mx-3 my-3 rounded-[10px] bg-blue-50 px-6 py-3 ring-2 ring-blue-500 dark:bg-blue-600/15 dark:ring-blue-400">
                    <h2 class="text-[11px] font-bold uppercase tracking-wider text-blue-700 dark:text-blue-300">{{ $group }}</h2>
                </div>

                <ul class="divide-inset">
                    @foreach ($groupSettings as $setting)
                        @php $displayVal = $this->displayValue($setting->value); @endphp
                        <li class="group relative mx-3 flex flex-col gap-3 px-5 py-4 transition-all hover:bg-blue-50 hover:rounded-[10px] hover:ring-1 hover:ring-inset hover:ring-blue-500 hover:after:hidden sm:flex-row sm:items-center sm:gap-6 dark:hover:bg-blue-600/15 dark:hover:ring-blue-400">
                            {{-- Key + description --}}
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <code class="rounded-[5px] bg-zinc-100 px-1.5 py-0.5 text-[11px] font-semibold text-zinc-700 dark:bg-zinc-700/50 dark:text-zinc-300">{{ $setting->key }}</code>
                                    @if (isset($this->savedKeys[$setting->key]))
                                        <span class="text-[10px] font-semibold text-emerald-600 dark:text-emerald-400">Saved</span>
                                    @endif
                                </div>
                                @if ($setting->description)
                                    <p class="mt-1 text-xs leading-relaxed text-zinc-500 dark:text-zinc-400">{{ $setting->description }}</p>
                                @endif
                            </div>

                            {{-- Editable value input --}}
                            <div class="w-full sm:w-72 lg:w-96">
                                <input
                                    type="text"
                                    value="{{ $displayVal }}"
                                    wire:change="updateSetting('{{ $setting->key }}', $event.target.value)"
                                    class="w-full rounded-[10px] border border-zinc-200 bg-white py-2.5 px-3 text-sm font-mono outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/15 dark:border-zinc-700/60 dark:bg-[#0c1a36] dark:text-white"
                                    placeholder="null"
                                    aria-label="{{ $setting->key }}"
                                />
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        @empty
            <div class="rounded-[10px] border-[1.5px] border-white bg-white p-12 text-center shadow-sm shadow-zinc-900/[0.04] dark:border-white dark:bg-[#1d3252]">
                <p class="text-sm font-semibold text-zinc-900 dark:text-white">No settings configured</p>
                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                    Use <code class="rounded-[5px] bg-zinc-100 px-1.5 py-0.5 dark:bg-zinc-700/50">SiteSetting::put('key', $value, 'group')</code> to seed settings, or run the site settings seeder.
                </p>
            </div>
        @endforelse

        {{-- Cache note --}}
        <p class="text-[11px] text-zinc-500 dark:text-zinc-400">
            Settings are cached for 1 hour per key via <code class="rounded-[5px] bg-zinc-100 px-1 dark:bg-zinc-700/50">SiteSetting::get()</code>. Runtime consumers pick up changes at next cache expiry. To force immediate propagation, run <code class="rounded-[5px] bg-zinc-100 px-1 dark:bg-zinc-700/50">php artisan cache:clear</code>.
        </p>
    </div>
</div>
