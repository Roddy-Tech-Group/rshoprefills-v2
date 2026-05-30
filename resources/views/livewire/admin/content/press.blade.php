<?php

use App\Models\PressArticle;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;

new
#[Layout('components.layouts.admin')]
#[Title('Press Articles')]
class extends Component {
    public ?int $editingId = null;

    public bool $showForm = false;

    #[Validate('required|string|max:200')]
    public string $title = '';

    #[Validate('required|string|max:200')]
    public string $slug = '';

    #[Validate('required|string|max:80')]
    public string $category = '';

    #[Validate('required|string|max:500')]
    public string $excerpt = '';

    #[Validate('nullable|string|max:500')]
    public string $image = '';

    #[Validate('required|date')]
    public string $publishedAt = '';

    #[Validate('required|string|max:50000')]
    public string $bodyText = '';

    #[Validate('boolean')]
    public bool $isPublished = true;

    #[Validate('integer|min:0|max:10000')]
    public int $sortOrder = 0;

    #[Computed]
    public function articles()
    {
        return PressArticle::orderByDesc('published_at')->get();
    }

    public function newArticle(): void
    {
        $this->resetForm();
        $this->publishedAt = now()->toDateString();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $article = PressArticle::findOrFail($id);
        $this->editingId = $article->id;
        $this->title = $article->title;
        $this->slug = $article->slug;
        $this->category = $article->category;
        $this->excerpt = $article->excerpt;
        $this->image = (string) $article->image;
        $this->publishedAt = $article->published_at->toDateString();
        $this->bodyText = is_array($article->body) ? implode("\n\n", $article->body) : (string) $article->body;
        $this->isPublished = $article->is_published;
        $this->sortOrder = $article->sort_order;
        $this->showForm = true;
    }

    public function updatedTitle(string $value): void
    {
        if (! $this->editingId && $this->slug === '') {
            $this->slug = Str::slug($value);
        }
    }

    public function save(): void
    {
        $this->validate();

        $bodyParagraphs = collect(preg_split("/\n\s*\n/", trim($this->bodyText)))
            ->map(fn ($p) => trim($p))
            ->filter()
            ->values()
            ->all();

        $payload = [
            'title' => $this->title,
            'slug' => Str::slug($this->slug),
            'category' => $this->category,
            'excerpt' => $this->excerpt,
            'image' => $this->image ?: 'placeholder.png',
            'body' => $bodyParagraphs,
            'published_at' => $this->publishedAt,
            'is_published' => $this->isPublished,
            'sort_order' => $this->sortOrder,
        ];

        if ($this->editingId) {
            PressArticle::findOrFail($this->editingId)->update($payload);
            session()->flash('status', 'Article updated.');
        } else {
            PressArticle::create($payload);
            session()->flash('status', 'Article created.');
        }

        $this->resetForm();
        unset($this->articles);
    }

    public function delete(int $id): void
    {
        PressArticle::findOrFail($id)->delete();
        session()->flash('status', 'Article deleted.');
        unset($this->articles);
    }

    public function togglePublish(int $id): void
    {
        $article = PressArticle::findOrFail($id);
        $article->update(['is_published' => ! $article->is_published]);
        unset($this->articles);
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->title = '';
        $this->slug = '';
        $this->category = '';
        $this->excerpt = '';
        $this->image = '';
        $this->publishedAt = now()->toDateString();
        $this->bodyText = '';
        $this->isPublished = true;
        $this->sortOrder = 0;
        $this->showForm = false;
        $this->resetValidation();
    }
}; ?>

