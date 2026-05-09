<?php

namespace App\Http\Controllers;

use App\Models\Hall;
use App\Models\Theatre;
use App\Models\Cinema;
use Illuminate\Http\Request;

class AdminHallController extends Controller
{
    /**
     * Assign one or more theatre types to a cinema branch.
     * Creates a hall record for each cinema ↔ theatre pair.
     *
     * POST /admin/cinema/{cinemaId}/halls
     */
    public function store(Request $request, int $cinemaId)
    {
        $cinema = Cinema::findOrFail($cinemaId);

        $request->validate([
            'theatre_ids'   => 'required|array|min:1',
            'theatre_ids.*' => 'integer|exists:theatres,theatre_id',
        ]);

        $assigned = 0;
        $skipped  = 0;

        foreach ($request->theatre_ids as $theatreId) {
            $alreadyExists = Hall::where('cinema_id', $cinemaId)
                ->where('theatre_id', $theatreId)
                ->exists();

            if ($alreadyExists) {
                $skipped++;
                continue;
            }

            Hall::create([
                'cinema_id'  => $cinemaId,
                'theatre_id' => $theatreId,
            ]);
            $assigned++;
        }

        $msg = $assigned . ' theatre(s) assigned to ' . $cinema->cinema_name . '.';
        if ($skipped > 0) {
            $msg .= ' ' . $skipped . ' already assigned — skipped.';
        }

        return redirect()
            ->route('admin.cinema.index')
            ->with('success', $msg);
    }
}