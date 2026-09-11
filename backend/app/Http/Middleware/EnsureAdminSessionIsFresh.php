<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminSessionIsFresh
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->user()?->currentAccessToken();
        abort_unless($token, 401, 'Authentication required.');

        $timeoutMinutes = max(1, (int) config('admin.session_timeout_minutes', 30));
        $lastActivity = $token->last_used_at ?? $token->created_at;

        if (! $lastActivity || $lastActivity->lt(now()->subMinutes($timeoutMinutes))) {
            $token->delete();
            abort(401, 'Admin session expired due to inactivity.');
        }

        return $next($request);
    }
}
