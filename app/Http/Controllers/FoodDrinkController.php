<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\FoodDrinkGeneral;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FoodDrinkController extends Controller
{
    /**
     * Show the form for creating a new food/drink item.
     */
    public function create()
    {
        return view('admin.admin_food_drink');
    }

    /**
     * Store a newly created item in the master catalog.
     */
    public function store(Request $request)
    {
        // 1. Validate incoming form inputs
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:food_drink_general,name',
            'category_tag' => 'required|string|in:popcorn_crunch,hot_bites,beverages,combos,sweets,premium',
            'suggested_price' => 'nullable|numeric|min:0|max:999999.99',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048', // 2MB max
        ]);

        // 2. Handle file upload if an image was provided
        $imagePath = null;
        if ($request->hasFile('image')) {
            // Stores inside storage/app/public/food_drinks
            $path = $request->file('image')->store('food_drinks', 'public');
            // Generates URL path for retrieval: /storage/food_drinks/filename.ext
            $imagePath = Storage::url($path);
        }

        // 3. Create record in database
        FoodDrinkGeneral::create([
            'name' => $validated['name'],
            'category_tag' => $validated['category_tag'],
            'suggested_price' => $validated['suggested_price'],
            'image_path' => $imagePath,
        ]);

        // 4. Redirect with a beautiful flash message captured by admin_team layout shell
        return redirect()->back()->with('success', 'Master item successfully added to catalog!');
    }
}