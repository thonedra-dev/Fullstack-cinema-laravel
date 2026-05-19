<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $table      = 'bookings';
    protected $primaryKey = 'booking_id';
    public    $timestamps = true;

   protected $fillable = [
    'user_id', 'cinema_id', 'booking_status',
    'total_amount', 'stripe_payment_intent_id', 'expires_at',
];
protected $casts = [
    'total_amount' => 'decimal:2',
    'expires_at'   => 'datetime',
];

    /* ── Relationships ─────────────────────────────────── */

    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'booking_id', 'booking_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'user_id', 'user_id');
    }
}