<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tour_bookings', function (Blueprint $table) {
            if (!Schema::hasColumn('tour_bookings', 'commission_amount')) {
                $table->decimal('commission_amount', 10, 2)->default(0)->after('total_price');
            }

            if (!Schema::hasColumn('tour_bookings', 'driver_share')) {
                $table->decimal('driver_share', 10, 2)->default(0)->after('commission_amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tour_bookings', function (Blueprint $table) {
            foreach (['driver_share', 'commission_amount'] as $column) {
                if (Schema::hasColumn('tour_bookings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
