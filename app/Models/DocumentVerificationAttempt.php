<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentVerificationAttempt extends Model
{
    protected $guarded = ['id'];

    protected $hidden = ['document_fingerprint'];

    protected $casts = ['simulated' => 'boolean', 'completed_at' => 'datetime'];
}
