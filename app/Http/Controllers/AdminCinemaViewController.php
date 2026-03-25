<?php

namespace App\Http\Controllers;

use App\Models\Cinema;

class AdminCinemaViewController extends Controller
{
    /**
     * Display all cinemas in a browsable table.
     *
     * GET /admin/cinema
     */
    public function index()
    {
        $cinemas = Cinema::with('city')->orderBy('cinema_id', 'desc')->get();

        return view('admin.view_cinema', compact('cinemas'));
    }
}