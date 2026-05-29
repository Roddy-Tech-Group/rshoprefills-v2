<?php

namespace App\Http\Controllers;

use App\Models\PressArticle;

class PressController extends Controller
{
    /**
     * Newsroom index: a grid of press articles.
     */
    public function index()
    {
        return view('shop.press', [
            'posts' => PressArticle::published()->ordered()->get(),
        ]);
    }

    /**
     * A single press article. 404s on unpublished / unknown slugs.
     */
    public function show(string $slug)
    {
        $post = PressArticle::published()->where('slug', $slug)->firstOrFail();

        $related = PressArticle::published()
            ->ordered()
            ->where('id', '!=', $post->id)
            ->limit(3)
            ->get();

        return view('shop.press-post', ['post' => $post, 'related' => $related]);
    }
}
