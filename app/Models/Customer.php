<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Customer extends Authenticatable
{
    use Notifiable;

    protected $table = 'customers';

    protected $primaryKey = 'user_id';

    protected $fillable = [
        'email_address',
        'name',
        'password',
        'avatar',
        'is_verified',
        'email_verified_at',
        'google_id',
        'verification_code',
        'verification_code_expires_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'verification_code_expires_at' => 'datetime',
        'is_verified' => 'boolean',
    ];
}
