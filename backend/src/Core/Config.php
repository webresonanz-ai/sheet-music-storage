<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Environment configuration loader.
 *
 * Reads real environment variables first, then falls back to a `.env` file.
 */
final class Config
{
    private static array $values = [];

    public static function load(string $envFile): void
    {
        $entries = [];

        if (is_file($envFile)) {
            foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                $line = trim($line);
                if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                    continue;
                }
                [$key, $value] = array_map('trim', explode('=', $line, 2));
                $value = trim($value, "\"'");
                if ($value !== '') {
                    $entries[$key] = $value;
                }
            }
        }

        self::$values = $entries;
    }

    public static function get(string $key, string $default = ''): string
    {
        $env = getenv($key);
        if ($env !== false && $env !== '') {
            return $env;
        }
        return self::$values[$key] ?? $default;
    }
}