<?php

use App\Models\Review;
use App\Models\SiteSetting;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;

new
#[Layout('components.layouts.admin')]
#[Title('Reviews')]
class extends Component {
    public ?int $editingId = null;

    public bool $showForm = false;

    #[Validate('required|string|max:80')]
    public string $authorName = '';

    #[Validate('nullable|string|max:4')]
    public string $initials = '';

    #[Validate('required|string|max:2000')]
    public string $body = '';

    #[Validate('required|numeric|min:1|max:5')]
    public float $rating = 5.0;

    #[Validate('required|in:Trustpilot,Google,RshopRefills')]
    public string $source = 'Trustpilot';

    #[Validate('required|date')]
    public string $reviewedAt = '';

    #[Validate('boolean')]
    public bool $isPublished = true;

    #[Validate('integer|min:0|max:10000')]
    public int $sortOrder = 0;

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

    public function newReview(): void
    {
        $this->resetForm();
        $this->reviewedAt = now()->toDateString();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $review = Review::findOrFail($id);
        $this->editingId = $review->id;
        $this->authorName = $review->author_name;
        $this->initials = $review->initials;
        $this->body = $review->body;
        $this->rating = $review->rating;
        $this->source = $review->source;
        $this->reviewedAt = $review->reviewed_at->toDateString();
        $this->isPublished = $review->is_published;
        $this->sortOrder = $review->sort_order;
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->validate();

        // Auto-generate initials from name when blank: "John Doe" -> "JD".
        $initials = $this->initials !== '' ? Str::upper($this->initials) : Str::upper(
            collect(explode(' ', trim($this->authorName)))
                ->filter()
                ->take(2)
                ->map(fn ($p) => Str::substr($p, 0, 1))
                ->implode('')
        );

        $payload = [
            'author_name' => $this->authorName,
            'initials' => $initials ?: 'A',
            'body' => $this->body,
            'rating' => $this->rating,
            'source' => $this->source,
            'reviewed_at' => $this->reviewedAt,
            'is_published' => $this->isPublished,
            'sort_order' => $this->sortOrder,
        ];

        if ($this->editingId) {
            Review::findOrFail($this->editingId)->update($payload);
            session()->flash('status', 'Review updated.');
        } else {
            Review::create($payload);
            session()->flash('status', 'Review created.');
        }

        $this->resetForm();
        unset($this->reviews);
    }

    public function delete(int $id): void
    {
        Review::findOrFail($id)->delete();
        session()->flash('status', 'Review deleted.');
        unset($this->reviews);
    }

    public function togglePublish(int $id): void
    {
        $review = Review::findOrFail($id);
        $review->update(['is_published' => ! $review->is_published]);
        unset($this->reviews);
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->authorName = '';
        $this->initials = '';
        $this->body = '';
        $this->rating = 5.0;
        $this->source = 'Trustpilot';
        $this->reviewedAt = now()->toDateString();
        $this->isPublished = true;
        $this->sortOrder = 0;
        $this->showForm = false;
        $this->resetValidation();
    }
}; ?>

