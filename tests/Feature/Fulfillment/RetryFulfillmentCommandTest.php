<?php

namespace Tests\Feature\Fulfillment;

use App\Domain\Fulfillment\Enums\FulfillmentStatus;
use App\Domain\Fulfillment\Interfaces\FulfillmentProviderInterface;
use App\Domain\Fulfillment\Providers\ZenditFulfillmentProvider;
use App\Domain\Fulfillment\Services\FulfillmentProviderFactory;
use App\Jobs\FulfillOrderItemJob;
use App\Jobs\PollPendingFulfillmentJob;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Subcategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Manual fulfillment retries. Zendit permanently burns the transaction ID of
 * a failed purchase ("transaction_duplicate_id" on reuse), so each manual
 * retry must send a fresh ID - while a transaction the provider still
 * considers alive must be polled, never re-bought.
 */
class RetryFulfillmentCommandTest extends TestCase
{
    use RefreshDatabase;

    private function makeFailedItem(array $overrides = []): OrderItem
    {
        $user = User::factory()->create();

        $order = Order::create([
            'user_id' => $user->id,
            'order_number' => 'RSR-TEST-'.fake()->unique()->numerify('######'),
            'cart_id' => null,
            'settlement_currency' => 'USD',
            'display_currency' => 'USD',
            'subtotal_amount' => 9.35,
            'markup_amount' => 0,
            'total_amount' => 9.35,
            'payment_method' => 'card',
            'payment_status' => 'paid',
            'fulfillment_status' => 'failed',
            'order_status' => 'failed',
            'placed_at' => now(),
            'metadata' => ['exchange_rate' => 1.0, 'settlement_total_usd' => 9.35],
        ]);

        $category = Category::factory()->create(['slug' => 'gift-cards']);
        $subcategory = Subcategory::factory()->create(['category_id' => $category->id]);
        $product = Product::factory()->create(['category_id' => $category->id]);
        $variant = ProductVariant::factory()->create(['product_id' => $product->id]);

        return OrderItem::create(array_merge([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'category_id' => $category->id,
            'subcategory_id' => $subcategory->id,
            'provider_name' => 'zendit',
            'provider_offer_id' => 'OFFER-1',
            'quantity' => 1,
            'display_currency' => 'USD',
            'display_amount' => 9.35,
            'provider_cost_usd' => 8.90,
            'markup_amount' => 0,
            'subtotal_amount' => 9.35,
            'product_snapshot' => ['name' => 'Test Gift Card', 'category' => ['slug' => 'gift-cards']],
            'variant_snapshot' => ['face_value' => 9.35, 'currency' => 'USD'],
            'fulfillment_status' => 'failed',
            'failed_at' => now(),
        ], $overrides));
    }

    private function fakeProvider(FulfillmentStatus $verifyStatus): void
    {
        $provider = new class($verifyStatus) implements FulfillmentProviderInterface
        {
            public function __construct(private FulfillmentStatus $verifyStatus) {}

            public function fulfill(OrderItem $item): array
            {
                return ['status' => FulfillmentStatus::Fulfilled, 'reference' => 'X', 'payload' => []];
            }

            public function verifyStatus(OrderItem $item): array
            {
                return ['status' => $this->verifyStatus, 'payload' => []];
            }

            public function refund(OrderItem $item): bool
            {
                return true;
            }

            public function normalizeResponse(array $rawPayload): array
            {
                return $rawPayload;
            }
        };

        $factory = $this->createStub(FulfillmentProviderFactory::class);
        $factory->method('getProvider')->willReturn($provider);
        $this->app->instance(FulfillmentProviderFactory::class, $factory);
    }

