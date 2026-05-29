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
        Schema::create('cart_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('cart_id')->constrained()->cascadeOnDelete();

            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->constrained()->cascadeOnDelete();
            $table->integer('quantity')->default(1);

            // Display Layer
            $table->string('display_currency', 3);
            $table->decimal('display_amount', 12, 4);

            // Settlement Layer
            $table->decimal('provider_cost_usd', 12, 4);
            $table->decimal('exchange_rate_snapshot', 12, 4)->nullable();

            // Pricing Snapshots
            $table->decimal('markup_amount', 12, 4)->default(0);
            $table->decimal('unit_price_snapshot', 12, 4);
            $table->decimal('subtotal_snapshot', 12, 4);

            // Integrity
            $table->json('metadata_snapshot')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cart_items');
    }
};
