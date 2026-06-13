@php
    // Floating, auto-dismissing toast for one-shot session flashes. Reads the
    // standard keys an action redirect sets - status/success are positive,
    // error/danger are negative. Renders nothing when none are present, so it's
    // safe to drop into any layout once.
    $flashSuccess = session('status') ?? session('success');
    $flashError = session('error') ?? session('danger');
    $flashMessage = $flashSuccess ?? $flashError;
    $flashIsError = $flashError !== null;
@endphp

@if ($flashMessage)
    <div
        x-data="{ show: false }"
        x-init="$nextTick(() => { show = true; setTimeout(() => show = false, 4500); })"
        x-show="show"
        x-transition:enter="transition duration-300 ease-[cubic-bezier(0.22,1,0.36,1)]"
        x-transition:enter-start="opacity-0 translate-y-3 sm:translate-y-0 sm:translate-x-4"
        x-transition:enter-end="opacity-100 translate-y-0 sm:translate-x-0"
        x-transition:leave="transition duration-200 ease-in"
        x-transition:leave-start="opacity-100 translate-x-0"
        x-transition:leave-end="opacity-0 translate-x-4"
        style="display:none;"
        class="fixed inset-x-4 top-4 z-[100] mx-auto flex max-w-sm items-start gap-2.5 rounded-[10px] px-4 py-3 text-sm font-medium shadow-lg shadow-zinc-900/15 ring-1 sm:inset-x-auto sm:right-5 sm:top-5
            {{ $flashIsError
                ? 'bg-red-50 text-red-700 ring-red-200 dark:bg-red-500/15 dark:text-red-300 dark:ring-red-500/30'
                : 'bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-500/15 dark:text-emerald-300 dark:ring-emerald-500/30' }}"
        role="status"
        aria-live="polite"
    >
        @if ($flashIsError)
            <svg class="mt-0.5 h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
            </svg>
        @else
            <svg class="mt-0.5 h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
            </svg>
        @endif
        <span class="min-w-0 flex-1">{{ $flashMessage }}</span>
        <button type="button" @click="show = false" class="-mr-1 -mt-0.5 shrink-0 rounded-[6px] p-0.5 opacity-60 transition-opacity hover:opacity-100" aria-label="Dismiss">
            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>
@endif
