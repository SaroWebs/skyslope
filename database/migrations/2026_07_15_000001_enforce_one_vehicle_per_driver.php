<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $duplicateDriver = DB::table('vehicles')
            ->select('driver_id')
            ->whereNotNull('driver_id')
            ->groupBy('driver_id')
            ->havingRaw('COUNT(*) > 1')
            ->first();

        if ($duplicateDriver) {
            throw new RuntimeException(
                "Driver {$duplicateDriver->driver_id} has multiple vehicles. Unassign the extras before running this migration."
            );
        }

        Schema::table('vehicles', function (Blueprint $table) {
            $table->string('approval_status', 20)->default('approved')->after('is_active');
            $table->timestamp('reviewed_at')->nullable()->after('approval_status');
            $table->foreignId('reviewed_by')->nullable()->after('reviewed_at')->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable()->after('reviewed_by');
            $table->unique('driver_id', 'vehicles_driver_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropUnique('vehicles_driver_id_unique');
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropColumn(['approval_status', 'reviewed_at', 'rejection_reason']);
        });
    }
};
