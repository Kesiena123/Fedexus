<?php

namespace App\Http\Middleware;

use App\Support\AdminSecurity;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireAdminAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_unless(AdminSecurity::isAdminUser($user), 403, 'Admin access is required.');

        // For API token auth, also verify the token ability
        $token = $user?->currentAccessToken();
        if ($token && ! $token->can(AdminSecurity::TOKEN_ABILITY)) {
            abort(403, 'Admin token is required.');
        }

        return $next($request);
    }
}
