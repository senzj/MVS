<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\System\AuditLogsService;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class EnsureTemporaryDeviceSession
{
    public function handle(Request $request, Closure $next): Response
    {
        $auditLogsService = app(AuditLogsService::class);

        if (Auth::check()) {
            if ($auditLogsService->isTemporaryDeviceExpired($request)) {
                $auditLogsService->revokeTemporaryDeviceToken($request);
                Auth::guard('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login');
            }

            $auditLogsService->restoreTemporaryDevice($request->user(), $request);

            return $next($request);
        }

        $plainToken = $request->cookie('temporary_device_token');
        if (! $plainToken) {
            return $next($request);
        }

        $device = DB::table('remember_devices')
            ->where('token', hash('sha256', $plainToken))
            ->where('expires_at', '>', now())
            ->first();

        if (! $device) {
            $auditLogsService->revokeTemporaryDeviceToken($request);
            return $next($request);
        }

        $user = User::find($device->user_id);
        if (! $user) {
            $auditLogsService->revokeTemporaryDeviceToken($request);
            return $next($request);
        }

        Auth::guard('web')->login($user);
        $request->session()->regenerate();
        $request->session()->put('temporary_device_token', $plainToken);
        $request->session()->put('temporary_device_expires_at', Carbon::parse($device->expires_at)->timestamp);
        $auditLogsService->restoreTemporaryDevice($user, $request);

        return $next($request);
    }
}
