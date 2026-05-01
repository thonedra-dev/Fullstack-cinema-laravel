<?php

namespace App\Http\Controllers;

use App\Models\Theatre;
use Illuminate\Http\Request;

class AdminTheatreResourceController extends Controller
{
    /**
     * Display the seat layout for a specific theatre.
     *
     * GET /admin/theatre/{id}/resources
     *
     * Eager-loads:
     *   theatre.cinema.city  — for breadcrumb context
     *   theatre.seats        — ordered row_label ASC, seat_number ASC (defined on model)
     */
    public function show(int $id)
    {
        $theatre = Theatre::with(['seats', 'services'])
                          ->findOrFail($id);

        /*
         * Group seats by row_label so the Blade can iterate rows cleanly.
         * Result shape:
         *   Illuminate\Support\Collection {
         *     'A' => Collection [ Seat, Seat, … ],
         *     'B' => Collection [ Seat, Seat, … ],
         *     …
         *   }
         *
         * The collection is sorted by row_label because the model orders by
         * row_label then seat_number, so groupBy preserves insertion order.
         */
        $seatsByRow = $theatre->seats->groupBy('row_label');

        return view('admin.theatre_resources', compact('theatre', 'seatsByRow'));
    }
}
