<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $table      = 'services';
    protected $primaryKey = 'service_id';
    public    $timestamps = false;

    protected $fillable = [
        'service_name',
        'service_icon',
    ];

    /**
     * A service belongs to many theatres (via theatre_services pivot table).
     */
    public function theatres()
    {
        return $this->belongsToMany(
            Theatre::class,
            'theatre_services',   // pivot table name
            'service_id',         // FK on pivot pointing to this model
            'theatre_id'          // FK on pivot pointing to related model
        );
    }
}