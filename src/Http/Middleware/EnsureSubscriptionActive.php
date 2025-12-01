<?php

namespace HSubscription\LaravelSubscription\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSubscriptionActive
{
    public function handle(Request $request, Closure $next, ?string $subscriptionName = 'default'): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

        if (! method_exists($user, 'active')) {
            abort(403, 'User model does not support subscriptions.');
        }

        if (! $user->active($subscriptionName)) {
            abort(403, 'Active subscription required.');
        }

        return $next($request);
    }
}
