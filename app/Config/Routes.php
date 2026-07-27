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

    $routes->group(
        'forgot-password',
        [
            'namespace' => 'App\Controllers\Web',
        ],
        static function ($routes): void {
            $routes->get(
                '',
                'ForgotPasswordController::index',
                [
                    'as' => 'web.forgot-password',
                ]
            );

            $routes->post(
                'send-otp',
                'ForgotPasswordController::sendOtp',
                [
                    'as' => 'web.forgot-password.send-otp',
                ]
            );

            $routes->get(
                'verify',
                'ForgotPasswordController::verifyPage',
                [
                    'as' => 'web.forgot-password.verify',
                ]
            );

            $routes->post(
                'verify',
                'ForgotPasswordController::verifyOtp',
                [
                    'as' => 'web.forgot-password.verify.submit',
                ]
            );

            $routes->post(
                'resend',
                'ForgotPasswordController::resendOtp',
                [
                    'as' => 'web.forgot-password.resend',
                ]
            );

            $routes->get(
                'password',
                'ForgotPasswordController::passwordPage',
                [
                    'as' => 'web.forgot-password.password',
                ]
            );

            $routes->post(
                'password',
                'ForgotPasswordController::updatePassword',
                [
                    'as' => 'web.forgot-password.password.update',
                ]
            );

            $routes->post(
                'cancel',
                'ForgotPasswordController::cancel',
                [
                    'as' => 'web.forgot-password.cancel',
                ]
            );
        }
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

    /*
 * Authenticated member navigation.
 */
    $routes->group(
        '',
        [
            'filter' => 'webAuth',
        ],
        static function (
            RouteCollection $routes
        ): void {
            $routes->get(
                'matches',
                'MemberNavigationController::matches',
                [
                    'as' => 'web.matches',
                ]
            );

            $routes->get(
                'interests',
                'MemberNavigationController::interests',
                [
                    'as' => 'web.interests',
                ]
            );

            $routes->get(
                'messages',
                'MemberNavigationController::messages',
                [
                    'as' => 'web.messages',
                ]
            );

            $routes->get(
                'notifications',
                'NotificationController::index',
                [
                    'as' => 'web.notifications',
                ]
            );

            $routes->post(
                'notifications/read-all',
                'NotificationController::readAll',
                [
                    'as' =>
                    'web.notifications.read-all',
                ]
            );

            $routes->post(
                'notifications/(:num)/open',
                'NotificationController::open/$1',
                [
                    'as' =>
                    'web.notifications.open',
                ]
            );
        }
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

    /*
     * Member photos.
     */
    $routes->get(
        'profile/photos',
        'MemberPhotoController::index',
        [
            'as' => 'web.profile.photos',
            'filter' => 'webAuth',
        ]
    );

    $routes->post(
        'profile/photos',
        'MemberPhotoController::upload',
        [
            'as' => 'web.profile.photos.upload',
            'filter' => 'webAuth',
        ]
    );

    $routes->post(
        'profile/photos/(:num)/primary',
        'MemberPhotoController::makePrimary/$1',
        [
            'as' => 'web.profile.photos.primary',
            'filter' => 'webAuth',
        ]
    );

    $routes->post(
        'profile/photos/(:num)/visibility',
        'MemberPhotoController::updateVisibility/$1',
        [
            'as' => 'web.profile.photos.visibility',
            'filter' => 'webAuth',
        ]
    );

    $routes->post(
        'profile/photos/(:num)/delete',
        'MemberPhotoController::delete/$1',
        [
            'as' => 'web.profile.photos.delete',
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

    /*
 * Member photo moderation.
 *
 * Both ADMIN and SUPER_ADMIN may access these routes because this
 * group is protected by adminAuth but is outside the superAdmin group.
 */
    $routes->group(
        'members/photo-approvals',
        static function (
            RouteCollection $routes
        ): void {
            $routes->get(
                '',
                'MemberPhotoApprovalController::index',
                [
                    'as' =>
                    'admin.members.photo-approvals',
                ]
            );

            /*
         * AJAX endpoint. Signed CloudFront URLs are generated only
         * when an administrator opens the member photo modal.
         */
            $routes->get(
                'members/(:num)/photos',
                'MemberPhotoApprovalController'
                    . '::memberPhotos/$1',
                [
                    'as' =>
                    'admin.members.photo-approvals.photos',
                ]
            );

            $routes->post(
                'photos/(:num)/approve',
                'MemberPhotoApprovalController'
                    . '::approvePhoto/$1',
                [
                    'as' =>
                    'admin.members.photos.approve',
                ]
            );

            $routes->post(
                'photos/(:num)/reject',
                'MemberPhotoApprovalController'
                    . '::rejectPhoto/$1',
                [
                    'as' =>
                    'admin.members.photos.reject',
                ]
            );

            $routes->post(
                'members/(:num)/approve',
                'MemberPhotoApprovalController'
                    . '::approveMemberPhotos/$1',
                [
                    'as' =>
                    'admin.members.photos.approve-all',
                ]
            );
        }
    );
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
// Development-only routes
// -----------------------------------------------------------------------------

if (ENVIRONMENT === 'development') {
    $routes->group(
        'development',
        [
            'namespace' => 'App\Controllers\Development',
        ],
        static function (
            RouteCollection $routes
        ): void {
            $routes->get(
                'member-media-test',
                'MemberMediaTestController::index',
                [
                    'as' => 'development.member-media-test',
                ]
            );
        }
    );

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
