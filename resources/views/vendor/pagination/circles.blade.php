@if ($paginator->hasPages())
    <nav class="flex items-center justify-center gap-1.5" role="navigation" aria-label="{{ __('Pagination Navigation') }}">

        {{-- Previous page link --}}
        @if ($paginator->onFirstPage())
            <span aria-disabled="true" aria-label="{{ __('pagination.previous') }}" class="flex h-10 w-10 items-center justify-center rounded-full bg-zinc-100 text-zinc-400 cursor-not-allowed">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/>
                </svg>
            </span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" wire:navigate rel="prev" aria-label="{{ __('pagination.previous') }}" class="flex h-10 w-10 items-center justify-center rounded-full bg-zinc-100 text-zinc-700 transition-colors hover:bg-zinc-200 hover:text-zinc-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-zinc-900/20">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/>
                </svg>
            </a>
        @endif

        {{-- Page numbers --}}
        @foreach ($elements as $element)
            {{-- Ellipsis --}}
            @if (is_string($element))
                <span aria-disabled="true" class="flex h-10 w-10 items-center justify-center text-sm font-semibold text-zinc-500" aria-hidden="true">…</span>
            @endif

            {{-- Array of links --}}
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span aria-current="page" class="flex h-10 w-10 items-center justify-center rounded-full bg-white text-sm font-bold text-zinc-900 ring-2 ring-zinc-900">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}" wire:navigate aria-label="{{ __('Go to page :page', ['page' => $page]) }}" class="flex h-10 w-10 items-center justify-center rounded-full bg-zinc-100 text-sm font-semibold text-zinc-700 transition-colors hover:bg-zinc-200 hover:text-zinc-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-zinc-900/20">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        {{-- Next page link --}}
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" wire:navigate rel="next" aria-label="{{ __('pagination.next') }}" class="flex h-10 w-10 items-center justify-center rounded-full bg-zinc-100 text-zinc-700 transition-colors hover:bg-zinc-200 hover:text-zinc-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-zinc-900/20">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
                </svg>
            </a>
        @else
            <span aria-disabled="true" aria-label="{{ __('pagination.next') }}" class="flex h-10 w-10 items-center justify-center rounded-full bg-zinc-100 text-zinc-400 cursor-not-allowed">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
                </svg>
            </span>
        @endif

    </nav>
@endif
