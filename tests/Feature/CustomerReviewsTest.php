<?php

namespace Tests\Feature;

use App\Models\Review;
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

        // The "4.6 / 5" caption is split across HTML tags
        // (`{number} <span> / 5</span>`), so check the pieces in order
        // against the stripped text. 4.6 is the per-source average computed
        // live from seeded reviews (Trustpilot and Google both round to 4.6).
        $this->get('/')
            ->assertOk()
            ->assertSee('What our customers say')
            ->assertSee('Adaeze O.')
            ->assertSeeTextInOrder(['4.6', '/ 5']);
    }

    public function test_homepage_falls_back_gracefully_when_no_reviews_exist(): void
    {
        $this->withoutVite();

        // No reviews + no aggregate settings — should still render without errors.
        $this->get('/')->assertOk();
    }

    public function test_homepage_shows_per_source_count_and_rating_from_seeded_reviews(): void
    {
        $this->withoutVite();
        $this->seed(ReviewSeeder::class);

        // The homepage aggregate card is computed LIVE from the reviews
        // table (10 Trustpilot + 10 Google in the seeder) so the score
        // and count stay honest with what's been imported. The
        // SiteSetting `reviews.aggregate.*` keys are only a fallback for
        // sources that have no rows yet.
        $this->get('/')
            ->assertOk()
            ->assertSeeTextInOrder(['4.6', '/ 5'])
            ->assertSeeText('10+ reviews on Trustpilot');
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
