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
        Schema::table('withdrawal_requests', function (Blueprint $table) {
            $table->string('rejection_reason')->nullable()->after('status');
            $table->string('utr_number')->nullable()->after('admin_notes');
            $table->string('razorpay_fund_account_id')->nullable()->after('utr_number');
            $table->string('razorpay_payout_id')->nullable()->after('razorpay_fund_account_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('withdrawal_requests', function (Blueprint $table) {
            $table->dropColumn(['rejection_reason', 'utr_number', 'razorpay_fund_account_id', 'razorpay_payout_id']);
        });
    }
};
