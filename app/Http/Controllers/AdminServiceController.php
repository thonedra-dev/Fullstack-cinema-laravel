<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\Request;

class AdminServiceController extends Controller
{
    /**
     * Show the Add Service form.
     *
     * GET /admin/service/create
     */
    public function create()
    {
        return view('admin.create_service');
    }

    /**
     * Persist a new Service record.
     * Handles optional icon file upload.
     *
     * POST /admin/service
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'service_name' => 'required|string|max:255',
            'service_icon' => 'nullable|image|mimes:png,svg,webp|max:1024',
        ]);

        $iconFilename = null;

        if ($request->hasFile('service_icon') && $request->file('service_icon')->isValid()) {
            $file         = $request->file('service_icon');
            $iconFilename = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('images/services'), $iconFilename);
        }

        Service::create([
            'service_name' => $validated['service_name'],
            'service_icon' => $iconFilename,
        ]);

        return redirect()
            ->route('admin.service.create')
            ->with('success', 'Service "' . $validated['service_name'] . '" added successfully.');
    }
}