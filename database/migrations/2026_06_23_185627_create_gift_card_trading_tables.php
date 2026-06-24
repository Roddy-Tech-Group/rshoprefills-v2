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
        // 1. Gift Card Brands
        Schema::create('gift_card_brands', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('currency', 3)->default('USD');
            $table->boolean('is_active')->default(true);
            $table->string('icon_url')->nullable();
            $table->text('guidelines')->nullable();
            $table->timestamps();
        });

        // 2. Gift Card Rates
        Schema::create('gift_card_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained('gift_card_brands')->cascadeOnDelete();
            $table->string('country_code', 2)->default('US');
            $table->string('currency', 5)->default('NGN'); // Payout Currency
            $table->decimal('min_value', 12, 2)->default(0);
            $table->decimal('max_value', 12, 2)->nullable();
            $table->decimal('rate', 12, 2); // Direct exchange rate multiplier
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 3. Bank Accounts
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('bank_name');
            $table->string('bank_code')->nullable(); // Required for Flutterwave transfers
            $table->string('account_number');
            $table->string('account_name');
            $table->boolean('is_verified')->default(false);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        // 4. Gift Card Trades
        Schema::create('gift_card_trades', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('rate_id')->constrained('gift_card_rates');
            $table->string('payout_method', 20)->default('wallet'); // wallet, bank
            $table->foreignId('bank_account_id')->nullable()->constrained('bank_accounts')->nullOnDelete();
            $table->decimal('declared_value', 12, 2);
            $table->decimal('calculated_payout', 12, 2);
            $table->string('payout_currency', 5);
            $table->string('code_pin', 255)->nullable(); // Optional PIN/Code for E-codes
            $table->string('status', 30)->default('pending_review'); // pending_review, under_review, need_more_info, approved, paying_out, paid, rejected
            $table->text('admin_notes')->nullable(); // Internal notes
            $table->string('rejection_reason', 255)->nullable(); // Shown to user
            $table->foreignId('reviewed_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });

        // 5. Trade Media (Images)
        Schema::create('trade_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trade_id')->constrained('gift_card_trades')->cascadeOnDelete();
            $table->string('file_path');
            $table->string('file_hash', 64)->nullable()->index(); // SHA-256 for duplicate detection
            $table->string('type', 20)->default('front'); // front, back, receipt
            $table->timestamps();
        });

        // 6. Trade Messages (Chat for 'Need More Info')
        Schema::create('trade_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trade_id')->constrained('gift_card_trades')->cascadeOnDelete();
            $table->morphs('sender'); // Can be User or Admin
            $table->text('message')->nullable();
            $table->string('attachment_path')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        // 7. Trade Audit Logs
        Schema::create('trade_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trade_id')->constrained('gift_card_trades')->cascadeOnDelete();
            $table->morphs('actor'); // User, Admin, or System
            $table->string('action'); // e.g. status_changed, message_sent
            $table->string('previous_status', 30)->nullable();
            $table->string('new_status', 30)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        // 8. Payouts (Bank or Wallet tracking)
        Schema::create('payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trade_id')->constrained('gift_card_trades')->cascadeOnDelete();
            $table->string('reference')->unique(); // Internal Wallet Ref or Flutterwave TxRef
            $table->decimal('amount', 12, 2);
            $table->string('status', 30)->default('pending'); // pending, successful, failed
            $table->json('gateway_response')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payouts');
        Schema::dropIfExists('trade_audit_logs');
        Schema::dropIfExists('trade_messages');
        Schema::dropIfExists('trade_media');
        Schema::dropIfExists('gift_card_trades');
        Schema::dropIfExists('bank_accounts');
        Schema::dropIfExists('gift_card_rates');
        Schema::dropIfExists('gift_card_brands');
    }
};
