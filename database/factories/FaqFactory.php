<?php

namespace Database\Factories;

use App\Models\Faq;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Faq>
 */
class FaqFactory extends Factory
{
    public function definition(): array
    {
        return [
            'topic' => fake()->randomElement([
                'Orders & Delivery',
                'Payments & Wallet',
                'Gift Cards & eSIMs',
                'Account & Verification',
                'Transaction PIN & Security',
                'Refunds & Disputes',
            ]),
            'question' => trim(fake()->sentence(8), '.').'?',
            'answer' => fake()->paragraph(2),
            'is_published' => true,
            'sort_order' => 0,
        ];
    }
}
