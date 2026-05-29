<?php

namespace Database\Factories;

use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'provider_offer_id' => $this->faker->uuid,
            'sku' => $this->faker->uuid,
            'currency' => 'USD',
            'face_value' => 10,
            'cost_price' => 8,
            'retail_price' => 10,
            'is_variable' => false,
            'is_available' => true,
        ];
    }
}
