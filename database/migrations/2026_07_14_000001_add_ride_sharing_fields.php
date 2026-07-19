<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('driver_availabilities', function (Blueprint $table) {
            $table->boolean('sharing_enabled')->default(false)->after('is_available');
            $table->unsignedTinyInteger('sharing_seat_capacity')->default(3)->after('sharing_enabled');
            $table->index(['status', 'is_available', 'sharing_enabled'], 'driver_availability_sharing_idx');
        });

        Schema::table('ride_bookings', function (Blueprint $table) {
            $table->string('ride_mode', 20)->default('private')->after('service_type');
            $table->boolean('sharing_requested')->default(false)->after('ride_mode');
            $table->string('sharing_enabled_by', 20)->nullable()->after('sharing_requested');
            $table->unsignedTinyInteger('reserved_seats')->default(1)->after('sharing_enabled_by');
            $table->decimal('full_car_fare', 10, 2)->default(0)->after('surge_multiplier');
            $table->decimal('sharing_discount_percent', 5, 2)->default(0)->after('full_car_fare');
            $table->decimal('sharing_savings', 10, 2)->default(0)->after('sharing_discount_percent');
            $table->index(['service_type', 'ride_mode', 'scheduled_at'], 'ride_booking_sharing_idx');
        });
    }

    public function down(): void
    {
        Schema::table('ride_bookings', function (Blueprint $table) {
            $table->dropIndex('ride_booking_sharing_idx');
            $table->dropColumn([
                'ride_mode',
                'sharing_requested',
                'sharing_enabled_by',
                'reserved_seats',
                'full_car_fare',
                'sharing_discount_percent',
                'sharing_savings',
            ]);
        });

        Schema::table('driver_availabilities', function (Blueprint $table) {
            $table->dropIndex('driver_availability_sharing_idx');
            $table->dropColumn(['sharing_enabled', 'sharing_seat_capacity']);
        });
    }
};
