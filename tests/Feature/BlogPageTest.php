<?php

namespace Tests\Feature;

use App\Models\BlogPost;
use Database\Seeders\BlogPostSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlogPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_blog_index_renders_with_posts(): void
    {
        $this->withoutVite();
        $this->seed(BlogPostSeeder::class);

        $this->get(route('shop.blog'))
            ->assertOk()
            ->assertSee('Guides, tips and stories')
            ->assertSee('Getting started: your first purchase on RshopRefills');
    }

    public function test_a_single_blog_post_renders(): void
    {
        $this->withoutVite();
        $this->seed(BlogPostSeeder::class);

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

    public function test_unpublished_blog_posts_are_hidden_from_the_public_site(): void
    {
        $this->withoutVite();
        BlogPost::factory()->draft()->create(['slug' => 'secret-draft', 'title' => 'Secret Draft Post']);

        $this->get(route('shop.blog'))
            ->assertOk()
            ->assertDontSee('Secret Draft Post');

        $this->get(route('shop.blog.show', 'secret-draft'))->assertNotFound();
    }
}
