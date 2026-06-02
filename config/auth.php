<?php

use App\Models\Employee;
use App\Models\Customer;
use App\Models\Supervisor;
use App\Models\Manager;

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Defaults
    |--------------------------------------------------------------------------
    */

    'defaults' => [
        'guard' => env('AUTH_GUARD', 'web'),
        'passwords' => env('AUTH_PASSWORD_BROKER', 'users'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication Guards
    |--------------------------------------------------------------------------
    */

    'guards' => [
        // Employee (existing system)
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],

        // Customer (NEW)
        'customer' => [
            'driver' => 'session',
            'provider' => 'customers',
        ],

        'supervisor' => [
            'driver' => 'session',
            'provider' => 'supervisors',
        ],

        'manager' => [
            'driver' => 'session',
            'provider' => 'managers',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | User Providers
    |--------------------------------------------------------------------------
    */

    'providers' => [
        // Employee provider (DO NOT TOUCH)
        'users' => [
            'driver' => 'eloquent',
            'model' => Employee::class,
        ],

        // Customer provider (NEW)
        'customers' => [
            'driver' => 'eloquent',
            'model' => Customer::class,
        ],

        'supervisors' => [
            'driver' => 'eloquent',
            'model' => Supervisor::class,
        ],

        'managers' => [
            'driver' => 'eloquent',
            'model' => Manager::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Resetting Passwords
    |--------------------------------------------------------------------------
    */

    'passwords' => [
        // Employee reset
        'users' => [
            'provider' => 'users',
            'table' => env('AUTH_PASSWORD_RESET_TOKEN_TABLE', 'password_reset_tokens'),
            'expire' => 60,
            'throttle' => 60,
        ],

        // Customer reset (future use)
        'customers' => [
            'provider' => 'customers',
            'table' => env('AUTH_PASSWORD_RESET_TOKEN_TABLE', 'password_reset_tokens'),
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Confirmation Timeout
    |--------------------------------------------------------------------------
    */

    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800),

];