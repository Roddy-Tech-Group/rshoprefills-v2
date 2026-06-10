<?php

namespace Tests\Feature\Dashboard;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class DashboardShopFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_mirrors_the_transactional_flow_routes(): void
    {
        $this->assertTrue(Route::has('dashboard.shop.cart'));
        $this->assertTrue(Route::has('dashboard.shop.checkout'));
        $this->assertTrue(Route::has('dashboard.shop.checkout.process'));
        $this->assertTrue(Route::has('dashboard.shop.checkout.return'));
        $this->assertTrue(Route::has('dashboard.shop.order'));
    }

    public function test_dashboard_cart_keeps_its_links_inside_the_dashboard(): void
    {
        $this->withoutVite();

        $response = $this->actingAs(User::factory()->create())
            ->get('/dashboard/shop/cart')
            ->assertOk();

        // Checkout button and continue-shopping link stay under /dashboard/shop/*.
        $response->assertSee('/dashboard/shop/checkout', false);
        $response->assertSee('/dashboard/shop/gift-cards', false);
    }

    public function test_dashboard_checkout_keeps_its_links_inside_the_dashboard(): void
    {
        $this->withoutVite();

        $response = $this->actingAs(User::factory()->create())
            ->get('/dashboard/shop/checkout')
            ->assertOk();

        // The POST endpoint and continue-shopping link stay in the dashboard.
        $response->assertSee('/dashboard/shop/checkout', false);
        $response->assertSee('/dashboard/shop/gift-cards', false);
    }

    public function test_storefront_cart_is_unchanged_and_links_stay_on_the_storefront(): void
    {
        $this->withoutVite();

        $response = $this->get('/cart')->assertOk();

        // Storefront context must not leak dashboard URLs into its links.
        $response->assertSee(route('shop.checkout'), false);
        $response->assertDontSee('/dashboard/shop/checkout', false);
    }

    public function test_dashboard_shop_pages_require_authentication(): void
    {
        $this->get('/dashboard/shop/cart')->assertRedirect(route('login'));
        $this->get('/dashboard/shop/checkout')->assertRedirect(route('login'));
    }
}
