<?php

declare(strict_types=1);

use App\Core\Application;
use App\Core\Config;
use App\Core\Router;
use App\Middleware\CorsMiddleware;
use App\Middleware\ErrorHandlerMiddleware;
use App\Middleware\JsonBodyMiddleware;
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

$router->get('/sheet-music', [$sheetMusic, 'index']);
$router->post('/sheet-music', [$sheetMusic, 'store']);
$router->get('/sheet-music/{id}', [$sheetMusic, 'show']);
$router->put('/sheet-music/{id}', [$sheetMusic, 'update']);
$router->patch('/sheet-music/{id}', [$sheetMusic, 'update']);
$router->delete('/sheet-music/{id}', [$sheetMusic, 'destroy']);

// --- Application (middleware runs in the order added) ----------------
$app = new Application($router);

$app->addMiddleware(new CorsMiddleware());          // outermost
$app->addMiddleware(new ErrorHandlerMiddleware());
$app->addMiddleware(new JsonBodyMiddleware());

return $app;