<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class CustomerLoginController extends Controller
{
    public function showLogin()
    {
        return view('users.login', [
            'slides' => $this->cinematicSlides(),
        ]);
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'email_address' => ['required', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ]);

        $customer = Customer::where('email_address', strtolower($validated['email_address']))->first();

        if ($customer && ! $customer->password && $customer->google_id) {
            throw ValidationException::withMessages([
                'email_address' => 'This account uses Google sign-in.',
            ]);
        }

        if (! $customer || ! $customer->password || ! Hash::check($validated['password'], $customer->password)) {
            throw ValidationException::withMessages([
                'email_address' => 'The email or password is incorrect.',
            ]);
        }

        if (! $customer->is_verified) {
            throw ValidationException::withMessages([
                'email_address' => 'Please verify your email before signing in.',
            ]);
        }

        Auth::guard('customer')->login($customer, (bool) ($validated['remember'] ?? false));
        $this->rememberCustomerSession($request, $customer);

        $request->session()->regenerate();

        return redirect()->route('home');
    }

    public function logout(Request $request)
    {
        Auth::guard('customer')->logout();

        $request->session()->forget([
            'customer_user_id',
            'customer_name',
            'customer_email',
        ]);

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    private function cinematicSlides(): array
    {
        $directory = public_path('images/cinematic');

        if (! is_dir($directory)) {
            return [];
        }

        $order = [
            'one' => 1,
            'two' => 2,
            'three' => 3,
            'four' => 4,
            'five' => 5,
            'six' => 6,
            'seven' => 7,
            'eight' => 8,
            'nine' => 9,
        ];

        return collect(File::files($directory))
            ->filter(fn ($file) => in_array(strtolower($file->getExtension()), ['jpg', 'jpeg', 'png', 'webp']))
            ->sortBy(function ($file) use ($order) {
                $name = strtolower($file->getFilenameWithoutExtension());

                foreach ($order as $word => $position) {
                    if (str_contains($name, $word)) {
                        return $position;
                    }
                }

                return 100 . $name;
            })
            ->map(fn ($file) => asset('images/cinematic/' . $file->getFilename()))
            ->values()
            ->all();
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
