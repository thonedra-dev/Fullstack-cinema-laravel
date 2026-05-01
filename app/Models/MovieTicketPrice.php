<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MovieTicketPrice extends Model
{
    protected $table = 'movie_ticket_prices';
    protected $primaryKey = 'price_id';
    public $timestamps = true;

    protected $fillable = [
        'movie_id',
        'theatre_id',
        'seat_type',
        'day_type',
        'price',
    ];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    public function movie()
    {
        return $this->belongsTo(Movie::class, 'movie_id', 'movie_id');
    }

    public function theatre()
    {
        return $this->belongsTo(Theatre::class, 'theatre_id', 'theatre_id');
    }
}
