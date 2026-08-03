<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Immutable HTTP request value object.
 */
final class Request
{
    private array $headers;

    /** @param array<string, string> $headers */
    private function __construct(
        private string $method,
        private string $path,
        array $headers,
        private array $query,
        private array $jsonBody = []
    ) {
        $this->headers = array_change_key_case($headers, CASE_LOWER);
    }

    public static function createFromGlobals(): self
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $path = rtrim($uri, '/');

        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $name = str_replace('_', '-', strtolower(substr($key, 5)));
                $headers[$name] = (string) $value;
            } elseif ($key === 'CONTENT_TYPE' || $key === 'CONTENT_LENGTH') {
                $headers[strtolower(str_replace('_', '-', $key))] = (string) $value;
            }
        }

        return new self($method, $path === '' ? '/' : $path, $headers, $_GET);
    }

    public function method(): string
    {
        return $this->method;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function header(string $name, string $default = ''): string
    {
        return $this->headers[strtolower($name)] ?? $default;
    }

    public function query(string $name, string $default = ''): string
    {
        return $this->query[$name] ?? $default;
    }

    /** @return array<string, mixed> */
    public function jsonBody(): array
    {
        return $this->jsonBody;
    }

    /** @param array<string, mixed> $body */
    public function withJsonBody(array $body): self
    {
        $clone = clone $this;
        $clone->jsonBody = $body;
        return $clone;
    }
}