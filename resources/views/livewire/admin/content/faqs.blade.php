<?php

use App\Models\Faq;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;

new
#[Layout('components.layouts.admin')]
#[Title('FAQs')]
class extends Component {
    public ?int $editingId = null;

    public bool $showForm = false;

    #[Validate('required|string|max:80')]
    public string $topic = '';

    #[Validate('required|string|max:200')]
    public string $question = '';

    #[Validate('required|string|max:5000')]
    public string $answer = '';

    #[Validate('boolean')]
    public bool $isPublished = true;

    #[Validate('integer|min:0|max:10000')]
    public int $sortOrder = 0;

    #[Computed]
    public function faqs()
    {
        return Faq::ordered()->get()->groupBy('topic');
    }

    public function newFaq(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $faq = Faq::findOrFail($id);
        $this->editingId = $faq->id;
        $this->topic = $faq->topic;
        $this->question = $faq->question;
        $this->answer = $faq->answer;
        $this->isPublished = $faq->is_published;
        $this->sortOrder = $faq->sort_order;
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->validate();

        $payload = [
            'topic' => $this->topic,
            'question' => $this->question,
            'answer' => $this->answer,
            'is_published' => $this->isPublished,
            'sort_order' => $this->sortOrder,
        ];

        if ($this->editingId) {
            Faq::findOrFail($this->editingId)->update($payload);
            session()->flash('status', 'FAQ updated.');
        } else {
            Faq::create($payload);
            session()->flash('status', 'FAQ created.');
        }

        $this->resetForm();
        unset($this->faqs);
    }

    public function delete(int $id): void
    {
        Faq::findOrFail($id)->delete();
        session()->flash('status', 'FAQ deleted.');
        unset($this->faqs);
    }

    public function togglePublish(int $id): void
    {
        $faq = Faq::findOrFail($id);
        $faq->update(['is_published' => ! $faq->is_published]);
        unset($this->faqs);
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->topic = '';
        $this->question = '';
        $this->answer = '';
        $this->isPublished = true;
        $this->sortOrder = 0;
        $this->showForm = false;
        $this->resetValidation();
    }
}; ?>

