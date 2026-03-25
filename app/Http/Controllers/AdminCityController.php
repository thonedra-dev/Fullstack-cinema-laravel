<?php

namespace App\Http\Controllers;

use App\Models\City;
use Illuminate\Http\Request;

class AdminCityController extends Controller
{
    /**
     * Show the Add City form.
     * Passes existing state names for the datalist autocomplete.
     *
     * GET /admin/city/create
     */
    public function create()
    {
        // Distinct sorted state names — used for <datalist> suggestions in the Blade
        $states = City::orderBy('city_state')
                      ->distinct()
                      ->pluck('city_state');

        return view('admin.expand_city', compact('states'));
    }

    /**
     * Persist a new City record.
     *
     * POST /admin/city
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'city_name'  => 'required|string|max:255',
            'city_state' => 'required|string|max:255',
        ]);

        City::create([
            'city_name'  => $validated['city_name'],
            'city_state' => $validated['city_state'],
        ]);

        return redirect()
            ->route('admin.city.create')
            ->with('success', 'City "' . $validated['city_name'] . '" added successfully.');
    }
}