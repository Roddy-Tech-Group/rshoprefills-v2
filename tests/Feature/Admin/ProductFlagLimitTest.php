<?php

namespace Tests\Feature\Admin;

use App\Domain\Admin\Enums\AdminRole;
use App\Models\Admin;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

/**
 * Storefront curation slots: up to 10 products may carry each of the
 * Featured / Popular badges. The toggle endpoints enforce the cap and always
 * report live slot usage.
 */
class ProductFlagLimitTest extends TestCase
{
    use RefreshDatabase;

    private function asAdmin(): self
    {
        $admin = Admin::firstOrCreate(
            ['email' => 'flags-admin@example.test'],
            ['name' => 'Flags Admin', 'password' => 'password', 'role' => AdminRole::SuperAdmin, 'is_active' => true],
        );
        $this->actingAs($admin, 'admin');

        return $this;
    }

    private function makeProducts(int $count): Collection
    {
        $category = Category::factory()->create();

        return Product::factory()->count($count)->create(['category_id' => $category->id]);
    }

    public function test_popular_flag_caps_at_ten_products(): void
    {
        $this->asAdmin();
        $products = $this->makeProducts(11);

        foreach ($products->take(10) as $product) {
            $this->patchJson("/admin/api/catalog/products/{$product->id}/toggle-popular")
                ->assertOk()
                ->assertJson(['is_popular' => true, 'max' => 10]);
        }

        $eleventh = $products->last();
        $this->patchJson("/admin/api/catalog/products/{$eleventh->id}/toggle-popular")
            ->assertStatus(422)
            ->assertJson(['flagged_count' => 10, 'max' => 10]);

        $this->assertFalse($eleventh->refresh()->is_popular);
        $this->assertSame(10, Product::where('is_popular', true)->count());
    }

    public function test_unflagging_frees_a_slot_at_the_cap(): void
    {
        $this->asAdmin();
        $products = $this->makeProducts(11);
        Product::whereIn('id', $products->take(10)->pluck('id'))->update(['is_popular' => true]);

        // Deselect (the tiny red X in the drawer) still works at the cap...
        $first = $products->first();
        $this->patchJson("/admin/api/catalog/products/{$first->id}/toggle-popular")
            ->assertOk()
            ->assertJson(['is_popular' => false, 'flagged_count' => 9]);

        // ...and the freed slot is immediately usable.
        $eleventh = $products->last();
        $this->patchJson("/admin/api/catalog/products/{$eleventh->id}/toggle-popular")
            ->assertOk()
            ->assertJson(['is_popular' => true, 'flagged_count' => 10]);
    }

    public function test_featured_flag_caps_independently_of_popular(): void
    {
        $this->asAdmin();
        $products = $this->makeProducts(11);
        Product::whereIn('id', $products->take(10)->pluck('id'))->update(['is_popular' => true]);

        // Popular being full must not consume Featured slots.
        $eleventh = $products->last();
        $this->patchJson("/admin/api/catalog/products/{$eleventh->id}/toggle-featured")
            ->assertOk()
            ->assertJson(['is_featured' => true, 'flagged_count' => 1, 'max' => 10]);
    }
}
