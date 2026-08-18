<?php

declare(strict_types=1);

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// -----------------------------------------------------------------------------
// Public legal pages
// -----------------------------------------------------------------------------
//
// These routes intentionally remain outside the deployment-specific homepage
// condition. Terms and privacy information must remain accessible in
// development, QA and production environments.
//

// -----------------------------------------------------------------------------
// SAK Volunteer portal
// -----------------------------------------------------------------------------

$routes->group(
    'field-officer',
    [
        'namespace' =>
        'App\Controllers\FieldOfficer',
    ],
    static function (
        RouteCollection $routes
    ): void {
        /*
         * Public SAK Volunteer authentication.
         */
        $routes->get(
            'login',
            'FieldOfficerAuthenticationController::index',
            [
                'as' =>
                'field-officer.login',
            ]
        );

        $routes->post(
            'login/send-otp',
            'FieldOfficerAuthenticationController::sendOtp',
            [
                'as' =>
                'field-officer.login.send-otp',
            ]
        );

        $routes->get(
            'login/verify',
            'FieldOfficerAuthenticationController::verifyPage',
            [
                'as' =>
                'field-officer.login.verify',
            ]
        );

        $routes->post(
            'login/verify',
            'FieldOfficerAuthenticationController::verifyOtp',
            [
                'as' =>
                'field-officer.login.verify.submit',
            ]
        );

        $routes->post(
            'login/resend',
            'FieldOfficerAuthenticationController::resendOtp',
            [
                'as' =>
                'field-officer.login.resend',
            ]
        );

        $routes->post(
            'login/cancel',
            'FieldOfficerAuthenticationController::cancel',
            [
                'as' =>
                'field-officer.login.cancel',
            ]
        );

        /*
         * Public SAK Volunteer self-registration.
         *
         * These routes intentionally remain outside
         * fieldOfficerAuth.
         */
        $routes->get(
            'register',
            'FieldOfficerRegistrationController::index',
            [
                'as' =>
                'field-officer.register',
            ]
        );

        $routes->post(
            'register',
            'FieldOfficerRegistrationController::store',
            [
                'as' =>
                'field-officer.register.store',
            ]
        );

        $routes->get(
            'register/success',
            'FieldOfficerRegistrationController::success',
            [
                'as' =>
                'field-officer.register.success',
            ]
        );

        $routes->get(
            'register/master/cities/(:num)',
            'FieldOfficerRegistrationController::cities/$1',
            [
                'as' =>
                'field-officer.register.cities',
            ]
        );

        /*
         * Protected SAK Volunteer portal.
         */
        $routes->group(
            '',
            [
                'filter' =>
                'fieldOfficerAuth',
            ],
            static function (
                RouteCollection $routes
            ): void {
                $routes->post(
                    'logout',
                    'FieldOfficerAuthenticationController::logout',
                    [
                        'as' =>
                        'field-officer.logout',
                    ]
                );

                $routes->get(
                    'dashboard',
                    'FieldOfficerDashboardController::index',
                    [
                        'as' =>
                        'field-officer.dashboard',
                    ]
                );

                $routes->get(
                    'profiles',
                    'FieldOfficerProfileController::index',
                    [
                        'as' =>
                        'field-officer.profiles.index',
                    ]
                );

                $routes->get(
                    'profiles/prelaunch/(:num)',
                    'FieldOfficerProfileController::prelaunch/$1',
                    [
                        'as' =>
                        'field-officer.profiles.prelaunch.view',
                    ]
                );

                $routes->get(
                    'profiles/member/(:num)',
                    'FieldOfficerProfileController::member/$1',
                    [
                        'as' =>
                        'field-officer.profiles.member.view',
                    ]
                );

                $routes->get(
                    'profiles/member/(:num)/photos/(:num)',
                    'FieldOfficerProfileController::memberPhoto/$1/$2',
                    [
                        'as' =>
                        'field-officer.profiles.photos.medium-url',
                    ]
                );

                $routes->get(
                    'profiles/prelaunch/(:num)/photos/(:num)',
                    'FieldOfficerProfileController::prelaunchPhoto/$1/$2',
                    [
                        'as' =>
                        'field-officer.profiles.prelaunch.photo',
                    ]
                );
            }
        );
    }
);

