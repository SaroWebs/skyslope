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
        Schema::create('ride_bookings', function (Blueprint $table) {
            $table->id();
            $table->string('booking_number')->unique();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('driver_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('service_type')->default('point_to_point'); // point_to_point, hourly_rental, round_trip

            // Customer details
            $table->string('customer_name');
            $table->string('customer_email');
            $table->string('customer_phone');
            $table->text('customer_address')->nullable();

            // Location details with coordinates
            $table->string('pickup_location');
            $table->decimal('pickup_lat', 10, 8)->nullable();
            $table->decimal('pickup_lng', 11, 8)->nullable();
            $table->string('dropoff_location')->nullable();
            $table->decimal('dropoff_lat', 10, 8)->nullable();
            $table->decimal('dropoff_lng', 11, 8)->nullable();

            // Timing
            $table->datetime('scheduled_at');
            $table->datetime('started_at')->nullable();
            $table->datetime('completed_at')->nullable();
            $table->integer('estimated_duration')->nullable(); // minutes
            $table->integer('actual_duration')->nullable(); // minutes

            // Pricing
            $table->decimal('base_fare', 10, 2)->default(0);
            $table->decimal('distance_fare', 10, 2)->default(0);
            $table->decimal('time_fare', 10, 2)->default(0);
            $table->decimal('waiting_fare', 10, 2)->default(0);
            $table->decimal('surge_multiplier', 3, 2)->default(1.00);
            $table->decimal('total_fare', 10, 2);
            $table->decimal('distance_km', 8, 2)->nullable();

            // Status tracking
            $table->enum('status', [
                'pending',
                'confirmed',
                'driver_assigned',
                'driver_arriving',
                'pickup',
                'in_transit',
                'completed',
                'cancelled'
            ])->default('pending');

            $table->enum('payment_status', [
                'pending',
                'paid',
                'failed',
                'refunded'
            ])->default('pending');

            $table->string('payment_method')->default('cash');
            $table->string('vehicle_number')->nullable();
            $table->text('special_requests')->nullable();
            $table->text('cancellation_reason')->nullable();

            // Real-time tracking
            $table->decimal('current_lat', 10, 8)->nullable();
            $table->decimal('current_lng', 11, 8)->nullable();
            $table->timestamp('last_location_update')->nullable();

            // Notifications
            $table->boolean('whatsapp_notification')->default(true);
            $table->boolean('email_notification')->default(true);
            $table->boolean('sms_notification')->default(false);

            $table->timestamps();

            // Indexes
            $table->index(['status', 'scheduled_at']);
            $table->index(['driver_id', 'status']);
            $table->index(['pickup_lat', 'pickup_lng']);
            $table->index(['current_lat', 'current_lng']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ride_bookings');
    }
};
