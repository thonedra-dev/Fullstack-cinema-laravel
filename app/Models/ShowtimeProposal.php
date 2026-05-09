<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShowtimeProposal extends Model
{
    protected $table      = 'showtime_proposals';
    protected $primaryKey = 'id';
    public    $timestamps = true;

    /*
     * Schema after migration:
     *   id, manager_id, cinema_id, hall_id, movie_id,
     *   start_datetime (timestamp), end_datetime (timestamp),
     *   status, admin_note, created_at, updated_at
     *
     * selected_dates / start_time / end_time were DROPPED by the alter migration.
     * We now store one row per date — each row is one scheduled slot.
     */
    protected $fillable = [
        'manager_id',
        'cinema_id',
        'hall_id',
        'movie_id',
        'start_datetime',   // full timestamp e.g. 2026-04-08 14:00:00
        'end_datetime',     // full timestamp e.g. 2026-04-08 16:28:00
    ];

    protected $casts = [
        'start_datetime' => 'datetime',
        'end_datetime'   => 'datetime',
    ];

    public function manager()
    {
        return $this->belongsTo(Manager::class, 'manager_id', 'manager_id');
    }

    public function cinema()
    {
        return $this->belongsTo(Cinema::class, 'cinema_id', 'cinema_id');
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

    public function movie()
    {
        return $this->belongsTo(Movie::class, 'movie_id', 'movie_id');
    }
}
