<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop legacy tables first
        Schema::dropIfExists('payments');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');

        // Create [NEW] orders table
        Schema::create('orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('order_number')->unique();
            $table->uuid('cart_id')->nullable();
            $table->string('settlement_currency', 10)->default('USD');
            $table->string('display_currency', 10)->default('USD');
            $table->decimal('subtotal_amount', 16, 4)->default(0);
            $table->decimal('markup_amount', 16, 4)->default(0);
            $table->decimal('total_amount', 16, 4)->default(0);
            $table->string('payment_method', 20); // wallet, flutterwave, crypto
            $table->string('payment_status', 20)->default('unpaid');
            $table->string('fulfillment_status', 20)->default('not_started');
            $table->string('order_status', 20)->default('pending');
            $table->string('provider_status', 50)->nullable();
            $table->string('provider_reference')->nullable();
            $table->timestamp('placed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('payment_status');
            $table->index('fulfillment_status');
            $table->index('order_status');
        });

        // Create [NEW] order_items table
        Schema::create('order_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('order_id');
            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subcategory_id')->constrained()->cascadeOnDelete();
            $table->string('provider_name', 50);
            $table->string('provider_offer_id')->nullable();
            $table->json('product_snapshot')->nullable();
            $table->json('variant_snapshot')->nullable();
            $table->unsignedInteger('quantity')->default(1);
            $table->string('display_currency', 10)->default('USD');
            $table->decimal('display_amount', 16, 4)->default(0);
            $table->decimal('provider_cost_usd', 16, 4)->default(0);
            $table->decimal('markup_amount', 16, 4)->default(0);
            $table->decimal('subtotal_amount', 16, 4)->default(0);
            $table->string('fulfillment_status', 20)->default('not_started');
            $table->string('fulfillment_reference')->nullable();
            $table->json('fulfillment_payload')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('order_id');
            $table->index('fulfillment_status');
            $table->index('provider_offer_id');
        });

        // Create [NEW] payment_attempts table
        Schema::create('payment_attempts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('order_id');
            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('gateway', 20); // wallet, flutterwave, nowpayments
            $table->string('gateway_reference')->unique()->nullable();
            $table->string('idempotency_key')->unique();
            $table->string('currency', 10)->default('USD');
            $table->decimal('amount', 16, 4)->default(0);
            $table->decimal('exchange_rate_snapshot', 16, 4)->default(1.0000);
            $table->string('payment_status', 20)->default('pending');
            $table->text('payment_url')->nullable();
            $table->json('verification_payload')->nullable();
            $table->json('webhook_payload')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('order_id');
            $table->index('user_id');
            $table->index('payment_status');
            $table->index('gateway');
        });

        // Create [NEW] fulfillment_logs table
        Schema::create('fulfillment_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('order_item_id');
            $table->foreign('order_item_id')->references('id')->on('order_items')->cascadeOnDelete();
            $table->string('provider_name', 50);
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->string('status', 20);
            $table->text('error_message')->nullable();
            $table->timestamp('processed_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fulfillment_logs');
        Schema::dropIfExists('payment_attempts');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
