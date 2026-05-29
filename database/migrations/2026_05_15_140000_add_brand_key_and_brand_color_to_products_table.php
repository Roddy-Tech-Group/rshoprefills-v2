<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Zendit's machine-readable brand identifier (e.g. "xbox-live", "amazon-com").
            // Used to call /v1/brands/{brand} and /v1/brands/{brand}/redemptionInstructions.
            // Indexed so cross-country brand groupings ("all Xbox cards globally") stay cheap.
            $table->string('brand_key')->nullable()->index()->after('provider_reference');

            // Brand accent colour from Zendit's brand API (e.g. "#107C10").
            // Used to theme the detail page hero + denomination picker per brand.
            $table->string('brand_color', 9)->nullable()->after('featured_image');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['brand_key', 'brand_color']);
        });
    }
};
