<?php

namespace App\Http\Controllers;

use App\Models\BranchManager;
use App\Models\Manager;
use App\Models\Movie;
use App\Models\Cinema;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

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

        // UI Enhancement: Landscape movie posters for the slideshow reel
        $slides = Movie::whereNotNull('landscape_poster')
            ->inRandomOrder()
            ->limit(8)
            ->pluck('landscape_poster')
            ->map(fn($p) => asset('images/movies/' . $p))
            ->toArray();

        return view('branch_manager.manager_login', compact('slides'));
    }

    /**
     * Authenticate a branch manager.
     *
     * POST /manager/login
     *
     * Flow:
     * 1. Find manager by email
     * 2. Verify password (bcrypt)
     * 3. Check that manager has a branch_managers assignment
     * 4. Store session keys and redirect to home
     */
    public function login(Request $request)
    {
        $request->validate([
            'manager_email' => 'required|email',
            'password'      => 'required|string',
        ]);

        // 1. Find manager by email
        $manager = Manager::where('manager_email', $request->manager_email)->first();

        // 2. Verify password
        if (!$manager || !Hash::check($request->password, $manager->password)) {
            return back()
                ->withInput(['manager_email' => $request->manager_email])
                ->with('bm_login_error', 'Invalid email or password.');
        }

        // 3. Check if this manager has a cinema assignment
        $assignment = BranchManager::where('manager_id', $manager->manager_id)->first();

        if (!$assignment) {
            return back()
                ->withInput(['manager_email' => $request->manager_email])
                ->with('bm_login_error', 'No cinema assigned to this account. Please contact an administrator.');
        }

        // Load cinema for display name
        $cinema = Cinema::with('city')->find($assignment->cinema_id);

        // 4. Store comprehensive session data
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