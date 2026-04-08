<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Employee;

class EmployeeAuthController extends Controller
{
    public function showLogin() {
        return view('users.user_login');
    }

    public function login(Request $request) {
        $credentials = $request->validate([
            'email_address' => 'required|email',
            'password' => 'required',
        ]);

        // Attempt login using the custom 'email_address' column
        if (Auth::attempt(['email_address' => $credentials['email_address'], 'password' => $credentials['password']])) {
            $request->session()->regenerate();
            return redirect()->route('home'); // Redirects to /users/homepage
        }

        return back()->withErrors(['email' => 'Invalid credentials, bro.']);
    }
}