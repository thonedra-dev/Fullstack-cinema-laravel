<?php

namespace App\Models;

// Changed from Illuminate\Database\Eloquent\Model to Pivot
use Illuminate\Database\Eloquent\Relations\Pivot;

class BranchManager extends Pivot
{
    /*
     * WHY WE EXTEND Pivot INSTEAD OF Model:
     * ─────────────────────────────────────────────────────────────
     * This class acts as an intermediate bridge (pivot) table for a Many-to-Many
     * relationship. 
     * * 1. Laravel requires the pivot class to extend Pivot so eager-loading 
     * methods like Manager::with('cinemas') work properly without crashing.
     * 2. Because Pivot extends Model under the hood, standalone queries like 
     * ::where(), ::with(), ::firstOrCreate(), and ->delete() continue to 
     * work flawlessly across your other controllers.
     * ─────────────────────────────────────────────────────────────
     */

    protected $table        = 'branch_managers';
    public    $timestamps   = false;
    public    $incrementing = false;

    // Composite primary key — tell Eloquent not to auto-increment
    protected $primaryKey = null;

    protected $fillable = [
        'manager_id',
        'cinema_id',
    ];

    /**
     * A branch_manager record belongs to a Manager.
     */
    public function manager()
    {
        return $this->belongsTo(Manager::class, 'manager_id', 'manager_id');
    }

    /**
     * A branch_manager record belongs to a Cinema.
     */
    public function cinema()
    {
        return $this->belongsTo(Cinema::class, 'cinema_id', 'cinema_id');
    }
}