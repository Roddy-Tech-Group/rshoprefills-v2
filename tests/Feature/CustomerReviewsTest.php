<?php

namespace Tests\Feature;

use App\Models\Review;
use App\Models\SiteSetting;
use Database\Seeders\ReviewSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerReviewsTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_renders_seeded_reviews_and_aggregate(): void
    {
        $this->withoutVite();
        $this->seed(ReviewSeeder::class);

        // The "4.4 / 5" caption is split across HTML tags
        // (`{number} <span> / 5</span>`), so check the pieces in order
        // against the stripped text instead of a single literal substring.
        $this->get('/')
            ->assertOk()
            ->assertSee('What our customers say')
            ->assertSee('Harshit Garg')
            ->assertSeeTextInOrder(['4.4', '/ 5']);
    }

    public function test_homepage_falls_back_gracefully_when_no_reviews_exist(): void
    {
        $this->withoutVite();

        // No reviews + no aggregate settings — should still render without errors.
        $this->get('/')->assertOk();
    }

    public function test_aggregate_setting_overrides_seeded_values(): void
    {
        $this->withoutVite();
        $this->seed(ReviewSeeder::class);

        SiteSetting::put('reviews.aggregate.rating', 4.9, 'reviews');
        SiteSetting::put('reviews.aggregate.count', 1234, 'reviews');

        $this->get('/')
            ->assertOk()
            ->assertSeeTextInOrder(['4.9', '/ 5'])
            ->assertSeeText('1,234+ reviews');
    }

    public function test_unpublished_reviews_are_hidden_from_homepage(): void
    {
        $this->withoutVite();
        Review::factory()->create([
            'author_name' => 'Visible Author',
            'is_published' => true,
        ]);
        Review::factory()->create([
            'author_name' => 'Hidden Author',
            'is_published' => false,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Visible Author')
            ->assertDontSee('Hidden Author');
    }
}
