<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Exceptions\HttpException;
use App\Core\Request;
use App\Core\Response;
use Throwable;

/**
 * Converts exceptions into JSON error responses.
 */
final class ErrorHandlerMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        try {
            return $next($request);
        } catch (HttpException $e) {
            return Response::json([
                'error' => $e->getMessage(),
            ], $e->statusCode());
        } catch (Throwable $e) {
            // Never leak internal details in production.
            return Response::json([
                'error' => 'Internal server error',
            ], 500);
        }
    }
}