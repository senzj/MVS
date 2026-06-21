<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ForcePasswordChange
{
    public function handle(Request $request, Closure $next)
    {
        // 1. Bulletproof Livewire protection (Checks URL string and execution headers)
        if ($request->is('livewire*') || $request->hasHeader('X-Livewire')) {
            return $next($request);
        }

        if (Auth::check()) {
            if (Auth::user()->change_password) {
                // 2. User MUST change password. Restrict access strictly to reset page or logout processing
                if (! $request->routeIs('password.reset', 'logout')) {
                    return redirect()->route('password.reset');
                }
            } else {
                // 3. User is safe (flag is false). If they try to visit the reset page, bounce them to the dashboard
                if ($request->routeIs('password.reset')) {
                    return redirect()->route('dashboard');
                }
            }
        } else {
            // 4. Guest rules
            if (! $request->routeIs('login', 'register', 'password.request')) {
                return redirect()->route('login');
            }
        }

        return $next($request);
    }
}
