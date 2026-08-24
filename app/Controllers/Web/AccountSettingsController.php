<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use App\Services\Account\MemberAccountSettingsService;
use App\Services\Account\MemberContactRequestService;
use App\Validation\Member\AccountSettingsValidation;
use App\Services\Account\MemberProfileReportService;
use CodeIgniter\HTTP\RedirectResponse;
use DomainException;
use Throwable;

final class AccountSettingsController extends BaseController
{
    private const ALLOWED_SECTIONS = [
        'password',
        'email',
        'visibility',
        'aadhaar-verification',
        'video-introduction',
        'report-profile',
        'plans',
        'contact',
    ];

    public function index(
        string $section = 'email'
    ): string {
        $profileReports = [];

        $section = $this->normaliseSection(
            $section
        );

        $userId = $this->authenticatedUserId();

        /** @var MemberAccountSettingsService $service */
        $service = service(
            'memberAccountSettingsService'
        );

        $settings = $service
            ->settingsForUser(
                $userId
            );

        if ($section === 'video-introduction') {
            $settings = array_merge(
                $settings,
                service(
                    'memberVideoIntroductionService'
                )->settingsForMember(
                    $userId
                )
            );
        }

        if ($section === 'aadhaar-verification') {
            /** @var \App\Services\Profile\MemberAadhaarService $aadhaarService */
            $aadhaarService = service(
                'memberAadhaarService'
            );

            $settings = array_merge(
                $settings,
                [
                    'aadhaarSettings' =>
                    $aadhaarService
                        ->settingsForMember(
                            $userId
                        ),

                    'aadhaarValidationErrors' =>
                    session(
                        'aadhaarValidationErrors'
                    ) ?? [],

                    'openAadhaarModal' =>
                    session(
                        'openAadhaarModal'
                    ) === true,
                ]
            );
        }

        $contactCaptcha = '';

        $contactRequests = [];

        if ($section === 'contact') {
            $contactCaptcha = service(
                'memberContactCaptchaService'
            )->generate();

            /** @var MemberContactRequestService $contactService */
            $contactService = service(
                'memberContactRequestService'
            );

            $contactRequests = $contactService
                ->historyForMember(
                    $userId
                );
        }

        if ($section === 'report-profile') {
            /** @var MemberProfileReportService $reportService */
            $reportService = service(
                'memberProfileReportService'
            );

            $profileReports = $reportService
                ->historyForReporter(
                    $userId
                );
        }

        return view(
            'Pages/AccountSettings/Index',
            array_merge(
                $settings,
                [
                    'pageTitle' =>
                    'Account Settings',

                    'activeSection' =>
                    $section,

                    'validationErrors' =>
                    session(
                        'validationErrors'
                    ) ?? [],

                    'formAlert' =>
                    $this->readFormAlert(),

                    'accountNotice' =>
                    session(
                        'accountNotice'
                    ),

                    'contactCaptcha' =>
                    $contactCaptcha,

                    'contactRequests' =>
                    $contactRequests,

                    'profileReports' =>
                    $profileReports,

                    'pageScripts' => [
                        'assets/js/components/form-validator.js',
                        'assets/js/components/password-toggle.js',
                        'assets/js/components/submit-loader.js',
                        'assets/js/pages/account-settings.js',
                        'assets/js/pages/video-introduction-playback.js',
                    ],
                ]
            )
        );
    }

