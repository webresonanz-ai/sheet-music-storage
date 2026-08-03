<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Config;
use App\Core\Request;
use App\Core\Response;

/**
 * Sets CORS headers and short-circuits OPTIONS preflight requests.
 *
 * Must run outermost so error responses also receive the headers.
 */
final class CorsMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        $allowedList = array_filter(
            array_map('trim', explode(',', Config::get('CORS_ALLOWED_ORIGINS')))
        );

        $origin = $request->header('Origin');
        $allowOrigin = in_array($origin, $allowedList, true) ? $origin : '*';

        $headers = [
            'Access-Control-Allow-Origin' => $allowOrigin,
            'Access-Control-Allow-Credentials' => 'true',
            'Access-Control-Allow-Methods' => 'GET, POST, PUT, PATCH, DELETE, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, Authorization, X-Requested-With',
            'Access-Control-Max-Age' => '86400',
        ];

        // Early return for preflight (no downstream processing).
        if ($request->method() === 'OPTIONS') {
            return (new Response('', 204, $headers))
                ->withHeader('Vary', 'Origin');
        }

        $response = $next($request);

        foreach ($headers as $name => $value) {
            $response = $response->withHeader($name, $value);
        }

        return $response;
    }
}