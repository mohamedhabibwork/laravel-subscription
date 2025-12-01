<?php

namespace HSubscription\LaravelSubscription\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSubscriptionNotCancelled
{
    public function handle(Request $request, Closure $next, ?string $subscriptionName = 'default'): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

        if (! method_exists($user, 'cancelled')) {
            abort(403, 'User model does not support subscriptions.');
        }

        if ($user->cancelled($subscriptionName)) {
            abort(403, 'Subscription has been cancelled.');
        }

        return $next($request);
    }
}
