<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;

/**
 * Decodes a JSON request body when the client sends `Content-Type: application/json`.
 */
final class JsonBodyMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        $contentType = $request->header('Content-Type');

        if (str_contains($contentType, 'application/json')) {
            $raw = file_get_contents('php://input') ?: '';
            $decoded = json_decode($raw, true);
            $request = $request->withJsonBody(is_array($decoded) ? $decoded : []);
        }

        return $next($request);
    }
}