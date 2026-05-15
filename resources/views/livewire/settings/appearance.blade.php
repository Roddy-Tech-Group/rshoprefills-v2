<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.dashboard')] class extends Component {
    //
}; ?>

<div class="mx-auto flex max-w-3xl flex-col gap-6">
    {{-- Page heading --}}
    <div class="text-center sm:text-left">
        <h1 class="text-2xl font-bold tracking-tight text-black sm:text-3xl">Appearance</h1>
        <p class="mt-1 text-sm text-zinc-600">Choose how RshopRefills looks for you. System matches your device setting.</p>
    </div>

    {{-- Theme picker --}}
    <div class="rounded-2xl bg-white p-6 shadow-sm shadow-zinc-900/5 ring-1 ring-zinc-100">
        <div class="mb-5">
            <h2 class="text-base font-semibold text-black">Theme</h2>
            <p class="mt-0.5 text-xs text-zinc-600">Switch between light and dark mode, or follow your system.</p>
        </div>

        <flux:radio.group x-data variant="segmented" x-model="$flux.appearance">
            <flux:radio value="light" icon="sun">Light</flux:radio>
            <flux:radio value="dark" icon="moon">Dark</flux:radio>
            <flux:radio value="system" icon="computer-desktop">System</flux:radio>
        </flux:radio.group>
    </div>
</div>
