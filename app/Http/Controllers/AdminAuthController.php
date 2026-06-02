<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminAuthController extends Controller
{
    // Show the login form
    public function showLoginForm()
    {
        return view('admin.admin_login');
    }

    // Handle the login attempt
    public function login(Request $request)
    {
        // Validating the exact fields from your Supervisor model
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // EXPLICITLY using the 'supervisor' guard we just created
        if (Auth::guard('supervisor')->attempt($credentials)) {
            
            // Regenerate session for security
            $request->session()->regenerate();

            // Redirect to your admin panel
            return redirect()->route('admin.panel');
        }

        // Boot them back if credentials fail
        return back()->withErrors([
            'email' => 'Access Denied. Invalid supervisor credentials.',
        ])->onlyInput('email');
    }

    // Handle logout
    public function logout(Request $request)
    {
        // Logout using the specific guard
        Auth::guard('supervisor')->logout();
        
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}