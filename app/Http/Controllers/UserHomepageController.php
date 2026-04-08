<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use Illuminate\Support\Facades\DB;

class UserHomepageController extends Controller
{
    /**
     * Show the public user homepage.
     * GET /
     *
     * Passes to view:
     *   $heroMovies    – All movies that have a landscape_poster (for hero carousel)
     *                    Each decorated with ->trailer_embed_url (null if no trailer)
     *   $nowShowing    – All movies that have a portrait_poster (for showtime grid)
     */
    public function index()
    {
        // Movies with landscape posters — for the hero slideshow
        // Also carry the first trailer embed URL so the Watch Trailer button works
        $heroMovies = Movie::with(['genres', 'trailers'])
            ->whereNotNull('landscape_poster')
            ->where('landscape_poster', '!=', '')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($movie) {
                // Grab the first trailer's embed URL if it exists
                $firstTrailer = $movie->trailers->first();
                $movie->trailer_embed_url = $firstTrailer?->embed_url;
                return $movie;
            });

        // Movies with portrait posters — for the "Now Showing" scroll row
        $nowShowing = Movie::with('genres')
            ->whereNotNull('portrait_poster')
            ->where('portrait_poster', '!=', '')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('users.homepage', compact('heroMovies', 'nowShowing'));
    }
}