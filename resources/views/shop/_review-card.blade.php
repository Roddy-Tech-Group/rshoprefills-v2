{{--
    Single review card used by the homepage carousel AND the /reviews page
    marquees. Source-agnostic markup: Trustpilot renders the emerald star mark
    + emerald square rating stars, Google renders the multi-colour "G" + gold
    rating stars, anything else falls back to the emerald style.

    Source labels are case-insensitive ("Trustpilot", "trustpilot", "TRUSTPILOT"
    all match) so admin form options can normalise however editors type them.

    @var \App\Models\Review $review
--}}
@php
    $sourceKey = strtolower(trim((string) $review->source));
    $isGoogle = $sourceKey === 'google';
@endphp

<article class="flex w-72 shrink-0 flex-col rounded-[10px] bg-white p-5 ring-1 ring-zinc-200 shadow-sm sm:w-80">

    <div class="flex items-start justify-between gap-3">
        <div class="flex min-w-0 items-start gap-3">
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-[10px] bg-zinc-100 text-sm font-bold text-zinc-600">{{ $review->initials }}</span>
            <div class="min-w-0 leading-tight">
                <p class="truncate text-sm font-semibold text-zinc-900">{{ $review->author_name }}</p>
                <p class="text-xs text-zinc-600">{{ $review->reviewed_at->format('M j, Y') }}</p>
            </div>
        </div>
        <div class="flex shrink-0 items-center gap-1">
            @if ($isGoogle)
                {{-- Google "G" logo (multi-colour, sourced from the official mark) --}}
                <svg class="h-4 w-4" viewBox="0 0 48 48" aria-hidden="true">
                    <path fill="#FFC107" d="M43.611 20.083H42V20H24v8h11.303c-1.649 4.657-6.08 8-11.303 8c-6.627 0-12-5.373-12-12s5.373-12 12-12c3.059 0 5.842 1.154 7.961 3.039l5.657-5.657C34.046 6.053 29.268 4 24 4C12.955 4 4 12.955 4 24s8.955 20 20 20s20-8.955 20-20c0-1.341-.138-2.65-.389-3.917"/>
                    <path fill="#FF3D00" d="m6.306 14.691l6.571 4.819C14.655 15.108 18.961 12 24 12c3.059 0 5.842 1.154 7.961 3.039l5.657-5.657C34.046 6.053 29.268 4 24 4C16.318 4 9.656 8.337 6.306 14.691"/>
                    <path fill="#4CAF50" d="M24 44c5.166 0 9.86-1.977 13.409-5.192l-6.19-5.238A11.91 11.91 0 0 1 24 36c-5.202 0-9.619-3.317-11.283-7.946l-6.522 5.025C9.505 39.556 16.227 44 24 44"/>
                    <path fill="#1976D2" d="M43.611 20.083H42V20H24v8h11.303a12.04 12.04 0 0 1-4.087 5.571l.003-.002l6.19 5.238C36.971 39.205 44 34 44 24c0-1.341-.138-2.65-.389-3.917"/>
                </svg>
            @else
                {{-- Trustpilot-style emerald star (default for Trustpilot + our own) --}}
                <svg class="h-3.5 w-3.5 text-emerald-500" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M12 .587l3.668 7.568L24 9.423l-6 5.951L19.336 24 12 19.897 4.664 24 6 15.374 0 9.423l8.332-1.268z"/>
                </svg>
            @endif
            <span class="text-[10px] font-bold text-zinc-900">{{ $review->source }}</span>
        </div>
    </div>

    <div class="mt-3 flex gap-0.5">
        @for ($i = 0; $i < max(0, min(5, $review->rating)); $i++)
            @if ($isGoogle)
                {{-- Google-style gold filled star (no square background) --}}
                <svg class="h-4 w-4 text-amber-400" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M12 .587l3.668 7.568L24 9.423l-6 5.951L19.336 24 12 19.897 4.664 24 6 15.374 0 9.423l8.332-1.268z"/>
                </svg>
            @else
                {{-- Trustpilot-style emerald square with white star inside --}}
                <span class="flex h-4 w-4 items-center justify-center bg-emerald-500">
                    <svg class="h-3 w-3 text-white" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <path d="M12 .587l3.668 7.568L24 9.423l-6 5.951L19.336 24 12 19.897 4.664 24 6 15.374 0 9.423l8.332-1.268z"/>
                    </svg>
                </span>
            @endif
        @endfor
    </div>

    <p class="mt-3 line-clamp-5 text-sm leading-relaxed text-zinc-700">{{ $review->body }}</p>
</article>
