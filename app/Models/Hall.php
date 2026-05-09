<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Hall extends Model
{
    protected $table = 'halls';
    protected $primaryKey = 'hall_id';
    public $timestamps = false;

    protected $fillable = [
        'cinema_id',
        'theatre_id',
    ];

    public function cinema()
    {
        return $this->belongsTo(Cinema::class, 'cinema_id', 'cinema_id');
    }

    public function theatre()
    {
        return $this->belongsTo(Theatre::class, 'theatre_id', 'theatre_id');
    }

    public function showtimes()
    {
        return $this->hasMany(Showtime::class, 'hall_id', 'hall_id');
    }

    public function showtimeProposals()
    {
        return $this->hasMany(ShowtimeProposal::class, 'hall_id', 'hall_id');
    }
}
