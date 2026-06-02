<?php

namespace App\Http\Controllers;

use App\Models\BranchManager;
use App\Models\Manager;
use App\Models\Cinema;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Auth;

class BranchManagerAuthController extends Controller
{
    /**
     * Show the branch manager login page.
     * GET /manager/login
     */
    public function showLogin()
    {
        // Redirect to home if already authenticated
        if (Auth::guard('manager')->check()) {
        return redirect()->route('manager.home');
    }

        // Read images dynamically from the local cinematic directory
        $directory = public_path('images/branch_manager_login/');
        $slides = [];

        if (File::exists($directory)) {
            $slides = collect(File::files($directory))
                ->filter(fn ($file) => in_array(strtolower($file->getExtension()), ['jpg', 'jpeg', 'png', 'webp']))
                ->map(fn ($file) => asset('images/branch_manager_login/' . $file->getFilename()))
                ->values()
                ->all();
        }

        return view('branch_manager.manager_login', compact('slides'));
    }

    /**
     * Authenticate a branch manager.
     * POST /manager/login
     */
    public function login(Request $request)
    {
        $request->validate([
            'manager_email' => 'required|email',
            'password'      => 'required|string',
        ]);

        $manager = Manager::where('manager_email', $request->manager_email)->first();

        if (!$manager || !Hash::check($request->password, $manager->password)) {
            return back()
                ->withInput(['manager_email' => $request->manager_email])
                ->with('bm_login_error', 'Invalid email or password.');
        }

        $assignment = BranchManager::where('manager_id', $manager->manager_id)->first();

        if (!$assignment) {
            return back()
                ->withInput(['manager_email' => $request->manager_email])
                ->with('bm_login_error', 'No cinema assigned to this account. Please contact an administrator.');
        }

        $cinema = Cinema::with('city')->find($assignment->cinema_id);

        Auth::guard('manager')->login($manager);
        $request->session()->put('bm_cinema_id',    $assignment->cinema_id);
        $request->session()->put('bm_manager_name', $manager->manager_name);
        $request->session()->put('bm_cinema_name',  $cinema?->cinema_name ?? 'Cinema');
        $request->session()->regenerate();

        return redirect()->route('manager.home');
    }

    /**
     * Log out the branch manager.
     * POST /manager/logout
     */
    public function logout(Request $request)
{
    // 1. Log out from the manager guard
    Auth::guard('manager')->logout();

    // 2. Invalidate the session and clear out the token and metadata
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->route('manager.login');
}
}