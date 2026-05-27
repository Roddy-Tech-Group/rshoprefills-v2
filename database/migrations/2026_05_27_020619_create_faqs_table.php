<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('faqs', function (Blueprint $table) {
            $table->id();
            // Free-text topic — kept as a string rather than a foreign key so
            // editors can rename / introduce a new topic without a schema bump.
            // Common values today: Orders & Delivery, Payments & Wallet, etc.
            $table->string('topic')->index();
            $table->string('question');
            $table->text('answer');
            $table->boolean('is_published')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('faqs');
    }
};
