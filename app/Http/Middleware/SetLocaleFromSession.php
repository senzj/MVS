<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SetLocaleFromSession
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Load locale from authenticated user's database preference, fallback to session, then config
        if (Auth::check()) {
            $locale = Auth::user()->lang ?? session('locale', config('app.locale'));
        } else {
            $locale = session('locale', config('app.locale'));
        }

        App::setLocale($locale);

        return $next($request);
    }
}