<div class="w-full px-4 py-8 sm:px-6 lg:px-8">

    <header class="mb-6 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-zinc-900">Press Articles</h1>
            <p class="mt-1 text-sm text-zinc-600">Newsroom posts shown at <a href="/press" target="_blank" class="text-blue-600 hover:underline">/press</a>.</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="rounded-[10px] bg-zinc-100 px-3 py-1 text-xs font-semibold text-zinc-600">{{ $this->articles->count() }} {{ \Illuminate\Support\Str::plural('article', $this->articles->count()) }}</span>
            <button wire:click="newArticle" type="button" class="inline-flex items-center gap-1.5 rounded-[10px] bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-blue-700">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                New article
            </button>
        </div>
    </header>

    @if (session('status'))
        <div class="mb-4 rounded-[10px] bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 ring-1 ring-emerald-200">{{ session('status') }}</div>
    @endif

    <div class="overflow-hidden rounded-[10px] border-[1.5px] border-white bg-white shadow-sm shadow-zinc-900/[0.04] dark:border-white dark:bg-[#1d3252]">
        <div class="overflow-x-auto p-3">
            <table class="admin-table w-full text-left text-sm">
                <thead class="bg-zinc-50 text-[11px] uppercase tracking-wider text-zinc-600">
                    <tr>
                        <th class="px-5 py-3 font-semibold">Title</th>
                        <th class="px-5 py-3 font-semibold">Category</th>
                        <th class="px-5 py-3 font-semibold">Published</th>
                        <th class="px-5 py-3 font-semibold">Status</th>
                        <th class="px-5 py-3 text-right font-semibold">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-inset">
                    @forelse ($this->articles as $article)
                        <tr class="hover:bg-zinc-50">
                            <td class="px-5 py-3">
                                <p class="font-semibold text-zinc-900">{{ $article->title }}</p>
                                <p class="mt-0.5 text-xs text-zinc-500">/{{ $article->slug }}</p>
                            </td>
                            <td class="px-5 py-3 text-zinc-700">{{ $article->category }}</td>
                            <td class="px-5 py-3 text-zinc-700">{{ $article->published_at->format('M j, Y') }}</td>
                            <td class="px-5 py-3">
                                <x-admin.badge :tone="$article->is_published ? 'emerald' : 'zinc'">
                                    {{ $article->is_published ? 'Published' : 'Draft' }}
                                </x-admin.badge>
                            </td>
                            <td class="px-5 py-3 text-right">
                                <div class="inline-flex items-center gap-1.5">
                                    <button wire:click="togglePublish({{ $article->id }})" type="button" class="rounded-[10px] bg-zinc-100 px-2.5 py-1 text-[11px] font-semibold text-zinc-700 transition-colors hover:bg-zinc-200">{{ $article->is_published ? 'Unpublish' : 'Publish' }}</button>
                                    <button wire:click="edit({{ $article->id }})" type="button" class="rounded-[10px] bg-blue-50 px-2.5 py-1 text-[11px] font-semibold text-blue-700 transition-colors hover:bg-blue-100">Edit</button>
                                    <button wire:click="delete({{ $article->id }})" wire:confirm="Delete this article permanently?" type="button" class="rounded-[10px] bg-red-50 px-2.5 py-1 text-[11px] font-semibold text-red-700 transition-colors hover:bg-red-100">Delete</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-12 text-center text-sm text-zinc-600">No articles yet. Click "New article" to create the first one.</td>
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
            <form wire:submit="save" class="relative max-h-[90vh] w-full max-w-2xl overflow-hidden rounded-[10px] bg-white shadow-2xl flex flex-col">
                <div class="flex shrink-0 items-start justify-between gap-4 border-b border-zinc-100 px-5 py-4">
                    <h3 class="text-sm font-bold text-zinc-900">{{ $editingId ? 'Edit article' : 'New article' }}</h3>
                    <button type="button" wire:click="$set('showForm', false)" aria-label="Close" class="flex h-8 w-8 items-center justify-center rounded-[10px] bg-zinc-100 text-zinc-600 transition-colors hover:bg-zinc-200">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="space-y-4 overflow-y-auto px-5 py-4">
                    <div>
                        <label class="text-[10px] font-semibold uppercase tracking-wider text-zinc-800">Title</label>
                        <input wire:model.live.debounce.300ms="title" type="text" class="mt-1.5 w-full rounded-[10px] border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/15">
                        @error('title') <p class="mt-1 text-[11px] font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-[10px] font-semibold uppercase tracking-wider text-zinc-800">Slug</label>
                            <input wire:model="slug" type="text" placeholder="auto-generated from title" class="mt-1.5 w-full rounded-[10px] border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/15">
                            @error('slug') <p class="mt-1 text-[11px] font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="text-[10px] font-semibold uppercase tracking-wider text-zinc-800">Category</label>
                            <input wire:model="category" type="text" placeholder="e.g. Newsroom" class="mt-1.5 w-full rounded-[10px] border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/15">
                            @error('category') <p class="mt-1 text-[11px] font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div>
                        <label class="text-[10px] font-semibold uppercase tracking-wider text-zinc-800">Excerpt</label>
                        <textarea wire:model="excerpt" rows="2" placeholder="Short summary shown on the press index." class="mt-1.5 w-full rounded-[10px] border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/15"></textarea>
                        @error('excerpt') <p class="mt-1 text-[11px] font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="text-[10px] font-semibold uppercase tracking-wider text-zinc-800">Body</label>
                        <p class="text-[11px] text-zinc-500">Separate paragraphs with a blank line.</p>
                        <textarea wire:model="bodyText" rows="10" class="mt-1.5 w-full rounded-[10px] border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/15"></textarea>
                        @error('bodyText') <p class="mt-1 text-[11px] font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-[10px] font-semibold uppercase tracking-wider text-zinc-800">Image filename</label>
                            <input wire:model="image" type="text" placeholder="e.g. launch-hero.png" class="mt-1.5 w-full rounded-[10px] border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/15">
                            <p class="mt-1 text-[11px] text-zinc-500">Place the file in public/assets first.</p>
                        </div>
                        <div>
                            <label class="text-[10px] font-semibold uppercase tracking-wider text-zinc-800">Published at</label>
                            <input wire:model="publishedAt" type="date" class="mt-1.5 w-full rounded-[10px] border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/15">
                            @error('publishedAt') <p class="mt-1 text-[11px] font-medium text-red-600">{{ $message }}</p> @enderror
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
                    <button type="submit" class="inline-flex items-center rounded-[10px] bg-blue-600 px-4 py-2 text-xs font-semibold text-white transition-colors hover:bg-blue-700">{{ $editingId ? 'Save changes' : 'Create article' }}</button>
                </div>
            </form>
        </div>
    @endif
</div>
