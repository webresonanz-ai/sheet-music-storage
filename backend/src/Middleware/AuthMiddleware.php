<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Models\User;

/**
 * Authentication and role-based access control.
 *
 * - Public paths (no token needed): `/api/auth/*`
 * - Protected read paths (any logged-in user): GET on `/api/sheet-music`,
 *   `/api/uploads/score-img/{filename}`
 * - Admin-only paths (role `admin`): all other methods on `/api/sheet-music`,
 *   `/api/uploads/score-img`
 */
final class AuthMiddleware implements MiddlewareInterface
{
    private const PUBLIC_PREFIXES = ['/api/auth'];

    private const READ_METHODS = ['GET'];

    private User $model;

    public function __construct(?User $model = null)
    {
        $this->model = $model ?? new User();
    }

    public function handle(Request $request, callable $next): Response
    {
        $path = $request->path();

        if (!$this->isProtected($path)) {
            return $next($request);
        }

        $token = $this->extractToken($request->header('Authorization'));

        if ($token === '') {
            return Response::json(['error' => 'Unauthorized. Please log in.'], 401);
        }

        $user = $this->model->findByToken($token);

        if ($user === null) {
            return Response::json(['error' => 'Unauthorized. Invalid or expired session.'], 401);
        }

        // Guests may read but not modify the collection.
        if ($this->requiresAdmin($request) && ($user['role'] ?? 'guest') !== 'admin') {
            return Response::json(
                ['error' => 'Forbidden. Only administrators can modify the collection.'],
                403
            );
        }

        return $next($request->withUser($user));
    }

    private function isProtected(string $path): bool
    {
        foreach (self::PUBLIC_PREFIXES as $prefix) {
            if (str_starts_with($path, $prefix)) {
                if ($path === '/api/auth/me') {
                    return true;
                }
                return false;
            }
        }

        // Allow public access to GET /api/uploads/score-img/{filename}
        if (preg_match('#^/api/uploads/score-img/[^/]+$#', $path)) {
            return false;
        }

        return str_starts_with($path, '/api/sheet-music')
            || str_starts_with($path, '/api/uploads');
    }

    private function requiresAdmin(Request $request): bool
    {
        if (in_array($request->method(), self::READ_METHODS, true)) {
            return false;
        }

        return str_starts_with($request->path(), '/api/sheet-music')
            || str_starts_with($request->path(), '/api/uploads');
    }

    private function extractToken(string $header): string
    {
        if (preg_match('/^Bearer\s+(.+)$/i', $header, $matches) === 1) {
            return trim($matches[1]);
        }

        return '';
    }
}