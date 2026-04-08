<?php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Employee extends Authenticatable
{
    use Notifiable;

    protected $table = 'employees';
    protected $primaryKey = 'employee_id';

    // Mass assignable fields
    protected $fillable = [
        'cinema_id',
        'name',
        'email_address',
        'password',
        'is_email_verified',
        'passport_image_path',
        'gender',
    ];

    // Hide the password from arrays (security first, dude)
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Tell Laravel to use email_address for Auth instead of 'email'
     */
    public function getAuthIdentifierName()
    {
        return 'email_address';
    }

    public function getAuthIdentifier()
{
    return $this->getAttribute('employee_id'); // This is what goes into the session (the number)
}
}