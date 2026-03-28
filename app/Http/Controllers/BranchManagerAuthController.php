<?php

namespace App\Http\Controllers;

use App\Models\BranchManager;
use App\Models\Manager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class BranchManagerAuthController extends Controller
{
    /**
     * Show the branch manager login page.
     *
     * GET /manager/login
     */
    public function showLogin()
    {
        // Redirect to home if already authenticated
        if (session('bm_manager_id')) {
            return redirect()->route('manager.home');
        }

        return view('branch_manager.manager_login');
    }

    /**
     * Authenticate a branch manager.
     *
     * POST /manager/login
     *
     * Flow:
     *   1. Find manager by email
     *   2. Verify password (bcrypt)
     *   3. Check that manager has a branch_managers assignment
     *   4. Store session keys and redirect to home
     */
    public function login(Request $request)
    {
        $request->validate([
            'manager_email' => 'required|email',
            'password'      => 'required|string',
        ]);

        // Find manager by email
        $manager = Manager::where('manager_email', $request->manager_email)->first();

        if (!$manager || !Hash::check($request->password, $manager->password)) {
            return back()
                ->withInput(['manager_email' => $request->manager_email])
                ->with('bm_login_error', 'Invalid email or password.');
        }

        // Check if this manager has a cinema assignment
        $assignment = BranchManager::where('manager_id', $manager->manager_id)->first();

        if (!$assignment) {
            return back()
                ->withInput(['manager_email' => $request->manager_email])
                ->with('bm_login_error', 'No cinema assigned to this account. Please contact an administrator.');
        }

        // Load cinema for display name
        $cinema = \App\Models\Cinema::with('city')->find($assignment->cinema_id);

        // Store session
        $request->session()->put('bm_manager_id',   $manager->manager_id);
        $request->session()->put('bm_cinema_id',    $assignment->cinema_id);
        $request->session()->put('bm_manager_name', $manager->manager_name);
        $request->session()->put('bm_cinema_name',  $cinema?->cinema_name ?? 'Cinema');

        return redirect()->route('manager.home');
    }

    /**
     * Log out the branch manager.
     *
     * POST /manager/logout
     */
    public function logout(Request $request)
    {
        $request->session()->forget([
            'bm_manager_id',
            'bm_cinema_id',
            'bm_manager_name',
            'bm_cinema_name',
        ]);

        return redirect()->route('manager.login');
    }
}