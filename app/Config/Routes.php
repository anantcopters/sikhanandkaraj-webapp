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
    $routes->post(
        'register',
        'RegistrationController::create',
        [
            'as' => 'web.register.create',
        ]
    );

    $routes->get(
        'register/verify-otp',
        'RegistrationVerificationController::index',
        [
            'as' => 'web.registration.verify',
        ]
    );

    $routes->post(
        'register/verify-otp',
        'RegistrationVerificationController::verify',
        [
            'as' => 'web.registration.verify.submit',
        ]
    );

    $routes->post(
        'register/resend-otp',
        'RegistrationVerificationController::resend',
        [
            'as' => 'web.registration.otp.resend',
        ]
    );

    $routes->post(
        'register/cancel',
        'RegistrationVerificationController::cancel',
        [
            'as' => 'web.registration.cancel',
        ]
    );

    $routes->get(
        'dashboard',
        'DashboardController::index',
        [
            'as' => 'web.dashboard',
            'filter' => 'webAuth',
        ]
    );

    $routes->post(
        'logout',
        'AuthenticationController::logout',
        [
            'as' => 'web.logout',
            'filter' => 'webAuth',
        ]
    );
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

// -----------------------------------------------------------------------------
//Ttemporary development-only routes.
// -----------------------------------------------------------------------------

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
