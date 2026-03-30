<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Theatre extends Model
{
    protected $table      = 'theatres';
    protected $primaryKey = 'theatre_id';
    public    $timestamps = false;

    protected $fillable = [
        'theatre_name',
        'theatre_icon',
        'theatre_poster',
        'cinema_id',
    ];

    /**
     * A theatre belongs to a cinema.
     */
    public function cinema()
    {
        return $this->belongsTo(Cinema::class, 'cinema_id', 'cinema_id');
    }

    /**
     * A theatre has many services (via theatre_services pivot table).
     */
    public function services()
    {
        return $this->belongsToMany(
            Service::class,
            'theatre_services',
            'theatre_id',
            'service_id'
        );
    }

    /**
     * A theatre has many seats.
     */
    public function seats()
    {
        return $this->hasMany(Seat::class, 'theatre_id', 'theatre_id')
                    ->orderBy('row_label')
                    ->orderBy('seat_number');
    }

    public function showtimes()
    {
        return $this->hasMany(Showtime::class, 'theatre_id', 'theatre_id');
    }
}
