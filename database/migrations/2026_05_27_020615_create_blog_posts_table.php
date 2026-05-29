<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blog_posts', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('category');
            $table->string('title');
            $table->string('excerpt', 500);
            $table->string('image');
            // `body` holds an array of paragraphs (one block per element). JSON
            // keeps the editor flexible: today plain prose, tomorrow rich blocks
            // (callouts, code, images) without another schema change.
            $table->json('body');
            $table->string('author')->default('RshopRefills Team');
            $table->string('read_time', 30)->nullable();
            $table->date('published_at')->index();
            $table->boolean('is_published')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blog_posts');
    }
};
