<?php

namespace App\Http\Controllers;

use App\Models\BranchManager;
use App\Models\Cinema;
use App\Models\Manager;
use Illuminate\Http\Request;

class AdminManagerController extends Controller
{
    /**
     * Show managers list and assignment form.
     *
     * GET /admin/managers
     */
    public function index()
    {
        // All managers with their already-assigned cinemas
        $managers = Manager::with('cinemas')->orderBy('manager_name')->get();

        // All cinemas for the assignment dropdown
        $cinemas = Cinema::with('city')->orderBy('cinema_name')->get();

        // Current branch manager assignments for the table
        $assignments = BranchManager::with(['manager', 'cinema'])->get();

        return view('admin.manage_managers', compact('managers', 'cinemas', 'assignments'));
    }

    /**
     * Assign a manager to a cinema.
     * Inserts into branch_managers (composite PK — duplicate silently ignored).
     *
     * POST /admin/managers/assign
     */
    public function assign(Request $request)
    {
        $validated = $request->validate([
            'manager_id' => 'required|integer|exists:managers,manager_id',
            'cinema_id'  => 'required|integer|exists:cinemas,cinema_id',
        ]);

        // firstOrCreate prevents duplicate composite key violations
        BranchManager::firstOrCreate([
            'manager_id' => $validated['manager_id'],
            'cinema_id'  => $validated['cinema_id'],
        ]);

        $manager = Manager::find($validated['manager_id']);
        $cinema  = Cinema::find($validated['cinema_id']);

        return redirect()
            ->route('admin.managers.index')
            ->with('success', $manager->manager_name . ' assigned to ' . $cinema->cinema_name . '.');
    }

    /**
     * Remove a manager–cinema assignment.
     *
     * POST /admin/managers/unassign
     */
    public function unassign(Request $request)
    {
        $validated = $request->validate([
            'manager_id' => 'required|integer',
            'cinema_id'  => 'required|integer',
        ]);

        BranchManager::where('manager_id', $validated['manager_id'])
                     ->where('cinema_id',  $validated['cinema_id'])
                     ->delete();

        return redirect()
            ->route('admin.managers.index')
            ->with('success', 'Assignment removed.');
    }
}