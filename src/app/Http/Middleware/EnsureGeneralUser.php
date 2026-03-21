<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureGeneralUser
{
    /**
     * Ensure the current user is a general user.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->isGeneralUser()) {
            abort(403);
        }

        return $next($request);
    }
}
