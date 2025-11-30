<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Itinerary;
use App\Models\Tour;
use App\Models\Place;

class ItinerarySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $itineraries = [
            // Everest Base Camp Trek (Tour 1)
            [
                'tour_id' => 1,
                'day_index' => 1,
                'time' => '09:00',
                'place_id' => 1, // Mount Everest Base Camp
                'details' => 'Arrival in Kathmandu and trek preparation. Visit local markets and cultural sites.',
            ],
            [
                'tour_id' => 1,
                'day_index' => 2,
                'time' => '08:00',
                'place_id' => 1,
                'details' => 'Flight to Lukla and begin trek to Phakding. Easy hiking through beautiful valleys.',
            ],
            [
                'tour_id' => 1,
                'day_index' => 3,
                'time' => '07:00',
                'place_id' => 1,
                'details' => 'Trek to Namche Bazaar. Acclimatization walk and visit to Sherpa museum.',
            ],

            // Santorini Island Escape (Tour 2)
            [
                'tour_id' => 2,
                'day_index' => 1,
                'time' => '14:00',
                'place_id' => 2, // Santorini
                'details' => 'Arrival in Santorini. Check into cave hotel and sunset viewing at Oia.',
            ],
            [
                'tour_id' => 2,
                'day_index' => 2,
                'time' => '10:00',
                'place_id' => 2,
                'details' => 'Volcanic beach day and wine tasting tour at local vineyards.',
            ],
            [
                'tour_id' => 2,
                'day_index' => 3,
                'time' => '09:00',
                'place_id' => 2,
                'details' => 'Boat tour to volcanic islands and hot springs. Traditional Greek cooking class.',
            ],

            // Machu Picchu Explorer (Tour 3)
            [
                'tour_id' => 3,
                'day_index' => 1,
                'time' => '08:00',
                'place_id' => 3, // Machu Picchu
                'details' => 'Arrival in Cusco and city tour. Visit Sacsayhuaman ruins and local markets.',
            ],
            [
                'tour_id' => 3,
                'day_index' => 2,
                'time' => '06:00',
                'place_id' => 3,
                'details' => 'Early morning train to Aguas Calientes. Afternoon exploration of Machu Picchu.',
            ],
            [
                'tour_id' => 3,
                'day_index' => 3,
                'time' => '05:00',
                'place_id' => 3,
                'details' => 'Sunrise at Machu Picchu. Guided tour of the citadel and Huayna Picchu climb.',
            ],

            // Tokyo Cultural Experience (Tour 4)
            [
                'tour_id' => 4,
                'day_index' => 1,
                'time' => '10:00',
                'place_id' => 4, // Tokyo
                'details' => 'Arrival and orientation. Visit Senso-ji Temple and Asakusa district.',
            ],
            [
                'tour_id' => 4,
                'day_index' => 2,
                'time' => '09:00',
                'place_id' => 4,
                'details' => 'Meiji Shrine and Harajuku exploration. Traditional tea ceremony experience.',
            ],
            [
                'tour_id' => 4,
                'day_index' => 3,
                'time' => '11:00',
                'place_id' => 4,
                'details' => 'Tsukiji Outer Market and sushi making class. Evening in Shibuya.',
            ],

            // Bali Wellness Retreat (Tour 5)
            [
                'tour_id' => 5,
                'day_index' => 1,
                'time' => '08:00',
                'place_id' => 5, // Bali
                'details' => 'Arrival in Ubud. Welcome ceremony and yoga session in rice terrace setting.',
            ],
            [
                'tour_id' => 5,
                'day_index' => 2,
                'time' => '07:00',
                'place_id' => 5,
                'details' => 'Morning meditation and visit to Tirta Empul Temple for purification ritual.',
            ],
            [
                'tour_id' => 5,
                'day_index' => 3,
                'time' => '09:00',
                'place_id' => 5,
                'details' => 'Spa day with traditional Balinese massage and flower bath experience.',
            ],

            // Swiss Alps Adventure (Tour 6)
            [
                'tour_id' => 6,
                'day_index' => 1,
                'time' => '10:00',
                'place_id' => 6, // Swiss Alps
                'details' => 'Arrival in Interlaken. Cable car to Harder Kulm for panoramic views.',
            ],
            [
                'tour_id' => 6,
                'day_index' => 2,
                'time' => '08:00',
                'place_id' => 6,
                'details' => 'Train to Jungfraujoch - Top of Europe. Ice palace and snow activities.',
            ],
            [
                'tour_id' => 6,
                'day_index' => 3,
                'time' => '09:00',
                'place_id' => 6,
                'details' => 'Lake Thun boat cruise and visit to Thun Castle.',
            ],

            // Dubai Luxury Experience (Tour 7)
            [
                'tour_id' => 7,
                'day_index' => 1,
                'time' => '15:00',
                'place_id' => 7, // Dubai
                'details' => 'VIP arrival and transfer to Burj Al Arab. Sunset cocktail at the Skyview Bar.',
            ],
            [
                'tour_id' => 7,
                'day_index' => 2,
                'time' => '10:00',
                'place_id' => 7,
                'details' => 'Private yacht cruise and visit to Palm Jumeirah. Luxury shopping experience.',
            ],
            [
                'tour_id' => 7,
                'day_index' => 3,
                'time' => '16:00',
                'place_id' => 7,
                'details' => 'Desert safari with falcon show and traditional Arabic dinner.',
            ],

            // Iceland Northern Lights (Tour 8)
            [
                'tour_id' => 8,
                'day_index' => 1,
                'time' => '12:00',
                'place_id' => 8, // Iceland
                'details' => 'Arrival in Reykjavik. City tour and visit to Hallgrímskirkja church.',
            ],
            [
                'tour_id' => 8,
                'day_index' => 2,
                'time' => '09:00',
                'place_id' => 8,
                'details' => 'Golden Circle tour: Thingvellir, Geysir, and Gullfoss waterfall.',
            ],
            [
                'tour_id' => 8,
                'day_index' => 3,
                'time' => '20:00',
                'place_id' => 8,
                'details' => 'Northern Lights hunting tour with professional photography guidance.',
            ],
        ];

        foreach ($itineraries as $itinerary) {
            Itinerary::create($itinerary);
        }
    }
}