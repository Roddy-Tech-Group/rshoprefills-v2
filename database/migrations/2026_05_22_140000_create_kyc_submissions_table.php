<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Stores a customer's identity-verification submission and its review state.
     * Document paths point at the private `local` disk (storage/app), never public.
     */
    public function up(): void
    {
        Schema::create('kyc_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('full_name');
            $table->date('date_of_birth')->nullable();
            $table->string('country')->nullable();
            $table->string('document_type');                 // passport | national_id | drivers_license
            $table->string('document_number')->nullable();
            $table->string('document_front_path');           // private disk path
            $table->string('document_back_path')->nullable();
            $table->string('selfie_path')->nullable();
            $table->string('status', 20)->default('pending'); // pending | approved | rejected
            $table->text('rejection_reason')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });

        Schema::table('users', function (Blueprint $table) {
            // unsubmitted | pending | verified | rejected
            $table->string('kyc_status', 20)->default('unsubmitted')->after('theme');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('kyc_status');
        });

        Schema::dropIfExists('kyc_submissions');
    }
};
