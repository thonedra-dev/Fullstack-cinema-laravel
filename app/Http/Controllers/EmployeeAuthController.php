<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Employee;

class EmployeeAuthController extends Controller
{
    public function showLogin() {
        return view('employees.login');
    }

   public function login(Request $request) {
    $credentials = $request->validate([
        'email_address' => 'required|email',
        'password' => 'required',
    ]);

    if (Auth::attempt(['email_address' => $credentials['email_address'], 'password' => $credentials['password']])) {
        $request->session()->regenerate();
        return "Login Successful"; // Stop redirect, just show text
    }

    return "Login Failed"; // Stop back redirect, just show text
}
}