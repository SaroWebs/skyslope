<?php

namespace Database\Seeders;

use App\Models\CarRentalExtra;
use Illuminate\Database\Seeder;

class CarRentalExtraSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $extras = [
            [
                'extra_type' => 'gps',
                'extra_name' => 'GPS Navigation',
                'description' => 'GPS navigation system for easy route guidance',
                'price_per_day' => 200.00,
                'quantity' => 1,
                'is_included' => false,
                'is_optional' => true,
            ],
            [
                'extra_type' => 'child_seat',
                'extra_name' => 'Child Seat',
                'description' => 'Safety seat for children (up to 5 years)',
                'price_per_day' => 150.00,
                'quantity' => 1,
                'is_included' => false,
                'is_optional' => true,
            ],
            [
                'extra_type' => 'extra_driver',
                'extra_name' => 'Additional Driver',
                'description' => 'Allow additional driver for the rental period',
                'price_per_day' => 300.00,
                'quantity' => 1,
                'is_included' => false,
                'is_optional' => true,
            ],
            [
                'extra_type' => 'wifi',
                'extra_name' => 'WiFi Hotspot',
                'description' => 'Portable WiFi hotspot for internet connectivity',
                'price_per_day' => 100.00,
                'quantity' => 1,
                'is_included' => false,
                'is_optional' => true,
            ],
            [
                'extra_type' => 'snacks',
                'extra_name' => 'Refreshments Package',
                'description' => 'Bottled water and light snacks for the journey',
                'price_per_day' => 250.00,
                'quantity' => 1,
                'is_included' => false,
                'is_optional' => true,
            ],
            [
                'extra_type' => 'toll_pass',
                'extra_name' => 'Toll Pass',
                'description' => 'Pre-loaded toll pass for highway travel',
                'price_per_day' => 50.00,
                'quantity' => 1,
                'is_included' => false,
                'is_optional' => true,
            ],
        ];

        foreach ($extras as $extra) {
            CarRentalExtra::create($extra);
        }
    }
}