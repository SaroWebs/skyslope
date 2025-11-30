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
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // e.g., 'create_tours', 'edit_users', 'view_bookings'
            $table->string('display_name'); // e.g., 'Create Tours', 'Edit Users', 'View Bookings'
            $table->text('description')->nullable();
            $table->string('module')->default('general'); // e.g., 'tours', 'users', 'bookings'
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};
