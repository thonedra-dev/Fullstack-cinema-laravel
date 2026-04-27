<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ManualSignupController extends Controller
{
    private const SESSION_CUSTOMER_ID = 'manual_signup_customer_id';

    public function start(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email_address' => ['required', 'email', 'max:255'],
            'terms' => ['accepted'],
        ]);

        $email = strtolower($validated['email_address']);
        $customer = Customer::where('email_address', $email)->first();

        if ($customer && ($customer->password || $customer->google_id)) {
            throw ValidationException::withMessages([
                'email_address' => 'This email already has an account. Please sign in instead.',
            ]);
        }

        $code = $this->newVerificationCode();

        $customer = Customer::updateOrCreate(
            ['email_address' => $email],
            [
                'name' => $validated['name'],
                'verification_code' => $code,
                'verification_code_expires_at' => now()->addMinutes(3),
                'is_verified' => false,
                'email_verified_at' => null,
            ]
        );

        $this->sendVerificationCode($customer, $code);

        $request->session()->put(self::SESSION_CUSTOMER_ID, $customer->getKey());

        return response()->json([
            'message' => 'Verification code sent.',
            'email' => $this->maskEmail($customer->email_address),
            'expires_in' => 180,
        ]);
    }

    public function verify(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'verification_code' => ['required', 'digits:6'],
        ]);

        $customer = $this->currentSignupCustomer($request);

        if (! $customer) {
            return response()->json([
                'message' => 'Your signup session expired. Please start again.',
            ], 409);
        }

        if (! $customer->verification_code || ! $customer->verification_code_expires_at) {
            throw ValidationException::withMessages([
                'verification_code' => 'Please request a fresh verification code.',
            ]);
        }

        if (now()->greaterThan($customer->verification_code_expires_at)) {
            throw ValidationException::withMessages([
                'verification_code' => 'This code has expired. Please resend a new code.',
            ]);
        }

        if (! hash_equals((string) $customer->verification_code, (string) $validated['verification_code'])) {
            throw ValidationException::withMessages([
                'verification_code' => 'The verification code is incorrect.',
            ]);
        }

        $customer->forceFill([
            'is_verified' => true,
            'email_verified_at' => now(),
            'verification_code' => null,
            'verification_code_expires_at' => null,
        ])->save();

        return response()->json([
            'message' => 'Email verified. Set your password to finish.',
        ]);
    }

    public function resend(Request $request): JsonResponse
    {
        $customer = $this->currentSignupCustomer($request);

        if (! $customer || $customer->password || $customer->google_id) {
            return response()->json([
                'message' => 'Please start signup again.',
            ], 409);
        }

        $code = $this->newVerificationCode();

        $customer->forceFill([
            'verification_code' => $code,
            'verification_code_expires_at' => now()->addMinutes(3),
            'is_verified' => false,
            'email_verified_at' => null,
        ])->save();

        $this->sendVerificationCode($customer, $code);

        return response()->json([
            'message' => 'A new verification code has been sent.',
            'email' => $this->maskEmail($customer->email_address),
            'expires_in' => 180,
        ]);
    }

    public function complete(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $customer = $this->currentSignupCustomer($request);

        if (! $customer) {
            return response()->json([
                'message' => 'Your signup session expired. Please start again.',
            ], 409);
        }

        if (! $customer->is_verified) {
            throw ValidationException::withMessages([
                'password' => 'Please verify your email before setting a password.',
            ]);
        }

        $avatarPath = $customer->avatar;

        if ($request->hasFile('avatar')) {
            $avatarPath = $this->storeAvatar($request, $customer);
        }

        $customer->forceFill([
            'password' => Hash::make($validated['password']),
            'avatar' => $avatarPath,
        ])->save();

        Auth::guard('customer')->login($customer);
        $request->session()->forget(self::SESSION_CUSTOMER_ID);
        $request->session()->regenerate();

        return response()->json([
            'message' => 'Signup complete.',
            'redirect' => url('/users/homepage'),
        ]);
    }

    private function currentSignupCustomer(Request $request): ?Customer
    {
        $customerId = $request->session()->get(self::SESSION_CUSTOMER_ID);

        if (! $customerId) {
            return null;
        }

        return Customer::find($customerId);
    }

    private function newVerificationCode(): string
    {
        return (string) random_int(100000, 999999);
    }

    private function sendVerificationCode(Customer $customer, string $code): void
    {
        Mail::raw(
            "Your CinemaX verification code is {$code}.\n\nThis code expires in 3 minutes.",
            function ($message) use ($customer) {
                $message
                    ->to($customer->email_address, $customer->name)
                    ->subject('Your CinemaX verification code');
            }
        );
    }

    private function storeAvatar(Request $request, Customer $customer): string
    {
        $directory = public_path('images/customers');

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $file = $request->file('avatar');
        $filename = 'customer_' . $customer->getKey() . '_' . Str::uuid() . '.' . $file->getClientOriginalExtension();

        $file->move($directory, $filename);

        return 'images/customers/' . $filename;
    }

    private function maskEmail(string $email): string
    {
        [$name, $domain] = explode('@', $email, 2);
        $visible = substr($name, 0, 2);

        return $visible . str_repeat('*', max(strlen($name) - 2, 2)) . '@' . $domain;
    }
}
