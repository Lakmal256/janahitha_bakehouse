<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Route as BakeryRoute;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserCanAccessRoute
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $route = $request->route('route');

        if ($route instanceof BakeryRoute) {
            abort_unless($request->user()?->canAccessRoute($route), 403);
        } elseif (is_numeric($route)) {
            abort_unless($request->user()?->canAccessRoute((int) $route), 403);
        }

        return $next($request);
    }
}