    public function test_retry_of_terminally_failed_item_bumps_sequence_and_redispatches(): void
    {
        Queue::fake();
        $this->fakeProvider(FulfillmentStatus::Failed);

        $item = $this->makeFailedItem(['fulfillment_reference' => 'RSR-BURNEDID']);

        $this->artisan('fulfillment:retry', ['--all-failed' => true])
            ->expectsOutputToContain('re-dispatched as attempt 1')
            ->assertExitCode(0);

        Queue::assertPushed(FulfillOrderItemJob::class, 1);
        Queue::assertNotPushed(PollPendingFulfillmentJob::class);

        $this->assertSame(1, (int) data_get($item->fresh()->metadata, 'fulfillment_retry'));
    }

    public function test_retry_sequence_increments_on_every_manual_retry(): void
    {
        Queue::fake();
        $this->fakeProvider(FulfillmentStatus::Failed);

        $item = $this->makeFailedItem([
            'fulfillment_reference' => 'RSR-BURNEDID',
            'metadata' => ['fulfillment_retry' => 1],
        ]);

        $this->artisan('fulfillment:retry', ['item' => $item->id])->assertExitCode(0);

        $this->assertSame(2, (int) data_get($item->fresh()->metadata, 'fulfillment_retry'));
    }

    public function test_alive_provider_transaction_is_polled_not_rebought(): void
    {
        Queue::fake();
        $this->fakeProvider(FulfillmentStatus::Processing);

        $item = $this->makeFailedItem(['fulfillment_reference' => 'RSR-STILLALIVE']);

        $this->artisan('fulfillment:retry', ['item' => $item->id])
            ->expectsOutputToContain('polling it instead of re-buying')
            ->assertExitCode(0);

        Queue::assertPushed(PollPendingFulfillmentJob::class, 1);
        Queue::assertNotPushed(FulfillOrderItemJob::class);

        $fresh = $item->fresh();
        $this->assertSame(FulfillmentStatus::Processing, $fresh->fulfillment_status);
        $this->assertNull(data_get($fresh->metadata, 'fulfillment_retry'));
    }

    public function test_item_without_real_reference_is_redispatched_without_verification(): void
    {
        Queue::fake();
        $this->fakeProvider(FulfillmentStatus::Processing); // would poll IF verified - must not be consulted

        $item = $this->makeFailedItem(['fulfillment_reference' => 'PENDING-abc123']);

        $this->artisan('fulfillment:retry', ['item' => $item->id])->assertExitCode(0);

        Queue::assertPushed(FulfillOrderItemJob::class, 1);
        Queue::assertNotPushed(PollPendingFulfillmentJob::class);
        $this->assertSame(1, (int) data_get($item->fresh()->metadata, 'fulfillment_retry'));
    }

    public function test_zendit_payload_carries_suffixed_transaction_id_on_retry(): void
    {
        config(['services.zendit.api_key' => 'real-key-not-mock', 'services.zendit.base_url' => 'https://api.zendit.test/v1']);

        Http::fake(['api.zendit.test/*' => Http::response(['transactionId' => 'ZND-1', 'status' => 'ACCEPTED'], 200)]);

        $item = $this->makeFailedItem(['metadata' => ['fulfillment_retry' => 1]]);

        (new ZenditFulfillmentProvider)->fulfill($item);

        $expectedId = 'RSR-'.str_replace('-', '', (string) $item->id).'-R1';
        Http::assertSent(fn ($request) => $request['transactionId'] === $expectedId);
    }

    public function test_zendit_payload_keeps_original_transaction_id_on_first_attempt(): void
    {
        config(['services.zendit.api_key' => 'real-key-not-mock', 'services.zendit.base_url' => 'https://api.zendit.test/v1']);

        Http::fake(['api.zendit.test/*' => Http::response(['transactionId' => 'ZND-1', 'status' => 'ACCEPTED'], 200)]);

        $item = $this->makeFailedItem();

        (new ZenditFulfillmentProvider)->fulfill($item);

        $expectedId = 'RSR-'.str_replace('-', '', (string) $item->id);
        Http::assertSent(fn ($request) => $request['transactionId'] === $expectedId);
    }
}
