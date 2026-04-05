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
        Schema::create('ride_booking_tips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ride_booking_id')->constrained('ride_bookings')->onDelete('cascade');
            $table->foreignId('customer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('driver_id')->constrained('users')->onDelete('cascade');
            $table->decimal('amount', 10, 2);
            $table->string('payment_method')->default('wallet');
            $table->string('status')->default('completed');
            $table->text('message')->nullable();
            $table->timestamps();

            $table->index(['driver_id', 'status']);
            $table->index(['ride_booking_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ride_booking_tips');
    }
};
