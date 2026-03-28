<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class BranchManager extends Pivot
{
    protected $table      = 'branch_managers';
    public    $timestamps = false;
    public    $incrementing = false;

    protected $fillable = [
        'manager_id',
        'cinema_id',
    ];

    public function manager()
    {
        return $this->belongsTo(Manager::class, 'manager_id', 'manager_id');
    }

    public function cinema()
    {
        return $this->belongsTo(Cinema::class, 'cinema_id', 'cinema_id');
    }
}