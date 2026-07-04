<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create permissions
        $permissions = [
            // User management permissions
            ['name' => 'view_users',    'display_name' => 'View Users',    'group' => 'users'],
            ['name' => 'create_users',  'display_name' => 'Create Users',  'group' => 'users'],
            ['name' => 'edit_users',    'display_name' => 'Edit Users',    'group' => 'users'],
            ['name' => 'delete_users',  'display_name' => 'Delete Users',  'group' => 'users'],

            // Role management permissions
            ['name' => 'view_roles',    'display_name' => 'View Roles',    'group' => 'roles'],
            ['name' => 'create_roles',  'display_name' => 'Create Roles',  'group' => 'roles'],
            ['name' => 'edit_roles',    'display_name' => 'Edit Roles',    'group' => 'roles'],
            ['name' => 'delete_roles',  'display_name' => 'Delete Roles',  'group' => 'roles'],

            // Tour management permissions
            ['name' => 'view_tours',    'display_name' => 'View Tours',    'group' => 'tours'],
            ['name' => 'create_tours',  'display_name' => 'Create Tours',  'group' => 'tours'],
            ['name' => 'edit_tours',    'display_name' => 'Edit Tours',    'group' => 'tours'],
            ['name' => 'delete_tours',  'display_name' => 'Delete Tours',  'group' => 'tours'],

            // Place management permissions
            ['name' => 'view_places',   'display_name' => 'View Places',   'group' => 'places'],
            ['name' => 'create_places', 'display_name' => 'Create Places', 'group' => 'places'],
            ['name' => 'edit_places',   'display_name' => 'Edit Places',   'group' => 'places'],
            ['name' => 'delete_places', 'display_name' => 'Delete Places', 'group' => 'places'],

            // Booking management permissions
            ['name' => 'view_bookings',   'display_name' => 'View Bookings',   'group' => 'bookings'],
            ['name' => 'create_bookings', 'display_name' => 'Create Bookings', 'group' => 'bookings'],
            ['name' => 'edit_bookings',   'display_name' => 'Edit Bookings',   'group' => 'bookings'],
            ['name' => 'delete_bookings', 'display_name' => 'Delete Bookings', 'group' => 'bookings'],

            // Driver management permissions
            ['name' => 'view_drivers',   'display_name' => 'View Drivers',   'group' => 'drivers'],
            ['name' => 'assign_drivers', 'display_name' => 'Assign Drivers', 'group' => 'drivers'],
            ['name' => 'manage_drivers', 'display_name' => 'Manage Drivers', 'group' => 'drivers'],

            // Guide management permissions
            ['name' => 'view_guides',   'display_name' => 'View Guides',   'group' => 'guides'],
            ['name' => 'assign_guides', 'display_name' => 'Assign Guides', 'group' => 'guides'],
            ['name' => 'manage_guides', 'display_name' => 'Manage Guides', 'group' => 'guides'],

            // System administration permissions
            ['name' => 'view_reports',    'display_name' => 'View Reports',    'group' => 'reports'],
            ['name' => 'manage_settings', 'display_name' => 'Manage Settings', 'group' => 'settings'],
            ['name' => 'view_logs',       'display_name' => 'View Logs',       'group' => 'logs'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission['name']],
                $permission
            );
        }

        // Create roles
        $roles = [
            [
                'name' => 'admin',
                'display_name' => 'Administrator',
                'description' => 'Full system access with all permissions',
                'permissions' => Permission::all()->pluck('name')->toArray()
            ],
            [
                'name' => 'guide',
                'display_name' => 'Tour Guide',
                'description' => 'Can manage tours and view bookings',
                'permissions' => ['view_tours', 'edit_tours', 'view_bookings', 'view_places', 'view_guides']
            ],
            [
                'name' => 'driver',
                'display_name' => 'Driver',
                'description' => 'Can view assigned tours and update status',
                'permissions' => ['view_tours', 'view_bookings', 'view_drivers']
            ],
            [
                'name' => 'customer',
                'display_name' => 'Customer',
                'description' => 'Can view and book tours',
                'permissions' => ['view_tours', 'view_places', 'create_bookings', 'view_bookings']
            ],
        ];

        foreach ($roles as $roleData) {
            $permissions = $roleData['permissions'];
            unset($roleData['permissions']);

            $role = Role::firstOrCreate(
                ['name' => $roleData['name']],
                $roleData
            );

            // Sync permissions for the role
            $role->permissions()->sync(
                Permission::whereIn('name', $permissions)->pluck('id')->toArray()
            );
        }
    }
}
