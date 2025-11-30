<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'role',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the bookings for the user.
     */
    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * Get the car rentals for the user.
     */
    public function carRentals()
    {
        return $this->hasMany(CarRental::class);
    }

    /**
     * Get the user logins for the user.
     */
    public function userLogins()
    {
        return $this->hasMany(UserLogin::class);
    }

    /**
     * Get the tours where the user is a driver.
     */
    public function tourDrivers()
    {
        return $this->hasMany(TourDriver::class);
    }

    /**
     * Get the tours where the user is a guide.
     */
    public function tourGuides()
    {
        return $this->hasMany(TourGuide::class);
    }

    /**
     * Get the roles for the user.
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_user');
    }

    /**
     * Get the permissions for the user through their roles.
     */
    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'role_permissions')
            ->join('role_user', 'roles.id', '=', 'role_user.role_id')
            ->join('users', 'role_user.user_id', '=', 'users.id')
            ->where('roles.active', true)
            ->where('permissions.active', true);
    }

    /**
     * Check if user has a specific role
     */
    public function hasRole(string $role): bool
    {
        return $this->roles()->where('name', $role)->exists();
    }

    /**
     * Check if user has any of the given roles
     */
    public function hasAnyRole(array $roles): bool
    {
        return $this->roles()->whereIn('name', $roles)->exists();
    }

    /**
     * Check if user has all of the given roles
     */
    public function hasAllRoles(array $roles): bool
    {
        $userRoles = $this->roles()->pluck('name')->toArray();
        return empty(array_diff($roles, $userRoles));
    }

    /**
     * Check if user has a specific permission
     */
    public function hasPermission(string $permission): bool
    {
        return $this->permissions()->where('name', $permission)->exists();
    }

    /**
     * Check if user has any of the given permissions
     */
    public function hasAnyPermission(array $permissions): bool
    {
        return $this->permissions()->whereIn('name', $permissions)->exists();
    }

    /**
     * Check if user has all of the given permissions
     */
    public function hasAllPermissions(array $permissions): bool
    {
        $userPermissions = $this->permissions()->pluck('name')->toArray();
        return empty(array_diff($permissions, $userPermissions));
    }

    /**
     * Assign a role to the user
     */
    public function assignRole(string $role): void
    {
        $roleModel = Role::where('name', $role)->first();

        if ($roleModel && !$this->hasRole($role)) {
            $this->roles()->attach($roleModel->id);
        }
    }

    /**
     * Remove a role from the user
     */
    public function removeRole(string $role): void
    {
        $roleModel = Role::where('name', $role)->first();

        if ($roleModel) {
            $this->roles()->detach($roleModel->id);
        }
    }

    /**
     * Get the user's primary role (first role)
     */
    public function getPrimaryRole(): ?Role
    {
        return $this->roles()->first();
    }

    /**
     * Check if user is an admin
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    /**
     * Check if user is a guide
     */
    public function isGuide(): bool
    {
        return $this->hasRole('guide');
    }

    /**
     * Check if user is a driver
     */
    public function isDriver(): bool
    {
        return $this->hasRole('driver');
    }

    /**
     * Check if user is a customer
     */
    public function isCustomer(): bool
    {
        return $this->hasRole('customer');
    }
}
