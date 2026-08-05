<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $table      = 'payments';
    protected $primaryKey = 'payment_id';
    public    $timestamps = true;

    protected $fillable = [
        'booking_id',
        'amount_paid',
        'stripe_intent_id',
        'payment_status',
    ];

    protected $casts = [
        'amount_paid' => 'decimal:2',
    ];

    /* ── Relationships ─────────────────────────────────── */

    public function booking()
    {
        return $this->belongsTo(Booking::class, 'booking_id', 'booking_id');
    }
}