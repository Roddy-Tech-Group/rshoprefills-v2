<?php

namespace Tests\Feature;

use App\Models\PressArticle;
use Database\Seeders\PressArticleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PressPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_press_index_renders_with_posts(): void
    {
        $this->withoutVite();
        $this->seed(PressArticleSeeder::class);

        $this->get(route('shop.press'))
            ->assertOk()
            ->assertSee('Newsroom')
            ->assertSee('RshopRefills launches the Rcoin rewards program')
            ->assertSee('Media enquiries');
    }

    public function test_a_single_press_post_renders(): void
    {
        $this->withoutVite();
        $this->seed(PressArticleSeeder::class);

        $this->get(route('shop.press.show', 'rcoin-rewards-program'))
            ->assertOk()
            ->assertSee('RshopRefills launches the Rcoin rewards program')
            ->assertSee('Back to newsroom')
            ->assertSee('More from the newsroom');
    }

    public function test_an_unknown_press_post_returns_404(): void
    {
        $this->get(route('shop.press.show', 'this-post-does-not-exist'))->assertNotFound();
    }

    public function test_unpublished_press_articles_are_hidden_from_the_public_site(): void
    {
        $this->withoutVite();
        PressArticle::factory()->draft()->create(['slug' => 'embargoed', 'title' => 'Embargoed Announcement']);

        $this->get(route('shop.press'))
            ->assertOk()
            ->assertDontSee('Embargoed Announcement');

        $this->get(route('shop.press.show', 'embargoed'))->assertNotFound();
    }
}
