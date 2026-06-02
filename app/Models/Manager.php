<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class Manager extends Authenticatable
{
    protected $table      = 'managers';
    protected $primaryKey = 'manager_id';
    public    $timestamps = true;

    // Hide password from array/JSON serialization
    protected $hidden  = ['password'];

    // Safely allow mass-assignment for these specific columns
    protected $fillable = [
        'manager_name',
        'manager_email',
        'password',
    ];

    /**
     * A manager can be assigned to multiple cinemas via the branch_managers pivot.
     */
    public function cinemas()
    {
        return $this->belongsToMany(
            Cinema::class,
            'branch_managers',
            'manager_id',
            'cinema_id'
        )->using(BranchManager::class);
    }
}