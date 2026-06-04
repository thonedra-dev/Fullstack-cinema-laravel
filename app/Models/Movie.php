<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

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

    public function ticketPrices()
    {
        return $this->hasMany(MovieTicketPrice::class, 'movie_id', 'movie_id');
    }

    // =========================================================================
    //  SCOPE: nowShowing
    // =========================================================================
    /**
     * Restrict the query to movies that are still actively showing.
     *
     * A movie is considered "now showing" when at least ONE of its
     * cinema_movie_quotas has a maximum_end_date >= today.
     *
     * Because the same movie_id can appear in multiple quota rows
     * (one per cinema), we use a whereHas() existence sub-query so
     * Eloquent generates a single EXISTS(...) clause — far cheaper
     * than a JOIN that would fan out rows and require DISTINCT.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeNowShowing($query)
    {
        return $query->whereHas('quotas', function ($q) {
            $q->whereDate('maximum_end_date', '>=', now()->toDateString());
        });
    }
}