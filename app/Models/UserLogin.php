<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserLogin extends Model
{
    protected $fillable = [
        'user_id',
        'username',
        'password',
        'login_method',
        'token',
        'token_expires_at',
        'active',
    ];

    protected $casts = [
        'token_expires_at' => 'datetime',
        'active' => 'boolean',
    ];

    /**
     * Get the user that owns the login.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
