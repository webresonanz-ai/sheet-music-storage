<?php

declare(strict_types=1);

require __DIR__ . '/../bootstrap/app.php';

// Boot the application and send the response.
if (isset($app) && $app instanceof App\Core\Application) {
    $app->handleGlobals();
} else {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Application failed to boot.']);
}