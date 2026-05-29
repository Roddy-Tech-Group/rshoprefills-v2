<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Suspension is a softer state than ban:
     *   - Banned   → forced logout, blocked from signing in
     *   - Suspended → can log in + view dashboard, blocked from purchases /
     *                 wallet funding / cart writes. Can request a review,
     *                 which surfaces in the admin notification feed.
     *
     * `suspension_reason` is admin-authored copy shown to the customer in the
     * banner. `suspension_review_requested_at` is non-null after the customer
     * clicks "Request review" — admin clears it when they re-evaluate the
     * account (un-suspending automatically clears it via the controller).
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('suspended_at')->nullable()->after('banned_at');
            $table->text('suspension_reason')->nullable()->after('suspended_at');
            $table->timestamp('suspension_review_requested_at')->nullable()->after('suspension_reason');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'suspended_at',
                'suspension_reason',
                'suspension_review_requested_at',
            ]);
        });
    }
};
