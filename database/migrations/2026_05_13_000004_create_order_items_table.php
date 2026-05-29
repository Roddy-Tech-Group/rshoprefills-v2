<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Order items represent individual products within an order. product_type
     * and product_id form a pseudo-polymorphic reference — product_type is a
     * string like 'gift_card' or 'esim', and product_id is the Zendit product
     * ID or internal catalog ID.
     *
     * product_name is snapshotted at purchase time so it remains accurate
     * even if the catalog product is renamed or removed later.
     *
     * fulfillment_data stores the Zendit API response (codes, PINs, voucher
     * numbers) after successful delivery. fulfillment_status tracks each
     * item independently — one item can succeed while another retries.
     */
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('product_type', 50);
            $table->string('product_id')->nullable();
            $table->string('product_name');
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('unit_price', 12, 4);
            $table->decimal('total_price', 12, 4);
            $table->string('currency', 10)->default('USD');
            $table->string('fulfillment_status', 20)->default('pending');
            $table->json('fulfillment_data')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('order_id');
            $table->index('product_type');
            $table->index('fulfillment_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
