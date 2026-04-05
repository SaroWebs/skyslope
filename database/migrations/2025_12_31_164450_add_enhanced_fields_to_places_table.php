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
        Schema::table('places', function (Blueprint $table) {
            $table->foreignId('place_category_id')->nullable()->after('id');
            $table->decimal('rating', 3, 2)->default(0.00)->after('description');
            $table->integer('reviews_count')->default(0)->after('rating');
            $table->json('opening_hours')->nullable()->after('reviews_count');
            $table->json('contact_info')->nullable()->after('opening_hours');
            $table->string('website')->nullable()->after('contact_info');
            $table->boolean('is_featured')->default(false)->after('website');
            $table->integer('tour_packages_count')->default(0)->after('is_featured');
            
            // Add foreign key constraint
            $table->foreign('place_category_id')->references('id')->on('place_categories')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('places', function (Blueprint $table) {
            $table->dropForeign(['place_category_id']);
            $table->dropColumn([
                'place_category_id',
                'rating',
                'reviews_count',
                'opening_hours',
                'contact_info',
                'website',
                'is_featured',
                'tour_packages_count'
            ]);
        });
    }
};