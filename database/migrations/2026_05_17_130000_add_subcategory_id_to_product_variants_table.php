<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A Product carries a single subcategory, but its variants (individual
     * provider offers) can each belong to a different subtype — e.g. one mobile
     * operator selling airtime, data and bundle offers. Storing the subcategory
     * per variant lets the storefront filter listings precisely instead of by
     * the product's one representative subcategory.
     */
    public function up(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->foreignId('subcategory_id')
                ->nullable()
                ->after('product_id')
                ->constrained()
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropConstrainedForeignId('subcategory_id');
        });
    }
};
