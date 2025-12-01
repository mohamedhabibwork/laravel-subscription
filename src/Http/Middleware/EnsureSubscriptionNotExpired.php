<?php

namespace HSubscription\LaravelSubscription\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSubscriptionNotExpired
{
    public function handle(Request $request, Closure $next, ?string $subscriptionName = 'default'): Response
    {
        $user = $request->user();

        abort_if(! $user, 401, 'Unauthenticated.');

        abort_if(! method_exists($user, 'subscription'), 403, 'User model does not support subscriptions.');

        abort_if($user->subscription($subscriptionName)?->isExpired(), 403, 'Subscription has expired.');

        return $next($request);
    }
}
