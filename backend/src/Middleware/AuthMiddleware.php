<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Models\User;

/**
 * Authentication and role-based access control.
 *
 * - Public read paths (no token needed): GET on `/api/sheet-music`,
 *   `/api/sheet-music/{id}`, `/api/uploads/score-img/{filename}` — anyone may
 *   browse the collection and view details.
 * - Public paths (no token needed): `/api/auth/*` except `/api/auth/me`.
 * - Admin-only paths (role `admin`): all other methods on `/api/sheet-music`
 *   and `/api/uploads/score-img` (create, edit, delete, upload).
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

        // Public read: anyone may browse the collection and view score images.
        if ($this->isPublicRead($request)) {
            return $next($request);
        }

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

    private function isPublicRead(Request $request): bool
    {
        if (!in_array($request->method(), self::READ_METHODS, true)) {
            return false;
        }

        if (preg_match('#^/api/uploads/score-img/[^/]+$#', $request->path()) === 1) {
            return true;
        }

        return preg_match('#^/api/sheet-music(?:/[0-9]+)?$#', $request->path()) === 1;
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