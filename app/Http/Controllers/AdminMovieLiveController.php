<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;

use App\Models\Movie;

class AdminMovieLiveController extends Controller
{
    /* ─────────────────────────────────────────────────────────────
       NOW SHOWING (tab)
       GET /admin/movies/now-showing
    ───────────────────────────────────────────────────────────── */
   public function nowShowing(Request $request)
{
    $activeTab = 'now_showing';

    $nowShowingMovies = Movie::with('genres')
        ->hasLiveShowtime()
        ->orderBy('created_at', 'desc')
        ->get();

    if ($request->ajax()) {
        return view('admin.partials.now_showing_movies', compact('nowShowingMovies'));
    }

    return view('admin.admin_movies', compact('nowShowingMovies', 'activeTab'));
}
}