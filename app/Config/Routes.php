<?php

declare(strict_types=1);

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// -----------------------------------------------------------------------------
// Member web routes
// -----------------------------------------------------------------------------

$routes->group('', [
    'namespace' => 'App\Controllers\Web',
], static function (
    RouteCollection $routes
): void {
    $routes->get(
        '/',
        'HomeController::index',
        [
            'as' => 'web.home',
        ]
    );

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
        'logout',
        'AuthenticationController::logout',
        [
            'as' => 'web.logout',
            'filter' => 'webAuth',
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

    $routes->get(
        'profile/edit',
        'ProfileController::edit',
        [
            'as' => 'web.profile.edit',
            'filter' => 'webAuth',
        ]
    );

    /*
    * Basic Details.
    */
    $routes->get(
        'profile/basic-details',
        'ProfileController::basicDetails',
        [
            'as' => 'web.profile.basic-details',
            'filter' => 'webAuth',
        ]
    );

    $routes->post(
        'profile/basic-details',
        'ProfileController::updateBasicDetails',
        [
            'as' => 'web.profile.basic-details.update',
            'filter' => 'webAuth',
        ]
    );

    /*
    * Education & Profession.
    */
    $routes->get(
        'profile/education-profession',
        'ProfileController::educationProfession',
        [
            'as' => 'web.profile.education-profession',
            'filter' => 'webAuth',
        ]
    );

    $routes->post(
        'profile/education-profession',
        'ProfileController::updateEducationProfession',
        [
            'as' => 'web.profile.education-profession.update',
            'filter' => 'webAuth',
        ]
    );

    /*
    * Family Details.
    */
    $routes->get(
        'profile/family-details',
        'ProfileController::familyDetails',
        [
            'as' => 'web.profile.family-details',
            'filter' => 'webAuth',
        ]
    );

    $routes->post(
        'profile/family-details',
        'ProfileController::updateFamilyDetails',
        [
            'as' => 'web.profile.family-details.update',
            'filter' => 'webAuth',
        ]
    );

    /*
    * Sikh & Religious Details.
    */
    $routes->get(
        'profile/sikh-religious-details',
        'ProfileController::sikhReligiousDetails',
        [
            'as' => 'web.profile.sikh-religious-details',
            'filter' => 'webAuth',
        ]
    );

    $routes->post(
        'profile/sikh-religious-details',
        'ProfileController::updateSikhReligiousDetails',
        [
            'as' =>
            'web.profile.sikh-religious-details.update',
            'filter' => 'webAuth',
        ]
    );

    $routes->get(
        'profile/master/sikh-subcommunities/(:num)',
        'ProfileMasterController::sikhSubcommunities/$1',
        [
            'as' =>
            'web.profile.master.sikh-subcommunities',
            'filter' => 'webAuth',
        ]
    );

    /*
    * Lifestyle.
    */
    $routes->get(
        'profile/lifestyle',
        'ProfileController::lifestyle',
        [
            'as' => 'web.profile.lifestyle',
            'filter' => 'webAuth',
        ]
    );

    $routes->post(
        'profile/lifestyle',
        'ProfileController::updateLifestyle',
        [
            'as' => 'web.profile.lifestyle.update',
            'filter' => 'webAuth',
        ]
    );

    /*
    * About Me.
    */
    $routes->get(
        'profile/about-me',
        'ProfileController::aboutMe',
        [
            'as' => 'web.profile.about-me',
            'filter' => 'webAuth',
        ]
    );

    $routes->post(
        'profile/about-me',
        'ProfileController::updateAboutMe',
        [
            'as' => 'web.profile.about-me.update',
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

    $routes->post(
        'email/verification/send',
        'EmailVerificationController::send',
        [
            'as' => 'web.email.verification.send',
            'filter' => 'webAuth',
        ]
    );

    $routes->get(
        'profile/master/cities/(:num)',
        'ProfileMasterController::cities/$1',
        [
            'as' => 'web.profile.master.cities',
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
// Administrator routes
// -----------------------------------------------------------------------------

$routes->group('admin', [
    'namespace' => 'App\Controllers\Admin',
], static function (
    RouteCollection $routes
): void {
    /*
     * Public administrator authentication routes.
     */
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

    /*
     * Public one-time invitation routes.
     */
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
            'as' => 'admin.invitation.accept',
        ]
    );

    /*
     * All routes inside this group require an active, verified administrator.
     */
    $routes->group('', [
        'filter' => 'adminAuth',
    ], static function (
        RouteCollection $routes
    ): void {
        $routes->post(
            'logout',
            'AdminAuthenticationController::logout',
            [
                'as' => 'admin.logout',
            ]
        );

        $routes->get(
            'dashboard',
            'AdminDashboardController::index',
            [
                'as' => 'admin.dashboard',
            ]
        );

        /*
         * Only SUPER_ADMIN may manage other administrators.
         */
        $routes->group('users', [
            'filter' => 'superAdmin',
        ], static function (
            RouteCollection $routes
        ): void {
            $routes->get(
                '',
                'AdminUserController::index',
                [
                    'as' => 'admin.users.index',
                ]
            );

            $routes->get(
                'create',
                'AdminUserController::create',
                [
                    'as' => 'admin.users.create',
                ]
            );

            $routes->post(
                '',
                'AdminUserController::store',
                [
                    'as' => 'admin.users.store',
                ]
            );

            $routes->post(
                '(:num)/resend-invitation',
                'AdminUserController::resend/$1',
                [
                    'as' => 'admin.users.resend',
                ]
            );

            $routes->post(
                '(:num)/suspend',
                'AdminUserController::suspend/$1',
                [
                    'as' => 'admin.users.suspend',
                ]
            );
        });
    });
});

// -----------------------------------------------------------------------------
// API v1 routes
// -----------------------------------------------------------------------------

$routes->group(API_ROUTE_PREFIX, [
    'namespace' => 'App\Controllers\Api\V1',
], static function (
    RouteCollection $routes
): void {
    $routes->get(
        'health',
        'HealthController::index',
        [
            'as' => 'api.v1.health',
        ]
    );
});

// -----------------------------------------------------------------------------
// Development-only error previews
// -----------------------------------------------------------------------------

if (ENVIRONMENT === 'development') {
    $routes->group(
        '_preview/errors',
        static function (
            RouteCollection $routes
        ): void {
            $routes->get(
                '403',
                static function () {
                    return service('response')
                        ->setStatusCode(403)
                        ->setBody(
                            view('errors/html/error_403')
                        );
                }
            );

            $routes->get(
                '404',
                static function () {
                    throw \CodeIgniter\Exceptions\PageNotFoundException
                        ::forPageNotFound();
                }
            );

            $routes->get(
                '500',
                static function () {
                    return service('response')
                        ->setStatusCode(500)
                        ->setBody(
                            view('errors/html/error_500')
                        );
                }
            );
        }
    );
}
