<?php

namespace App\Http\Controllers;

use App\Models\Cinema;
use App\Models\CinemaMovieQuota;
use App\Models\Genre;
use App\Models\Movie;
use App\Models\Supervisor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminMovieController extends Controller
{
    /**
     * Show the Movie Creation form.
     *
     * GET /admin/movie/create
     */
    public function create()
    {
        $cinemas     = Cinema::with('city')->orderBy('cinema_name')->get();
        $supervisors = Supervisor::orderBy('supervisor_name')->get();
        $genres      = Genre::orderBy('genre_name')->get();

        return view('admin.movie_creation', compact('cinemas', 'supervisors', 'genres'));
    }

    /**
     * Handle movie creation, genre assignment, and cinema quota assignments.
     *
     * POST /admin/movie
     */
    public function store(Request $request)
    {
        // ── 1. Validate ───────────────────────────────────────
        $validated = $request->validate([
            'movie_name'          => 'required|string|max:255',
            'runtime'             => 'required|integer|min:1|max:600',
            'language'            => 'required|string|max:255',
            'production_name'     => 'required|string|max:255',
            'landscape_poster'    => 'nullable|image|mimes:jpeg,png,jpg,webp|max:4096',
            'portrait_poster'     => 'nullable|image|mimes:jpeg,png,jpg,webp|max:4096',
            'genres'              => 'nullable|array',
            'genres.*'            => 'integer|exists:genres,genre_id',
            'supervisor_id'       => 'required|integer|exists:supervisors,supervisor_id',
            'supervisor_password' => 'required|string',
            'cinemas_json'        => 'nullable|string',
        ]);

        // ── 2. Verify supervisor password ──────────────────────
        $supervisor = Supervisor::find($validated['supervisor_id']);

        if (!$supervisor || !Hash::check($validated['supervisor_password'], $supervisor->password)) {
            return back()
                ->withInput()
                ->with('auth_error', 'Incorrect supervisor password. Please try again.');
        }

        // ── 3. Parse and validate cinemas_json ─────────────────
        $assignments = [];

        if (!empty($validated['cinemas_json'])) {
            $decoded = json_decode($validated['cinemas_json'], true);

            if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
                return back()
                    ->withInput()
                    ->withErrors(['cinemas_json' => 'Invalid cinema assignment data.']);
            }

            foreach ($decoded as $index => $item) {
                $rowNum = $index + 1;

                if (empty($item['cinemaId']) || empty($item['startDate']) ||
                    empty($item['endDate'])   || empty($item['slots'])) {
                    return back()->withInput()
                        ->withErrors(['cinemas_json' => "Incomplete data on assignment #{$rowNum}."]);
                }

                if ($item['endDate'] <= $item['startDate']) {
                    return back()->withInput()
                        ->withErrors(['cinemas_json' => "End date must be after start date on assignment #{$rowNum}."]);
                }

                $slots = (int) $item['slots'];
                if ($slots < 1 || $slots > 20) {
                    return back()->withInput()
                        ->withErrors(['cinemas_json' => "Invalid slot count on assignment #{$rowNum}."]);
                }

                if (!Cinema::where('cinema_id', $item['cinemaId'])->exists()) {
                    return back()->withInput()
                        ->withErrors(['cinemas_json' => "Cinema #{$item['cinemaId']} not found."]);
                }

                $assignments[] = [
                    'cinema_id'        => (int) $item['cinemaId'],
                    'start_date'       => $item['startDate'],
                    'maximum_end_date' => $item['endDate'],
                    'showtime_slots'   => $slots,
                ];
            }
        }

        // ── 4. Handle poster uploads ───────────────────────────
        $landscapeFilename = null;
        $portraitFilename  = null;

        if ($request->hasFile('landscape_poster') && $request->file('landscape_poster')->isValid()) {
            $file              = $request->file('landscape_poster');
            $landscapeFilename = time() . '_landscape_' . $file->getClientOriginalName();
            $file->move(public_path('images/movies'), $landscapeFilename);
        }

        if ($request->hasFile('portrait_poster') && $request->file('portrait_poster')->isValid()) {
            $file             = $request->file('portrait_poster');
            $portraitFilename = time() . '_portrait_' . $file->getClientOriginalName();
            $file->move(public_path('images/movies'), $portraitFilename);
        }

        // ── 5. Insert Movie ────────────────────────────────────
        $movie = Movie::create([
            'movie_name'       => $validated['movie_name'],
            'runtime'          => $validated['runtime'],
            'language'         => $validated['language'],
            'production_name'  => $validated['production_name'],
            'landscape_poster' => $landscapeFilename,
            'portrait_poster'  => $portraitFilename,
        ]);

        // ── 6. Sync genres into movie_genres ───────────────────
        if (!empty($validated['genres'])) {
            $movie->genres()->sync($validated['genres']);
        }

        // ── 7. Insert CinemaMovieQuota records ─────────────────
        foreach ($assignments as $assignment) {
            CinemaMovieQuota::create([
                'movie_id'         => $movie->movie_id,
                'cinema_id'        => $assignment['cinema_id'],
                'supervisor_id'    => $supervisor->supervisor_id,
                'showtime_slots'   => $assignment['showtime_slots'],
                'start_date'       => $assignment['start_date'],
                'maximum_end_date' => $assignment['maximum_end_date'],
            ]);
        }

        return redirect()
            ->route('admin.movie.create')
            ->with('success', 'Movie "' . $movie->movie_name . '" created and assigned to ' . count($assignments) . ' cinema(s).');
    }
}