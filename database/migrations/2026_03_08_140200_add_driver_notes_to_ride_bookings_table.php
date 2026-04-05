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
            $table->text('driver_notes')->nullable()->after('special_requests');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ride_bookings', function (Blueprint $table) {
            $table->dropColumn('driver_notes');
        });
    }
};
