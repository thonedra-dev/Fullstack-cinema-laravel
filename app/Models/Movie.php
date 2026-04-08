<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Movie extends Model
{
    protected $table      = 'movies';
    protected $primaryKey = 'movie_id';
    public    $timestamps = true;

    protected $fillable = [
        'movie_name',
        'runtime',
        'language',
        'production_name',
        'landscape_poster',
        'portrait_poster',
    ];

    /**
     * A movie belongs to many genres via movie_genres pivot.
     */
    public function genres()
    {
        return $this->belongsToMany(
            Genre::class,
            'movie_genres',
            'movie_id',
            'genre_id'
        );
    }

    /**
     * A movie has many cinema quota assignments.
     */
    public function quotas()
    {
        return $this->hasMany(CinemaMovieQuota::class, 'movie_id', 'movie_id');
    }

    /**
     * A movie belongs to many cinemas via cinema_movie_quotas.
     */
    public function cinemas()
    {
        return $this->belongsToMany(
            Cinema::class,
            'cinema_movie_quotas',
            'movie_id',
            'cinema_id'
        )->withPivot('supervisor_id', 'showtime_slots', 'start_date', 'maximum_end_date');
    }

    /**
     * A movie has many trailers (YouTube links).
     */
    public function trailers()
    {
        return $this->hasMany(Trailer::class, 'movie_id', 'movie_id');
    }
}