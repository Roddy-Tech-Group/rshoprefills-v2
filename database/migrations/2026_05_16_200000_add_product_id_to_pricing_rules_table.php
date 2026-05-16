<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
     * Adds a per-product markup override to pricing_rules. The markup resolution
     * is now a hybrid hierarchy: product > subcategory > category > global
     * (a rule with product_id, subcategory_id and category_id all null).
     *
     * cascadeOnDelete: a per-product override is meaningless without its product,
     * and nulling product_id would silently turn the row into a global rule.
     */
    public function up(): void
    {
        Schema::table('pricing_rules', function (Blueprint $table) {
            $table->foreignId('product_id')->nullable()->after('subcategory_id')->constrained()->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pricing_rules', function (Blueprint $table) {
            $table->dropConstrainedForeignId('product_id');
        });
    }
};
