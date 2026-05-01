<?php

namespace App\Http\Controllers;

use App\Models\Cinema;
use App\Models\CinemaMovieQuota;
use App\Models\Genre;
use App\Models\Movie;
use App\Models\MovieTicketPrice;
use App\Models\Supervisor;
use App\Models\Theatre;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminMovieController extends Controller
{
    private const PRICING_THEATRES = ['Standard', 'Deluxe', '3D Hall', 'VIP lounge', 'IMAX'];
    private const PRICING_SEATS = ['standard', 'premium', 'family', 'couple'];
    private const PRICING_DAYS = ['weekday', 'weekend'];

    /**
     * Show the Movie Creation form.
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
     * Handle movie creation, genre assignment, cinema quota assignments,
     * and optional trailer URL insertion.
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
            'trailer_url'         => 'nullable|url|max:500',
            'genres'              => 'nullable|array',
            'genres.*'            => 'integer|exists:genres,genre_id',
            'supervisor_id'       => 'required|integer|exists:supervisors,supervisor_id',
            'supervisor_password' => 'required|string',
            'cinemas_json'        => 'nullable|string',
            'ticket_prices_json'  => 'required|string',
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
        $seenCinemaIds = [];

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

                // Slots: unlimited — only minimum of 1 enforced
                $slots = (int) $item['slots'];
                if ($slots < 1) {
                    return back()->withInput()
                        ->withErrors(['cinemas_json' => "Slot count must be at least 1 on assignment #{$rowNum}."]);
                }

                if (!Cinema::where('cinema_id', $item['cinemaId'])->exists()) {
                    return back()->withInput()
                        ->withErrors(['cinemas_json' => "Cinema #{$item['cinemaId']} not found."]);
                }

                $cinemaId = (int) $item['cinemaId'];
                if (isset($seenCinemaIds[$cinemaId])) {
                    return back()->withInput()
                        ->withErrors(['cinemas_json' => "Cinema #{$cinemaId} has been assigned more than once."]);
                }
                $seenCinemaIds[$cinemaId] = true;

                $assignments[] = [
                    'cinema_id'        => $cinemaId,
                    'start_date'       => $item['startDate'],
                    'maximum_end_date' => $item['endDate'],
                    'showtime_slots'   => $slots,
                ];
            }
        }

        if (empty($assignments)) {
            return back()->withInput()
                ->withErrors(['cinemas_json' => 'Assign at least one cinema before creating the movie.']);
        }

        // Ticket prices are defined once by theatre type, then applied to the
        // matching theatre records under every assigned cinema.
        $ticketPriceRules = [];
        $decodedPrices = json_decode($validated['ticket_prices_json'], true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decodedPrices)) {
            return back()->withInput()
                ->withErrors(['ticket_prices_json' => 'Invalid ticket pricing data.']);
        }

        $validTheatreMap = array_combine(
            array_map(fn ($name) => strtolower($name), self::PRICING_THEATRES),
            self::PRICING_THEATRES
        );

        foreach ($decodedPrices as $index => $item) {
            $rowNum = $index + 1;
            $theatreKey = strtolower(trim((string) ($item['theatreName'] ?? '')));
            $seatType = strtolower(trim((string) ($item['seatType'] ?? '')));
            $dayType = strtolower(trim((string) ($item['dayType'] ?? '')));
            $price = $item['price'] ?? null;

            if (!isset($validTheatreMap[$theatreKey])) {
                return back()->withInput()
                    ->withErrors(['ticket_prices_json' => "Invalid theatre type on ticket price rule #{$rowNum}."]);
            }

            if (!in_array($seatType, self::PRICING_SEATS, true)) {
                return back()->withInput()
                    ->withErrors(['ticket_prices_json' => "Invalid seat type on ticket price rule #{$rowNum}."]);
            }

            if (!in_array($dayType, self::PRICING_DAYS, true)) {
                return back()->withInput()
                    ->withErrors(['ticket_prices_json' => "Invalid day type on ticket price rule #{$rowNum}."]);
            }

            if (!is_numeric($price) || (float) $price <= 0 || (float) $price > 999999.99) {
                return back()->withInput()
                    ->withErrors(['ticket_prices_json' => "Ticket price must be between RM0.01 and RM999999.99 on rule #{$rowNum}."]);
            }

            $theatreName = $validTheatreMap[$theatreKey];
            $ruleKey = "{$theatreName}|{$seatType}|{$dayType}";

            if (isset($ticketPriceRules[$ruleKey])) {
                return back()->withInput()
                    ->withErrors(['ticket_prices_json' => "Duplicate ticket price rule for {$theatreName}, {$seatType}, {$dayType}."]);
            }

            $ticketPriceRules[$ruleKey] = [
                'theatre_name' => $theatreName,
                'seat_type'    => $seatType,
                'day_type'     => $dayType,
                'price'        => number_format((float) $price, 2, '.', ''),
            ];
        }

        foreach (self::PRICING_THEATRES as $theatreName) {
            foreach (self::PRICING_SEATS as $seatType) {
                foreach (self::PRICING_DAYS as $dayType) {
                    $ruleKey = "{$theatreName}|{$seatType}|{$dayType}";
                    if (!isset($ticketPriceRules[$ruleKey])) {
                        return back()->withInput()
                            ->withErrors(['ticket_prices_json' => "Missing ticket price rule for {$theatreName}, {$seatType}, {$dayType}."]);
                    }
                }
            }
        }

        $selectedCinemaIds = array_values(array_unique(array_column($assignments, 'cinema_id')));
        $cinemaNames = Cinema::whereIn('cinema_id', $selectedCinemaIds)
            ->pluck('cinema_name', 'cinema_id');
        $theatres = Theatre::whereIn('cinema_id', $selectedCinemaIds)->get();
        $theatresByCinemaAndName = [];

        foreach ($theatres as $theatre) {
            $theatreKey = strtolower(trim($theatre->theatre_name));
            if (!isset($validTheatreMap[$theatreKey])) {
                continue;
            }

            $mapKey = $theatre->cinema_id . '|' . $validTheatreMap[$theatreKey];
            $theatresByCinemaAndName[$mapKey] ??= $theatre;
        }

        $missingTheatres = [];
        foreach ($selectedCinemaIds as $cinemaId) {
            foreach (self::PRICING_THEATRES as $theatreName) {
                $mapKey = $cinemaId . '|' . $theatreName;
                if (!isset($theatresByCinemaAndName[$mapKey])) {
                    $missingTheatres[] = $theatreName . ' at ' . ($cinemaNames[$cinemaId] ?? "cinema #{$cinemaId}");
                }
            }
        }

        if (!empty($missingTheatres)) {
            $shownMissing = array_slice($missingTheatres, 0, 6);
            $suffix = count($missingTheatres) > 6 ? ' and ' . (count($missingTheatres) - 6) . ' more' : '';

            return back()->withInput()
                ->withErrors([
                    'ticket_prices_json' => 'Missing theatre records for pricing: ' . implode(', ', $shownMissing) . $suffix . '.',
                ]);
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
        $movie = DB::transaction(function () use (
            $validated,
            $landscapeFilename,
            $portraitFilename,
            $assignments,
            $supervisor,
            $ticketPriceRules,
            $selectedCinemaIds,
            $theatresByCinemaAndName
        ) {
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

        // ── 8. Insert Trailer row (if URL provided) ────────────
            $now = now();
            $ticketPriceRows = [];

            foreach ($selectedCinemaIds as $cinemaId) {
                foreach ($ticketPriceRules as $rule) {
                    $mapKey = $cinemaId . '|' . $rule['theatre_name'];
                    $theatre = $theatresByCinemaAndName[$mapKey];

                    $ticketPriceRows[] = [
                        'movie_id'    => $movie->movie_id,
                        'theatre_id'  => $theatre->theatre_id,
                        'seat_type'   => $rule['seat_type'],
                        'day_type'    => $rule['day_type'],
                        'price'       => $rule['price'],
                        'created_at'  => $now,
                        'updated_at'  => $now,
                    ];
                }
            }

            MovieTicketPrice::insert($ticketPriceRows);

        $trailerUrl = trim($validated['trailer_url'] ?? '');
        if ($trailerUrl !== '') {
            DB::table('trailers')->insert([
                'movie_id'    => $movie->movie_id,
                'youtube_url' => $trailerUrl,
                'type'        => 'main',
                'created_at'  => $now,
                'updated_at'  => $now,
            ]);
        }

            return $movie;
        });

        return redirect()
            ->route('admin.movie.create')
            ->with('success', 'Movie "' . $movie->movie_name . '" created and assigned to ' . count($assignments) . ' cinema(s).');
    }
}
