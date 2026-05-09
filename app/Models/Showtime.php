<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Showtime extends Model
{
    protected $table      = 'showtimes';
    protected $primaryKey = 'showtime_id';
    public    $timestamps = true;

    protected $fillable = [
        'hall_id',
        'movie_id',
        'cinema_id',
        'start_time',
        'end_time',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time'   => 'datetime',
    ];

    public function hall()
    {
        return $this->belongsTo(Hall::class, 'hall_id', 'hall_id');
    }

    public function getTheatreIdAttribute($value)
    {
        return $value ?? $this->hall?->theatre_id;
    }

    public function theatre()
    {
        return $this->hasOneThrough(
            Theatre::class,
            Hall::class,
            'hall_id',
            'theatre_id',
            'hall_id',
            'theatre_id'
        );
    }

    public function cinema()
    {
        return $this->hasOneThrough(
            Cinema::class,
            Hall::class,
            'hall_id',
            'cinema_id',
            'hall_id',
            'cinema_id'
        );
    }

    public function movie()
    {
        return $this->belongsTo(Movie::class, 'movie_id', 'movie_id');
    }
}
