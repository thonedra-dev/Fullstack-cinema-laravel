<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class GoogleController extends Controller
{
    private const SESSION_INTENT = 'google_auth_intent';

    public function redirect(Request $request)
    {
        $intent = $request->query('intent') === 'login' ? 'login' : 'signup';
        $request->session()->put(self::SESSION_INTENT, $intent);

        return Socialite::driver('google')->redirect();
    }

    public function callback(Request $request)
    {
        try {
            $googleUser = Socialite::driver('google')->user();
            $intent = $request->session()->pull(self::SESSION_INTENT, 'signup');
            $email = strtolower($googleUser->getEmail());

            if ($intent === 'login') {
                $customer = Customer::where('google_id', $googleUser->getId())->first()
                    ?? Customer::where('email_address', $email)->first();

                if (! $customer) {
                    $customer = new Customer(['email_address' => $email]);
                }

                if ($customer->google_id && $customer->google_id !== $googleUser->getId()) {
                    return redirect()
                        ->route('users.login')
                        ->withErrors(['google' => 'This email is connected to a different Google account.']);
                }

                $customer->forceFill([
                    'name' => $customer->name ?: $googleUser->getName(),
                    'google_id' => $googleUser->getId(),
                    'avatar' => $customer->avatar ?: $googleUser->getAvatar(),
                    'is_verified' => true,
                    'email_verified_at' => $customer->email_verified_at ?: now(),
                    'verification_code' => null,
                    'verification_code_expires_at' => null,
                ])->save();

                Auth::guard('customer')->login($customer);
                $this->rememberCustomerSession($request, $customer);

                $request->session()->regenerate();

                return redirect()->route('home');
            }

            Customer::updateOrCreate(
                ['email_address' => $email],
                [
                    'name' => $googleUser->getName(),
                    'google_id' => $googleUser->getId(),
                    'avatar' => $googleUser->getAvatar(),
                    'is_verified' => true,
                    'email_verified_at' => now(),
                ]
            );

            Auth::guard('customer')->logout();
            $request->session()->forget([
                'customer_user_id',
                'customer_name',
                'customer_email',
            ]);

            return redirect()
                ->route('users.login')
                ->with('status', 'Google signup is complete. Sign in to continue.');
        } catch (Throwable $e) {
            return redirect()
                ->route('users.login')
                ->withErrors(['google' => 'Google authentication failed. Please try again.']);
        }
    }

    private function rememberCustomerSession(Request $request, Customer $customer): void
    {
        $request->session()->put([
            'customer_user_id' => $customer->getKey(),
            'customer_name' => $customer->name,
            'customer_email' => $customer->email_address,
        ]);
    }
}
