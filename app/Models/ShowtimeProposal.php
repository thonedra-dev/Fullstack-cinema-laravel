<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShowtimeProposal extends Model
{
    protected $table      = 'showtime_proposals';
    protected $primaryKey = 'id';
    public    $timestamps = true;

    protected $fillable = [
        'status_id',        // New FK pointing to showtime_proposal_status
        'hall_id',
        'start_datetime',
        'end_datetime',
    ];

    protected $casts = [
        'start_datetime' => 'datetime',
        'end_datetime'   => 'datetime',
    ];

    // Connects this slot to the main proposal batch
    public function status()
    {
        return $this->belongsTo(ShowtimeProposalStatus::class, 'status_id', 'id');
    }

    public function hall()
    {
        return $this->belongsTo(Hall::class, 'hall_id', 'hall_id');
    }

    public function getTheatreIdAttribute($value)
    {
        return $value ?? $this->hall?->theatre_id;
    }

    public function theatre()
    {
        return $this->hasOneThrough(
            Theatre::class,
            Hall::class,
            'hall_id',
            'theatre_id',
            'hall_id',
            'theatre_id'
        );
    }

    // Pass-through relationships via the parent status
    public function manager()
    {
        return $this->status?->manager();
    }

    public function cinema()
    {
        return $this->status?->cinema();
    }

    public function movie()
    {
        return $this->status?->movie();
    }
}