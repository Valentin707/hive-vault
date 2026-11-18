<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Livewire\Livewire;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeadersMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $nonce = base64_encode(random_bytes(16));

        Vite::useCspNonce($nonce);
        Livewire::useScriptTagAttributes(['nonce' => $nonce]);

        $response = $next($request);

        $scriptSources = "'self' 'nonce-{$nonce}'";
        $styleSources = "'self' 'nonce-{$nonce}' 'unsafe-inline'";
        $connectSources = "'self'";

        if (app()->isLocal()) {
            $viteOrigins = ' http://localhost:5173 http://127.0.0.1:5173';
            $scriptSources .= $viteOrigins;
            $styleSources .= $viteOrigins;
            $connectSources .= $viteOrigins.' ws://localhost:5173 ws://127.0.0.1:5173';
        }

        $response->headers->set('Content-Security-Policy', implode('; ', [
            "default-src 'self'",
            "base-uri 'self'",
            "object-src 'none'",
            "frame-ancestors 'none'",
            "form-action 'self'",
            "script-src {$scriptSources}",
            "script-src-attr 'none'",
            "style-src {$styleSources}",
            "img-src 'self' data:",
            "font-src 'self' data:",
            "connect-src {$connectSources}",
        ]));
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'no-referrer');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        return $response;
    }
}
