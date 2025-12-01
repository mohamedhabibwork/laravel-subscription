<?php

namespace HSubscription\LaravelSubscription\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureHasModule
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $module, ?string $subscriptionName = 'default'): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

        if (! method_exists($user, 'hasModule')) {
            abort(500, 'User model must use HasSubscriptions trait.');
        }

        if (! $user->hasModule($module, $subscriptionName)) {
            abort(403, 'You do not have access to this module.');
        }

        return $next($request);
    }
}
