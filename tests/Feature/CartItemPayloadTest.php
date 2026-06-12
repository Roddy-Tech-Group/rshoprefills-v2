<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Subcategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The /cart/data payload drives the cart page, the nav popup and checkout.
 * eSIM products have no brand logo or brand key, so each item must carry
 * enough for the views to render honestly: the product name as fallback,
 * the country flag (or a Global marker), and the category for unit labels
 * ("eSIM", never "card").
 */
class CartItemPayloadTest extends TestCase
{
    use RefreshDatabase;

    private function makeEsimVariant(string $countryCode): ProductVariant
    {
        $category = Category::factory()->create(['slug' => 'esims', 'name' => 'eSIMs']);
        $subcategory = Subcategory::factory()->create(['category_id' => $category->id]);
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'subcategory_id' => $subcategory->id,
            'provider_name' => 'zendit',
            'brand_key' => null,
            'logo_url' => null,
            'country_code' => $countryCode,
            'name' => ($countryCode === 'WW' ? 'Global' : $countryCode).' Data eSIM',
        ]);

        return ProductVariant::factory()->create([
            'product_id' => $product->id,
            'subcategory_id' => $subcategory->id,
            'currency' => 'USD',
            'face_value' => 2.5,
            'cost_price' => 1.8,
            'retail_price' => 2.5,
        ]);
    }

    private function cartDataWithItem(ProductVariant $variant): array
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/cart/items', ['product_variant_id' => $variant->id, 'quantity' => 1])
            ->assertSuccessful();

        return $this->actingAs($user)->getJson('/cart/data')->assertOk()->json();
    }

    public function test_esim_item_carries_name_flag_and_category_for_the_views(): void
    {
        $data = $this->cartDataWithItem($this->makeEsimVariant('US'));

        $item = $data['items'][0];

        // brandDisplayName is empty for eSIMs - the product name must step in.
        $this->assertSame('US Data eSIM', $item['name']);
        $this->assertSame('esims', $item['category_slug']);
        $this->assertSame('https://flagcdn.com/w160/us.png', $item['flag']);
        $this->assertFalse($item['is_global']);
    }

    public function test_global_esim_is_marked_global_with_no_flag(): void
    {
        $data = $this->cartDataWithItem($this->makeEsimVariant('WW'));

        $item = $data['items'][0];

        $this->assertTrue($item['is_global']);
        $this->assertNull($item['flag']);
        $this->assertSame('Global', $item['country_name']);
    }
}
