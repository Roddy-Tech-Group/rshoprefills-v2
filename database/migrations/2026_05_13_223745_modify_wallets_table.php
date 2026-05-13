<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('wallets', function (Blueprint $table) {
            // Drop the foreign key first to allow dropping the unique index
            $table->dropForeign(['user_id']);
            $table->dropUnique(['user_id']);

            // Re-add the foreign key
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();

            // Add new fintech fields
            $table->decimal('locked_balance', 16, 4)->default(0)->after('balance');
            $table->timestamp('last_activity_at')->nullable()->after('is_active');

            // Add the new composite unique constraint (one wallet per currency per user)
            $table->unique(['user_id', 'currency']);
        });
    }

    public function down(): void
    {
        Schema::table('wallets', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'currency']);
            $table->dropColumn(['locked_balance', 'last_activity_at']);
            $table->unique('user_id');
        });
    }
};
