<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Customer-submitted reviews. A shopper can leave a review after a completed
 * order; it lands unpublished + flagged as a customer submission so the admin
 * approves it before it appears on the storefront. `user_id` links it back to
 * the account that wrote it (and stops one account spamming many reviews).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->boolean('is_customer_submitted')->default(false)->after('is_published')->index();
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
            $table->dropColumn('is_customer_submitted');
        });
    }
};
