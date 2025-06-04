<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;

class HttpsProtocolMiddleware
{
    public function handle($request, Closure $next)
    {
        // Skip HTTPS redirect for Midtrans notification endpoints
        if (
            $request->is('api/payments/notification') ||
            $request->is('api/payments/notification/recurring') ||
            $request->is('payments/notification') ||
            $request->is('payments/notification/recurring')
        ) {
            Log::info('Midtrans notification received', [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'content' => $request->getContent()
            ]);
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
