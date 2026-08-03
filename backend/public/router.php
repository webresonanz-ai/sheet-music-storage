<?php

/**
 * Router for the PHP built-in server.
 *
 * Usage:
 *   php -S 127.0.0.1:8000 -t public public/router.php
 *
 * Forwards every request to the front controller so REST routes behave the
 * same way they do under Apache.
 */

if (PHP_SAPI !== 'cli-server') {
    http_response_code(404);
    echo json_encode(['error' => 'Use the PHP built-in server.']);
    exit;
}

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$requested = realpath(__DIR__ . $uri);

// Serve real, existing files directly (e.g. static assets).
if ($uri !== '/' && $requested !== false && is_file($requested)) {
    return false;
}

require __DIR__ . '/index.php';