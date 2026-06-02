<?php

namespace App\Models;

// Change this import:
use Illuminate\Foundation\Auth\User as Authenticatable; 

// Extend Authenticatable instead of Model
class Supervisor extends Authenticatable 
{
    protected $table      = 'supervisors';
    protected $primaryKey = 'supervisor_id';
    public    $timestamps = true;

    protected $guarded  = ['supervisor_id'];
    protected $hidden   = ['password'];

    protected $fillable = [
        'supervisor_name',
        'email',
        'password',
    ];

    public function quotas()
    {
        return $this->hasMany(CinemaMovieQuota::class, 'supervisor_id', 'supervisor_id');
    }
}