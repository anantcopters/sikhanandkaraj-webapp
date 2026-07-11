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

$routes->group(API_ROUTE_PREFIX, [
    'namespace' => 'App\Controllers\Api\V1',
], static function (RouteCollection $routes): void {
    $routes->get('health', 'HealthController::index', [
        'as' => 'api.v1.health',
    ]);
});

if (ENVIRONMENT === 'development') {
    $routes->group('_preview/errors', static function (
        RouteCollection $routes
    ): void {
        $routes->get('403', static function () {
            return service('response')
                ->setStatusCode(403)
                ->setBody(view('errors/html/error_403'));
        });

        $routes->get('404', static function () {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        });

        $routes->get('500', static function () {
            return service('response')
                ->setStatusCode(500)
                ->setBody(view('errors/html/error_500'));
        });
    });
}