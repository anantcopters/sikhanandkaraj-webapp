<?php

declare(strict_types=1);

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// -----------------------------------------------------------------------------
// Web routes
// -----------------------------------------------------------------------------

$routes->group('', [
    'namespace' => 'App\Controllers\Web',
], static function (RouteCollection $routes): void {
    $routes->get('/', 'HomeController::index', [
        'as' => 'web.home',
    ]);
});

// -----------------------------------------------------------------------------
// API version 1 routes
// -----------------------------------------------------------------------------

$routes->group('api/v1', [
    'namespace' => 'App\Controllers\Api\V1',
], static function (RouteCollection $routes): void {
    $routes->get('health', 'HealthController::index', [
        'as' => 'api.v1.health',
    ]);
});