<?php

namespace Database\Factories;

use App\Models\Review;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Review>
 */
class ReviewFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->name();

        return [
            'initials' => Str::upper(Str::substr($name, 0, 2)),
            'author_name' => $name,
            'body' => fake()->sentence(15),
            'rating' => fake()->numberBetween(4, 5),
            'source' => 'Trustpilot',
            'reviewed_at' => fake()->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'is_published' => true,
            'sort_order' => 0,
        ];
    }
}
