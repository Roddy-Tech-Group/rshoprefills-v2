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
        Schema::create('financial_ledger_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('wallet_transaction_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event_type'); // 'deposit', 'withdraw', 'transfer', 'lock', 'unlock', etc.
            $table->decimal('amount', 19, 4);
            $table->decimal('balance_after', 19, 4);
            $table->string('currency', 3)->default('USD');
            $table->string('hash', 64)->unique(); // sha256 hash of previous hash + event data
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('event_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financial_ledger_events');
    }
};
