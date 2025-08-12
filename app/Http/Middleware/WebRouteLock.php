<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class WebRouteLock
{
    public function handle(Request $request, Closure $next)
    {
        // You can customize this logic:
        // Redirect all users to a single page (e.g. /lock)

        // Optionally allow access to that page itself
//        if ($request->is('lock') || $request->is('logout')) {
//            return $next($request);
//        }

        return redirect('/lock');
    }
}

