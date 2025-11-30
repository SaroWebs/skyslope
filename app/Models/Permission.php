<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    protected $fillable = [
        'name',
        'display_name',
        'description',
        'module',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    /**
     * Get the roles for the permission.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_permissions');
    }

    /**
     * Get the users that have this permission through their roles.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'role_permissions')
            ->join('role_user', 'roles.id', '=', 'role_user.role_id')
            ->join('users', 'role_user.user_id', '=', 'users.id');
    }

    /**
     * Scope a query to only include active permissions.
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Scope a query to only include permissions for a specific module.
     */
    public function scopeByModule($query, string $module)
    {
        return $query->where('module', $module);
    }
}
