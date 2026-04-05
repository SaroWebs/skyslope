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
        Schema::create('withdrawal_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_number')->unique();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('wallet_id')->constrained()->onDelete('cascade');
            
            // Amount details
            $table->decimal('amount', 12, 2);
            $table->decimal('processing_fee', 8, 2)->default(0);
            $table->decimal('net_amount', 12, 2);
            
            // Bank details
            $table->string('bank_account_name');
            $table->string('bank_account_number');
            $table->string('bank_ifsc');
            $table->string('bank_name')->nullable();
            
            // Razorpay payout details
            $table->string('razorpay_fund_account_id')->nullable();
            $table->string('razorpay_payout_id')->nullable();
            $table->string('razorpay_batch_id')->nullable();
            
            // Status tracking
            $table->enum('status', [
                'pending',
                'processing',
                'approved',
                'rejected',
                'completed',
                'failed'
            ])->default('pending');
            
            // Admin review
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('reviewed_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->text('admin_notes')->nullable();
            
            // Processing details
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('failure_reason')->nullable();
            
            // UTR number for bank transfer
            $table->string('utr_number')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['user_id', 'status']);
            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('withdrawal_requests');
    }
};
