<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();

            // Per-variant scope only (one coupon, one SKU). Cascade so deleting
            // a variant cleans up its coupons — they're meaningless without it.
            $table->foreignId('product_variant_id')
                ->constrained('product_variants')
                ->cascadeOnDelete();

            // Human-typed redemption code. Stored uppercase, unique across the
            // whole table (no scoping by variant — customer types one code).
            $table->string('code', 64)->unique();

            // 'percent' (0-100) or 'fixed' (USD amount off the sales price).
            $table->string('discount_type', 16);
            $table->decimal('discount_value', 12, 4);

            // Optional cap on redemptions. NULL = unlimited.
            $table->unsignedInteger('max_uses')->nullable();
            $table->unsignedInteger('used_count')->default(0);

            // Auto-expiry. NULL valid_until = never expires.
            $table->timestamp('valid_from')->nullable();
            $table->timestamp('valid_until')->nullable();

            $table->boolean('is_active')->default(true);

            // Audit trail — who created it (admin user id is a string-ish ref;
            // keep loose so we don't have to FK-link the admin guard table).
            $table->string('created_by', 64)->nullable();

            $table->timestamps();

            // Fast lookup for "all active coupons for this variant" used by the
            // drawer + checkout redemption path.
            $table->index(['product_variant_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
