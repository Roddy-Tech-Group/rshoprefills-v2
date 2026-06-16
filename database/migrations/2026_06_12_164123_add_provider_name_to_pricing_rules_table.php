<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Provider scope for pricing rules. Suppliers price the same category very
     * differently (Airalo eSIMs carry ~50% built-in margin, Zendit eSIMs are
     * thin like gift cards), so a rule can now target "eSIMs + airalo"
     * separately from "eSIMs + zendit". NULL keeps the rule provider-agnostic;
     * a provider-scoped rule outranks the agnostic one at the same tier.
     */
    public function up(): void
    {
        Schema::table('pricing_rules', function (Blueprint $table) {
            $table->string('provider_name', 50)->nullable()->after('product_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('pricing_rules', function (Blueprint $table) {
            $table->dropColumn('provider_name');
        });
    }
};
