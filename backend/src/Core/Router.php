<?php

declare(strict_types=1);

namespace App\Core;

use App\Core\Exceptions\MethodNotAllowedException;
use App\Core\Exceptions\NotFoundException;

/**
 * Route registry and dispatcher.
 *
 * Routes support `{param}` placeholders, e.g. `/sheet-music/{id}`.
 */
final class Router
{
    /** @var list<array{method: string, path: string, action: callable}> */
    private array $routes = [];

    public function get(string $path, callable $action): void
    {
        $this->add('GET', $path, $action);
    }

    public function post(string $path, callable $action): void
    {
        $this->add('POST', $path, $action);
    }

    public function put(string $path, callable $action): void
    {
        $this->add('PUT', $path, $action);
    }

    public function patch(string $path, callable $action): void
    {
        $this->add('PATCH', $path, $action);
    }

    public function delete(string $path, callable $action): void
    {
        $this->add('DELETE', $path, $action);
    }

    public function add(string $method, string $path, callable $action): void
    {
        $this->routes[] = compact('method', 'path', 'action');
    }

    public function dispatch(Request $request): Response
    {
        $path = $request->path();
        $method = $request->method();

        $allowed = [];

        foreach ($this->routes as $route) {
            $regex = preg_replace(
                '#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#',
                '(?P<$1>[^/]+)',
                $route['path']
            );

            if (preg_match("#^{$regex}$#", $path, $matches) !== 1) {
                continue;
            }

            if ($route['method'] !== $method) {
                $allowed[] = $route['method'];
                continue;
            }

            $params = $this->extractParams($matches);

            return call_user_func_array($route['action'], [$request, ...$params]);
        }

        if ($allowed !== []) {
            throw new MethodNotAllowedException();
        }

        throw new NotFoundException();
    }

    /**
     * @param array<string, string> $matches
     * @return list<string|int>
     */
    private function extractParams(array $matches): array
    {
        $params = [];
        foreach ($matches as $key => $value) {
            if (is_string($key)) {
                $params[] = ctype_digit($value) ? (int) $value : $value;
            }
        }
        return $params;
    }
}