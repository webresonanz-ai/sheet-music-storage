<?php

declare(strict_types=1);

use App\Core\Application;
use App\Core\Config;
use App\Core\Router;
use App\Middleware\AuthMiddleware;
use App\Middleware\CorsMiddleware;
use App\Middleware\ErrorHandlerMiddleware;
use App\Middleware\JsonBodyMiddleware;
use App\Controllers\AuthController;
use App\Controllers\ScoreImageController;
use App\Controllers\SheetMusicController;

$rootDir = __DIR__ . '/..';

// Composer autoload if present, otherwise the built-in PSR-4 autoloader.
if (is_file($rootDir . '/vendor/autoload.php')) {
    require_once $rootDir . '/vendor/autoload.php';
} else {
    require_once $rootDir . '/src/Core/Autoloader.php';
    App\Core\Autoloader::register($rootDir . '/src');
}

Config::load($rootDir . '/.env');

// --- Routes -----------------------------------------------------------
$router = new Router();
$sheetMusic = new SheetMusicController();
$scoreImage = new ScoreImageController();
$auth = new AuthController();

// Auth (public)
$router->post('/api/auth/register', [$auth, 'register']);
$router->post('/api/auth/login', [$auth, 'login']);
$router->post('/api/auth/logout', [$auth, 'logout']);
$router->get('/api/auth/me', [$auth, 'me']);

// Sheet music (protected by AuthMiddleware)
$router->get('/api/sheet-music', [$sheetMusic, 'index']);
$router->post('/api/sheet-music', [$sheetMusic, 'store']);
$router->post('/api/sheet-music/import-excel', [$sheetMusic, 'importExcel']);
$router->get('/api/sheet-music/{id}', [$sheetMusic, 'show']);
$router->put('/api/sheet-music/{id}', [$sheetMusic, 'update']);
$router->patch('/api/sheet-music/{id}', [$sheetMusic, 'update']);
$router->delete('/api/sheet-music/{id}', [$sheetMusic, 'destroy']);

$router->post('/api/uploads/score-img', [$scoreImage, 'upload']);
$router->get('/api/uploads/score-img/{filename}', [$scoreImage, 'serve']);

// --- Application (middleware runs in the order added) ----------------
$app = new Application($router);

$app->addMiddleware(new CorsMiddleware());          // outermost
$app->addMiddleware(new ErrorHandlerMiddleware());
$app->addMiddleware(new AuthMiddleware());
$app->addMiddleware(new JsonBodyMiddleware());      // innermost

return $app;