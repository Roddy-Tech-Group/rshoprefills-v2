<?php

namespace Tests\Feature\Cart;

use App\Models\Category;
use App\Models\PricingRule;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Subcategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CartTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private ProductVariant $variant;

    protected function setUp(): void
    {
        parent::setUp();

        config(['pricing.safety_markup_percent' => 10.0, 'pricing.min_margin_percent' => 1.0]);

        $this->user = User::factory()->create();

        $category = Category::create(['name' => 'Gift Cards', 'slug' => 'gift-cards', 'type' => 'digital']);
        $subcategory = Subcategory::create(['category_id' => $category->id, 'name' => 'Gaming', 'slug' => 'gaming']);
        $product = Product::create([
            'category_id' => $category->id,
            'subcategory_id' => $subcategory->id,
            'provider_name' => 'zendit',
            'brand_key' => 'Steam',
            'country_code' => 'US',
            'currency_code' => 'USD',
            'name' => 'Steam Gift Card',
            'slug' => 'steam-gift-card',
            'is_active' => true,
        ]);
        $this->variant = ProductVariant::create([
            'product_id' => $product->id,
            'provider_offer_id' => 'steam-offer-10',
            'sku' => 'STEAM10',
            'currency' => 'USD',
            'face_value' => 10.00,
            'cost_price' => 9.50,
            'retail_price' => 10.50,
            'is_available' => true,
        ]);

        Cache::flush();
        PricingRule::create(['markup_type' => 'fixed', 'markup_value' => 1.00, 'is_active' => true]);
    }

    public function test_a_customer_can_add_an_item_to_the_cart(): void
    {
        $this->actingAs($this->user)
            ->postJson(route('cart.items.add'), [
                'product_variant_id' => $this->variant->id,
                'quantity' => 2,
            ])
            ->assertOk()
            ->assertJsonPath('count', 2)
            ->assertJsonCount(1, 'items');
    }

    public function test_a_customer_can_update_an_item_quantity(): void
    {
        $add = $this->actingAs($this->user)->postJson(route('cart.items.add'), [
            'product_variant_id' => $this->variant->id,
            'quantity' => 1,
        ])->assertOk();
        $itemId = $add->json('items.0.id');

        $this->actingAs($this->user)
            ->patchJson(route('cart.items.update', $itemId), ['quantity' => 3])
            ->assertOk()
            ->assertJsonPath('count', 3);
    }

    public function test_a_customer_can_remove_an_item(): void
    {
        $add = $this->actingAs($this->user)->postJson(route('cart.items.add'), [
            'product_variant_id' => $this->variant->id,
            'quantity' => 1,
        ])->assertOk();
        $itemId = $add->json('items.0.id');

        $this->actingAs($this->user)
            ->deleteJson(route('cart.items.remove', $itemId))
            ->assertOk()
            ->assertJsonPath('count', 0);
    }

    public function test_adding_an_unknown_variant_is_rejected(): void
    {
        $this->actingAs($this->user)
            ->postJson(route('cart.items.add'), [
                'product_variant_id' => 999999,
                'quantity' => 1,
            ])
            ->assertStatus(422);
    }

    public function test_an_empty_cart_reports_zero(): void
    {
        $this->actingAs($this->user)
            ->getJson(route('cart.data'))
            ->assertOk()
            ->assertJsonPath('count', 0);
    }
}
