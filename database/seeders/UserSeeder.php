<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'name' => 'Admin User',
                'email' => 'admin@skyslope.com',
                'phone' => '+1-555-0100',
                'role' => 'admin',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'John Smith',
                'email' => 'john@example.com',
                'phone' => '+1-555-0101',
                'role' => 'customer',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Sarah Johnson',
                'email' => 'sarah@example.com',
                'phone' => '+1-555-0102',
                'role' => 'customer',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Mike Wilson',
                'email' => 'mike@example.com',
                'phone' => '+1-555-0103',
                'role' => 'guide',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Emma Davis',
                'email' => 'emma@example.com',
                'phone' => '+1-555-0104',
                'role' => 'driver',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($users as $userData) {
            $role = $userData['role'];
            unset($userData['role']);

            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                $userData
            );

            // Only assign role if user was just created (not if it already existed)
            if ($user->wasRecentlyCreated) {
                $user->assignRole($role);
            }
        }
    }
}