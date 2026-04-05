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
            $table->json('last_admin_change_snapshot')->nullable()->after('start_pin_verified_at');
            $table->timestamp('last_admin_changed_at')->nullable()->after('last_admin_change_snapshot');
            $table->unsignedBigInteger('last_admin_changed_by')->nullable()->after('last_admin_changed_at');
            $table->index('last_admin_changed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ride_bookings', function (Blueprint $table) {
            $table->dropIndex(['last_admin_changed_at']);
            $table->dropColumn([
                'last_admin_change_snapshot',
                'last_admin_changed_at',
                'last_admin_changed_by',
            ]);
        });
    }
};
