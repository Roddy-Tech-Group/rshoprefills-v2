<?php

namespace Tests\Feature\Checkout;

use App\Domain\Order\Exceptions\InvalidCouponException;
use App\Domain\Order\Services\CheckoutService;
use App\Domain\Shared\Enums\Currency;
use App\Jobs\FulfillOrderItemJob;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\PricingRule;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Subcategory;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class CouponRedemptionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private ProductVariant $variant;

    private Cart $cart;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.zendit.api_key' => 'ZENDIT_API_KEY_MOCK',
            'services.flutterwave.secret_key' => 'FLW_SECRET_KEY_MOCK',
            'services.nowpayments.api_key' => 'NOWPAYMENTS_KEY_MOCK',
            'pricing.safety_markup_percent' => 10.0,
            'pricing.min_margin_percent' => 1.0,
        ]);

        $this->user = User::factory()->create();

        $category = Category::create(['name' => 'Gift Cards', 'slug' => 'gift-cards', 'type' => 'digital']);
        $subcategory = Subcategory::create(['category_id' => $category->id, 'name' => 'Gaming', 'slug' => 'gaming']);
        $product = Product::create([
            'category_id' => $category->id,
            'subcategory_id' => $subcategory->id,
            'provider_name' => 'zendit',
            'country_code' => 'US',
            'currency_code' => 'USD',
            'name' => 'Steam Gift Card',
            'slug' => 'steam-gift-card',
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

        $this->cart = Cart::create([
            'user_id' => $this->user->id,
            'status' => 'active',
            'last_activity_at' => now(),
        ]);

        CartItem::create([
            'cart_id' => $this->cart->id,
            'product_id' => $product->id,
            'product_variant_id' => $this->variant->id,
            'quantity' => 1,
            'display_currency' => 'USD',
            'display_amount' => 11.00,
            'provider_cost_usd' => 9.50,
            'markup_amount' => 1.00,
            'unit_price_snapshot' => 11.00,
            'subtotal_snapshot' => 11.00,
        ]);

        Wallet::create([
            'user_id' => $this->user->id,
            'currency' => Currency::USD,
            'balance' => 100.00,
            'locked_balance' => 0.00,
            'is_active' => true,
        ]);

        Queue::fake([FulfillOrderItemJob::class]);
    }

    public function test_percent_coupon_discounts_the_order_total_and_increments_used_count(): void
    {
        $coupon = Coupon::create([
            'product_variant_id' => $this->variant->id,
            'code' => 'SAVE10',
            'discount_type' => 'percent',
            'discount_value' => 10,
            'is_active' => true,
        ]);

        $order = app(CheckoutService::class)->placeOrder(
            user: $this->user,
            cart: $this->cart,
            paymentMethod: 'wallet',
            displayCurrency: 'USD',
            couponCode: 'save10',
        );

        // $11.00 - 10% = $9.90
        $this->assertEqualsWithDelta(9.90, (float) $order->total_amount, 0.01);
        $this->assertSame('SAVE10', $order->metadata['coupon_code']);
        $this->assertEqualsWithDelta(1.10, (float) $order->metadata['coupon_discount_usd'], 0.01);

        $coupon->refresh();
        $this->assertSame(1, $coupon->used_count);
    }

    public function test_fixed_coupon_subtracts_a_flat_amount(): void
    {
        Coupon::create([
            'product_variant_id' => $this->variant->id,
            'code' => 'FIVEOFF',
            'discount_type' => 'fixed',
            'discount_value' => 5,
            'is_active' => true,
        ]);

        $order = app(CheckoutService::class)->placeOrder(
            user: $this->user,
            cart: $this->cart,
            paymentMethod: 'wallet',
            displayCurrency: 'USD',
            couponCode: 'FIVEOFF',
        );

        // $11.00 - $5.00 = $6.00
        $this->assertEqualsWithDelta(6.00, (float) $order->total_amount, 0.01);
    }

    public function test_unknown_coupon_throws_invalid_coupon_exception(): void
    {
        $this->expectException(InvalidCouponException::class);
        $this->expectExceptionMessage('That coupon code is not valid.');

        app(CheckoutService::class)->placeOrder(
            user: $this->user,
            cart: $this->cart,
            paymentMethod: 'wallet',
            displayCurrency: 'USD',
            couponCode: 'NOPE',
        );
    }

    public function test_inactive_coupon_is_rejected_as_no_longer_available(): void
    {
        Coupon::create([
            'product_variant_id' => $this->variant->id,
            'code' => 'DISABLED',
            'discount_type' => 'percent',
            'discount_value' => 10,
            'is_active' => false,
        ]);

        $this->expectException(InvalidCouponException::class);
        $this->expectExceptionMessage('This coupon is no longer available.');

        app(CheckoutService::class)->placeOrder(
            user: $this->user,
            cart: $this->cart,
            paymentMethod: 'wallet',
            displayCurrency: 'USD',
            couponCode: 'DISABLED',
        );
    }

    public function test_used_up_coupon_is_rejected(): void
    {
        Coupon::create([
            'product_variant_id' => $this->variant->id,
            'code' => 'MAXED',
            'discount_type' => 'percent',
            'discount_value' => 10,
            'is_active' => true,
            'max_uses' => 1,
            'used_count' => 1,
        ]);

        $this->expectException(InvalidCouponException::class);
        $this->expectExceptionMessage('This coupon is no longer available.');

        app(CheckoutService::class)->placeOrder(
            user: $this->user,
            cart: $this->cart,
            paymentMethod: 'wallet',
            displayCurrency: 'USD',
            couponCode: 'MAXED',
        );
    }

    public function test_coupon_for_a_variant_not_in_cart_is_rejected(): void
    {
        $otherVariant = ProductVariant::create([
            'product_id' => $this->variant->product_id,
            'provider_offer_id' => 'steam-offer-25',
            'sku' => 'STEAM25',
            'currency' => 'USD',
            'face_value' => 25.00,
            'cost_price' => 24.00,
            'retail_price' => 26.00,
            'is_available' => true,
        ]);

        Coupon::create([
            'product_variant_id' => $otherVariant->id,
            'code' => 'WRONGSKU',
            'discount_type' => 'percent',
            'discount_value' => 10,
            'is_active' => true,
        ]);

        $this->expectException(InvalidCouponException::class);
        $this->expectExceptionMessage('This coupon does not apply to any item in your cart.');

        app(CheckoutService::class)->placeOrder(
            user: $this->user,
            cart: $this->cart,
            paymentMethod: 'wallet',
            displayCurrency: 'USD',
            couponCode: 'WRONGSKU',
        );
    }

    public function test_no_coupon_code_still_places_the_order_normally(): void
    {
        $order = app(CheckoutService::class)->placeOrder(
            user: $this->user,
            cart: $this->cart,
            paymentMethod: 'wallet',
            displayCurrency: 'USD',
        );

        $this->assertEqualsWithDelta(11.00, (float) $order->total_amount, 0.01);
        $this->assertNull($order->metadata['coupon_id']);
    }
}
