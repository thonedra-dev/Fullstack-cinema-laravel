<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cinema extends Model
{
    protected $table      = 'cinemas';
    protected $primaryKey = 'cinema_id';
    public    $timestamps = false;

    protected $fillable = [
        'cinema_name',
        'cinema_address',
        'cinema_contact',
        'cinema_description',
        'cinema_picture',
        'city_id',
    ];

    /**
     * A cinema belongs to a city.
     */
    public function city()
    {
        return $this->belongsTo(City::class, 'city_id', 'city_id');
    }

    public function halls()
    {
        return $this->hasMany(Hall::class, 'cinema_id', 'cinema_id');
    }

    /**
     * A cinema has many theatre types through its halls.
     */
    public function theatres()
    {
        return $this->belongsToMany(
            Theatre::class,
            'halls',
            'cinema_id',
            'theatre_id'
        )->withPivot('hall_id');
    }

    public function showtimes()
    {
        return $this->hasManyThrough(
            Showtime::class,
            Hall::class,
            'cinema_id',
            'hall_id',
            'cinema_id',
            'hall_id'
        );
    }

    /**
     * A cinema has many assigned movies (via cinema_movie_quotas).
     */
    public function movies()
    {
        return $this->belongsToMany(
            Movie::class,
            'cinema_movie_quotas',
            'cinema_id',
            'movie_id'
        )->withPivot('supervisor_id', 'showtime_slots', 'start_date', 'maximum_end_date');
    }
}
