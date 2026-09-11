<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceAdminIpRules
{
    public function handle(Request $request, Closure $next): Response
    {
        $ip = (string) $request->ip();
        $allowlist = config('admin.ip_allowlist', []);
        $blocklist = config('admin.ip_blocklist', []);

        abort_if(in_array($ip, $blocklist, true), 403, 'This IP address is blocked from admin access.');

        if ($allowlist !== []) {
            abort_unless(in_array($ip, $allowlist, true), 403, 'This IP address is not allowed for admin access.');
        }

        return $next($request);
    }
}
