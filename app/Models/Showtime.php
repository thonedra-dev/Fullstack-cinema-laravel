<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Showtime extends Model
{
    protected $table      = 'showtimes';
    protected $primaryKey = 'showtime_id';
    public    $timestamps = true;

    protected $fillable = [
        'theatre_id',
        'movie_id',
        'start_time',
        'end_time',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time'   => 'datetime',
    ];

    public function theatre()
    {
        return $this->belongsTo(Theatre::class, 'theatre_id', 'theatre_id');
    }

    public function movie()
    {
        return $this->belongsTo(Movie::class, 'movie_id', 'movie_id');
    }
}