<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->indexIfColumnsExist('driver_availabilities', ['is_available', 'status', 'last_updated'], 'idx_da_available_status_updated');
        $this->indexIfColumnsExist('driver_availabilities', ['is_available', 'status', 'current_lat', 'current_lng'], 'idx_da_available_status_location');

        $this->indexIfColumnsExist('driver_locations', ['context', 'created_at'], 'idx_dl_context_created');
        $this->indexIfColumnsExist('driver_locations', ['driver_id', 'context', 'created_at'], 'idx_dl_driver_context_created');

        $this->indexIfColumnsExist('ride_dispatch_attempts', ['status', 'expires_at'], 'idx_rda_status_expires');
        $this->indexIfColumnsExist('ride_dispatch_attempts', ['driver_id', 'status', 'created_at'], 'idx_rda_driver_status_created');

        $this->indexIfColumnsExist('ride_bookings', ['dispatch_status', 'admin_assignable', 'scheduled_at'], 'idx_rb_dispatch_queue');
        $this->indexIfColumnsExist('ride_bookings', ['driver_id', 'status', 'scheduled_at'], 'idx_rb_driver_status_sched');

        $this->indexIfColumnsExist('car_rentals', ['driver_id', 'status', 'start_date'], 'idx_cr_driver_status_start');
        $this->indexIfColumnsExist('tour_bookings', ['assigned_driver_id', 'status', 'travel_date'], 'idx_tb_driver_status_travel');
        $this->indexIfColumnsExist('tour_driver_assignments', ['driver_id', 'status', 'created_at'], 'idx_tda_driver_status_created');
    }

    public function down(): void
    {
        $this->dropIndexIfTableExists('tour_driver_assignments', 'idx_tda_driver_status_created');
        $this->dropIndexIfTableExists('tour_bookings', 'idx_tb_driver_status_travel');
        $this->dropIndexIfTableExists('car_rentals', 'idx_cr_driver_status_start');
        $this->dropIndexIfTableExists('ride_bookings', 'idx_rb_driver_status_sched');
        $this->dropIndexIfTableExists('ride_bookings', 'idx_rb_dispatch_queue');
        $this->dropIndexIfTableExists('ride_dispatch_attempts', 'idx_rda_driver_status_created');
        $this->dropIndexIfTableExists('ride_dispatch_attempts', 'idx_rda_status_expires');
        $this->dropIndexIfTableExists('driver_locations', 'idx_dl_driver_context_created');
        $this->dropIndexIfTableExists('driver_locations', 'idx_dl_context_created');
        $this->dropIndexIfTableExists('driver_availabilities', 'idx_da_available_status_location');
        $this->dropIndexIfTableExists('driver_availabilities', 'idx_da_available_status_updated');
    }

    private function indexIfColumnsExist(string $tableName, array $columns, string $indexName): void
    {
        if (!Schema::hasTable($tableName)) {
            return;
        }

        foreach ($columns as $column) {
            if (!Schema::hasColumn($tableName, $column)) {
                return;
            }
        }

        Schema::table($tableName, function (Blueprint $table) use ($columns, $indexName) {
            $table->index($columns, $indexName);
        });
    }

    private function dropIndexIfTableExists(string $tableName, string $indexName): void
    {
        if (!Schema::hasTable($tableName)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($indexName) {
            $table->dropIndex($indexName);
        });
    }
};
