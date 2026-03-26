<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CinemaMovieQuota extends Model
{
    protected $table      = 'cinema_movie_quotas';
    protected $primaryKey = 'id';
    public    $timestamps = true;

    protected $fillable = [
        'movie_id',
        'cinema_id',
        'supervisor_id',
        'showtime_slots',
        'start_date',
        'maximum_end_date',
    ];

    protected $casts = [
        'start_date'       => 'date',
        'maximum_end_date' => 'date',
    ];

    public function movie()
    {
        return $this->belongsTo(Movie::class, 'movie_id', 'movie_id');
    }

    public function cinema()
    {
        return $this->belongsTo(Cinema::class, 'cinema_id', 'cinema_id');
    }

    public function supervisor()
    {
        return $this->belongsTo(Supervisor::class, 'supervisor_id', 'supervisor_id');
    }
}