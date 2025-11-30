<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Destination;

class DestinationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $destinations = [
            [
                'name' => 'Guwahati',
                'slug' => 'guwahati',
                'description' => 'Gateway to Northeast India with rich culture and history',
                'state' => 'Assam',
                'region' => 'northeast_india',
                'type' => 'city',
                'latitude' => 26.1445,
                'longitude' => 91.7362,
                'distance_from_guwahati' => 0,
                'estimated_travel_time' => 0,
                'best_time_to_visit' => ['October', 'November', 'December', 'January', 'February', 'March'],
                'attractions' => ['Kamakhya Temple', 'Brahmaputra River', 'Assam State Museum', 'Guwahati Zoo'],
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Shillong',
                'slug' => 'shillong',
                'description' => 'Scotland of the East with beautiful hills and waterfalls',
                'state' => 'Meghalaya',
                'region' => 'northeast_india',
                'type' => 'hill_station',
                'latitude' => 25.5788,
                'longitude' => 91.8933,
                'distance_from_guwahati' => 100,
                'estimated_travel_time' => 3,
                'best_time_to_visit' => ['March', 'April', 'May', 'September', 'October', 'November'],
                'attractions' => ['Umiam Lake', 'Elephant Falls', 'Shillong Peak', 'Ward\'s Lake'],
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Cherrapunji',
                'slug' => 'cherrapunji',
                'description' => 'Wettest place on Earth with stunning waterfalls and living root bridges',
                'state' => 'Meghalaya',
                'region' => 'northeast_india',
                'type' => 'hill_station',
                'latitude' => 25.2789,
                'longitude' => 91.7326,
                'distance_from_guwahati' => 150,
                'estimated_travel_time' => 4,
                'best_time_to_visit' => ['September', 'October', 'November', 'March', 'April'],
                'attractions' => ['Nohkalikai Falls', 'Living Root Bridges', 'Mawsmai Cave', 'Seven Sisters Falls'],
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'name' => 'Kaziranga',
                'slug' => 'kaziranga',
                'description' => 'UNESCO World Heritage Site famous for one-horned rhinoceros',
                'state' => 'Assam',
                'region' => 'northeast_india',
                'type' => 'wildlife',
                'latitude' => 26.5775,
                'longitude' => 93.1711,
                'distance_from_guwahati' => 200,
                'estimated_travel_time' => 5,
                'best_time_to_visit' => ['November', 'December', 'January', 'February', 'March', 'April'],
                'attractions' => ['One-horned Rhinoceros', 'Elephant Safari', 'Jeep Safari', 'Brahmaputra River'],
                'is_active' => true,
                'sort_order' => 4,
            ],
            [
                'name' => 'Tawang',
                'slug' => 'tawang',
                'description' => 'Buddhist monastery town with breathtaking mountain views',
                'state' => 'Arunachal Pradesh',
                'region' => 'northeast_india',
                'type' => 'hill_station',
                'latitude' => 27.5861,
                'longitude' => 91.8594,
                'distance_from_guwahati' => 500,
                'estimated_travel_time' => 12,
                'best_time_to_visit' => ['March', 'April', 'May', 'September', 'October'],
                'attractions' => ['Tawang Monastery', 'Sela Pass', 'Madhuri Lake', 'Pangateng Tso Lake'],
                'is_active' => true,
                'sort_order' => 5,
            ],
            [
                'name' => 'Dirang',
                'slug' => 'dirang',
                'description' => 'Picturesque valley town known for hot springs and monasteries',
                'state' => 'Arunachal Pradesh',
                'region' => 'northeast_india',
                'type' => 'hill_station',
                'latitude' => 27.3667,
                'longitude' => 92.2333,
                'distance_from_guwahati' => 380,
                'estimated_travel_time' => 10,
                'best_time_to_visit' => ['March', 'April', 'May', 'September', 'October'],
                'attractions' => ['Dirang Monastery', 'Hot Springs', 'Sangti Valley', 'Apple Orchards'],
                'is_active' => true,
                'sort_order' => 6,
            ],
            [
                'name' => 'Bomdila',
                'slug' => 'bomdila',
                'description' => 'Scenic hill station with Buddhist monasteries and mountain views',
                'state' => 'Arunachal Pradesh',
                'region' => 'northeast_india',
                'type' => 'hill_station',
                'latitude' => 27.2646,
                'longitude' => 92.4047,
                'distance_from_guwahati' => 350,
                'estimated_travel_time' => 9,
                'best_time_to_visit' => ['March', 'April', 'May', 'September', 'October'],
                'attractions' => ['Bomdila Monastery', 'Eagle\'s Nest Wildlife Sanctuary', 'Craft Centre'],
                'is_active' => true,
                'sort_order' => 7,
            ],
            [
                'name' => 'Tezpur',
                'slug' => 'tezpur',
                'description' => 'Cultural capital of Assam with ancient temples and ruins',
                'state' => 'Assam',
                'region' => 'northeast_india',
                'type' => 'city',
                'latitude' => 26.6528,
                'longitude' => 92.7926,
                'distance_from_guwahati' => 180,
                'estimated_travel_time' => 4,
                'best_time_to_visit' => ['October', 'November', 'December', 'January', 'February', 'March'],
                'attractions' => ['Agnigarh Hill', 'Mahabhairab Temple', 'Bamuni Hills', 'Chitralekha Udyan'],
                'is_active' => true,
                'sort_order' => 8,
            ],
        ];

        foreach ($destinations as $destination) {
            Destination::create($destination);
        }
    }
}