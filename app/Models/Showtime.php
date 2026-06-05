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

    // =========================================================================
    //  ACCESSOR — is_bookable
    // =========================================================================
    /**
     * A showtime is considered bookable when its start_time is at least
     * 15 minutes from now.  This is the single source of truth used by
     * both the Blade template (inline disabled styling) and any downstream
     * logic that needs the same cutoff.
     *
     * Accessible as:  $showtime->is_bookable  (Laravel magic accessor)
     *
     * @return bool
     */
    public function getIsBookableAttribute(): bool
    {
        if (!$this->start_time) return false;

        // now()->addMinutes(15) = the earliest start_time we will accept
        return $this->start_time->gt(now()->addMinutes(15));
    }

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