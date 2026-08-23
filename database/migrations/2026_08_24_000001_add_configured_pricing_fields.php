<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ride_bookings', function (Blueprint $table) {
            $table->decimal('tax_amount', 12, 2)->nullable()->after('discount_amount');
            $table->decimal('service_fee_amount', 12, 2)->nullable()->after('tax_amount');
        });
        Schema::table('tour_bookings', function (Blueprint $table) {
            $table->decimal('tax_amount', 12, 2)->nullable()->after('discount_amount');
            $table->decimal('service_fee_amount', 12, 2)->nullable()->after('tax_amount');
        });
        Schema::table('car_rentals', function (Blueprint $table) {
            $table->decimal('tax_amount', 12, 2)->nullable()->after('discount_amount');
            $table->decimal('service_fee_amount', 12, 2)->nullable()->after('tax_amount');
            $table->decimal('security_deposit', 12, 2)->nullable()->after('service_fee_amount');
        });
    }

    public function down(): void
    {
        Schema::table('ride_bookings', fn (Blueprint $table) => $table->dropColumn(['tax_amount', 'service_fee_amount']));
        Schema::table('tour_bookings', fn (Blueprint $table) => $table->dropColumn(['tax_amount', 'service_fee_amount']));
        Schema::table('car_rentals', fn (Blueprint $table) => $table->dropColumn(['tax_amount', 'service_fee_amount', 'security_deposit']));
    }
};