<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShowtimeProposalStatus extends Model
{
    protected $table      = 'showtime_proposal_status';
    protected $primaryKey = 'id';
    public    $timestamps = true;

    protected $fillable = [
        'movie_id',
        'cinema_id',
        'manager_id',
        'status',      // 'pending', 'approved', 'rejected'
        'admin_note',
    ];

    // Fetches all the individual proposed slots tied to this batch
    public function proposals()
    {
        return $this->hasMany(ShowtimeProposal::class, 'status_id', 'id');
    }

    public function movie()
    {
        return $this->belongsTo(Movie::class, 'movie_id', 'movie_id');
    }

    public function cinema()
    {
        return $this->belongsTo(Cinema::class, 'cinema_id', 'cinema_id');
    }

    public function manager()
    {
        return $this->belongsTo(Manager::class, 'manager_id', 'manager_id');
    }
}