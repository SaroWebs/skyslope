<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    // Admin-only — no Sanctum tokens, session auth only
    protected $table = 'users';

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

    // ── RBAC ──────────────────────────────────────────────────────

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user');
    }

    public function assignRole(string $roleName): void
    {
        $role = Role::where('name', $roleName)->first();
        if ($role) {
            $this->roles()->syncWithoutDetaching([$role->id]);
        }
    }

    public function hasRole(string $role): bool
    {
        return $this->roles->pluck('name')->contains($role);
    }

    public function hasPermission(string $permission): bool
    {
        return $this->roles->flatMap(fn($r) => $r->permissions->pluck('name'))->contains($permission);
    }

    // ── Helpers ────────────────────────────────────────────────────

    public function isAdmin(): bool    { return true; }
    public function isDriver(): bool   { return false; }
    public function isCustomer(): bool { return false; }
    public function isGuide(): bool    { return false; }
}
