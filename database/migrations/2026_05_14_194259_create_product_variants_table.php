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
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();

            $table->string('provider_offer_id')->unique();
            $table->string('sku')->nullable()->index();

            $table->string('currency', 10);
            $table->decimal('face_value', 16, 4)->nullable();
            $table->decimal('cost_price', 16, 4);
            $table->decimal('retail_price', 16, 4);

            $table->decimal('min_amount', 16, 4)->nullable();
            $table->decimal('max_amount', 16, 4)->nullable();
            $table->boolean('is_variable')->default(false);

            $table->boolean('is_available')->default(true)->index();

            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
