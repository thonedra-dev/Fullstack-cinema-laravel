<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    protected $table      = 'cities';
    protected $primaryKey = 'city_id';
    public    $timestamps = false;

    protected $fillable = [
        'city_name',
        'city_state',
    ];

    /**
     * A city has many cinemas.
     */
    public function cinemas()
    {
        return $this->hasMany(Cinema::class, 'city_id', 'city_id');
    }
}