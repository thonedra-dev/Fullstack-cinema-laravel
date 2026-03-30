<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BranchManager extends Model
{
    /*
     * WHY Model AND NOT Pivot:
     * ─────────────────────────────────────────────────────────────
     * This class is used in two ways:
     *   1. As a pivot record inside Manager::cinemas() belongsToMany
     *      — works fine with Model, you can still pass it via ->using()
     *   2. As a standalone queryable class with ::where(), ::with(),
     *      ::firstOrCreate(), ->delete() in controllers
     *      — these static methods ONLY exist on Model, not on Pivot
     *
     * Extending Pivot breaks usage #2. Extending Model supports both.
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