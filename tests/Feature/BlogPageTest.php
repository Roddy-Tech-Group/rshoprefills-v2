<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlogPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_blog_index_renders_with_posts(): void
    {
        $this->withoutVite();

        $this->get(route('shop.blog'))
            ->assertOk()
            ->assertSee('Guides, tips and stories')
            ->assertSee('Getting started: your first purchase on RshopRefills');
    }

    public function test_a_single_blog_post_renders(): void
    {
        $this->withoutVite();

        $this->get(route('shop.blog.show', 'getting-started-your-first-purchase'))
            ->assertOk()
            ->assertSee('Getting started: your first purchase on RshopRefills')
            ->assertSee('Back to blog')
            ->assertSee('More from the blog');
    }

    public function test_an_unknown_blog_post_returns_404(): void
    {
        $this->get(route('shop.blog.show', 'this-article-does-not-exist'))->assertNotFound();
    }
}
