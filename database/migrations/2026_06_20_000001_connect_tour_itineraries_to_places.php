<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tour_itineraries', function (Blueprint $table) {
            if (!Schema::hasColumn('tour_itineraries', 'place_id')) {
                $table->foreignId('place_id')->nullable()->after('tour_id')->constrained('places')->nullOnDelete();
            }

            if (!Schema::hasColumn('tour_itineraries', 'day_index')) {
                $table->unsignedInteger('day_index')->nullable()->after('place_id');
            }

            if (!Schema::hasColumn('tour_itineraries', 'time')) {
                $table->time('time')->nullable()->after('day_index');
            }

            if (!Schema::hasColumn('tour_itineraries', 'details')) {
                $table->text('details')->nullable()->after('description');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tour_itineraries', function (Blueprint $table) {
            if (Schema::hasColumn('tour_itineraries', 'place_id')) {
                $table->dropConstrainedForeignId('place_id');
            }

            foreach (['day_index', 'time', 'details'] as $column) {
                if (Schema::hasColumn('tour_itineraries', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
