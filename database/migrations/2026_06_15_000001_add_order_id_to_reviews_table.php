<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Links a customer review back to the order it was left from. A review written
 * on an order page carries that order's id so its rating can roll up under each
 * gift card the customer bought (the per-brand rating shown on the product
 * page). A review left on the public reviews page has no order and stays a
 * general, non-product review. `orders.id` is a UUID, so the column matches.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->foreignUuid('order_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropConstrainedForeignId('order_id');
        });
    }
};
