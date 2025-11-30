<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Place;

class PlaceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $places = [
            [
                'name' => 'Mount Everest Base Camp',
                'description' => 'Experience the majesty of the world\'s highest peak. Trek through stunning Himalayan landscapes, visit ancient monasteries, and witness breathtaking sunrise views over the mountains.',
                'lng' => 86.9244,
                'lat' => 27.9881,
                'status' => 'available',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Santorini, Greece',
                'description' => 'A picturesque island paradise known for its stunning sunsets, white-washed buildings, and crystal-clear blue waters. Explore volcanic beaches and ancient ruins.',
                'lng' => 25.4615,
                'lat' => 36.3932,
                'status' => 'available',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Machu Picchu, Peru',
                'description' => 'The ancient Incan citadel perched high in the Andes Mountains. Discover archaeological wonders, terraced fields, and panoramic mountain views.',
                'lng' => -72.5449,
                'lat' => -13.1631,
                'status' => 'available',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Tokyo, Japan',
                'description' => 'A vibrant metropolis blending ultra-modern technology with ancient traditions. Experience bustling streets, serene temples, and exquisite cuisine.',
                'lng' => 139.6917,
                'lat' => 35.6895,
                'status' => 'available',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Bali, Indonesia',
                'description' => 'Tropical paradise with stunning beaches, lush rice terraces, and rich cultural heritage. Perfect for relaxation and adventure activities.',
                'lng' => 115.1889,
                'lat' => -8.3405,
                'status' => 'available',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Swiss Alps',
                'description' => 'Majestic mountain landscapes offering world-class skiing, hiking trails, and charming alpine villages. Experience pristine nature and fresh mountain air.',
                'lng' => 8.2275,
                'lat' => 46.8182,
                'status' => 'available',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Dubai, UAE',
                'description' => 'Ultra-modern city showcasing architectural marvels, luxury shopping, and desert adventures. From skyscrapers to sand dunes, experience the extraordinary.',
                'lng' => 55.2708,
                'lat' => 25.2048,
                'status' => 'available',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Iceland Ring Road',
                'description' => 'Journey around Iceland\'s spectacular Ring Road, witnessing volcanoes, glaciers, waterfalls, and geysers. A photographer\'s dream destination.',
                'lng' => -19.0208,
                'lat' => 64.9631,
                'status' => 'available',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($places as $place) {
            Place::create($place);
        }
    }
}