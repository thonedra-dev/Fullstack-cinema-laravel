<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supervisor extends Model
{
    protected $table      = 'supervisors';
    protected $primaryKey = 'supervisor_id';
    public    $timestamps = true;

    /**
     * Columns that should never be mass-assigned or returned in JSON.
     */
    protected $guarded  = ['supervisor_id'];
    protected $hidden   = ['password'];

    protected $fillable = [
        'supervisor_name',
        'email',
        'password',
    ];

    /**
     * A supervisor has approved many cinema movie quotas.
     */
    public function quotas()
    {
        return $this->hasMany(CinemaMovieQuota::class, 'supervisor_id', 'supervisor_id');
    }
}