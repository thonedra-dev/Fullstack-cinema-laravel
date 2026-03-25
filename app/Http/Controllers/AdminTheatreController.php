<?php

namespace App\Http\Controllers;

use App\Models\Cinema;
use App\Models\Service;
use App\Models\Theatre;
use Illuminate\Http\Request;

class AdminTheatreController extends Controller
{
    /**
     * Show the Create Theatre form.
     * Provides cinema list and service list for the selectors.
     *
     * GET /admin/theatre/create
     */
    public function create()
    {
        $cinemas  = Cinema::orderBy('cinema_name')->get();
        $services = Service::orderBy('service_name')->get();

        return view('admin.create_theatre', compact('cinemas', 'services'));
    }

    /**
     * Persist a new Theatre record.
     * Handles optional icon and poster uploads.
     * Associates selected services via the theatre_services pivot table.
     *
     * POST /admin/theatre
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'theatre_name'   => 'required|string|max:255',
            'cinema_id'      => 'required|integer|exists:cinemas,cinema_id',
            'theatre_icon'   => 'nullable|image|mimes:png,svg,webp|max:1024',
            'theatre_poster' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'services'       => 'nullable|array',
            'services.*'     => 'integer|exists:services,service_id',
        ]);

        // Handle icon upload
        $iconFilename = null;
        if ($request->hasFile('theatre_icon') && $request->file('theatre_icon')->isValid()) {
            $file         = $request->file('theatre_icon');
            $iconFilename = time() . '_icon_' . $file->getClientOriginalName();
            $file->move(public_path('images/theatres'), $iconFilename);
        }

        // Handle poster upload
        $posterFilename = null;
        if ($request->hasFile('theatre_poster') && $request->file('theatre_poster')->isValid()) {
            $file           = $request->file('theatre_poster');
            $posterFilename = time() . '_poster_' . $file->getClientOriginalName();
            $file->move(public_path('images/theatres'), $posterFilename);
        }

        // Create the theatre row
        $theatre = Theatre::create([
            'theatre_name'   => $validated['theatre_name'],
            'cinema_id'      => $validated['cinema_id'],
            'theatre_icon'   => $iconFilename,
            'theatre_poster' => $posterFilename,
        ]);

        // Attach selected services to the pivot table (theatre_services)
        if (!empty($validated['services'])) {
            $theatre->services()->sync($validated['services']);
        }

        return redirect()
            ->route('admin.theatre.create')
            ->with('success', 'Theatre "' . $validated['theatre_name'] . '" created successfully.');
    }
}