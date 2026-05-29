<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rcoin_withdrawals', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // What was requested.
            $table->unsignedInteger('rcoin_amount');     // Rcoin debited from the wallet
            $table->decimal('usd_value', 12, 4);         // rcoin × withdrawal_conversion_rate at request time
            $table->decimal('fee_usd', 12, 4)->default(0); // withdrawal_fee_percentage × usd_value
            $table->decimal('payout_usd', 12, 4);        // usd_value − fee_usd (what we actually owe)

            // How the customer wants to be paid.
            $table->string('method', 30); // 'wallet' | 'bank' | 'mobile_money'
            $table->json('payout_details')->nullable(); // account number, network, phone, etc.

            // Lifecycle. Snapshot the conversion rate so the admin sees exactly
            // what the customer saw when they submitted, even if the rate
            // drifts before payout.
            $table->decimal('rate_snapshot', 12, 8);
            $table->string('status', 30)->default('pending'); // 'pending' | 'approved' | 'paid' | 'rejected' | 'cancelled'
            $table->text('reject_reason')->nullable();

            // Audit trail.
            $table->string('reviewed_by', 64)->nullable(); // admin id who actioned
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->string('payout_reference', 120)->nullable(); // gateway / bank ref

            // Wallet ledger pointer - the WalletTransaction row that debited
            // the user's Rcoin balance at submit time. Lets us reverse the
            // debit cleanly if the request is rejected.
            $table->foreignId('debit_transaction_id')->nullable()->constrained('wallet_transactions')->nullOnDelete();

            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rcoin_withdrawals');
    }
};