$routes->group(
    '',
    [
        'namespace' => 'App\Controllers\Web',
    ],
    static function (
        RouteCollection $routes
    ): void {
        $routes->get(
            'terms-and-conditions',
            'LegalController::termsAndConditions',
            [
                'as' => 'web.legal.terms',
            ]
        );

        $routes->get(
            'privacy-policy',
            'LegalController::privacyPolicy',
            [
                'as' => 'web.legal.privacy',
            ]
        );

        $routes->get(
            'grievances',
            'LegalController::grievances',
            [
                'as' => 'web.legal.grievances',
            ]
        );

        $routes->get(
            'fraud-alert',
            'LegalController::fraudAlert',
            [
                'as' => 'web.legal.fraud-alert',
            ]
        );

        $routes->get(
            'cookie-policy',
            'LegalController::cookiePolicy',
            [
                'as' => 'web.legal.cookie-policy',
            ]
        );
    }
);

// -----------------------------------------------------------------------------
// Public information pages
// -----------------------------------------------------------------------------
//
// These pages remain publicly accessible in development, QA and production.
// They must therefore remain outside the deployment-specific homepage routes.
//
$routes->group(
    '',
    [
        'namespace' => 'App\Controllers\Web',
    ],
    static function (
        RouteCollection $routes
    ): void {
        $routes->get(
            'about-us',
            'InformationController::aboutUs',
            [
                'as' => 'web.information.about',
            ]
        );

        $routes->get(
            'advertise-with-us',
            'InformationController::advertiseWithUs',
            [
                'as' => 'web.information.advertise',
            ]
        );

        $routes->get(
            'payment-options',
            'InformationController::paymentOptions',
            [
                'as' => 'web.information.payment-options',
            ]
        );

        $routes->get(
            'careers',
            'InformationController::career',
            [
                'as' => 'web.information.careers',
            ]
        );
    }
);

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

        $routes->post(
            'member/aadhaar',
            'MemberAadhaarController::upload',
            [
                'as' => 'web.member.aadhaar.upload',
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
                    'InterestController::index',
                    [
                        'as' => 'web.interests',
                    ]
                );

                $routes->post(
                    'interests/received/(:segment)/accept',
                    'InterestController::accept/$1',
                    [
                        'as' =>
                        'web.interests.received.accept',
                    ]
                );

                $routes->post(
                    'interests/received/(:segment)/decline',
                    'InterestController::decline/$1',
                    [
                        'as' =>
                        'web.interests.received.decline',
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

                $routes->get(
                    'members/(:segment)',
                    'MemberProfileController::view/$1',
                    [
                        'as' =>
                        'web.members.view',
                    ]
                );

                $routes->post(
                    'members/(:segment)/interest',
                    'MemberProfileController::showInterest/$1',
                    [
                        'as' =>
                        'web.members.interest',
                    ]
                );

                $routes->post(
                    'members/(:segment)/interest/accept',
                    'MemberProfileController::acceptInterest/$1',
                    [
                        'as' =>
                        'web.members.interest.accept',
                    ]
                );

                $routes->post(
                    'members/(:segment)/interest/decline',
                    'MemberProfileController::declineInterest/$1',
                    [
                        'as' =>
                        'web.members.interest.decline',
                    ]
                );

                $routes->post(
                    'members/(:segment)/shortlist',
                    'MemberProfileController'
                        . '::toggleShortlist/$1',
                    [
                        'as' =>
                        'web.members.shortlist',
                    ]
                );

                $routes->post(
                    'members/(:segment)/block',
                    'MemberProfileController::block/$1',
                    [
                        'as' =>
                        'web.members.block',
                    ]
                );

                $routes->get(
                    'members/(:segment)/photos/(:num)/medium-url',
                    'MemberProfileController::photoMediumUrl/$1/$2',
                    [
                        'as' => 'web.members.photos.medium-url',
                    ]
                );

                /*
                * --------------------------------------------------------------------------
                * Member Search
                * --------------------------------------------------------------------------
                *
                * Search criteria and Search results deliberately use different routes.
                *
                * /search
                *     Search criteria entry/editing.
                *
                * /search/results
                *     Matching member listing, sorting and pagination.
                *
                * /search/profile
                *     Universal exact Profile-ID lookup.
                *
                * /search/cities
                *     Dependent active-city master endpoint.
                */

                $routes->get(
                    'search',
                    'SearchController::index',
                    [
                        'as' =>
                        'web.search',
                    ]
                );

                $routes->get(
                    'search/results',
                    'SearchController::results',
                    [
                        'as' =>
                        'web.search.results',
                    ]
                );

                $routes->get(
                    'search/profile',
                    'SearchController::profile',
                    [
                        'as' =>
                        'web.search.profile',
                    ]
                );

                $routes->get(
                    'search/states',
                    'SearchController::states',
                    [
                        'as' =>
                        'web.search.states',
                    ]
                );

                $routes->get(
                    'search/cities',
                    'SearchController::cities',
                    [
                        'as' =>
                        'web.search.cities',
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
        * Lazily return the medium URL for one approved,
        * member-owned photo.
        *
        * Original member photographs are deliberately never
        * exposed through member-facing gallery endpoints.
        */
        $routes->get(
            'profile/photos/(:num)/medium-url',
            'ProfilePhotoController::mediumUrl/$1',
            [
                'as' =>
                'web.profile.photos.medium-url',

                'filter' =>
                'webAuth',
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
            'profile/family-details/field-officer/verify',
            'ProfileController::verifyFamilyFieldOfficer',
            [
                'as' =>
                'web.profile.family-details.field-officer.verify',

                'filter' =>
                'webAuth',
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
            'account-settings',
            'AccountSettingsController::index',
            [
                'as' =>
                'web.account.settings',
            ]
        );

        $routes->get(
            'account-settings/(:segment)',
            'AccountSettingsController::index/$1',
            [
                'as' =>
                'web.account.settings.section',
            ]
        );

        $routes->post(
            'account-settings/password',
            'AccountSettingsController::changePassword',
            [
                'as' =>
                'web.account.settings.password',
            ]
        );

        $routes->post(
            'account-settings/password/setup',
            'ForgotPasswordController::sendOtpForPasswordSetup',
            [
                'as' =>
                'web.account.settings.password.setup',

                'filter' =>
                'webAuth',
            ]
        );

        $routes->post(
            'account-settings/email',
            'AccountSettingsController::saveEmail',
            [
                'as' =>
                'web.account.settings.email',
            ]
        );

        $routes->post(
            'account-settings/email/resend',
            'AccountSettingsController::resendEmail',
            [
                'as' =>
                'web.account.settings.email.resend',
            ]
        );

        $routes->post(
            'account-settings/visibility',
            'AccountSettingsController::saveVisibility',
            [
                'as' =>
                'web.account.settings.visibility',
            ]
        );

        $routes->post(
            'account-settings/contact',
            'AccountSettingsController::contact',
            [
                'as' =>
                'web.account.settings.contact',
            ]
        );

        $routes->post(
            'members/(:segment)/report',
            'MemberProfileController::report/$1',
            [
                'as' =>
                'web.members.report',
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
            'profile/master/states/(:num)',
            'ProfileMasterController::states/$1',
            [
                'as' => 'web.profile.master.states',
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

    /*
    * Administrator Forgot Password
    *
    * These routes must remain publicly accessible because the
    * administrator is not authenticated during password recovery.
    */
    $routes->group(
        'admin/forgot-password',
        [
            'namespace' =>
            'App\Controllers\Admin',
        ],
        static function (
            $routes
        ): void {

            $routes->get(
                '',
                'AdminForgotPasswordController::index',
                [
                    'as' =>
                    'admin.forgot-password',
                ]
            );

            $routes->post(
                'send-otp',
                'AdminForgotPasswordController::sendOtp',
                [
                    'as' =>
                    'admin.forgot-password.send-otp',
                ]
            );

            $routes->get(
                'verify',
                'AdminForgotPasswordController::verifyPage',
                [
                    'as' =>
                    'admin.forgot-password.verify',
                ]
            );

            $routes->post(
                'verify',
                'AdminForgotPasswordController::verifyOtp',
                [
                    'as' =>
                    'admin.forgot-password.verify.submit',
                ]
            );

            $routes->post(
                'resend',
                'AdminForgotPasswordController::resendOtp',
                [
                    'as' =>
                    'admin.forgot-password.resend',
                ]
            );

            $routes->get(
                'password',
                'AdminForgotPasswordController::passwordPage',
                [
                    'as' =>
                    'admin.forgot-password.password',
                ]
            );

            $routes->post(
                'password',
                'AdminForgotPasswordController::updatePassword',
                [
                    'as' =>
                    'admin.forgot-password.password.update',
                ]
            );

            $routes->post(
                'cancel',
                'AdminForgotPasswordController::cancel',
                [
                    'as' =>
                    'admin.forgot-password.cancel',
                ]
            );
        }
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

        $routes->group(
            'support',
            static function (
                \CodeIgniter\Router\RouteCollection $routes
            ): void {
                $routes->get(
                    'profile-reports',
                    'MemberSupportController::reports',
                    [
                        'as' =>
                        'admin.support.reports',
                    ]
                );

                $routes->post(
                    'profile-reports/(:num)',
                    'MemberSupportController::updateReport/$1',
                    [
                        'as' =>
                        'admin.support.reports.update',
                    ]
                );

                $routes->get(
                    'contact-requests',
                    'MemberSupportController::contacts',
                    [
                        'as' =>
                        'admin.support.contacts',
                    ]
                );

                $routes->post(
                    'contact-requests/(:num)',
                    'MemberSupportController::updateContact/$1',
                    [
                        'as' =>
                        'admin.support.contacts.update',
                    ]
                );
            }
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
        * --------------------------------------------------------------------------
        * SAK Volunteer management
        * --------------------------------------------------------------------------
        *
        * Authenticated Admin users may:
        *
        * - list;
        * - view verification documents;
        * - add;
        * - edit normal volunteer data;
        * - approve/reject self-registration;
        * - activate/deactivate according to the existing business rules.
        *
        * Verification-document replacement has a stricter Super Admin boundary.
        */
        $routes->group(
            'field-officers',
            [],
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

                /*
         * Private document download.
         *
         * No physical writable path is exposed.
         */
                $routes->get(
                    '(:num)/documents/(:segment)',
                    'FieldOfficerController::document/$1/$2',
                    [
                        'as' =>
                        'admin.field-officers.document',
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

                $routes->post(
                    '(:num)/approve-registration',
                    'FieldOfficerController::approveRegistration/$1',
                    [
                        'as' =>
                        'admin.field-officers.approve-registration',
                    ]
                );

                $routes->post(
                    '(:num)/reject-registration',
                    'FieldOfficerController::rejectRegistration/$1',
                    [
                        'as' =>
                        'admin.field-officers.reject-registration',
                    ]
                );

                /*
         * Document replacement is the only SAK Volunteer operation
         * restricted specifically to Super Admin by this requirement.
         */
                $routes->post(
                    '(:num)/documents/(:segment)/replace',
                    'FieldOfficerController::replaceDocument/$1/$2',
                    [
                        'as' =>
                        'admin.field-officers.document.replace',

                        'filter' =>
                        'superAdmin',
                    ]
                );

                /*
 * Display profiles connected with one SAK Volunteer.
 *
 * The listing reuses the existing SAK Volunteer profile-list service
 * and UI. Access is protected by the parent adminAuth group.
 */
                $routes->get(
                    '(:num)/profiles',
                    'FieldOfficerController::profiles/$1',
                    [
                        'as' =>
                        'admin.field-officers.profiles',
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

                $routes->group(
                    'aadhaar-approvals',
                    static function (RouteCollection $routes): void {
                        $routes->get(
                            '',
                            'MemberAadhaarReviewController::index',
                            [
                                'as' => 'admin.members.aadhaar-approvals',
                            ]
                        );

                        $routes->get(
                            '(:segment)/document',
                            'MemberAadhaarReviewController::document/$1',
                            [
                                'as' =>
                                'admin.members.aadhaar-approvals.document',
                            ]
                        );

                        $routes->get(
                            '(:segment)',
                            'MemberAadhaarReviewController::review/$1',
                            [
                                'as' =>
                                'admin.members.aadhaar-approvals.review',
                            ]
                        );

                        $routes->post(
                            '(:segment)/approve',
                            'MemberAadhaarReviewController::approve/$1',
                            [
                                'as' =>
                                'admin.members.aadhaar-approvals.approve',
                            ]
                        );

                        $routes->post(
                            '(:segment)/reject',
                            'MemberAadhaarReviewController::reject/$1',
                            [
                                'as' =>
                                'admin.members.aadhaar-approvals.reject',
                            ]
                        );
                    }
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

        $routes->post(
            'field-officer/verify',
            'PrelaunchProfileController::verifyFieldOfficer',
            [
                'as' => 'prelaunch.field-officer.verify',
            ]
        );

        /*
        * Return active cities for the selected state.
        *
        * This endpoint is intentionally public because the standalone
        * prelaunch profile form does not require member authentication.
        *
        * Example:
        * GET /prelaunch/profile/master/cities/29
        */
        $routes->get(
            'profile/master/states/(:num)',
            'PrelaunchProfileController::states/$1',
            [
                'as' =>
                'prelaunch.master.states',
            ]
        );

        $routes->get(
            'profile/master/cities/(:num)',
            'PrelaunchProfileController::cities/$1',
            [
                'as' =>
                'prelaunch.master.cities',
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
