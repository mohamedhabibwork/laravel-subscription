<?php

namespace HSubscription\LaravelSubscription\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureHasFeature
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $feature, ?string $subscriptionName = 'default'): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

        if (! method_exists($user, 'hasFeature')) {
            abort(500, 'User model must use HasSubscriptions trait.');
        }

        if (! $user->hasFeature($feature, $subscriptionName)) {
            abort(403, 'You do not have access to this feature.');
        }

        return $next($request);
    }
}
