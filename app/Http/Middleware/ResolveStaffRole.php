<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ResolveStaffRole
{
    public function handle(Request $request, Closure $next)
    {
        $role = null;

        // 1. Check Supervisor Guard
        if (Auth::guard('supervisor')->check()) {
            $role = 'supervisor';
        } 
        // 2. Check Manager Guard (Unified native approach)
        elseif (Auth::guard('manager')->check()) {
            $role = 'manager';
        }

        // Block unauthenticated access
        if ($role === null) {
            return redirect()->route('manager.login');
        }

        // Optional: Share the resolved role with the request or views
        $request->attributes->set('resolved_role', $role);

        return $next($request);
    }
}