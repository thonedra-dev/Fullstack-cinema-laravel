<?php

namespace App\Http\Controllers;

use App\Models\Cinema;
use App\Models\City;
use Illuminate\Http\Request;

class AdminCinemaController extends Controller
{
    /**
     * Show the Add Cinema form.
     * Provides state list and city map for the dynamic dropdown.
     *
     * GET /admin/cinema/create
     */
    public function create()
    {
        $cities        = City::orderBy('city_state')->orderBy('city_name')->get();
        $citiesByState = $cities->groupBy('city_state');
        $states        = $citiesByState->keys()->sort()->values();

        return view('admin.add_cinema', compact('citiesByState', 'states'));
    }

    /**
     * Persist a new Cinema record.
     * Validates input, handles optional file upload, inserts row.
     *
     * POST /admin/cinema
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'cinema_name'        => 'required|string|max:255',
            'cinema_address'     => 'required|string|max:500',
            'cinema_contact'     => 'required|string|max:50',
            'cinema_description' => 'nullable|string|max:1000',
            'city_id'            => 'required|integer|exists:cities,city_id',
            'cinema_picture'     => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $pictureFilename = null;

        if ($request->hasFile('cinema_picture') && $request->file('cinema_picture')->isValid()) {
            $file            = $request->file('cinema_picture');
            $pictureFilename = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('images/cinemas'), $pictureFilename);
        }

        Cinema::create([
            'cinema_name'        => $validated['cinema_name'],
            'cinema_address'     => $validated['cinema_address'],
            'cinema_contact'     => $validated['cinema_contact'],
            'cinema_description' => $validated['cinema_description'] ?? null,
            'cinema_picture'     => $pictureFilename,
            'city_id'            => $validated['city_id'],
        ]);

        return redirect()
            ->route('admin.cinema.create')
            ->with('success', 'Cinema "' . $validated['cinema_name'] . '" added successfully.');
    }
}