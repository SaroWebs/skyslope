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
        Schema::create('car_rental_extras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('car_rental_id')->constrained('car_rentals')->onDelete('cascade');
            $table->string('extra_type'); // gps, extra_driver, child_seat, etc.
            $table->string('extra_name'); // GPS Navigation, Additional Driver, etc.
            $table->text('description')->nullable();
            $table->decimal('price_per_day', 8, 2);
            $table->integer('quantity')->default(1);
            $table->decimal('total_price', 8, 2);
            $table->boolean('is_included')->default(false); // If included in base price
            $table->boolean('is_optional')->default(true);
            $table->timestamps();

            $table->index(['car_rental_id', 'extra_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('car_rental_extras');
    }
};