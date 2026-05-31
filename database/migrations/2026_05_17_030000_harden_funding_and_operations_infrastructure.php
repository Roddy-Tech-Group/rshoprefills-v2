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
        // 1. Create exchange_rates table
        Schema::create('exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->string('base_currency', 10);
            $table->string('target_currency', 10);
            $table->decimal('rate', 24, 8);
            $table->string('provider', 50);
            $table->string('source', 50);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamp('fetched_at')->useCurrent();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->unique(['base_currency', 'target_currency', 'provider']);
        });

        // 2. Modify payment_attempts to be polymorphic and nullable order_id
        Schema::table('payment_attempts', function (Blueprint $table) {
            $table->uuid('order_id')->nullable()->change();
            $table->string('payable_type')->nullable()->after('user_id');
            $table->string('payable_id', 50)->nullable()->after('payable_type');

            $table->index(['payable_type', 'payable_id']);
        });

        // 3. Modify wallet_fundings table
        Schema::table('wallet_fundings', function (Blueprint $table) {
            $table->string('display_currency', 10)->nullable()->after('currency');
            $table->decimal('requested_amount', 16, 4)->nullable()->after('amount');
            $table->decimal('settled_amount_usd', 16, 4)->nullable()->after('requested_amount');
            $table->decimal('exchange_rate_snapshot', 16, 4)->default(1.0000)->after('settled_amount_usd');
            $table->text('payment_link')->nullable()->after('gateway_payload');
            $table->json('provider_payload_snapshot')->nullable()->after('payment_link');
            $table->json('verification_payload_snapshot')->nullable()->after('provider_payload_snapshot');
            $table->timestamp('verified_at')->nullable()->after('verification_payload_snapshot');
            $table->timestamp('completed_at')->nullable()->after('verified_at');
            $table->json('metadata')->nullable()->after('completed_at');
        });

        // 4. Modify payment_webhooks table
        Schema::table('payment_webhooks', function (Blueprint $table) {
            $table->json('headers')->nullable()->after('payload');
            $table->string('processing_status', 30)->default('pending')->after('processed');
            $table->unsignedInteger('processing_attempts')->default(0)->after('processing_status');
            $table->text('exception_traces')->nullable()->after('processing_attempts');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_webhooks', function (Blueprint $table) {
            $table->dropColumn(['headers', 'processing_status', 'processing_attempts', 'exception_traces']);
        });

        Schema::table('wallet_fundings', function (Blueprint $table) {
            $table->dropColumn([
                'display_currency',
                'requested_amount',
                'settled_amount_usd',
                'exchange_rate_snapshot',
                'payment_link',
                'provider_payload_snapshot',
                'verification_payload_snapshot',
                'verified_at',
                'completed_at',
                'metadata',
            ]);
        });

        Schema::table('payment_attempts', function (Blueprint $table) {
            $table->dropIndex(['payable_type', 'payable_id']);
            $table->dropColumn(['payable_type', 'payable_id']);
            $table->uuid('order_id')->nullable(false)->change();
        });

        Schema::dropIfExists('exchange_rates');
    }
};
