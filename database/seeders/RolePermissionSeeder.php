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
            ['name' => 'view_users', 'display_name' => 'View Users', 'module' => 'users'],
            ['name' => 'create_users', 'display_name' => 'Create Users', 'module' => 'users'],
            ['name' => 'edit_users', 'display_name' => 'Edit Users', 'module' => 'users'],
            ['name' => 'delete_users', 'display_name' => 'Delete Users', 'module' => 'users'],

            // Role management permissions
            ['name' => 'view_roles', 'display_name' => 'View Roles', 'module' => 'roles'],
            ['name' => 'create_roles', 'display_name' => 'Create Roles', 'module' => 'roles'],
            ['name' => 'edit_roles', 'display_name' => 'Edit Roles', 'module' => 'roles'],
            ['name' => 'delete_roles', 'display_name' => 'Delete Roles', 'module' => 'roles'],

            // Tour management permissions
            ['name' => 'view_tours', 'display_name' => 'View Tours', 'module' => 'tours'],
            ['name' => 'create_tours', 'display_name' => 'Create Tours', 'module' => 'tours'],
            ['name' => 'edit_tours', 'display_name' => 'Edit Tours', 'module' => 'tours'],
            ['name' => 'delete_tours', 'display_name' => 'Delete Tours', 'module' => 'tours'],

            // Place management permissions
            ['name' => 'view_places', 'display_name' => 'View Places', 'module' => 'places'],
            ['name' => 'create_places', 'display_name' => 'Create Places', 'module' => 'places'],
            ['name' => 'edit_places', 'display_name' => 'Edit Places', 'module' => 'places'],
            ['name' => 'delete_places', 'display_name' => 'Delete Places', 'module' => 'places'],

            // Booking management permissions
            ['name' => 'view_bookings', 'display_name' => 'View Bookings', 'module' => 'bookings'],
            ['name' => 'create_bookings', 'display_name' => 'Create Bookings', 'module' => 'bookings'],
            ['name' => 'edit_bookings', 'display_name' => 'Edit Bookings', 'module' => 'bookings'],
            ['name' => 'delete_bookings', 'display_name' => 'Delete Bookings', 'module' => 'bookings'],

            // Driver management permissions
            ['name' => 'view_drivers', 'display_name' => 'View Drivers', 'module' => 'drivers'],
            ['name' => 'assign_drivers', 'display_name' => 'Assign Drivers', 'module' => 'drivers'],
            ['name' => 'manage_drivers', 'display_name' => 'Manage Drivers', 'module' => 'drivers'],

            // Guide management permissions
            ['name' => 'view_guides', 'display_name' => 'View Guides', 'module' => 'guides'],
            ['name' => 'assign_guides', 'display_name' => 'Assign Guides', 'module' => 'guides'],
            ['name' => 'manage_guides', 'display_name' => 'Manage Guides', 'module' => 'guides'],

            // System administration permissions
            ['name' => 'view_reports', 'display_name' => 'View Reports', 'module' => 'reports'],
            ['name' => 'manage_settings', 'display_name' => 'Manage Settings', 'module' => 'settings'],
            ['name' => 'view_logs', 'display_name' => 'View Logs', 'module' => 'logs'],
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
