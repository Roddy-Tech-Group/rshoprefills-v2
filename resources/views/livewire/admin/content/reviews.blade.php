<?php

use App\Models\Review;
use App\Models\SiteSetting;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new
#[Layout('components.layouts.admin')]
#[Title('Reviews')]
class extends Component {
    #[Computed]
    public function reviews()
    {
        return Review::orderByDesc('reviewed_at')->get();
    }

    #[Computed]
    public function aggregate(): array
    {
        return [
            'rating' => (float) SiteSetting::get('reviews.aggregate.rating', 0),
            'count' => (int) SiteSetting::get('reviews.aggregate.count', 0),
            'since' => (int) SiteSetting::get('reviews.aggregate.since_year', date('Y')),
            'source' => (string) SiteSetting::get('reviews.aggregate.source', 'Trustpilot'),
        ];
    }
}; ?>

<div class="w-full px-4 py-8 sm:px-6 lg:px-8">

    <header class="mb-6">
        <h1 class="text-2xl font-bold text-zinc-900">Reviews</h1>
        <p class="mt-1 text-sm text-zinc-600">Customer reviews shown on the homepage carousel.</p>
    </header>

    {{-- Aggregate stats card --}}
    <div class="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-4">
        <div class="rounded-[10px] bg-white p-5 ring-1 ring-zinc-100 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wider text-zinc-500">Rating</p>
            <p class="mt-2 text-2xl font-bold text-zinc-900">{{ number_format($this->aggregate['rating'], 1) }} / 5</p>
        </div>
        <div class="rounded-[10px] bg-white p-5 ring-1 ring-zinc-100 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wider text-zinc-500">Review count</p>
            <p class="mt-2 text-2xl font-bold text-zinc-900">{{ number_format($this->aggregate['count']) }}+</p>
        </div>
        <div class="rounded-[10px] bg-white p-5 ring-1 ring-zinc-100 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wider text-zinc-500">Since</p>
            <p class="mt-2 text-2xl font-bold text-zinc-900">{{ $this->aggregate['since'] }}</p>
        </div>
        <div class="rounded-[10px] bg-white p-5 ring-1 ring-zinc-100 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wider text-zinc-500">Source</p>
            <p class="mt-2 text-2xl font-bold text-zinc-900">{{ $this->aggregate['source'] }}</p>
        </div>
    </div>

    <div class="overflow-hidden rounded-[10px] bg-white ring-1 ring-zinc-100 shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-zinc-50 text-[11px] uppercase tracking-wider text-zinc-600">
                    <tr>
                        <th class="px-5 py-3 font-semibold">Author</th>
                        <th class="px-5 py-3 font-semibold">Source</th>
                        <th class="px-5 py-3 font-semibold">Rating</th>
                        <th class="px-5 py-3 font-semibold">Reviewed</th>
                        <th class="px-5 py-3 font-semibold">Body</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    @forelse ($this->reviews as $review)
                        <tr class="hover:bg-zinc-50">
                            <td class="px-5 py-3 font-semibold text-zinc-900">{{ $review->author_name }}</td>
                            <td class="px-5 py-3 text-zinc-700">{{ $review->source }}</td>
                            <td class="px-5 py-3 text-zinc-700">{{ $review->rating }} / 5</td>
                            <td class="px-5 py-3 text-zinc-700">{{ $review->reviewed_at->format('M j, Y') }}</td>
                            <td class="px-5 py-3 text-zinc-600 max-w-md truncate">{{ $review->body }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-12 text-center text-sm text-zinc-600">No reviews yet. Run <code class="rounded-[10px] bg-zinc-100 px-1.5 py-0.5">php artisan db:seed --class=ReviewSeeder</code>.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
