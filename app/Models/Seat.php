<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Seat extends Model
{
    protected $table      = 'seats';
    protected $primaryKey = 'seat_id';
    public    $timestamps = true; // seats table has created_at / updated_at

    protected $fillable = [
        'theatre_id',
        'row_label',
        'seat_number',
        'seat_type',
    ];

    /**
     * A seat belongs to a theatre.
     */
    public function theatre()
    {
        return $this->belongsTo(Theatre::class, 'theatre_id', 'theatre_id');
    }
}