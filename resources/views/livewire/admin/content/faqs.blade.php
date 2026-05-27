<?php

use App\Models\Faq;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new
#[Layout('components.layouts.admin')]
#[Title('FAQs')]
class extends Component {
    #[Computed]
    public function faqs()
    {
        return Faq::ordered()->get()->groupBy('topic');
    }
}; ?>

<div class="w-full px-4 py-8 sm:px-6 lg:px-8">

    <header class="mb-6 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-zinc-900">FAQs</h1>
            <p class="mt-1 text-sm text-zinc-600">Help Center questions, grouped by topic. Shown at <a href="/help" target="_blank" class="text-blue-600 hover:underline">/help</a>.</p>
        </div>
    </header>

    @forelse ($this->faqs as $topic => $items)
        <section class="mb-6 overflow-hidden rounded-2xl bg-white ring-1 ring-zinc-100 shadow-sm">
            <div class="border-b border-zinc-100 bg-zinc-50 px-5 py-3">
                <h2 class="text-sm font-bold uppercase tracking-wider text-zinc-700">{{ $topic }}</h2>
            </div>
            <ul class="divide-y divide-zinc-100">
                @foreach ($items as $faq)
                    <li class="px-5 py-4">
                        <p class="text-sm font-semibold text-zinc-900">{{ $faq->question }}</p>
                        <p class="mt-1.5 text-sm leading-relaxed text-zinc-600">{{ $faq->answer }}</p>
                        @unless ($faq->is_published)
                            <span class="mt-2 inline-flex items-center rounded-[5px] bg-zinc-400 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-white">Draft</span>
                        @endunless
                    </li>
                @endforeach
            </ul>
        </section>
    @empty
        <div class="rounded-2xl bg-white p-8 text-center ring-1 ring-zinc-100 shadow-sm">
            <p class="text-sm text-zinc-600">No FAQs yet. Run <code class="rounded bg-zinc-100 px-1.5 py-0.5">php artisan db:seed --class=FaqSeeder</code>.</p>
        </div>
    @endforelse
</div>