    public function changePassword(): RedirectResponse
    {
        $input = [
            'current_password' =>
            (string) $this->request
                ->getPost(
                    'current_password'
                ),

            'password' =>
            (string) $this->request
                ->getPost(
                    'password'
                ),

            'password_confirmation' =>
            (string) $this->request
                ->getPost(
                    'password_confirmation'
                ),
        ];

        $validation = service(
            'validation'
        );

        $validation->setRules(
            AccountSettingsValidation
                ::changePasswordRules()
        );

        if (!$validation->run($input)) {
            return redirect()
                ->to(
                    route_to(
                        'web.account.settings.section',
                        'password'
                    )
                )
                ->with(
                    'validationErrors',
                    $validation->getErrors()
                );
        }

        try {
            /** @var MemberAccountSettingsService $service */
            $service = service(
                'memberAccountSettingsService'
            );

            $validated = $validation
                ->getValidated();

            $service->changePassword(
                $this->authenticatedUserId(),
                (string) $validated['current_password'],
                (string) $validated['password']
            );

            return redirect()
                ->to(
                    route_to(
                        'web.account.settings.section',
                        'password'
                    )
                )
                ->with(
                    'accountNotice',
                    [
                        'type' =>
                        'success',

                        'title' =>
                        'Password changed',

                        'message' =>
                        'Your password has been changed. '
                            . 'You will now be logged out.',

                        'logoutAfterClose' =>
                        true,
                    ]
                );
        } catch (DomainException $exception) {
            return redirect()
                ->to(
                    route_to(
                        'web.account.settings.section',
                        'password'
                    )
                )
                ->with(
                    'validationErrors',
                    [
                        'current_password' =>
                        $exception->getMessage(),
                    ]
                );
        } catch (Throwable $exception) {
            log_message(
                'error',
                'Member password change failed. '
                    . 'Member: {memberId}; '
                    . 'reason: {message}',
                [
                    'memberId' =>
                    $this->authenticatedUserId(),

                    'message' =>
                    $exception->getMessage(),
                ]
            );

            return redirect()
                ->to(
                    route_to(
                        'web.account.settings.section',
                        'password'
                    )
                )
                ->with(
                    'formAlert',
                    [
                        'type' => 'danger',
                        'title' => 'Password not changed',
                        'message' =>
                        'We could not change your password. '
                            . 'Please try again.',
                    ]
                );
        }
    }

    public function saveEmail(): RedirectResponse
    {
        $input = [
            'email_address' =>
            mb_strtolower(
                trim(
                    (string) $this->request
                        ->getPost(
                            'email_address'
                        )
                )
            ),
        ];

        $validation = service(
            'validation'
        );

        $validation->setRules(
            AccountSettingsValidation
                ::emailRules()
        );

        if (!$validation->run($input)) {
            return redirect()
                ->to(
                    route_to(
                        'web.account.settings.section',
                        'email'
                    )
                )
                ->withInput()
                ->with(
                    'validationErrors',
                    $validation->getErrors()
                );
        }

        try {
            /** @var MemberAccountSettingsService $service */
            $service = service(
                'memberAccountSettingsService'
            );

            $result = $service->saveEmail(
                $this->authenticatedUserId(),
                (string) $validation
                    ->getValidated()['email_address']
            );

            return redirect()
                ->to(
                    route_to(
                        'web.account.settings.section',
                        'email'
                    )
                )
                ->with(
                    'accountNotice',
                    [
                        'type' => 'success',
                        'title' =>
                        'Verification email sent',
                        'message' =>
                        'A verification link valid for '
                            . '24 hours has been sent to '
                            . $result['email']
                            . '.',
                        'logoutAfterClose' =>
                        false,
                    ]
                );
        } catch (DomainException $exception) {
            return redirect()
                ->to(
                    route_to(
                        'web.account.settings.section',
                        'email'
                    )
                )
                ->withInput()
                ->with(
                    'validationErrors',
                    [
                        'email_address' =>
                        $exception->getMessage(),
                    ]
                );
        } catch (Throwable $exception) {
            log_message(
                'error',
                'Member email update failed. '
                    . 'Member: {memberId}; '
                    . 'reason: {message}',
                [
                    'memberId' =>
                    $this->authenticatedUserId(),

                    'message' =>
                    $exception->getMessage(),
                ]
            );

            return redirect()
                ->to(
                    route_to(
                        'web.account.settings.section',
                        'email'
                    )
                )
                ->with(
                    'formAlert',
                    [
                        'type' => 'danger',
                        'title' =>
                        'Email not updated',
                        'message' =>
                        'We could not update your email. '
                            . 'Please try again.',
                    ]
                );
        }
    }

