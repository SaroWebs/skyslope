<?php

namespace Database\Seeders;

use App\Models\CarCategory;
use App\Models\Customer;
use App\Models\Destination;
use App\Models\Driver;
use App\Models\DriverAvailability;
use App\Models\Place;
use App\Models\PlaceCategory;
use App\Models\PlaceMedia;
use App\Models\Tour;
use App\Models\TourCategory;
use App\Models\TourItinerary;
use App\Models\TourSchedule;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class MarketplaceSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['email' => 'admin@skyslope.com'],
            ['name' => 'SkySlope Administrator', 'phone' => '7002000000', 'password' => Hash::make('password'), 'email_verified_at' => now()],
        );
        $admin->assignRole('admin');

        $places = $this->seedPlaces($admin);
        $this->seedDestinations($places);
        $this->seedTours($places);
        $categories = $this->seedCarCategories();
        $drivers = $this->seedDrivers();
        $this->seedVehicles($categories, $drivers);
        $this->seedCustomers();
    }

    private function seedPlaces(User $admin): array
    {
        $categories = collect([
            ['nature', 'Nature & Landscapes'],
            ['culture', 'Culture & Heritage'],
            ['wildlife', 'Wildlife'],
            ['adventure', 'Adventure'],
            ['city', 'Cities & Gateways'],
        ])->mapWithKeys(function (array $row) {
            $category = PlaceCategory::updateOrCreate(
                ['slug' => $row[0]],
                ['name' => $row[1], 'description' => "Northeast India {$row[1]}", 'is_active' => true],
            );

            return [$row[0] => $category];
        })->all();

        $rows = [
            ['Guwahati', 'Assam', 'Guwahati', 26.1445, 91.7362, 'city', 'Gateway city on the Brahmaputra, known for Kamakhya Temple and river sunsets.', ['Brahmaputra', 'temples', 'food'], 'https://images.unsplash.com/photo-1590766940554-634a7ed41450?auto=format&fit=crop&w=1400&q=82'],
            ['Kaziranga National Park', 'Assam', 'Golaghat', 26.5775, 93.1711, 'wildlife', 'UNESCO-listed grasslands and wetlands, home to the great one-horned rhinoceros.', ['rhino', 'safari', 'UNESCO'], 'https://images.unsplash.com/photo-1549366021-9f761d450615?auto=format&fit=crop&w=1400&q=82'],
            ['Majuli', 'Assam', 'Majuli', 27.0016, 94.2243, 'culture', 'A great river island shaped by satras, mask-making traditions, and village life.', ['river island', 'satras', 'culture'], 'https://images.unsplash.com/photo-1500530855697-b586d89ba3ee?auto=format&fit=crop&w=1400&q=82'],
            ['Sivasagar', 'Assam', 'Sivasagar', 26.9826, 94.6425, 'culture', 'Historic Ahom capital with monumental tanks, temples, and royal architecture.', ['Ahom', 'heritage', 'temples'], 'https://images.unsplash.com/photo-1561361513-2d000a50f0dc?auto=format&fit=crop&w=1400&q=82'],
            ['Shillong', 'Meghalaya', 'Shillong', 25.5788, 91.8933, 'city', 'A lively hill capital with music, cafés, waterfalls, and easy access to Khasi country.', ['music', 'waterfalls', 'hill city'], 'https://images.unsplash.com/photo-1626621341517-bbf3d9990a23?auto=format&fit=crop&w=1400&q=82'],
            ['Sohra', 'Meghalaya', 'Cherrapunji', 25.2702, 91.7320, 'nature', 'Dramatic cliffs, monsoon waterfalls, caves, and living root bridge trails.', ['root bridges', 'waterfalls', 'caves'], 'https://images.unsplash.com/photo-1464822759023-fed622ff2c3b?auto=format&fit=crop&w=1400&q=82'],
            ['Dawki & Shnongpdeng', 'Meghalaya', 'Dawki', 25.1844, 92.0240, 'adventure', 'Clear Umngot River water, riverside camps, kayaking, and border landscapes.', ['river', 'kayaking', 'camping'], 'https://images.unsplash.com/photo-1437482078695-73f5ca6c96e2?auto=format&fit=crop&w=1400&q=82'],
            ['Mawlynnong', 'Meghalaya', 'Mawlynnong', 25.2017, 91.9160, 'culture', 'A carefully tended Khasi village near forest walks and a living root bridge.', ['village', 'Khasi', 'root bridge'], 'https://images.unsplash.com/photo-1511497584788-876760111969?auto=format&fit=crop&w=1400&q=82'],
            ['Tawang', 'Arunachal Pradesh', 'Tawang', 27.5861, 91.8594, 'culture', 'High-altitude monastery town surrounded by lakes, passes, and Himalayan valleys.', ['monastery', 'Himalaya', 'lakes'], 'https://images.unsplash.com/photo-1589802829985-817e51171b92?auto=format&fit=crop&w=1400&q=82'],
            ['Ziro Valley', 'Arunachal Pradesh', 'Ziro', 27.5448, 93.8197, 'culture', 'Apatani villages, pine-clad ridges, rice landscapes, and a strong music culture.', ['Apatani', 'rice fields', 'music'], 'https://images.unsplash.com/photo-1500534314209-a25ddb2bd429?auto=format&fit=crop&w=1400&q=82'],
            ['Dirang', 'Arunachal Pradesh', 'Dirang', 27.3586, 92.2412, 'nature', 'A gentle mountain stop with orchards, a riverside valley, and nearby monasteries.', ['valley', 'orchards', 'monastery'], 'https://images.unsplash.com/photo-1500534623283-312aade485b7?auto=format&fit=crop&w=1400&q=82'],
            ['Mechuka', 'Arunachal Pradesh', 'Mechuka', 28.6000, 94.1167, 'adventure', 'A remote valley of wooden homes, mountain trails, and the Siyom River.', ['remote valley', 'trekking', 'river'], 'https://images.unsplash.com/photo-1486911278844-a81c5267e227?auto=format&fit=crop&w=1400&q=82'],
            ['Gangtok', 'Sikkim', 'Gangtok', 27.3389, 88.6065, 'city', 'Sikkim’s hillside capital with monasteries, markets, and views toward Kangchenjunga.', ['monasteries', 'markets', 'mountains'], 'https://images.unsplash.com/photo-1558431382-27e303142255?auto=format&fit=crop&w=1400&q=82'],
            ['Yumthang Valley', 'Sikkim', 'Lachung', 27.8265, 88.6958, 'nature', 'A high valley of seasonal flowers, hot springs, and snowbound ridgelines.', ['flowers', 'snow', 'hot springs'], 'https://images.unsplash.com/photo-1464278533981-50106e6176b1?auto=format&fit=crop&w=1400&q=82'],
            ['Pelling', 'Sikkim', 'Pelling', 27.3000, 88.2400, 'nature', 'A quiet base for monastery visits, forest walks, and Kangchenjunga views.', ['Kangchenjunga', 'monasteries', 'skywalk'], 'https://images.unsplash.com/photo-1500530855697-b586d89ba3ee?auto=format&fit=crop&w=1400&q=82'],
            ['Kohima', 'Nagaland', 'Kohima', 25.6751, 94.1086, 'culture', 'Hill capital known for the war cemetery, Naga heritage, food, and nearby villages.', ['Naga culture', 'history', 'food'], 'https://images.unsplash.com/photo-1514933651103-005eec06c04b?auto=format&fit=crop&w=1400&q=82'],
            ['Dzukou Valley', 'Nagaland', 'Viswema', 25.5520, 94.0870, 'adventure', 'A rolling highland valley reached by a rewarding ridge trek.', ['trek', 'valley', 'camping'], 'https://images.unsplash.com/photo-1500534314209-a25ddb2bd429?auto=format&fit=crop&w=1400&q=82'],
            ['Loktak Lake', 'Manipur', 'Moirang', 24.5590, 93.7868, 'nature', 'A vast freshwater lake famous for floating phumdis and Keibul Lamjao National Park.', ['lake', 'phumdis', 'Sangai'], 'https://images.unsplash.com/photo-1439066615861-d1af74d74000?auto=format&fit=crop&w=1400&q=82'],
            ['Unakoti', 'Tripura', 'Kailashahar', 24.3167, 92.0667, 'culture', 'Forest-set rock reliefs and monumental Shaiva carvings dating back centuries.', ['rock carvings', 'heritage', 'forest'], 'https://images.unsplash.com/photo-1561361513-2d000a50f0dc?auto=format&fit=crop&w=1400&q=82'],
            ['Reiek', 'Mizoram', 'Reiek', 23.6927, 92.6106, 'adventure', 'A ridge village and viewpoint above forested hills west of Aizawl.', ['ridge', 'village', 'hiking'], 'https://images.unsplash.com/photo-1464822759023-fed622ff2c3b?auto=format&fit=crop&w=1400&q=82'],
        ];

        $places = [];
        foreach ($rows as $index => [$name, $state, $city, $latitude, $longitude, $category, $description, $tags, $image]) {
            $slug = Str::slug($name);
            $place = Place::updateOrCreate(
                ['slug' => $slug],
                [
                    'place_category_id' => $categories[$category]->id,
                    'name' => $name,
                    'description' => $description,
                    'short_description' => Str::limit($description, 150),
                    'location' => "{$city}, {$state}",
                    'city' => $city,
                    'state' => $state,
                    'country' => 'India',
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'rating' => 4.35 + (($index % 6) * 0.1),
                    'review_count' => 120 + ($index * 37),
                    'cover_image' => $image,
                    'tags' => $tags,
                    'is_active' => true,
                    'is_featured' => $index < 12,
                ],
            );

            PlaceMedia::updateOrCreate(
                ['place_id' => $place->id, 'sort_order' => 0],
                [
                    'path' => $image,
                    'type' => 'image',
                    'source' => 'admin',
                    'approval_status' => 'approved',
                    'reviewed_by' => $admin->id,
                    'reviewed_at' => now(),
                    'caption' => "{$name} travel view",
                ],
            );
            $places[$slug] = $place;
        }

        return $places;
    }

    private function seedDestinations(array $places): void
    {
        foreach (array_values($places) as $index => $place) {
            Destination::updateOrCreate(
                ['slug' => $place->slug],
                [
                    'name' => $place->name,
                    'description' => $place->description,
                    'short_description' => $place->short_description,
                    'country' => 'India',
                    'state' => $place->state,
                    'city' => $place->city,
                    'latitude' => $place->latitude,
                    'longitude' => $place->longitude,
                    'cover_image' => $place->cover_image,
                    'gallery' => [$place->cover_image],
                    'highlights' => $place->tags,
                    'rating' => $place->rating,
                    'is_active' => true,
                    'is_featured' => $place->is_featured,
                    'sort_order' => $index + 1,
                ],
            );
        }
    }

    private function seedTours(array $places): void
    {
        $categories = collect([
            ['cultural-circuits', 'Cultural Circuits'],
            ['mountain-escapes', 'Mountain Escapes'],
            ['wildlife-journeys', 'Wildlife Journeys'],
            ['weekend-breaks', 'Weekend Breaks'],
        ])->mapWithKeys(function (array $row) {
            $category = TourCategory::updateOrCreate(
                ['slug' => $row[0]],
                ['name' => $row[1], 'description' => "{$row[1]} across Northeast India", 'is_active' => true],
            );
            return [$row[0] => $category];
        })->all();

        $rows = [
            ['assam-rhino-river', 'Assam Rhino & River Trail', 'wildlife-journeys', 4, 3, 18900, ['guwahati', 'kaziranga-national-park', 'majuli'], 'Assam'],
            ['meghalaya-waterfall-circuit', 'Meghalaya Waterfall Circuit', 'weekend-breaks', 4, 3, 16900, ['shillong', 'sohra', 'dawki-shnongpdeng', 'mawlynnong'], 'Meghalaya'],
            ['tawang-high-road', 'Tawang Tales: The High Road', 'mountain-escapes', 7, 6, 32900, ['guwahati', 'dirang', 'tawang'], 'Arunachal Pradesh'],
            ['ziro-apatani-stories', 'Ziro & Apatani Stories', 'cultural-circuits', 5, 4, 24500, ['guwahati', 'ziro-valley'], 'Arunachal Pradesh'],
            ['sikkim-valleys-monasteries', 'Sikkim Valleys & Monasteries', 'mountain-escapes', 6, 5, 28900, ['gangtok', 'yumthang-valley', 'pelling'], 'Sikkim'],
            ['nagaland-hills-heritage', 'Nagaland Hills & Heritage', 'cultural-circuits', 5, 4, 23900, ['kohima', 'dzukou-valley'], 'Nagaland'],
            ['loktak-manipur-weekend', 'Loktak Manipur Weekend', 'weekend-breaks', 3, 2, 13900, ['loktak-lake'], 'Manipur'],
            ['northeast-grand-circuit', 'Grand Northeast Discovery', 'cultural-circuits', 12, 11, 54900, ['guwahati', 'shillong', 'kaziranga-national-park', 'kohima', 'loktak-lake'], 'Northeast India'],
        ];

        foreach ($rows as $tourIndex => [$slug, $title, $category, $days, $nights, $price, $placeSlugs, $region]) {
            $tour = Tour::updateOrCreate(
                ['slug' => $slug],
                [
                    'tour_category_id' => $categories[$category]->id,
                    'title' => $title,
                    'description' => "A locally planned {$days}-day journey through {$region}, with reliable hill transport, realistic travel times, and thoughtfully paced stops.",
                    'short_description' => "A {$days}-day locally planned route through {$region}.",
                    'highlights' => collect($placeSlugs)->map(fn ($placeSlug) => $places[$placeSlug]->name)->all(),
                    'inclusions' => $slug === 'tawang-high-road'
                        ? ['Private vehicle from Guwahati and return', 'Inner-line permit arrangements', 'Accommodation', 'Breakfast and dinner', 'Entry fees', 'Local guide where listed', '24/7 trip support']
                        : ['Verified vehicle and driver', 'Accommodation', 'Daily breakfast', 'Local coordination'],
                    'exclusions' => $slug === 'tawang-high-road'
                        ? ['Flights or trains to Guwahati', 'Lunch except where listed', 'Personal expenses', 'Travel or medical insurance', 'Tips and room service', 'Costs caused by weather or route changes']
                        : ['Flights or trains', 'Personal expenses', 'Monument and activity tickets'],
                    'cancellation_policy' => $slug === 'tawang-high-road'
                        ? 'Booking is confirmed after advance payment. Date changes and cancellations are subject to supplier commitments; weather-affected travel will be rescheduled or adjusted with the operations team wherever possible.'
                        : 'Cancellation and date-change charges depend on the departure date and confirmed supplier commitments. The exact refundable amount is shown before payment.',
                    'duration_days' => $days,
                    'duration_nights' => $nights,
                    'min_group_size' => 2,
                    'max_group_size' => 12,
                    'price_per_person' => $price,
                    'child_price' => round($price * 0.65),
                    'discount' => $tourIndex % 3 === 0 ? 7 : 0,
                    'start_location' => $places[$placeSlugs[0]]->name,
                    'end_location' => $slug === 'tawang-high-road' ? 'Guwahati' : $places[$placeSlugs[count($placeSlugs) - 1]]->name,
                    'region' => $region,
                    'difficulty' => $days >= 7 ? 'moderate' : 'easy',
                    'cover_image' => $places[$placeSlugs[min(1, count($placeSlugs) - 1)]]->cover_image,
                    'gallery' => collect($placeSlugs)->map(fn ($placeSlug) => $places[$placeSlug]->cover_image)->all(),
                    'available_from' => now()->toDateString(),
                    'available_to' => now()->addYear()->toDateString(),
                    'is_active' => true,
                    'is_featured' => $tourIndex < 6,
                ],
            );

            if ($slug === 'tawang-high-road') {
                $this->seedTawangBrief($tour, $places);
            } else {
                foreach ($placeSlugs as $stop => $placeSlug) {
                    $day = min($stop + 1, $days);
                    TourItinerary::updateOrCreate(
                        ['tour_id' => $tour->id, 'day_number' => $day, 'stop_order' => 1],
                        [
                            'place_id' => $places[$placeSlug]->id,
                            'day_index' => $day,
                            'time' => '09:00',
                            'title' => "Explore {$places[$placeSlug]->name}",
                            'description' => $places[$placeSlug]->short_description,
                            'details' => $places[$placeSlug]->description,
                            'activities' => ['Guided orientation', 'Local experience', 'Scenic transfer'],
                            'meals_included' => ['breakfast'],
                            'distance_km' => (string) (70 + ($stop * 45)),
                        ],
                    );
                }
            }

            foreach ([14, 35, 63] as $scheduleIndex => $daysFromNow) {
                TourSchedule::updateOrCreate(
                    ['tour_id' => $tour->id, 'departure_date' => now()->addDays($daysFromNow + $tourIndex)->toDateString()],
                    [
                        'return_date' => now()->addDays($daysFromNow + $tourIndex + $days - 1)->toDateString(),
                        'departure_time' => '07:30',
                        'departure_point' => $tour->start_location,
                        'total_seats' => 12,
                        'booked_seats' => $scheduleIndex,
                        'reserved_seats' => 0,
                        'status' => 'open',
                    ],
                );
            }
        }
    }

    private function seedTawangBrief(Tour $tour, array $places): void
    {
        $days = [
            [1, 'dirang', 'Guwahati to Dirang', 'Guwahati', 'Dirang', '315', '8-9 hours', 'My Village Cottage', ['dinner'], ['Via Balemu Bhutan Border'], [
                ['name' => 'Tippi Orchid Research Centre', 'description' => 'A conservation centre known for rare orchid species.'],
                ['name' => 'Nechiphu Waterfall', 'description' => 'A scenic roadside waterfall on the climb into the hills.'],
                ['name' => 'Nag Mandir', 'description' => 'A small revered shrine maintained by the Indian Army.'],
            ], ['Inner-line permit', 'Dinner', 'Stay', 'Entry fees', 'Transportation'], ['Breakfast', 'Lunch', 'Personal expenses']],
            [2, 'tawang', 'Dirang to Tawang', 'Dirang', 'Tawang', '135', '7-8 hours', 'Tseten Homestay', ['breakfast', 'dinner'], ['High-altitude transfer', 'Heritage stops'], [
                ['name' => 'Dirang Monastery', 'description' => 'A serene Buddhist learning centre above the valley.'],
                ['name' => 'Sela Pass and Sela Lake', 'description' => 'The dramatic 13,700 ft gateway to Tawang.'],
                ['name' => 'Jaswant Garh War Memorial', 'description' => 'A memorial to Rifleman Jaswant Singh Rawat.'],
                ['name' => 'Tawang Market', 'description' => 'An evening introduction to the town and local shops.'],
            ], ['Breakfast', 'Dinner', 'Stay', 'Entry fees', 'Transportation'], ['Lunch', 'Personal expenses']],
            [3, 'tawang', 'Tawang high-altitude lakes and Bum La', 'Tawang', 'Tawang', '80', '6-7 hours', 'Tseten Homestay', ['breakfast', 'dinner'], ['Border excursion', 'Lake circuit'], [
                ['name' => 'Bum La Pass', 'description' => 'The Indo-China border excursion, subject to a separate local permit.'],
                ['name' => 'Sangetsar Lake', 'description' => 'A high-altitude lake known for upright tree trunks in the water.'],
                ['name' => 'PTSO and Nagula lakes', 'description' => 'A chain of alpine lakes along the high road.'],
                ['name' => 'Tawang War Memorial', 'description' => 'Evening light and sound show, subject to the operating schedule.'],
            ], ['Breakfast', 'Dinner', 'Stay', 'Entry fees', 'Bum La permit support', 'Transportation'], ['Lunch', 'Personal expenses']],
            [4, 'tawang', 'Monasteries, craft and Tawang town', 'Tawang', 'Tawang', '25', '2-3 hours', 'Hotel Vajra', ['breakfast', 'dinner'], ['Culture and local market'], [
                ['name' => 'Tawang Monastery', 'description' => 'The landmark hilltop monastery and its cultural precinct.'],
                ['name' => 'Urgelling Monastery', 'description' => 'A quiet historic monastery associated with the sixth Dalai Lama.'],
                ['name' => 'Craft Centre and Emporium', 'description' => 'Regional weaving, crafts and locally made souvenirs.'],
                ['name' => 'Giant Buddha and Tawang Market', 'description' => 'Panoramic town views followed by time in the market.'],
            ], ['Breakfast', 'Dinner', 'Stay', 'Entry fees', 'Transportation'], ['Lunch', 'Personal expenses']],
            [5, 'dirang', 'Tawang to Sangti Valley', 'Tawang', 'Sangti Valley', '145', '7-8 hours', 'Lanjom Homestay', ['breakfast', 'dinner'], ['Waterfalls and valley transfer'], [
                ['name' => 'Chagzam Bridge', 'description' => 'A historic iron suspension bridge in the Tawang region.'],
                ['name' => 'Jang Waterfalls', 'description' => 'A powerful mountain waterfall also known as Nuranang Falls.'],
                ['name' => 'Sela Pass and Sela Tunnel', 'description' => 'Return through the high pass and all-weather tunnel corridor.'],
                ['name' => 'Sangti Valley', 'description' => 'A gentle riverside valley near Dirang.'],
            ], ['Breakfast', 'Dinner', 'Stay', 'Local guide', 'Entry fees', 'Transportation'], ['Lunch', 'Personal expenses']],
            [6, 'dirang', 'Sangti Valley to Shergaon', 'Sangti Valley', 'Shergaon', '110', '4-5 hours', 'Acorn Homestay', ['breakfast', 'lunch', 'dinner'], ['Village and food experience'], [
                ['name' => 'Sangti Valley footbridge', 'description' => 'A quiet walking stop beside the river and farms.'],
                ['name' => 'Lubrang', 'description' => 'A hosted introduction to nomadic life, food and local culture.'],
                ['name' => 'Shergaon', 'description' => 'A forested village known for orchards and traditional homes.'],
            ], ['Breakfast', 'Lunch', 'Dinner', 'Stay', 'Local guide', 'Entry fees', 'Transportation'], ['Personal expenses']],
            [7, 'guwahati', 'Shergaon to Guwahati', 'Shergaon', 'Guwahati', '320', '6-7 hours', null, ['breakfast'], ['Return transfer'], [
                ['name' => 'Scenic return drive', 'description' => 'Travel back to Guwahati with comfort and refreshment stops.'],
            ], ['Breakfast', 'Transportation'], ['Lunch', 'Personal expenses']],
        ];

        foreach ($days as [$day, $placeSlug, $title, $from, $to, $distance, $travelTime, $stay, $meals, $activities, $keyStops, $inclusions, $exclusions]) {
            TourItinerary::updateOrCreate(
                ['tour_id' => $tour->id, 'day_number' => $day, 'stop_order' => 1],
                [
                    'place_id' => $places[$placeSlug]->id,
                    'day_index' => $day,
                    'time' => '08:00',
                    'title' => $title,
                    'start_location' => $from,
                    'end_location' => $to,
                    'description' => "Travel from {$from} to {$to} with a locally paced route and planned experience stops.",
                    'details' => "Travel from {$from} to {$to} with a locally paced route and planned experience stops.",
                    'activities' => $activities,
                    'accommodation' => $stay,
                    'meals_included' => $meals,
                    'distance_km' => $distance,
                    'travel_time' => $travelTime,
                    'key_stops' => $keyStops,
                    'inclusions' => $inclusions,
                    'exclusions' => $exclusions,
                ],
            );
        }
    }

    private function seedCarCategories(): array
    {
        $rows = [
            ['Compact', 'hatchback', 4, 65, 15, 2100, 'petrol', 'https://images.unsplash.com/photo-1549317661-bd32c8ce0db2?auto=format&fit=crop&w=1000&q=80'],
            ['Comfort Sedan', 'sedan', 4, 80, 18, 2900, 'petrol', 'https://images.unsplash.com/photo-1552519507-da3b142c6e3d?auto=format&fit=crop&w=1000&q=80'],
            ['Hill SUV', 'suv', 6, 110, 24, 4500, 'diesel', 'https://images.unsplash.com/photo-1519641471654-76ce0107ad1b?auto=format&fit=crop&w=1000&q=80'],
            ['Premium MUV', 'muv', 7, 125, 27, 5200, 'diesel', 'https://images.unsplash.com/photo-1542282088-72c9c27ed0cd?auto=format&fit=crop&w=1000&q=80'],
            ['Tempo Traveller', 'tempo_traveller', 12, 180, 34, 7800, 'diesel', 'https://images.unsplash.com/photo-1570125909232-eb263c188f7e?auto=format&fit=crop&w=1000&q=80'],
            ['Electric City', 'electric', 4, 70, 16, 2600, 'electric', 'https://images.unsplash.com/photo-1597404294360-feeeda04612e?auto=format&fit=crop&w=1000&q=80'],
        ];

        $categories = [];
        foreach ($rows as $index => [$name, $type, $seats, $baseFare, $perKm, $perDay, $fuel, $image]) {
            $category = CarCategory::updateOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'name' => $name,
                    'description' => "{$name} vehicles selected for city, highway, and Northeast hill routes.",
                    'vehicle_type' => $type,
                    'seats' => $seats,
                    'has_ac' => true,
                    'has_driver' => true,
                    'base_fare' => $baseFare,
                    'price_per_km' => $perKm,
                    'price_per_minute' => 2.5,
                    'waiting_charge_per_min' => 2,
                    'min_fare' => $baseFare + 40,
                    'base_price_per_day' => $perDay,
                    'extra_km_charge' => $perKm,
                    'fuel_type' => $fuel,
                    'year' => 2024,
                    'features' => ['GPS', 'Air conditioning', 'USB charging', 'Emergency kit'],
                    'images' => [$image],
                    'is_active' => true,
                    'sort_order' => $index + 1,
                ],
            );
            $categories[] = $category;
        }

        return $categories;
    }

    private function seedDrivers(): array
    {
        $names = [
            ['Arup Das', 'Assamese'], ['Rinchen Tsering', 'Monpa'], ['Bantei Khongwir', 'Khasi'],
            ['Imsong Jamir', 'Ao'], ['Lalrinmawia', 'Mizo'], ['Thoiba Meitei', 'Manipuri'],
            ['Nima Lepcha', 'Nepali'], ['Priyanka Bora', 'Assamese'], ['Tashi Doma', 'Bhutia'],
            ['Meban Lyngdoh', 'Khasi'], ['Kenei Zeliang', 'Nagamese'], ['Rakesh Debbarma', 'Kokborok'],
        ];

        $drivers = [];
        foreach ($names as $index => [$name, $language]) {
            $driver = Driver::updateOrCreate(
                ['phone' => '70030'.str_pad((string) ($index + 1), 5, '0', STR_PAD_LEFT)],
                [
                    'name' => $name,
                    'email' => 'driver'.($index + 1).'@happymiles.test',
                    'password' => Hash::make('password'),
                    'license_number' => 'NE-DRV-'.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT),
                    'license_expiry' => now()->addYears(3)->toDateString(),
                    'status' => 'active',
                    'is_online' => false,
                    'is_active' => true,
                    'is_approved' => true,
                    'approved_at' => now()->subMonths(3),
                    'rating' => 4.55 + (($index % 5) * 0.08),
                    'can_short_ride' => true,
                    'can_long_ride' => true,
                    'can_tour_lead' => $index % 3 === 0,
                    'can_tour_transport' => true,
                    'can_rental_delivery' => true,
                    'languages' => ['Hindi', 'English', $language],
                    'expertise_tags' => ['Northeast hill roads', 'Guest assistance', 'Safe driving'],
                    'certification_notes' => 'Seeded verified fleet partner.',
                    'phone_verified_at' => now()->subMonths(3),
                ],
            );
            DriverAvailability::updateOrCreate(
                ['driver_id' => $driver->id],
                ['status' => 'offline', 'is_available' => false, 'sharing_enabled' => $index % 2 === 0, 'sharing_seat_capacity' => 3, 'last_updated' => now()],
            );
            $drivers[] = $driver;
        }

        return $drivers;
    }

    private function seedVehicles(array $categories, array $drivers): void
    {
        $models = [
            ['Maruti Suzuki', 'Dzire'], ['Tata', 'Nexon'], ['Mahindra', 'Scorpio N'],
            ['Toyota', 'Innova Crysta'], ['Hyundai', 'Creta'], ['Kia', 'Carens'],
            ['Tata', 'Safari'], ['Mahindra', 'Bolero Neo'], ['Honda', 'Amaze'],
            ['Maruti Suzuki', 'Ertiga'], ['Toyota', 'Glanza'], ['Tata', 'Tigor EV'],
            ['Force', 'Traveller 12'], ['Mahindra', 'XUV700'], ['Toyota', 'Innova Hycross'],
        ];

        foreach ($models as $index => [$make, $model]) {
            $driver = $drivers[$index] ?? null;
            $category = $categories[$index % count($categories)];
            Vehicle::updateOrCreate(
                ['registration_number' => 'AS01HM'.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT)],
                [
                    'car_category_id' => $category->id,
                    'driver_id' => $driver?->id,
                    'make' => $make,
                    'model' => $model,
                    'year' => 2021 + ($index % 5),
                    'color' => ['White', 'Silver', 'Blue', 'Grey', 'Black'][$index % 5],
                    'fuel_type' => $category->fuel_type,
                    'seats' => $category->seats,
                    'is_ac' => true,
                    'insurance_expiry' => now()->addYear()->toDateString(),
                    'permit_expiry' => now()->addMonths(10)->toDateString(),
                    'fitness_expiry' => now()->addMonths(14)->toDateString(),
                    'pollution_expiry' => now()->addMonths(6)->toDateString(),
                    'odometer_km' => 18000 + ($index * 4200),
                    'is_active' => true,
                    'condition' => $index % 7 === 0 ? 'good' : 'excellent',
                    'approval_status' => 'approved',
                    'reviewed_at' => now()->subMonth(),
                    'notes' => $driver ? "Assigned to {$driver->name}" : 'Available for driver assignment',
                ],
            );
        }
    }

    private function seedCustomers(): void
    {
        foreach (['Ananya Sharma', 'Rahul Deka', 'Isha Sen', 'Kabir Ahmed', 'Maya Gurung', 'Neha Roy'] as $index => $name) {
            Customer::updateOrCreate(
                ['phone' => '70040'.str_pad((string) ($index + 1), 5, '0', STR_PAD_LEFT)],
                [
                    'name' => $name,
                    'email' => 'customer'.($index + 1).'@happymiles.test',
                    'password' => Hash::make('password'),
                    'is_active' => true,
                    'phone_verified_at' => now()->subMonth(),
                ],
            );
        }
    }
}
