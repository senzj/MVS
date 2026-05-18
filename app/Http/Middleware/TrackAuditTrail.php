<?php

namespace App\Http\Middleware;

use App\Services\System\AuditLogsService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class TrackAuditTrail
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $user = $request->user();

        if ($user && Auth::check() && $response->getStatusCode() < 400) {
            app(AuditLogsService::class)->recordRequest($user, $request);
        }

        return $response;
    }
}
