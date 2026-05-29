<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Orders use soft deletes — we never truly delete order records for
     * audit/compliance. The order_number is a human-readable unique reference
     * (e.g., RSR-20260513-A1B2) generated in application code.
     *
     * subtotal, tax, and total are stored separately so we can reconstruct
     * pricing without recalculating from items. completed_at records when
     * all fulfillment finished, distinct from updated_at.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('order_number')->unique();
            $table->string('status', 20)->default('pending');
            $table->decimal('subtotal', 12, 4)->default(0);
            $table->decimal('tax', 12, 4)->default(0);
            $table->decimal('total', 12, 4)->default(0);
            $table->string('currency', 10)->default('USD');
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('user_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
