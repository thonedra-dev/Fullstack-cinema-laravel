<?php

namespace App\Http\Controllers;

use App\Models\Movie;

class AdminMovieLiveController extends Controller
{
    /* ─────────────────────────────────────────────────────────────
       NOW SHOWING (tab)
       GET /admin/movies/now-showing
    ───────────────────────────────────────────────────────────── */
    public function nowShowing()
    {
        $activeTab = 'now_showing';

        $nowShowingMovies = Movie::with('genres')
            ->hasLiveShowtime()
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.admin_movies', compact('nowShowingMovies', 'activeTab'));
    }
}