<?php

declare(strict_types=1);

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// -----------------------------------------------------------------------------
// Member web routes
// -----------------------------------------------------------------------------

if (env('APP_DEPLOYMENT', 'development') === 'production') {

    $prelaunchRedirect = static function () {
        return redirect()->to(
            site_url('prelaunch/profile')
        );
    };

    $routes->get(
        '/',
        $prelaunchRedirect,
        [
            'as' => 'web.home',
        ]
    );

    $routes->get(
        'login',
        $prelaunchRedirect,
        [
            'as' => 'web.login',
        ]
    );

    $routes->get(
        'register',
        $prelaunchRedirect
    );
} else {

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

        /*
 * Member login method selection.
 */
        $routes->get(
            'login',
            'AuthenticationController::index',
            [
                'as' => 'web.login',
            ]
        );

        /*
 * Existing password login.
 */
        $routes->get(
            'login/password',
            'AuthenticationController::password',
            [
                'as' =>
                'web.login.password',
            ]
        );

        $routes->post(
            'login/password',
            'AuthenticationController::login',
            [
                'as' =>
                'web.login.submit',
            ]
        );

        /*
 * Passwordless login through a verified mobile OTP.
 */
        $routes->get(
            'login/otp',
            'OtpLoginController::index',
            [
                'as' =>
                'web.login.otp',
            ]
        );

        $routes->post(
            'login/otp/send',
            'OtpLoginController::sendOtp',
            [
                'as' =>
                'web.login.otp.send',
            ]
        );

        $routes->get(
            'login/otp/verify',
            'OtpLoginController::verifyPage',
            [
                'as' =>
                'web.login.otp.verify',
            ]
        );

        $routes->post(
            'login/otp/verify',
            'OtpLoginController::verifyOtp',
            [
                'as' =>
                'web.login.otp.verify.submit',
            ]
        );

        $routes->post(
            'login/otp/resend',
            'OtpLoginController::resendOtp',
            [
                'as' =>
                'web.login.otp.resend',
            ]
        );

        $routes->post(
            'login/otp/cancel',
            'OtpLoginController::cancel',
            [
                'as' =>
                'web.login.otp.cancel',
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

                $routes->get(
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
 * Authenticated member Partner Preference routes.
 */
        $routes->group(
            'partner-preference',
            [
                'filter' => 'webAuth',
            ],
            static function (
                RouteCollection $routes
            ): void {
                $routes->get(
                    '',
                    'PartnerPreferenceController::index',
                    [
                        'as' =>
                        'web.partner-preference',
                    ]
                );

                /*
         * Existing Basic preference item routes.
         */
                $routes->get(
                    'basic/(:segment)',
                    'PartnerPreferenceController'
                        . '::editBasicItem/$1',
                    [
                        'as' =>
                        'web.partner-preference.basic.edit',
                    ]
                );

                $routes->post(
                    'basic/(:segment)',
                    'PartnerPreferenceController'
                        . '::updateBasicItem/$1',
                    [
                        'as' =>
                        'web.partner-preference.basic.update',
                    ]
                );

                /*
         * Religious, Professional, Location and Special Request.
         */
                $routes->get(
                    'item/(:segment)',
                    'PartnerPreferenceController'
                        . '::editItem/$1',
                    [
                        'as' =>
                        'web.partner-preference.item.edit',
                    ]
                );

                $routes->post(
                    'item/(:segment)',
                    'PartnerPreferenceController'
                        . '::updateItem/$1',
                    [
                        'as' =>
                        'web.partner-preference.item.update',
                    ]
                );

                /*
                * Return active cities for one or more selected states.
                *
                * Example:
                * GET /partner-preference/master/cities?state_ids=1,2,3
                */
                $routes->get(
                    'master/cities',
                    'PartnerPreferenceController::cities',
                    [
                        'as' =>
                        'web.partner-preference.master.cities',
                    ]
                );
            }
        );

        /*
        * Authenticated member profile preview.
        *
        * This page shows the logged-in member how the approved profile
        * information will appear to other members.
        */
        $routes->get(
            'profile/view',
            'ProfileController::view',
            [
                'as' => 'web.profile.view',
                'filter' => 'webAuth',
            ]
        );

        /*
        * Lazily return the original and medium URLs for one approved,
        * member-owned photo.
        *
        * The original is never signed during the initial profile-page request.
        */
        $routes->get(
            'profile/photos/(:num)/original-url',
            'ProfilePhotoController::originalUrl/$1',
            [
                'as' => 'web.profile.photos.original-url',
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
}

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

        /*
        * Only SUPER_ADMIN may manage Field Officers.
        */
        $routes->group(
            'field-officers',
            [
                'filter' => 'superAdmin',
            ],
            static function (
                RouteCollection $routes
            ): void {
                $routes->get(
                    '',
                    'FieldOfficerController::index',
                    [
                        'as' =>
                        'admin.field-officers.index',
                    ]
                );

                $routes->get(
                    'create',
                    'FieldOfficerController::create',
                    [
                        'as' =>
                        'admin.field-officers.create',
                    ]
                );

                $routes->post(
                    '',
                    'FieldOfficerController::store',
                    [
                        'as' =>
                        'admin.field-officers.store',
                    ]
                );

                $routes->get(
                    '(:num)/edit',
                    'FieldOfficerController::edit/$1',
                    [
                        'as' =>
                        'admin.field-officers.edit',
                    ]
                );

                $routes->post(
                    '(:num)',
                    'FieldOfficerController::update/$1',
                    [
                        'as' =>
                        'admin.field-officers.update',
                    ]
                );

                $routes->get(
                    'master/cities/(:num)',
                    'FieldOfficerController::cities/$1',
                    [
                        'as' =>
                        'admin.field-officers.master.cities',
                    ]
                );

                $routes->post(
                    '(:num)/activate',
                    'FieldOfficerController::activate/$1',
                    [
                        'as' =>
                        'admin.field-officers.activate',
                    ]
                );

                $routes->post(
                    '(:num)/deactivate',
                    'FieldOfficerController::deactivate/$1',
                    [
                        'as' =>
                        'admin.field-officers.deactivate',
                    ]
                );
            }
        );

        $routes->group(
            'prelaunch/profiles',
            static function (
                RouteCollection $routes
            ): void {
                $routes->get(
                    '',
                    'PrelaunchProfileController::index',
                    [
                        'as' =>
                        'admin.prelaunch.profiles.index',
                    ]
                );

                $routes->get(
                    '(:num)',
                    'PrelaunchProfileController::review/$1',
                    [
                        'as' =>
                        'admin.prelaunch.profiles.review',
                    ]
                );

                $routes->get(
                    'photos/(:num)/(:segment)',
                    'PrelaunchProfileController::photo/$1/$2',
                    [
                        'as' =>
                        'admin.prelaunch.photos.view',
                    ]
                );

                $routes->post(
                    'photos/(:num)/approve',
                    'PrelaunchProfileController::approvePhoto/$1',
                    [
                        'as' =>
                        'admin.prelaunch.photos.approve',
                    ]
                );

                $routes->post(
                    'photos/(:num)/reject',
                    'PrelaunchProfileController::rejectPhoto/$1',
                    [
                        'as' =>
                        'admin.prelaunch.photos.reject',
                    ]
                );

                // $routes->post(
                //     '(:num)/contact',
                //     'PrelaunchProfileController::updateContact/$1',
                //     [
                //         'as' =>
                //         'admin.prelaunch.profiles.contact',
                //     ]
                // );

                $routes->post(
                    '(:num)/approve',
                    'PrelaunchProfileController::approve/$1',
                    [
                        'as' =>
                        'admin.prelaunch.profiles.approve',
                    ]
                );

                $routes->post(
                    '(:num)/reject',
                    'PrelaunchProfileController::reject/$1',
                    [
                        'as' =>
                        'admin.prelaunch.profiles.reject',
                    ]
                );
            }
        );

        /*
        * Member administration.
        *
        * Both ADMIN and SUPER_ADMIN may access these routes because this group is
        * inside the existing adminAuth group and does not use the superAdmin filter.
        */
        $routes->group(
            'members',
            static function (
                RouteCollection $routes
            ): void {
                $routes->get(
                    '',
                    'MemberController::index',
                    [
                        'as' =>
                        'admin.members.index',
                    ]
                );

                /*
                * More-specific routes are declared before members/(:num).
                */
                $routes->get(
                    '(:num)/status-history',
                    'MemberController::history/$1',
                    [
                        'as' =>
                        'admin.members.history',
                    ]
                );

                $routes->get(
                    '(:num)/photos/(:num)/modal-urls',
                    'MemberController::photoModalUrls/$1/$2',
                    [
                        'as' =>
                        'admin.members.photos.modal-urls',
                    ]
                );

                $routes->post(
                    '(:num)/block',
                    'MemberController::block/$1',
                    [
                        'as' =>
                        'admin.members.block',
                    ]
                );

                $routes->post(
                    '(:num)/unblock',
                    'MemberController::unblock/$1',
                    [
                        'as' =>
                        'admin.members.unblock',
                    ]
                );

                $routes->get(
                    '(:num)',
                    'MemberController::view/$1',
                    [
                        'as' =>
                        'admin.members.view',
                    ]
                );
            }
        );
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
// Standalone pre-launch profile collection routes
// -----------------------------------------------------------------------------

$routes->group(
    'prelaunch',
    [
        'namespace' => 'App\Controllers\Prelaunch',
    ],
    static function (
        RouteCollection $routes
    ): void {
        $routes->get(
            'profile',
            'PrelaunchProfileController::index',
            [
                'as' => 'prelaunch.profile.index',
            ]
        );

        $routes->post(
            'profile',
            'PrelaunchProfileController::store',
            [
                'as' => 'prelaunch.profile.store',
            ]
        );

        // $routes->post(
        //     'field-officer/verify',
        //     'PrelaunchProfileController::verifyFieldOfficer',
        //     [
        //         'as' => 'prelaunch.field-officer.verify',
        //     ]
        // );

        /*
         * Public dependent master-data endpoints.
         *
         * These routes must not use webAuth because the prelaunch
         * collection page is intentionally public.
         */
        $routes->get(
            'partner-preference/master/cities',
            'PartnerPreferenceController::cities',
            [
                'as' =>
                'web.partner-preference.master.cities',
            ]
        );

        $routes->get(
            'profile/success/(:num)',
            'PrelaunchProfileController::success/$1',
            [
                'as' => 'prelaunch.profile.success',
            ]
        );
    }
);

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
