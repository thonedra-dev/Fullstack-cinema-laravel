<?php

namespace App\Http\Controllers;

use App\Models\Cinema;
use App\Models\Hall;
use App\Models\Movie;
use App\Models\Showtime;
use Illuminate\Support\Facades\Auth;

class BranchManagerResourceController extends Controller
{
    private BranchManagerNotificationController $notifications;

    public function __construct(BranchManagerNotificationController $notifications)
    {
        $this->notifications = $notifications;
    }

    /**
     * Show theatres and ACTIVE movies (those with at least one showtime
     * AND whose quota has not yet passed maximum_end_date).
     *
     * Also detects movies that have just EXPIRED (past maximum_end_date)
     * and delegates notification creation to BranchManagerNotificationController,
     * so all notification construction stays centralized in one place.
     *
     * GET /manager/resources
     */
    public function index()
    {
        if (!Auth::guard('manager')->check() || !session('bm_cinema_id')) {
            return redirect()->route('manager.login');
        }

        $cinemaId  = session('bm_cinema_id');
        $managerId = Auth::guard('manager')->id();
        $cinema    = Cinema::findOrFail($cinemaId);

        // Halls for this cinema; services and seats still belong to the master theatre type.
        $halls = Hall::with('theatre.seats')
            ->where('cinema_id', $cinemaId)
            ->get();

        $theatres = $halls
            ->map(function ($hall) {
                $theatre = $hall->theatre;

                if ($theatre) {
                    $theatre->setAttribute('hall_id', $hall->hall_id);
                }

                return $theatre;
            })
            ->filter()
            ->sortBy('theatre_name')
            ->values();

        $hallIds = $halls->pluck('hall_id');

        // Movie IDs that have at least one showtime in this cinema's halls.
        $activeMovieIds = Showtime::whereIn('hall_id', $hallIds)
            ->distinct()
            ->pluck('movie_id');

        // ── Running Movies = assigned to cinema AND have showtimes ──
        // AND still within their quota window (maximum_end_date has not passed).
        $movies = Movie::with('genres')
            ->join('cinema_movie_quotas as cmq', 'movies.movie_id', '=', 'cmq.movie_id')
            ->where('cmq.cinema_id', $cinemaId)
            ->whereIn('movies.movie_id', $activeMovieIds)
            ->whereDate('cmq.maximum_end_date', '>=', now()->toDateString())
            ->select('movies.*', 'cmq.showtime_slots', 'cmq.start_date', 'cmq.maximum_end_date')
            ->get();

        // ── Detect newly EXPIRED movies (same base pool, but date has passed) ──
        $expiredMovies = Movie::join('cinema_movie_quotas as cmq', 'movies.movie_id', '=', 'cmq.movie_id')
            ->where('cmq.cinema_id', $cinemaId)
            ->whereIn('movies.movie_id', $activeMovieIds)
            ->whereDate('cmq.maximum_end_date', '<', now()->toDateString())
            ->select('movies.*', 'cmq.maximum_end_date')
            ->get();

        foreach ($expiredMovies as $movie) {
            $this->notifications->notifyExpiredMovie(
                $managerId,
                $movie->movie_name,
                $movie->portrait_poster
            );
        }

        return view('branch_manager.bm_resources', compact('cinema', 'halls', 'theatres', 'movies'));
    }
}