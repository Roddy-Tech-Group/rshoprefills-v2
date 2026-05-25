<?php

namespace App\Http\Controllers;

use Illuminate\Support\Collection;

class PressController extends Controller
{
    /**
     * Newsroom index: a grid of press posts.
     */
    public function index()
    {
        return view('shop.press', ['posts' => $this->posts()->all()]);
    }

    /**
     * A single press post.
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

        return view('shop.press-post', ['post' => $post, 'related' => $related]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function posts(): Collection
    {
        return collect(config('press.posts', []));
    }
}
