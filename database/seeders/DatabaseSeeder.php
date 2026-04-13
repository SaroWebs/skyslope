<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use App\Models\TourCategory;
use App\Models\CarCategory;
use App\Models\Tour;
use App\Models\TourSchedule;
use App\Models\TourItinerary;
use App\Models\Place;
use App\Models\PlaceCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Roles & Permissions
        $superAdminRole = Role::firstOrCreate(['name' => 'super_admin', 'display_name' => 'Super Admin']);
        $managerRole = Role::firstOrCreate(['name' => 'manager', 'display_name' => 'Manager']);

        $permissions = [
            'manage_tours',
            'manage_bookings',
            'manage_drivers',
            'manage_guides'
        ];

        foreach ($permissions as $permName) {
            $permission = Permission::firstOrCreate(['name' => $permName, 'display_name' => ucwords(str_replace('_', ' ', $permName))]);
            $superAdminRole->permissions()->syncWithoutDetaching([$permission->id]);
            $managerRole->permissions()->syncWithoutDetaching([$permission->id]);
        }

        // 2. Admin User
        $admin = User::firstOrCreate(
            ['email' => 'admin@skyslope.com'],
            [
                'name' => 'Admin User',
                'phone' => '1234567890',
                'password' => Hash::make('password')
            ]
        );
        $admin->roles()->sync([$superAdminRole->id]);

        // 3. Tour Categories
        $tourCategories = ['Adventure', 'Cultural', 'Wildlife', 'Beach', 'Hill Station'];
        foreach ($tourCategories as $catName) {
            TourCategory::firstOrCreate(
                ['slug' => str()->slug($catName)],
                ['name' => $catName, 'is_active' => true]
            );
        }

        // 4. Car Categories
        $carCategories = [
            ['name' => 'Hatchback', 'type' => 'hatchback', 'seats' => 4, 'ride_per_km' => 12.00, 'rental_per_day' => 1500.00],
            ['name' => 'Sedan', 'type' => 'sedan', 'seats' => 4, 'ride_per_km' => 15.00, 'rental_per_day' => 2000.00],
            ['name' => 'SUV', 'type' => 'suv', 'seats' => 6, 'ride_per_km' => 20.00, 'rental_per_day' => 3000.00],
            ['name' => 'Innova', 'type' => 'suv', 'seats' => 7, 'ride_per_km' => 25.00, 'rental_per_day' => 4000.00],
        ];

        foreach ($carCategories as $carCat) {
            CarCategory::firstOrCreate(
                ['slug' => str()->slug($carCat['name'])],
                [
                    'name' => $carCat['name'],
                    'vehicle_type' => $carCat['type'],
                    'base_fare' => 50.00,
                    'price_per_km' => $carCat['ride_per_km'],
                    'price_per_minute' => 2.00,
                    'base_price_per_day' => $carCat['rental_per_day'],
                    'seats' => $carCat['seats'],
                    'is_active' => true,
                ]
            );
        }

        // setup basic Place stuff for itineraries
        $placeCategory = PlaceCategory::firstOrCreate(['slug' => 'tourist-spot'], ['name' => 'Tourist Spot', 'is_active' => true]);
        $place1 = Place::firstOrCreate(['slug' => 'fort-aguada'], ['name' => 'Fort Aguada', 'place_category_id' => $placeCategory->id, 'description' => 'A famous fort']);
        $place2 = Place::firstOrCreate(['slug' => 'baga-beach'], ['name' => 'Baga Beach', 'place_category_id' => $placeCategory->id, 'description' => 'A famous beach']);

        // 5. Sample Tours & Itineraries
        $advCat = TourCategory::where('name', 'Adventure')->first();
        $beachCat = TourCategory::where('name', 'Beach')->first();

        // Tour 1
        $tour1 = Tour::firstOrCreate(
            ['slug' => 'goa-adventure'],
            [
                'tour_category_id' => $advCat->id,
                'title' => 'Goa Adventure Tour',
                'description' => 'Thrilling adventure in Goa.',
                'price_per_person' => 5000.00,
                'duration_days' => 3,
                'duration_nights' => 2,
                'is_active' => true,
            ]
        );

        TourItinerary::firstOrCreate([
            'tour_id' => $tour1->id,
            'day_number' => 1,
            'title' => 'Fort Aguada Visit',
        ]);
        
        TourSchedule::firstOrCreate([
            'tour_id' => $tour1->id,
            'departure_date' => Carbon::today()->addDays(5)->toDateString(),
            'return_date' => Carbon::today()->addDays(8)->toDateString(),
            'total_seats' => 20,
            'status' => 'open'
        ]);

        // Tour 2
        $tour2 = Tour::firstOrCreate(
            ['slug' => 'goa-beach-relaxation'],
            [
                'tour_category_id' => $beachCat->id,
                'title' => 'Goa Beach Relaxation',
                'description' => 'Relax on the beaches of Goa.',
                'price_per_person' => 3500.00,
                'duration_days' => 2,
                'duration_nights' => 1,
                'is_active' => true,
            ]
        );

        TourItinerary::firstOrCreate([
            'tour_id' => $tour2->id,
            'day_number' => 1,
            'title' => 'Baga Beach Visit',
        ]);
        
        TourSchedule::firstOrCreate([
            'tour_id' => $tour2->id,
            'departure_date' => Carbon::today()->addDays(10)->toDateString(),
            'return_date' => Carbon::today()->addDays(12)->toDateString(),
            'total_seats' => 10,
            'status' => 'open'
        ]);

        $this->command->info('Database seeder successfully populated fresh data!');
    }
}
