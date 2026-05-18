<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    protected $table      = 'tickets';
    protected $primaryKey = 'ticket_id';
    public    $timestamps = true;

    protected $fillable = [
        'booking_id',
        'showtime_id',
        'seat_id',
        'price_id',
        'price_paid',
    ];

    protected $casts = [
        'price_paid' => 'decimal:2',
    ];

    /* ── Relationships ─────────────────────────────────── */

    public function booking()
    {
        return $this->belongsTo(Booking::class, 'booking_id', 'booking_id');
    }

    public function seat()
    {
        return $this->belongsTo(Seat::class, 'seat_id', 'seat_id');
    }

    public function showtime()
    {
        return $this->belongsTo(Showtime::class, 'showtime_id', 'showtime_id');
    }

    public function movieTicketPrice()
    {
        return $this->belongsTo(MovieTicketPrice::class, 'price_id', 'price_id');
    }
}