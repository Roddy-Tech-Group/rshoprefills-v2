<?php

namespace App\Http\Controllers;

use Illuminate\Support\Collection;

class BlogController extends Controller
{
    /**
     * Blog index: a grid of articles.
     */
    public function index()
    {
        return view('shop.blog', ['posts' => $this->posts()->all()]);
    }

    /**
     * A single blog article.
     */
    public function show(string $slug)
    {
        $post = $this->posts()->firstWhere('slug', $slug);

        abort_unless($post, 404);

        $related = $this->posts()
            ->where('slug', '!=', $slug)
            ->take(3)
            ->values()
            ->all();

        return view('shop.blog-post', ['post' => $post, 'related' => $related]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function posts(): Collection
    {
        return collect(config('blog.posts', []));
    }
}
