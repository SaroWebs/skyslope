<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Tour;

class TourSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tours = [
            [
                'title' => 'Everest Base Camp Trek',
                'description' => 'Embark on the ultimate Himalayan adventure to Everest Base Camp. This 14-day journey takes you through traditional Sherpa villages, across suspension bridges, and to the foot of the world\'s highest mountain.',
                'price' => 2500,
                'discount' => 10,
                'available_from' => now()->addDays(30),
                'available_to' => now()->addDays(60),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Santorini Island Escape',
                'description' => 'Discover the magic of Santorini with our 7-day luxury escape. Stay in cave hotels, watch legendary sunsets, explore volcanic beaches, and taste world-renowned Greek wines.',
                'price' => 1800,
                'discount' => 5,
                'available_from' => now()->addDays(15),
                'available_to' => now()->addDays(45),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Machu Picchu Explorer',
                'description' => 'Journey to the mystical Incan citadel of Machu Picchu. This 10-day adventure includes the Inca Trail trek, visits to ancient ruins, and cultural immersion in Peruvian traditions.',
                'price' => 2200,
                'discount' => 15,
                'available_from' => now()->addDays(45),
                'available_to' => now()->addDays(75),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Tokyo Cultural Experience',
                'description' => 'Immerse yourself in Japanese culture with our 8-day Tokyo adventure. Visit ancient temples, experience traditional tea ceremonies, explore modern districts, and savor authentic cuisine.',
                'price' => 3200,
                'discount' => 0,
                'available_from' => now()->addDays(20),
                'available_to' => now()->addDays(50),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Bali Wellness Retreat',
                'description' => 'Rejuvenate your mind, body, and spirit in tropical Bali. This 12-day wellness retreat includes yoga sessions, spa treatments, temple visits, and healthy Balinese cuisine.',
                'price' => 1900,
                'discount' => 8,
                'available_from' => now()->addDays(10),
                'available_to' => now()->addDays(40),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Swiss Alps Adventure',
                'description' => 'Experience the breathtaking beauty of the Swiss Alps. This 9-day tour includes hiking in Interlaken, visiting Jungfraujoch, and exploring charming alpine villages.',
                'price' => 2800,
                'discount' => 12,
                'available_from' => now()->addDays(35),
                'available_to' => now()->addDays(65),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Dubai Luxury Experience',
                'description' => 'Indulge in luxury with our 6-day Dubai experience. Stay in 7-star hotels, enjoy desert safaris, shop in luxury malls, and witness architectural marvels.',
                'price' => 3500,
                'discount' => 0,
                'available_from' => now()->addDays(25),
                'available_to' => now()->addDays(55),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Iceland Northern Lights',
                'description' => 'Chase the Northern Lights across Iceland\'s stunning landscapes. This 8-day winter adventure includes visits to geysers, waterfalls, and the Blue Lagoon.',
                'price' => 2600,
                'discount' => 7,
                'available_from' => now()->addDays(60),
                'available_to' => now()->addDays(90),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($tours as $tour) {
            Tour::create($tour);
        }
    }
}