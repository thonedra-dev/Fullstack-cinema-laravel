<?php

namespace App\Http\Controllers;

use App\Models\Cinema;
use App\Models\Hall;
use App\Models\Seat;
use App\Models\Service;
use App\Models\Theatre;
use Illuminate\Http\Request;

class AdminTheatreController extends Controller
{
    /**
     * Show the Create Theatre form.
     * GET /admin/theatre/create
     */
    public function create()
    {
        // All cinemas — admin picks which ones to assign this new theatre to
        $cinemas  = Cinema::with('city')->orderBy('cinema_name')->get();
        $services = Service::orderBy('service_name')->get();

        return view('admin.create_theatre', compact('cinemas', 'services'));
    }

    /**
     * Persist a new Theatre record, its services, seat structure,
     * and optionally assign it to one or more cinema branches (halls).
     *
     * POST /admin/theatre
     */
    public function store(Request $request)
    {
        // ── 1. Validate core theatre fields ───────────────────────
        $validated = $request->validate([
            'theatre_name'   => 'required|string|max:255|unique:theatres,theatre_name',
            'theatre_icon'   => 'nullable|image|mimes:png,svg,webp|max:1024',
            'theatre_poster' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'services'       => 'nullable|array',
            'services.*'     => 'integer|exists:services,service_id',
            'seats_json'     => 'nullable|string',
            // cinema_ids[] — optional, assign this theatre to these cinemas immediately
            'cinema_ids'     => 'nullable|array',
            'cinema_ids.*'   => 'integer|exists:cinemas,cinema_id',
        ]);

        // ── 2. Parse and validate seats_json ──────────────────────
        $seatRows = [];

        if (!empty($validated['seats_json'])) {
            $decoded = json_decode($validated['seats_json'], true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $allowedTypes = ['Standard', 'Couple', 'Premium', 'Family'];

                foreach ($decoded as $index => $row) {
                    if (
                        !isset($row['label'], $row['count'], $row['type']) ||
                        !is_string($row['label']) ||
                        !is_int($row['count'])    ||
                        $row['count'] < 1         ||
                        $row['count'] > 40        ||
                        !in_array($row['type'], $allowedTypes, true)
                    ) {
                        return back()
                            ->withInput()
                            ->withErrors(['seats_json' => 'Invalid seat structure on row ' . ($index + 1) . '.']);
                    }

                    $seatRows[] = [
                        'label' => strtoupper(substr(trim($row['label']), 0, 1)),
                        'count' => (int) $row['count'],
                        'type'  => $row['type'],
                    ];
                }
            }
        }

        // ── 3. Handle file uploads ─────────────────────────────────
        $iconFilename   = null;
        $posterFilename = null;

        if ($request->hasFile('theatre_icon') && $request->file('theatre_icon')->isValid()) {
            $file         = $request->file('theatre_icon');
            $iconFilename = time() . '_icon_' . $file->getClientOriginalName();
            $file->move(public_path('images/theatres'), $iconFilename);
        }

        if ($request->hasFile('theatre_poster') && $request->file('theatre_poster')->isValid()) {
            $file           = $request->file('theatre_poster');
            $posterFilename = time() . '_poster_' . $file->getClientOriginalName();
            $file->move(public_path('images/theatres'), $posterFilename);
        }

        // ── 4. Create the Theatre row ──────────────────────────────
        $theatre = Theatre::create([
            'theatre_name'   => $validated['theatre_name'],
            'theatre_icon'   => $iconFilename,
            'theatre_poster' => $posterFilename,
        ]);

        // ── 5. Attach services (pivot) ─────────────────────────────
        if (!empty($validated['services'])) {
            $theatre->services()->sync($validated['services']);
        }

        // ── 6. Create individual Seat records ──────────────────────
        foreach ($seatRows as $row) {
            for ($seatNum = 1; $seatNum <= $row['count']; $seatNum++) {
                Seat::create([
                    'theatre_id'  => $theatre->theatre_id,
                    'row_label'   => $row['label'],
                    'seat_number' => $seatNum,
                    'seat_type'   => $row['type'],
                ]);
            }
        }

        // ── 7. Assign to cinema branches → insert into halls ───────
        // A hall is simply the link between a cinema and a theatre type.
        // Duplicate protection: ignore if the pair already exists (shouldn't happen
        // for a brand-new theatre, but safe to guard).
        $assignedCount = 0;

        if (!empty($validated['cinema_ids'])) {
            foreach ($validated['cinema_ids'] as $cinemaId) {
                $alreadyExists = Hall::where('cinema_id', $cinemaId)
                    ->where('theatre_id', $theatre->theatre_id)
                    ->exists();

                if (!$alreadyExists) {
                    Hall::create([
                        'cinema_id'  => $cinemaId,
                        'theatre_id' => $theatre->theatre_id,
                    ]);
                    $assignedCount++;
                }
            }
        }

        $successMsg = 'Theatre "' . $validated['theatre_name'] . '" created successfully.';
        if ($assignedCount > 0) {
            $successMsg .= ' Assigned to ' . $assignedCount . ' cinema branch(es).';
        }

        return redirect()
            ->route('admin.theatre.create')
            ->with('success', $successMsg);
    }
}