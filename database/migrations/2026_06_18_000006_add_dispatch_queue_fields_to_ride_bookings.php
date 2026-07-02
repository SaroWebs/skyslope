<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ride_bookings', function (Blueprint $table) {
            if (!Schema::hasColumn('ride_bookings', 'dispatch_status')) {
                $table->enum('dispatch_status', ['pending', 'offered', 'admin_queue', 'assigned', 'expired'])
                    ->default('pending')
                    ->after('status');
            }

            if (!Schema::hasColumn('ride_bookings', 'admin_assignable')) {
                $table->boolean('admin_assignable')->default(false)->after('dispatch_status');
            }

            if (!Schema::hasColumn('ride_bookings', 'dispatch_failed_at')) {
                $table->timestamp('dispatch_failed_at')->nullable()->after('admin_assignable');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ride_bookings', function (Blueprint $table) {
            foreach (['dispatch_failed_at', 'admin_assignable', 'dispatch_status'] as $column) {
                if (Schema::hasColumn('ride_bookings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
