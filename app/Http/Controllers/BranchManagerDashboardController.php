<?php

namespace App\Http\Controllers;

use App\Models\Cinema;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // 👈 Added the Auth Facade

class BranchManagerDashboardController extends Controller
{
    /**
     * Guard helper — redirect to login if session missing.
     */
    private function guardOrRedirect()
    {
        // ✨ FIXED: Check the native guard instead of the old raw session key
        if (!Auth::guard('manager')->check() || !session('bm_cinema_id')) {
            return redirect()->route('manager.login');
        }
        return null;
    }

    /**
     * Show the branch manager home (4-portal dashboard).
     *
     * GET /manager/home
     */
    public function home()
    {
        if ($redirect = $this->guardOrRedirect()) return $redirect;

        $cinema = Cinema::with('city')->findOrFail(session('bm_cinema_id'));

        return view('branch_manager.cinema_homepage', compact('cinema'));
    }

    /**
     * Show the cinema profile portal.
     *
     * GET /manager/cinema/profile
     */
    public function cinemaProfile()
    {
        if ($redirect = $this->guardOrRedirect()) return $redirect;

        $cinema = Cinema::with('city')->findOrFail(session('bm_cinema_id'));

        return view('branch_manager.cinema_profile', compact('cinema'));
    }
}