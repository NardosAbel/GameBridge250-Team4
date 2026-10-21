<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ($user->isBanned() || $user->isSuspended())) {
            $user->currentAccessToken()?->delete();

            abort(403, 'This account cannot access the platform.');
        }

        return $next($request);
    }
}
