<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;

class BlogController extends Controller
{
    /**
     * Blog index: a grid of articles.
     */
    public function index()
    {
        return view('shop.blog', [
            'posts' => BlogPost::published()->ordered()->get(),
        ]);
    }

    /**
     * A single blog article. 404s on an unpublished or unknown slug — never
     * leak draft content via the public URL.
     */
    public function show(string $slug)
    {
        $post = BlogPost::published()->where('slug', $slug)->firstOrFail();

        $related = BlogPost::published()
            ->ordered()
            ->where('id', '!=', $post->id)
            ->limit(3)
            ->get();

        return view('shop.blog-post', ['post' => $post, 'related' => $related]);
    }
}
