<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Only lets users with role "admin" through.
 * Registered in app/Http/Kernel.php under the alias "admin".
 */
class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()) {
            return redirect()->route('login');
        }

        if (! $request->user()->isAdmin()) {
            abort(403, 'Only administrators can access this page.');
        }

        return $next($request);
    }
}
