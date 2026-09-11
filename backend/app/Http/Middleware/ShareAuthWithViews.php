<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ShareAuthWithViews
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            view()->share('authUser', Auth::user());
        }

        return $next($request);
    }
}
