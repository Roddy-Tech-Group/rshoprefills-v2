<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Every wallet movement is recorded as a transaction with a full audit trail:
     * balance_before and balance_after capture the wallet state at the exact
     * moment of the transaction, making reconciliation and debugging possible.
     *
     * user_id is denormalized (could be derived via wallet) for fast query
     * performance — "show me all transactions for user X" without a join.
     *
     * reference is nullable and unique — used for linking to gateway transaction
     * IDs or internal operation references.
     */
    public function up(): void
    {
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 10); // credit or debit (backed enum)
            $table->decimal('amount', 16, 4);
            $table->decimal('balance_before', 16, 4);
            $table->decimal('balance_after', 16, 4);
            $table->string('description')->nullable();
            $table->string('reference')->nullable()->unique();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('wallet_id');
            $table->index('user_id');
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
    }
};
