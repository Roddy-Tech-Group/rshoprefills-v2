<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Promote `rating` from tinyint unsigned (whole stars only) to
     * decimal(2,1) so the storefront can render half-stars (3.5, 4.5, etc.).
     * Existing integer values cast cleanly to the new type.
     */
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->decimal('rating', 2, 1)->default(5.0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->unsignedTinyInteger('rating')->default(5)->change();
        });
    }
};
