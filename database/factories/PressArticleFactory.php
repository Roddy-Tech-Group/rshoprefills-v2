<?php

namespace Database\Factories;

use App\Models\PressArticle;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PressArticle>
 */
class PressArticleFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->unique()->sentence(6);

        return [
            'slug' => Str::slug($title),
            'category' => fake()->randomElement(['News', 'Implementation', 'Announcement']),
            'title' => $title,
            'excerpt' => fake()->sentence(12),
            'image' => 'best prices.svg',
            'body' => [fake()->paragraph(3), fake()->paragraph(3)],
            'published_at' => fake()->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'is_published' => true,
            'sort_order' => 0,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => ['is_published' => false]);
    }
}
