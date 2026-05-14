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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subcategory_id')->nullable()->constrained()->nullOnDelete();

            // Provider mapping
            $table->string('provider_name')->index();
            $table->string('provider_reference')->nullable()->index();

            // Geographic & financial
            $table->string('country_code', 2)->index();
            $table->string('currency_code', 10);

            // Core
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->text('redeem_instructions')->nullable();
            $table->text('terms_and_conditions')->nullable();

            // Media
            $table->string('logo_url')->nullable();
            $table->string('featured_image')->nullable();

            // Merchandising
            $table->boolean('is_featured')->default(false)->index();
            $table->boolean('is_popular')->default(false)->index();
            $table->boolean('is_active')->default(true)->index();

            // Extensibility
            $table->json('metadata')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
