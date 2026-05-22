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
        Schema::table('users', function (Blueprint $table) {
            $table->string('transaction_pin')->nullable()->after('password');
            $table->timestamp('transaction_pin_set_at')->nullable()->after('transaction_pin');
            $table->unsignedTinyInteger('transaction_pin_attempts')->default(0)->after('transaction_pin_set_at');
            $table->timestamp('transaction_pin_locked_until')->nullable()->after('transaction_pin_attempts');
            $table->timestamp('last_transaction_pin_used_at')->nullable()->after('transaction_pin_locked_until');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'transaction_pin',
                'transaction_pin_set_at',
                'transaction_pin_attempts',
                'transaction_pin_locked_until',
                'last_transaction_pin_used_at'
            ]);
        });
    }
};
