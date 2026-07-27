<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tour_itineraries', function (Blueprint $table) {
            $table->dropUnique('tour_itineraries_tour_id_day_number_unique');
            $table->unsignedInteger('stop_order')->default(1)->after('day_number');
            $table->unique(['tour_id', 'day_number', 'stop_order'], 'tour_itineraries_day_stop_unique');
            $table->index(['tour_id', 'day_number'], 'tour_itineraries_day_index');
        });
    }

    public function down(): void
    {
        DB::table('tour_itineraries')->where('stop_order', '>', 1)->delete();
        Schema::table('tour_itineraries', function (Blueprint $table) {
            $table->dropUnique('tour_itineraries_day_stop_unique');
            $table->dropIndex('tour_itineraries_day_index');
            $table->dropColumn('stop_order');
            $table->unique(['tour_id', 'day_number']);
        });
    }
};