<div class="w-full px-4 py-8 sm:px-6 lg:px-8">

    <header class="mb-6 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-zinc-900">FAQs</h1>
            <p class="mt-1 text-sm text-zinc-600">Help Center questions, grouped by topic. Shown at <a href="/help" target="_blank" class="text-blue-600 hover:underline">/help</a>.</p>
        </div>
        <button wire:click="newFaq" type="button" class="inline-flex items-center gap-1.5 rounded-[10px] bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-blue-700">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            New FAQ
        </button>
    </header>

    @if (session('status'))
        <div class="mb-4 rounded-[10px] bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 ring-1 ring-emerald-200">{{ session('status') }}</div>
    @endif

    @forelse ($this->faqs as $topic => $items)
        <section class="mb-6 overflow-hidden rounded-[10px] bg-white ring-1 ring-zinc-100 shadow-sm">
            <div class="border-b border-zinc-100 bg-zinc-50 px-5 py-3">
                <h2 class="text-sm font-bold uppercase tracking-wider text-zinc-700">{{ $topic }}</h2>
            </div>
            <ul class="divide-y divide-zinc-100">
                @foreach ($items as $faq)
                    <li class="flex items-start justify-between gap-4 px-5 py-4">
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-zinc-900">{{ $faq->question }}</p>
                            <p class="mt-1.5 text-sm leading-relaxed text-zinc-600">{{ $faq->answer }}</p>
                            @unless ($faq->is_published)
                                <span class="mt-2 inline-block">
                                    <x-admin.badge tone="zinc">Draft</x-admin.badge>
                                </span>
                            @endunless
                        </div>
                        <div class="flex shrink-0 items-center gap-1.5">
                            <button wire:click="togglePublish({{ $faq->id }})" type="button" class="rounded-[10px] bg-zinc-100 px-2.5 py-1 text-[11px] font-semibold text-zinc-700 transition-colors hover:bg-zinc-200" title="Toggle publish">
                                {{ $faq->is_published ? 'Unpublish' : 'Publish' }}
                            </button>
                            <button wire:click="edit({{ $faq->id }})" type="button" class="rounded-[10px] bg-blue-50 px-2.5 py-1 text-[11px] font-semibold text-blue-700 transition-colors hover:bg-blue-100">Edit</button>
                            <button wire:click="delete({{ $faq->id }})" wire:confirm="Delete this FAQ permanently?" type="button" class="rounded-[10px] bg-red-50 px-2.5 py-1 text-[11px] font-semibold text-red-700 transition-colors hover:bg-red-100">Delete</button>
                        </div>
                    </li>
                @endforeach
            </ul>
        </section>
    @empty
        <div class="rounded-[10px] bg-white p-8 text-center ring-1 ring-zinc-100 shadow-sm">
            <p class="text-sm text-zinc-600">No FAQs yet. Click "New FAQ" to create one, or run <code class="rounded-[10px] bg-zinc-100 px-1.5 py-0.5">php artisan db:seed --class=FaqSeeder</code>.</p>
        </div>
    @endforelse

    {{-- Create/edit modal --}}
    @if ($showForm)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true">
            <div wire:click="$set('showForm', false)" class="absolute inset-0 bg-zinc-900/40"></div>
            <form wire:submit="save" class="relative w-full max-w-xl overflow-hidden rounded-[10px] bg-white shadow-2xl">
                <div class="flex items-start justify-between gap-4 border-b border-zinc-100 px-5 py-4">
                    <h3 class="text-sm font-bold text-zinc-900">{{ $editingId ? 'Edit FAQ' : 'New FAQ' }}</h3>
                    <button type="button" wire:click="$set('showForm', false)" aria-label="Close" class="flex h-8 w-8 items-center justify-center rounded-[10px] bg-zinc-100 text-zinc-600 transition-colors hover:bg-zinc-200">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="space-y-4 px-5 py-4">
                    <div>
                        <label class="text-[10px] font-semibold uppercase tracking-wider text-zinc-800">Topic</label>
                        <input wire:model="topic" type="text" placeholder="e.g. Orders and delivery" class="mt-1.5 w-full rounded-[10px] border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/15">
                        @error('topic') <p class="mt-1 text-[11px] font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="text-[10px] font-semibold uppercase tracking-wider text-zinc-800">Question</label>
                        <input wire:model="question" type="text" placeholder="What does this answer?" class="mt-1.5 w-full rounded-[10px] border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/15">
                        @error('question') <p class="mt-1 text-[11px] font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="text-[10px] font-semibold uppercase tracking-wider text-zinc-800">Answer</label>
                        <textarea wire:model="answer" rows="5" placeholder="Write the answer the customer will see." class="mt-1.5 w-full rounded-[10px] border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/15"></textarea>
                        @error('answer') <p class="mt-1 text-[11px] font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-[10px] font-semibold uppercase tracking-wider text-zinc-800">Sort order</label>
                            <input wire:model="sortOrder" type="number" min="0" max="10000" class="mt-1.5 w-full rounded-[10px] border border-zinc-200 bg-white px-3 py-2 text-sm tabular-nums text-zinc-900 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/15">
                            @error('sortOrder') <p class="mt-1 text-[11px] font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <label class="flex items-end gap-2 pb-2">
                            <input wire:model="isPublished" type="checkbox" class="h-4 w-4 cursor-pointer accent-blue-600">
                            <span class="text-sm font-semibold text-zinc-700">Published</span>
                        </label>
                    </div>
                </div>
                <div class="flex items-center justify-end gap-2 border-t border-zinc-100 bg-zinc-50 px-5 py-3">
                    <button type="button" wire:click="$set('showForm', false)" class="inline-flex items-center rounded-[10px] px-3.5 py-2 text-xs font-semibold text-zinc-700 transition-colors hover:bg-zinc-100">Cancel</button>
                    <button type="submit" class="inline-flex items-center rounded-[10px] bg-blue-600 px-4 py-2 text-xs font-semibold text-white transition-colors hover:bg-blue-700">{{ $editingId ? 'Save changes' : 'Create FAQ' }}</button>
                </div>
            </form>
        </div>
    @endif
</div>
