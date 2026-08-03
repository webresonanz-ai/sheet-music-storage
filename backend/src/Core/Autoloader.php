<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Minimal PSR-4 autoloader for the `App\` namespace.
 *
 * Maps `App\Foo\Bar` to `src/Foo/Bar.php`. This lets the application work
 * without Composer while remaining compatible with a Composer autoload setup.
 */
final class Autoloader
{
    private const PREFIX = 'App\\';
    private string $baseDir;

    private function __construct(string $baseDir)
    {
        $this->baseDir = rtrim($baseDir, '/\\') . '/';
    }

    public static function register(string $baseDir): void
    {
        $loader = new self($baseDir);
        spl_autoload_register(static function (string $class) use ($loader): void {
            $loader->load($class);
        });
    }

    private function load(string $class): void
    {
        if (!str_starts_with($class, self::PREFIX)) {
            return;
        }

        $relative = substr($class, strlen(self::PREFIX));
        $path = $this->baseDir . str_replace('\\', DIRECTORY_SEPARATOR, $relative) . '.php';

        if (is_file($path)) {
            require $path;
        }
    }
}