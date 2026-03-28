<?php

namespace App\Http\Controllers;

use App\Models\Cinema;
use Illuminate\Http\Request;

class BranchManagerDashboardController extends Controller
{
    /**
     * Guard helper — redirect to login if session missing.
     */
    private function guardOrRedirect()
    {
        if (!session('bm_manager_id') || !session('bm_cinema_id')) {
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