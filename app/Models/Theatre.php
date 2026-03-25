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
            'theatre_services',   // pivot table name
            'theatre_id',         // FK on pivot pointing to this model
            'service_id'          // FK on pivot pointing to related model
        );
    }
}