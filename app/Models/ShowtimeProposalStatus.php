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

    /**
     * Relationship: The movie this status belongs to.
     */
    public function movie()
    {
        return $this->belongsTo(Movie::class, 'movie_id', 'movie_id');
    }

    /**
     * Relationship: The cinema this status belongs to.
     */
    public function cinema()
    {
        return $this->belongsTo(Cinema::class, 'cinema_id', 'cinema_id');
    }

    /**
     * Relationship: The manager who submitted this proposal.
     */
    public function manager()
    {
        return $this->belongsTo(Manager::class, 'manager_id', 'manager_id');
    }
}