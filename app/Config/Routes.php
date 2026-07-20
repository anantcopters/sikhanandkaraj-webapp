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

    $routes->get(
        'profile/edit',
        'ProfileController::edit',
        [
            'as' => 'web.profile.edit',
            'filter' => 'webAuth',
        ]
    );

    $routes->get(
        'account/settings',
        'AccountSettingsController::index',
        [
            'as' => 'web.account.settings',
            'filter' => 'webAuth',
        ]
    );

    $routes->get(
        'login',
        'AuthenticationController::index',
        [
            'as' => 'web.login',
        ]
    );

    $routes->post(
        'login',
        'AuthenticationController::login',
        [
            'as' => 'web.login.submit',
        ]
    );

    $routes->post(
        'email/verification/send',
        'EmailVerificationController::send',
        [
            'as' => 'web.email.verification.send',
            'filter' => 'webAuth',
        ]
    );

    $routes->get(
        'email/verify/(:segment)',
        'EmailVerificationController::verify/$1',
        [
            'as' => 'web.email.verify',
        ]
    );
});

// -----------------------------------------------------------------------------
// Admin routes
// -----------------------------------------------------------------------------

$routes->group('admin', [
    'namespace' => 'App\Controllers\Admin',
], static function (
    RouteCollection $routes
): void {
    $routes->get(
        'login',
        'AdminAuthenticationController::index',
        [
            'as' => 'admin.login',
        ]
    );

    $routes->post(
        'login',
        'AdminAuthenticationController::login',
        [
            'as' => 'admin.login.submit',
        ]
    );

    $routes->get(
        'invitation/(:segment)',
        'AdminInvitationController::show/$1',
        [
            'as' => 'admin.invitation.show',
        ]
    );

    $routes->post(
        'invitation/(:segment)',
        'AdminInvitationController::accept/$1',
        [
            'as' =>
            'admin.invitation.accept',
        ]
    );

    $routes->post(
        'logout',
        'AdminAuthenticationController::logout',
        [
            'as' => 'admin.logout',
            'filter' => 'adminAuth',
        ]
    );

    $routes->get(
        'dashboard',
        'AdminDashboardController::index',
        [
            'as' => 'admin.dashboard',
            'filter' => 'adminAuth',
        ]
    );

    $routes->group('users', [
        'filter' => 'adminAuth,superAdmin',
    ], static function (
        RouteCollection $routes
    ): void {
        $routes->get(
            '',
            'AdminUserController::index',
            [
                'as' =>
                'admin.users.index',
            ]
        );

        $routes->get(
            'create',
            'AdminUserController::create',
            [
                'as' =>
                'admin.users.create',
            ]
        );

        $routes->post(
            '',
            'AdminUserController::store',
            [
                'as' =>
                'admin.users.store',
            ]
        );

        $routes->post(
            '(:num)/resend-invitation',
            'AdminUserController::resend/$1',
            [
                'as' =>
                'admin.users.resend',
            ]
        );

        $routes->post(
            '(:num)/suspend',
            'AdminUserController::suspend/$1',
            [
                'as' =>
                'admin.users.suspend',
            ]
        );
    });
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
