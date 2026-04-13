<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('driver_availabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->unique()->constrained('drivers')->cascadeOnDelete();
            $table->boolean('is_available')->default(false);
            $table->enum('status', ['online', 'offline', 'on_ride', 'on_tour'])->default('offline');
            $table->decimal('current_lat', 10, 8)->nullable();
            $table->decimal('current_lng', 11, 8)->nullable();
            $table->timestamp('last_updated')->nullable();
            $table->timestamps();
        });

        Schema::create('driver_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->constrained('drivers')->cascadeOnDelete();
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->decimal('heading', 5, 2)->nullable();  // 0-360 degrees
            $table->decimal('speed', 6, 2)->nullable();    // km/h
            $table->decimal('accuracy', 8, 2)->nullable(); // meters
            $table->string('context')->nullable(); // 'ride:{id}', 'tour:{id}', 'idle'
            $table->timestamps();

            $table->index(['driver_id', 'created_at']);
            $table->index(['latitude', 'longitude']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_locations');
        Schema::dropIfExists('driver_availabilities');
    }
};
