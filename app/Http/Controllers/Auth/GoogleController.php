<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Laravel\Socialite\Facades\Socialite;
use App\Models\Customer;
use Illuminate\Support\Facades\Auth;

class GoogleController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();

            $user = Customer::updateOrCreate(
                ['email_address' => $googleUser->getEmail()],
                [
                    'name' => $googleUser->getName(),
                    'google_id' => $googleUser->getId(),
                    'avatar' => $googleUser->getAvatar(),
                    'is_verified' => true,
                    'email_verified_at' => now(),
                ]
            );

            Auth::guard('customer')->login($user);

            return redirect('/users/homepage');

        } catch (\Exception $e) {
            dd($e->getMessage());
        }
    }
}