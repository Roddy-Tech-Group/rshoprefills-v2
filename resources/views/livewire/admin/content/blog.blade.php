<?php

use App\Models\BlogPost;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new
#[Layout('components.layouts.admin')]
#[Title('Blog Posts')]
class extends Component {
    #[Computed]
    public function posts()
    {
        return BlogPost::orderByDesc('published_at')->get();
    }
}; ?>

<div class="w-full px-4 py-8 sm:px-6 lg:px-8">

    <header class="mb-6 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-zinc-900">Blog Posts</h1>
            <p class="mt-1 text-sm text-zinc-600">CMS-managed articles shown at <a href="/blog" target="_blank" class="text-blue-600 hover:underline">/blog</a>. Editing UI lands soon — use a seeder or tinker for now.</p>
        </div>
        <span class="rounded-[10px] bg-zinc-100 px-3 py-1 text-xs font-semibold text-zinc-600">{{ $this->posts->count() }} {{ \Illuminate\Support\Str::plural('post', $this->posts->count()) }}</span>
    </header>

    <div class="overflow-hidden rounded-[10px] bg-white ring-1 ring-zinc-100 shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-zinc-50 text-[11px] uppercase tracking-wider text-zinc-600">
                    <tr>
                        <th class="px-5 py-3 font-semibold">Title</th>
                        <th class="px-5 py-3 font-semibold">Category</th>
                        <th class="px-5 py-3 font-semibold">Published</th>
                        <th class="px-5 py-3 font-semibold">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    @forelse ($this->posts as $post)
                        <tr class="hover:bg-zinc-50">
                            <td class="px-5 py-3">
                                <p class="font-semibold text-zinc-900">{{ $post->title }}</p>
                                <p class="mt-0.5 text-xs text-zinc-500">/{{ $post->slug }}</p>
                            </td>
                            <td class="px-5 py-3 text-zinc-700">{{ $post->category }}</td>
                            <td class="px-5 py-3 text-zinc-700">{{ $post->published_at->format('M j, Y') }}</td>
                            <td class="px-5 py-3">
                                <span class="inline-flex items-center rounded-[5px] {{ $post->is_published ? 'bg-emerald-500' : 'bg-zinc-400' }} px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-white">
                                    {{ $post->is_published ? 'Published' : 'Draft' }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-5 py-12 text-center text-sm text-zinc-600">No posts yet. Run <code class="rounded-[10px] bg-zinc-100 px-1.5 py-0.5">php artisan db:seed --class=BlogPostSeeder</code>.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
