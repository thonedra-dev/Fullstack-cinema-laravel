<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Manager extends Model
{
    protected $table      = 'managers';
    protected $primaryKey = 'manager_id';
    public    $timestamps = true;

    protected $hidden   = ['password'];

    protected $fillable = [
        'manager_name',
        'manager_email',
        'manager_passport_pic',
        'password',
    ];

    /**
     * A manager can be assigned to many cinemas (via branch_managers pivot).
     */
    public function cinemas()
    {
        return $this->belongsToMany(
            Cinema::class,
            'branch_managers',
            'manager_id',
            'cinema_id'
        );
    }
}