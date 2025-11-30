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
        Schema::create('car_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Innova Crysta, Swift Dzire, etc.
            $table->string('slug')->unique(); // URL-friendly version
            $table->text('description')->nullable();
            $table->string('vehicle_type'); // sedan, suv, tempo_traveller, etc.
            $table->integer('seats'); // Number of passengers
            $table->boolean('has_ac')->default(true);
            $table->boolean('has_driver')->default(true);
            $table->decimal('base_price_per_day', 10, 2);
            $table->decimal('price_per_km', 8, 2)->default(0);
            $table->json('features')->nullable(); // GPS, Music System, etc.
            $table->json('images')->nullable();
            $table->string('fuel_type')->default('diesel'); // diesel, petrol, electric
            $table->integer('year')->nullable(); // Model year
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
            $table->index('vehicle_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('car_categories');
    }
};