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
        Schema::table('ride_bookings', function (Blueprint $table) {
            $table->string('start_ride_pin', 4)->nullable()->after('last_location_update');
            $table->timestamp('start_pin_verified_at')->nullable()->after('start_ride_pin');
            $table->index(['start_ride_pin', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ride_bookings', function (Blueprint $table) {
            $table->dropIndex(['start_ride_pin', 'status']);
            $table->dropColumn(['start_ride_pin', 'start_pin_verified_at']);
        });
    }
};
