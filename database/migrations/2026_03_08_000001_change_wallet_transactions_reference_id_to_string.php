<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE wallet_transactions MODIFY reference_id VARCHAR(191) NULL');
            return;
        }

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE wallet_transactions ALTER COLUMN reference_id TYPE VARCHAR(191)');
            return;
        }

        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->string('reference_id', 191)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE wallet_transactions MODIFY reference_id INT NULL');
            return;
        }

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE wallet_transactions ALTER COLUMN reference_id TYPE INTEGER USING NULLIF(reference_id, \'\')::INTEGER');
            return;
        }

        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->integer('reference_id')->nullable()->change();
        });
    }
};
