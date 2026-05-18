<?php

namespace App\Livewire\Actions;

use App\Models\User;
use App\Services\System\AuditLogsService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class Logout
{
    /**
     * Log the current user out of the application.
     */
    public function __invoke(AuditLogsService $auditLogsService)
    {
        $user = Auth::user();

        if ($user instanceof User) {
            DB::table('remember_devices')->where('user_id', $user->id)->delete();
            DB::table('sessions')->where('user_id', $user->id)->delete();
        }

        $auditLogsService->revokeTemporaryDeviceToken(request());
        $auditLogsService->recordLogout($user, request());

        Auth::guard('web')->logout();

        Session::invalidate();
        Session::regenerateToken();

        return redirect('/login');
    }
}
