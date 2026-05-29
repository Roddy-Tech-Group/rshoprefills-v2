<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->string('initials', 4);
            $table->string('author_name');
            // Review body + per-review rating (1-5). The aggregate "4.4 / 5" is
            // stored separately on settings, not derived from this column —
            // that lets the CMS pin the public score independent of new entries.
            $table->text('body');
            $table->unsignedTinyInteger('rating')->default(5);
            // Source string ("Trustpilot", "Google", ...) — small bag of strings,
            // not an enum yet because the marketing team adds new sources ad-hoc.
            $table->string('source', 40)->default('Trustpilot');
            $table->date('reviewed_at')->index();
            $table->boolean('is_published')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
