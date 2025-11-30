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
        Schema::create('destinations', function (Blueprint $table) {
            $table->id();
            $table->string('name',191); // Guwahati, Shillong, Tawang, etc.
            $table->string('slug',191)->unique(); // URL-friendly version
            $table->text('description')->nullable();
            $table->string('state', 191); // Assam, Meghalaya, Arunachal Pradesh
            $table->string('region', 191)->default('northeast_india'); // For grouping
            $table->string('type', 191); // city, hill_station, wildlife, etc.
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->json('popular_routes')->nullable(); // Common routes to this destination
            $table->decimal('distance_from_guwahati', 8, 2)->nullable(); // Distance in KM
            $table->integer('estimated_travel_time')->nullable(); // Travel time in hours
            $table->json('best_time_to_visit')->nullable(); // Seasons/months
            $table->json('attractions')->nullable(); // Popular places
            $table->json('images')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
            $table->index('state');
            $table->index('type');
            $table->index(['region', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('destinations');
    }
};