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
        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->string('currency', 10)->default('USD')->after('amount');
            $table->string('transaction_category', 50)->default('purchase')->after('currency');
            $table->string('transaction_group', 100)->nullable()->after('transaction_category');
            $table->string('idempotency_key', 100)->nullable()->unique()->after('transaction_group');
            $table->string('source_type')->nullable()->after('idempotency_key');
            $table->unsignedBigInteger('source_id')->nullable()->after('source_type');

            $table->index('transaction_category');
            $table->index(['source_type', 'source_id']);
        });
    }

    public function down(): void
    {
        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->dropUnique(['idempotency_key']);
            $table->dropIndex(['transaction_category']);
            $table->dropIndex(['source_type', 'source_id']);

            $table->dropColumn([
                'currency',
                'transaction_category',
                'transaction_group',
                'idempotency_key',
                'source_type',
                'source_id',
            ]);
        });
    }
};
