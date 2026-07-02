<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('drivers', function (Blueprint $table) {
            if (!Schema::hasColumn('drivers', 'can_short_ride')) {
                $table->boolean('can_short_ride')->default(true)->after('total_tours');
            }
            if (!Schema::hasColumn('drivers', 'can_long_ride')) {
                $table->boolean('can_long_ride')->default(true)->after('can_short_ride');
            }
            if (!Schema::hasColumn('drivers', 'can_tour_lead')) {
                $table->boolean('can_tour_lead')->default(false)->after('can_long_ride');
            }
            if (!Schema::hasColumn('drivers', 'can_tour_transport')) {
                $table->boolean('can_tour_transport')->default(false)->after('can_tour_lead');
            }
            if (!Schema::hasColumn('drivers', 'can_rental_delivery')) {
                $table->boolean('can_rental_delivery')->default(true)->after('can_tour_transport');
            }
            if (!Schema::hasColumn('drivers', 'languages')) {
                $table->json('languages')->nullable()->after('can_rental_delivery');
            }
            if (!Schema::hasColumn('drivers', 'expertise_tags')) {
                $table->json('expertise_tags')->nullable()->after('languages');
            }
            if (!Schema::hasColumn('drivers', 'certification_notes')) {
                $table->text('certification_notes')->nullable()->after('expertise_tags');
            }
        });

        Schema::table('tour_driver_assignments', function (Blueprint $table) {
            if (!Schema::hasColumn('tour_driver_assignments', 'role')) {
                $table->string('role', 40)->default('transport')->after('vehicle_id');
            }
        });

        Schema::table('tour_bookings', function (Blueprint $table) {
            if (!Schema::hasColumn('tour_bookings', 'current_lat')) {
                $table->decimal('current_lat', 10, 8)->nullable()->after('cancelled_at');
            }
            if (!Schema::hasColumn('tour_bookings', 'current_lng')) {
                $table->decimal('current_lng', 11, 8)->nullable()->after('current_lat');
            }
            if (!Schema::hasColumn('tour_bookings', 'last_location_update')) {
                $table->timestamp('last_location_update')->nullable()->after('current_lng');
            }
            if (!Schema::hasColumn('tour_bookings', 'current_stop_index')) {
                $table->unsignedInteger('current_stop_index')->default(0)->after('last_location_update');
            }
        });

        Schema::table('places', function (Blueprint $table) {
            if (!Schema::hasColumn('places', 'google_place_id')) {
                $table->string('google_place_id')->nullable()->after('tags');
            }
            if (!Schema::hasColumn('places', 'google_rating')) {
                $table->decimal('google_rating', 3, 2)->nullable()->after('google_place_id');
            }
            if (!Schema::hasColumn('places', 'google_review_count')) {
                $table->unsignedInteger('google_review_count')->default(0)->after('google_rating');
            }
            if (!Schema::hasColumn('places', 'google_reviews')) {
                $table->json('google_reviews')->nullable()->after('google_review_count');
            }
            if (!Schema::hasColumn('places', 'google_photos')) {
                $table->json('google_photos')->nullable()->after('google_reviews');
            }
            if (!Schema::hasColumn('places', 'google_synced_at')) {
                $table->timestamp('google_synced_at')->nullable()->after('google_photos');
            }
        });

        Schema::create('place_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('place_id')->constrained('places')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->text('review')->nullable();
            $table->timestamps();

            $table->unique(['place_id', 'customer_id']);
            $table->index('place_id');
        });

        Schema::create('tour_booking_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tour_booking_id')->constrained('tour_bookings')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained('drivers')->nullOnDelete();
            $table->unsignedTinyInteger('tour_rating')->nullable();
            $table->unsignedTinyInteger('driver_rating')->nullable();
            $table->text('review')->nullable();
            $table->timestamps();

            $table->unique(['tour_booking_id', 'customer_id']);
        });

        Schema::create('car_rental_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('car_rental_id')->constrained('car_rentals')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained('drivers')->nullOnDelete();
            $table->unsignedTinyInteger('rental_rating')->nullable();
            $table->unsignedTinyInteger('driver_rating')->nullable();
            $table->text('review')->nullable();
            $table->timestamps();

            $table->unique(['car_rental_id', 'customer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('car_rental_reviews');
        Schema::dropIfExists('tour_booking_reviews');
        Schema::dropIfExists('place_reviews');

        Schema::table('places', function (Blueprint $table) {
            foreach (['google_synced_at', 'google_photos', 'google_reviews', 'google_review_count', 'google_rating', 'google_place_id'] as $column) {
                if (Schema::hasColumn('places', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('tour_bookings', function (Blueprint $table) {
            foreach (['current_stop_index', 'last_location_update', 'current_lng', 'current_lat'] as $column) {
                if (Schema::hasColumn('tour_bookings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('tour_driver_assignments', function (Blueprint $table) {
            if (Schema::hasColumn('tour_driver_assignments', 'role')) {
                $table->dropColumn('role');
            }
        });

        Schema::table('drivers', function (Blueprint $table) {
            foreach ([
                'certification_notes',
                'expertise_tags',
                'languages',
                'can_rental_delivery',
                'can_tour_transport',
                'can_tour_lead',
                'can_long_ride',
                'can_short_ride',
            ] as $column) {
                if (Schema::hasColumn('drivers', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
