<?php

namespace Tests\Feature;

use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Customers can submit a review after an order. It is stored unpublished and
 * flagged as a customer submission, so it only reaches the storefront once an
 * admin publishes it.
 */
class CustomerReviewSubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_logged_in_customer_can_submit_a_pending_review(): void
    {
        $user = User::factory()->create(['name' => 'Divine Ofeh']);

        $this->actingAs($user)
            ->postJson('/reviews', [
                'author_name' => 'Divine Ofeh',
                'body' => 'Fast delivery and great prices, very happy.',
                'rating' => 5,
            ])
            ->assertOk()
            ->assertJson(['ok' => true]);

        $review = Review::first();
        $this->assertNotNull($review);
        $this->assertFalse($review->is_published);
        $this->assertTrue($review->is_customer_submitted);
        $this->assertSame($user->id, $review->user_id);
        $this->assertSame('RshopRefills', $review->source);
        $this->assertSame('DO', $review->initials);
        $this->assertSame(5, (int) $review->rating);
    }

    public function test_pending_reviews_are_hidden_from_the_storefront_until_published(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->postJson('/reviews', [
            'author_name' => 'Jane Doe',
            'body' => 'Worked perfectly, recommend it.',
            'rating' => 4,
        ])->assertOk();

        $this->assertSame(0, Review::published()->count());
        $this->assertSame(1, Review::pending()->count());

        // Admin approves -> now public.
        Review::first()->update(['is_published' => true]);
        $this->assertSame(1, Review::published()->count());
    }

    public function test_resubmitting_updates_the_same_pending_review(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/reviews', ['author_name' => 'Sam', 'body' => 'First version of my review.', 'rating' => 3])->assertOk();
        $this->actingAs($user)->postJson('/reviews', ['author_name' => 'Sam', 'body' => 'Updated, even better than I thought.', 'rating' => 5])->assertOk();

        $this->assertSame(1, Review::where('user_id', $user->id)->count());
        $this->assertSame(5, (int) Review::where('user_id', $user->id)->first()->rating);
    }

    public function test_guests_cannot_submit(): void
    {
        $this->postJson('/reviews', ['author_name' => 'Anon', 'body' => 'Trying to submit as guest.', 'rating' => 5])
            ->assertUnauthorized();
    }

    public function test_validation_rejects_bad_input(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)
            ->postJson('/reviews', ['author_name' => '', 'body' => 'x', 'rating' => 9])
            ->assertStatus(422);
    }
}
