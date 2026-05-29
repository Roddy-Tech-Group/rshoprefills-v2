<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Generic key/value bag for marketing copy + CMS-editable site-wide values
     * that don't deserve their own table (review aggregate, hero stats, footer
     * tagline, etc.). Schemaless on purpose — `value` is JSON so a setting can
     * hold a string, number, array, or object without another migration.
     */
    public function up(): void
    {
        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->json('value')->nullable();
            $table->string('group', 60)->default('general')->index();
            $table->string('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_settings');
    }
};
