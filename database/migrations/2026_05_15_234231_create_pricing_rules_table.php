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
        Schema::create('pricing_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('subcategory_id')->nullable()->constrained()->nullOnDelete();

            $table->string('markup_type')->default('percentage'); // 'percentage' or 'fixed'
            $table->decimal('markup_value', 8, 4)->default(0);

            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Allow multiple nulls by not enforcing uniqueness across the nullable fields
            // but in a real system we'd enforce one rule per category/subcategory
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pricing_rules');
    }
};
