<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tours', function (Blueprint $table) {
            $table->text('cancellation_policy')->nullable()->after('exclusions');
        });

        Schema::table('tour_itineraries', function (Blueprint $table) {
            $table->string('start_location')->nullable()->after('title');
            $table->string('end_location')->nullable()->after('start_location');
            $table->string('travel_time')->nullable()->after('distance_km');
            $table->json('key_stops')->nullable()->after('travel_time');
            $table->json('inclusions')->nullable()->after('key_stops');
            $table->json('exclusions')->nullable()->after('inclusions');
        });
    }

    public function down(): void
    {
        Schema::table('tour_itineraries', function (Blueprint $table) {
            $table->dropColumn([
                'start_location',
                'end_location',
                'travel_time',
                'key_stops',
                'inclusions',
                'exclusions',
            ]);
        });

        Schema::table('tours', function (Blueprint $table) {
            $table->dropColumn('cancellation_policy');
        });
    }
};
