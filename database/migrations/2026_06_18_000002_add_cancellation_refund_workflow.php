<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['ride_bookings', 'tour_bookings', 'car_rentals'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if ($tableName === 'car_rentals' && !Schema::hasColumn($tableName, 'cancelled_at')) {
                    $table->timestamp('cancelled_at')->nullable()->after('cancellation_reason');
                }

                if (!Schema::hasColumn($tableName, 'cancellation_fee')) {
                    $table->decimal('cancellation_fee', 10, 2)->default(0)->after('cancellation_reason');
                }

                if (!Schema::hasColumn($tableName, 'refund_amount')) {
                    $table->decimal('refund_amount', 10, 2)->default(0)->after('cancellation_fee');
                }

                if (!Schema::hasColumn($tableName, 'refunded_at')) {
                    $table->timestamp('refunded_at')->nullable()->after('refund_amount');
                }
            });
        }

        if (!Schema::hasTable('booking_refunds')) {
            Schema::create('booking_refunds', function (Blueprint $table) {
                $table->id();
                $table->string('refundable_type');
                $table->unsignedBigInteger('refundable_id');
                $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
                $table->foreignId('wallet_transaction_id')->nullable()->constrained('wallet_transactions')->nullOnDelete();
                $table->decimal('amount', 10, 2)->default(0);
                $table->decimal('cancellation_fee', 10, 2)->default(0);
                $table->string('method')->default('wallet');
                $table->enum('status', ['pending', 'processed', 'failed'])->default('pending');
                $table->text('reason')->nullable();
                $table->timestamp('processed_at')->nullable();
                $table->unsignedBigInteger('processed_by')->nullable();
                $table->timestamps();

                $table->index(['refundable_type', 'refundable_id']);
                $table->index(['customer_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_refunds');

        foreach (['ride_bookings', 'tour_bookings', 'car_rentals'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                foreach (['refunded_at', 'refund_amount', 'cancellation_fee'] as $column) {
                    if (Schema::hasColumn($tableName, $column)) {
                        $table->dropColumn($column);
                    }
                }

                if ($tableName === 'car_rentals' && Schema::hasColumn($tableName, 'cancelled_at')) {
                    $table->dropColumn('cancelled_at');
                }
            });
        }
    }
};
