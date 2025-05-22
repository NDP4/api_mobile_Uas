<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\App;

class HttpsProtocolMiddleware
{
    public function handle($request, Closure $next)
    {
        // Skip HTTPS redirect for Midtrans notification endpoint
        if ($request->is('api/payments/notification')) {
            return $next($request);
        }

        if (!$request->secure() && App::environment('production')) {
            // Handle reverse proxy headers if needed
            $request->setTrustedProxies([$request->getClientIp()], $request->getTrustedHeaderSet());

            // Force HTTPS in production
            return redirect()->secure($request->getRequestUri());
        }

        return $next($request);
    }
}
