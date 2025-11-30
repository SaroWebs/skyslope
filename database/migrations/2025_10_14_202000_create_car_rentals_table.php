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
        Schema::create('car_rentals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('car_category_id')->constrained('car_categories')->onDelete('cascade');
            $table->string('booking_number')->unique(); // Human-readable booking ID
            $table->string('customer_name');
            $table->string('customer_email');
            $table->string('customer_phone');
            $table->text('customer_address')->nullable();

            // Rental Details
            $table->date('start_date');
            $table->date('end_date');
            $table->time('start_time')->default('09:00');
            $table->time('end_time')->default('18:00');
            $table->string('pickup_location');
            $table->string('dropoff_location')->nullable();
            $table->text('destination_details')->nullable(); // Specific destinations/routes

            // Pricing
            $table->integer('number_of_days');
            $table->decimal('base_price', 10, 2); // Category base price
            $table->decimal('distance_km', 8, 2)->default(0);
            $table->decimal('distance_price', 10, 2)->default(0); // Additional KM charges
            $table->decimal('extras_price', 10, 2)->default(0); // Additional services
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('total_price', 10, 2);

            // Booking Status
            $table->enum('status', ['pending', 'confirmed', 'in_progress', 'completed', 'cancelled'])->default('pending');
            $table->enum('payment_status', ['pending', 'paid', 'failed', 'refunded'])->default('pending');
            $table->enum('payment_method', ['cash', 'online', 'bank_transfer'])->default('cash');

            // Additional Details
            $table->text('special_requests')->nullable();
            $table->text('internal_notes')->nullable();
            $table->string('assigned_driver')->nullable();
            $table->string('vehicle_number')->nullable(); // Actual vehicle assigned

            // Contact Preferences
            $table->boolean('whatsapp_notification')->default(true);
            $table->boolean('email_notification')->default(true);
            $table->boolean('sms_notification')->default(false);

            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['status', 'start_date']);
            $table->index('booking_number');
            $table->index(['car_category_id', 'start_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('car_rentals');
    }
};