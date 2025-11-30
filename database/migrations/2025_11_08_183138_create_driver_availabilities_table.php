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
        Schema::create('driver_availabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->constrained('users')->onDelete('cascade');
            $table->boolean('is_online')->default(false);
            $table->boolean('is_available')->default(true);
            $table->decimal('current_lat', 10, 8)->nullable();
            $table->decimal('current_lng', 11, 8)->nullable();
            $table->timestamp('last_ping')->nullable();
            $table->json('service_areas')->nullable(); // Array of destination IDs
            $table->string('vehicle_type')->nullable(); // sedan, suv, hatchback, etc.
            $table->string('vehicle_number')->nullable();
            $table->decimal('rating', 3, 2)->default(5.00);
            $table->integer('completed_rides')->default(0);
            $table->timestamps();

            // Indexes
            $table->index(['is_online', 'is_available']);
            $table->index(['current_lat', 'current_lng']);
            $table->index('last_ping');
            $table->unique('driver_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('driver_availabilities');
    }
};