<div class="w-full px-4 py-8 sm:px-6 lg:px-8">

    <header class="mb-6 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Reviews</h1>
            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Customer reviews shown on the homepage carousel and the public <a href="/reviews" target="_blank" class="text-blue-600 hover:underline dark:text-blue-300">reviews page</a>.</p>
        </div>
        <button wire:click="newReview" type="button" class="inline-flex items-center gap-1.5 rounded-[10px] bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-blue-700">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            New review
        </button>
    </header>

    @if (session('status'))
        <div class="mb-4 rounded-[10px] bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 ring-1 ring-emerald-200 dark:bg-emerald-500/15 dark:text-emerald-300 dark:ring-emerald-500/30">{{ session('status') }}</div>
    @endif

    {{-- Aggregate stats — same KPI strip pattern used across every admin
         list page (Rates, Pricing Rules, Account Activity, etc.). --}}
    <div class="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-4">
        @foreach ([
            ['label' => 'Rating',       'value' => number_format($this->aggregate['rating'], 1) . ' / 5', 'dot' => 'bg-amber-500'],
            ['label' => 'Review count', 'value' => number_format($this->aggregate['count']) . '+',       'dot' => 'bg-blue-500'],
            ['label' => 'Since',        'value' => $this->aggregate['since'],                             'dot' => 'bg-emerald-500'],
            ['label' => 'Source',       'value' => $this->aggregate['source'],                            'dot' => 'bg-fuchsia-500'],
        ] as $stat)
            <div class="rounded-[10px] border-[1.5px] border-white bg-white p-4 shadow-sm shadow-zinc-900/[0.04] dark:border-white dark:bg-[#1d3252]">
                <p class="flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-300">
                    <span class="inline-block h-1.5 w-1.5 rounded-full {{ $stat['dot'] }}"></span>
                    {{ $stat['label'] }}
                </p>
                <p class="mt-2 text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">{{ $stat['value'] }}</p>
            </div>
        @endforeach
    </div>

    <div class="overflow-hidden rounded-[10px] border-[1.5px] border-white bg-white shadow-sm shadow-zinc-900/[0.04] dark:border-white dark:bg-[#1d3252]">
        <div class="overflow-x-auto p-3">
            <table class="admin-table w-full text-left text-sm">
                <thead>
                    <tr>
                        <th>Author</th>
                        <th>Source</th>
                        <th>Rating</th>
                        <th>Reviewed</th>
                        <th>Body</th>
                        <th>Status</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->reviews as $review)
                        <tr>
                            <td>
                                <p class="font-semibold text-zinc-900 dark:text-white">{{ $review->author_name }}</p>
                                <p class="mt-0.5 text-[11px] text-zinc-500 dark:text-zinc-400">{{ $review->initials }}</p>
                            </td>
                            <td>{{ $review->source }}</td>
                            <td>{{ rtrim(rtrim(number_format($review->rating, 1), '0'), '.') }} / 5</td>
                            <td>{{ $review->reviewed_at->format('M j, Y') }}</td>
                            <td class="max-w-md truncate">{{ $review->body }}</td>
                            <td>
                                <x-admin.badge :tone="$review->is_published ? 'emerald' : 'zinc'">
                                    {{ $review->is_published ? 'Published' : 'Draft' }}
                                </x-admin.badge>
                            </td>
                            <td class="whitespace-nowrap text-right">
                                <div class="inline-flex items-center gap-1.5">
                                    <button wire:click="togglePublish({{ $review->id }})" type="button" class="rounded-[5px] bg-zinc-100 px-2.5 py-1 text-[11px] font-semibold text-zinc-700 transition-colors hover:bg-zinc-200 dark:bg-zinc-700/50 dark:text-zinc-300 dark:hover:bg-zinc-700">{{ $review->is_published ? 'Unpublish' : 'Publish' }}</button>
                                    <button wire:click="edit({{ $review->id }})" type="button" class="rounded-[5px] bg-blue-50 px-2.5 py-1 text-[11px] font-semibold text-blue-700 transition-colors hover:bg-blue-100 dark:bg-blue-500/15 dark:text-blue-300 dark:hover:bg-blue-500/25">Edit</button>
                                    <button wire:click="delete({{ $review->id }})" wire:confirm="Delete this review permanently?" type="button" class="rounded-[5px] bg-red-50 px-2.5 py-1 text-[11px] font-semibold text-red-700 transition-colors hover:bg-red-100 dark:bg-red-500/15 dark:text-red-300 dark:hover:bg-red-500/25">Delete</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-12 text-center text-sm text-zinc-600 dark:text-zinc-400">No reviews yet. Click "New review" to add the first one.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Create/edit modal --}}
    @if ($showForm)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true">
            <div wire:click="$set('showForm', false)" class="absolute inset-0 bg-zinc-900/40"></div>
            <form wire:submit="save" class="relative max-h-[90vh] w-full max-w-xl overflow-hidden rounded-[10px] bg-white shadow-2xl flex flex-col">
                <div class="flex shrink-0 items-start justify-between gap-4 border-b border-zinc-100 px-5 py-4">
                    <h3 class="text-sm font-bold text-zinc-900">{{ $editingId ? 'Edit review' : 'New review' }}</h3>
                    <button type="button" wire:click="$set('showForm', false)" aria-label="Close" class="flex h-8 w-8 items-center justify-center rounded-[10px] bg-zinc-100 text-zinc-600 transition-colors hover:bg-zinc-200">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="space-y-4 overflow-y-auto px-5 py-4">
                    <div class="grid grid-cols-3 gap-4">
                        <div class="col-span-2">
                            <label class="text-[10px] font-semibold uppercase tracking-wider text-zinc-800">Author name</label>
                            <input wire:model="authorName" type="text" placeholder="e.g. Sarah J." class="mt-1.5 w-full rounded-[10px] border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/15">
                            @error('authorName') <p class="mt-1 text-[11px] font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="text-[10px] font-semibold uppercase tracking-wider text-zinc-800">Initials</label>
                            <input wire:model="initials" type="text" maxlength="4" placeholder="auto" class="mt-1.5 w-full rounded-[10px] border border-zinc-200 bg-white px-3 py-2 text-sm uppercase text-zinc-900 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/15">
                        </div>
                    </div>
                    <div>
                        <label class="text-[10px] font-semibold uppercase tracking-wider text-zinc-800">Body</label>
                        <textarea wire:model="body" rows="4" placeholder="The review text the customer left." class="mt-1.5 w-full rounded-[10px] border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/15"></textarea>
                        @error('body') <p class="mt-1 text-[11px] font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <label class="text-[10px] font-semibold uppercase tracking-wider text-zinc-800">Rating</label>
                            <x-admin.select wire:model="rating" :options="['1' => '1 star', '1.5' => '1.5 stars', '2' => '2 stars', '2.5' => '2.5 stars', '3' => '3 stars', '3.5' => '3.5 stars', '4' => '4 stars', '4.5' => '4.5 stars', '5' => '5 stars']" />
                            @error('rating') <p class="mt-1 text-[11px] font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="text-[10px] font-semibold uppercase tracking-wider text-zinc-800">Source</label>
                            <x-admin.select wire:model="source" :options="['Trustpilot' => 'Trustpilot (emerald star)', 'Google' => 'Google (multi-colour G)', 'RshopRefills' => 'RshopRefills (our website)']" />
                            <p class="mt-1 text-[10px] text-zinc-500">Picks the brand icon and star colour on the storefront card.</p>
                            @error('source') <p class="mt-1 text-[11px] font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="text-[10px] font-semibold uppercase tracking-wider text-zinc-800">Reviewed on</label>
                            <input wire:model="reviewedAt" type="date" class="mt-1.5 w-full rounded-[10px] border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/15">
                            @error('reviewedAt') <p class="mt-1 text-[11px] font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-[10px] font-semibold uppercase tracking-wider text-zinc-800">Sort order</label>
                            <input wire:model="sortOrder" type="number" min="0" max="10000" class="mt-1.5 w-full rounded-[10px] border border-zinc-200 bg-white px-3 py-2 text-sm tabular-nums text-zinc-900 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/15">
                        </div>
                        <label class="flex items-end gap-2 pb-2">
                            <input wire:model="isPublished" type="checkbox" class="h-4 w-4 cursor-pointer accent-blue-600">
                            <span class="text-sm font-semibold text-zinc-700">Published</span>
                        </label>
                    </div>
                </div>
                <div class="flex shrink-0 items-center justify-end gap-2 border-t border-zinc-100 bg-zinc-50 px-5 py-3">
                    <button type="button" wire:click="$set('showForm', false)" class="inline-flex items-center rounded-[10px] px-3.5 py-2 text-xs font-semibold text-zinc-700 transition-colors hover:bg-zinc-100">Cancel</button>
                    <button type="submit" class="inline-flex items-center rounded-[10px] bg-blue-600 px-4 py-2 text-xs font-semibold text-white transition-colors hover:bg-blue-700">{{ $editingId ? 'Save changes' : 'Create review' }}</button>
                </div>
            </form>
        </div>
    @endif
</div>