    public function resendEmail(): RedirectResponse
    {
        try {
            /** @var MemberAccountSettingsService $service */
            $service = service(
                'memberAccountSettingsService'
            );

            $result = $service
                ->resendEmailVerification(
                    $this->authenticatedUserId()
                );

            return redirect()
                ->to(
                    route_to(
                        'web.account.settings.section',
                        'email'
                    )
                )
                ->with(
                    'accountNotice',
                    [
                        'type' => 'success',
                        'title' =>
                        'Verification email resent',
                        'message' =>
                        'A new 24-hour verification link '
                            . 'has been sent to '
                            . $result['email']
                            . '.',
                        'logoutAfterClose' =>
                        false,
                    ]
                );
        } catch (DomainException $exception) {
            return redirect()
                ->to(
                    route_to(
                        'web.account.settings.section',
                        'email'
                    )
                )
                ->with(
                    'formAlert',
                    [
                        'type' => 'warning',
                        'title' =>
                        'Email not sent',
                        'message' =>
                        $exception->getMessage(),
                    ]
                );
        }
    }

    public function saveVisibility(): RedirectResponse
    {
        $input = [
            'profile_visibility' =>
            mb_strtoupper(
                trim(
                    (string) $this->request
                        ->getPost(
                            'profile_visibility'
                        )
                )
            ),
        ];

        $validation = service(
            'validation'
        );

        $validation->setRules(
            AccountSettingsValidation
                ::visibilityRules()
        );

        if (!$validation->run($input)) {
            return redirect()
                ->to(
                    route_to(
                        'web.account.settings.section',
                        'visibility'
                    )
                )
                ->with(
                    'validationErrors',
                    $validation->getErrors()
                );
        }

        /** @var MemberAccountSettingsService $service */
        $service = service(
            'memberAccountSettingsService'
        );

        $service->saveVisibility(
            $this->authenticatedUserId(),
            (string) $validation
                ->getValidated()['profile_visibility']
        );

        return redirect()
            ->to(
                route_to(
                    'web.account.settings.section',
                    'visibility'
                )
            )
            ->with(
                'accountNotice',
                [
                    'type' => 'success',
                    'title' =>
                    'Visibility updated',
                    'message' =>
                    'Your profile visibility has been updated.',
                    'logoutAfterClose' =>
                    false,
                ]
            );
    }

    public function contact(): RedirectResponse
    {
        $input = [
            'message' =>
            preg_replace(
                '/\s+/u',
                ' ',
                trim(
                    (string) $this->request
                        ->getPost(
                            'message'
                        )
                )
            ) ?? '',

            'captcha_answer' =>
            trim(
                (string) $this->request
                    ->getPost(
                        'captcha_answer'
                    )
            ),
        ];

        $validation = service(
            'validation'
        );

        $validation->setRules(
            AccountSettingsValidation
                ::contactRules()
        );

        if (
            !$validation->run($input)
            || !service(
                'memberContactCaptchaService'
            )->verify(
                $input['captcha_answer']
            )
        ) {
            $errors = $validation
                ->getErrors();

            if (
                !isset(
                    $errors['captcha_answer']
                )
            ) {
                $errors['captcha_answer'] =
                    'The security answer is incorrect or expired.';
            }

            return redirect()
                ->to(
                    route_to(
                        'web.account.settings.section',
                        'contact'
                    )
                )
                ->withInput()
                ->with(
                    'validationErrors',
                    $errors
                );
        }

        try {
            /** @var MemberContactRequestService $service */
            $service = service(
                'memberContactRequestService'
            );

            $requestReference = $service->create(
                $this->authenticatedUserId(),
                (string) $validation
                    ->getValidated()['message']
            );

            return redirect()
                ->to(
                    route_to(
                        'web.account.settings.section',
                        'contact'
                    )
                )
                ->with(
                    'accountNotice',
                    [
                        'type' => 'success',

                        'title' =>
                        'Request received',

                        'message' =>
                        'Your support request '
                            . $requestReference
                            . ' has been sent to our support team.',

                        'logoutAfterClose' =>
                        false,
                    ]
                );
        } catch (DomainException $exception) {
            return redirect()
                ->to(
                    route_to(
                        'web.account.settings.section',
                        'contact'
                    )
                )
                ->with(
                    'formAlert',
                    [
                        'type' => 'warning',
                        'title' =>
                        'Message not sent',
                        'message' =>
                        $exception->getMessage(),
                    ]
                );
        }
    }

    private function normaliseSection(
        string $section
    ): string {
        $section = mb_strtolower(
            trim($section)
        );

        return in_array(
            $section,
            self::ALLOWED_SECTIONS,
            true
        )
            ? $section
            : 'email';
    }
}
