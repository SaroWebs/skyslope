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
        Schema::create('extended_care', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('insurance_id')->nullable()->constrained('insurance_policies')->onDelete('set null');
            $table->enum('care_type', ['emergency', 'roadside', 'medical', 'legal']);
            $table->enum('status', ['active', 'in_progress', 'completed', 'cancelled'])->default('active');
            $table->date('request_date');
            $table->date('completion_date')->nullable();
            $table->string('service_provider')->nullable();
            $table->decimal('cost_incurred', 10, 2)->default(0.00);
            $table->decimal('coverage_applied', 10, 2)->default(0.00);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('extended_care');
    }
};