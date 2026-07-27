<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            MarketplaceSeeder::class,
            CmsContentSeeder::class,
            CustomerCouponSeeder::class,
        ]);

        $this->command->info('Database populated with Northeast India catalog, fleet, users, permissions, and CMS content.');
        $this->command->warn('Admin login: admin@skyslope.com / password');
    }
}
