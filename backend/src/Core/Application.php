<?php

declare(strict_types=1);

namespace App\Core;

use App\Middleware\MiddlewareInterface;

/**
 * Composes middleware into a pipeline around the router.
 *
 * Middleware added first runs outermost. Each middleware is invoked through
 * its `handle(Request $request, callable $next): Response` method.
 */
final class Application
{
    /** @var list<MiddlewareInterface> */
    private array $middleware = [];

    public function __construct(private Router $router)
    {
    }

    public function router(): Router
    {
        return $this->router;
    }

    public function addMiddleware(MiddlewareInterface $middleware): void
    {
        $this->middleware[] = $middleware;
    }

    public function run(Request $request): Response
    {
        $pipeline = function (Request $request): Response {
            return $this->router->dispatch($request);
        };

        foreach (array_reverse($this->middleware) as $middleware) {
            $pipeline = static fn (Request $req) => $middleware->handle($req, $pipeline);
        }

        return $pipeline($request);
    }

    public function handleGlobals(): void
    {
        $this->run(Request::createFromGlobals())->send();
    }
}