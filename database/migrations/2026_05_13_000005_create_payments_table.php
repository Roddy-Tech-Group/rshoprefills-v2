<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Payments link an order to a specific gateway transaction. An order
     * can have multiple payments (e.g., a failed Flutterwave attempt
     * followed by a successful wallet payment).
     *
     * user_id is denormalized for fast "all payments by user" queries.
     * gateway_transaction_id stores the external reference from Flutterwave
     * or NowPayments. gateway_response stores the full webhook/callback
     * payload for debugging and compliance.
     *
     * paid_at is distinct from updated_at — it records the exact moment
     * the gateway confirmed the payment, not when we last touched the record.
     */
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('gateway', 20);
            $table->string('gateway_transaction_id')->nullable();
            $table->string('status', 20)->default('pending');
            $table->decimal('amount', 16, 4);
            $table->string('currency', 10)->default('USD');
            $table->json('gateway_response')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('order_id');
            $table->index('user_id');
            $table->index('gateway');
            $table->index('gateway_transaction_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
