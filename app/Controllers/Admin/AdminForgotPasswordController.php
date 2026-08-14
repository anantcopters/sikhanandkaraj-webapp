<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Services\Admin\Authentication\AdminPasswordResetService;
use App\Support\OtpInputNormalizer;
use App\Validation\PasswordValidation;
use CodeIgniter\HTTP\RedirectResponse;
use Throwable;

/**
 * Handles the administrator forgot-password workflow.
 *
 * Flow:
 *
 * 1. Admin submits registered email/mobile.
 * 2. OTP is sent to verified mobile.
 * 3. Admin verifies OTP.
 * 4. Admin creates a new password.
 * 5. Admin returns to Admin Login.
 *
 * This controller does not authenticate the administrator.
 */
final class AdminForgotPasswordController
extends BaseController
{
    private const SESSION_ADMIN_ID =
    'admin_password_reset_user_id';

    private const SESSION_OTP_VERIFIED =
    'admin_password_reset_otp_verified';

    private const SESSION_STARTED_AT =
    'admin_password_reset_started_at';

    private const SESSION_VERIFIED_AT =
    'admin_password_reset_verified_at';

    private const RESET_SESSION_LIFETIME_SECONDS =
    1800;

    private const VERIFIED_SESSION_LIFETIME_SECONDS =
    900;

    /**
     * Display the identifier form.
     */
    public function index(): string
    {
        $this->preventPageCaching();

        return view(
            'Admin/Authentication/ForgotPassword',
            [
                'pageTitle' =>
                'Forgot Administrator Password',

                'validationErrors' =>
                $this->readValidationErrors(),

                'formAlert' =>
                $this->readFormAlert(),

                'adminLoginUrl' =>
                route_to(
                    'admin.login'
                ),

                'pageScripts' => [
                    'assets/js/components/submit-loader.js',
                ],
            ]
        );
    }

    /**
     * Resolve the administrator and send OTP.
     */
    public function sendOtp(): RedirectResponse
    {
        $identifier =
            trim(
                (string) $this->request
                    ->getPost(
                        'identifier'
                    )
            );

        $validation =
            service(
                'validation'
            );

        $validation->setRules([
            'identifier' => [
                'label' =>
                'Email or Mobile Number',

                'rules' => [
                    'required',
                    'max_length[254]',
                ],

                'errors' => [
                    'required' =>
                    'Please enter your registered email address or mobile number.',

                    'max_length' =>
                    'The identifier is too long.',
                ],
            ],
        ]);

        if (
            !$validation->run([
                'identifier' =>
                $identifier,
            ])
        ) {
            return redirect()
                ->back()
                ->withInput()
                ->with(
                    'validationErrors',
                    $validation->getErrors()
                );
        }

        try {
            /** @var AdminPasswordResetService $service */
            $service =
                service(
                    'adminPasswordResetService'
                );

            $result =
                $service->requestOtp(
                    $identifier
                );

            if (!$result->successful) {
                return redirect()
                    ->back()
                    ->withInput()
                    ->with(
                        'formAlert',
                        [
                            'type' =>
                            'danger',

                            'title' =>
                            'Unable to send OTP',

                            'message' =>
                            $result->message,
                        ]
                    );
            }

            if (
                $result->adminUserId === null
            ) {
                throw new \RuntimeException(
                    'Password reset service did not return an administrator ID.'
                );
            }

            session()->regenerate(
                true
            );

            session()->set([
                self::SESSION_ADMIN_ID =>
                $result->adminUserId,

                self::SESSION_OTP_VERIFIED =>
                false,

                self::SESSION_STARTED_AT =>
                time(),
            ]);

            session()->remove(
                self::SESSION_VERIFIED_AT
            );

            return redirect()
                ->to(
                    route_to(
                        'admin.forgot-password.verify'
                    )
                )
                ->with(
                    'formAlert',
                    [
                        'type' =>
                        'success',

                        'title' =>
                        'OTP sent',

                        'message' =>
                        'An OTP has been sent to your verified mobile number.',
                    ]
                );
        } catch (Throwable $exception) {
            log_message(
                'error',
                'Administrator password reset OTP request failed: {message}',
                [
                    'message' =>
                    $exception->getMessage(),
                ]
            );

            return redirect()
                ->back()
                ->withInput()
                ->with(
                    'formAlert',
                    [
                        'type' =>
                        'danger',

                        'title' =>
                        'Unable to send OTP',

                        'message' =>
                        'We could not send the OTP. Please try again.',
                    ]
                );
        }
    }

    /**
     * Display the reusable OTP verification screen.
     */
    public function verifyPage(): string|RedirectResponse
    {
        $adminUserId =
            $this->pendingAdminUserId();

        if ($adminUserId === null) {
            return $this->redirectToForgotPassword();
        }

        if (
            session(
                self::SESSION_OTP_VERIFIED
            ) === true
        ) {
            return redirect()->to(
                route_to(
                    'admin.forgot-password.password'
                )
            );
        }

        try {
            /** @var AdminPasswordResetService $service */
            $service =
                service(
                    'adminPasswordResetService'
                );

            return view(
                'Pages/Registration/VerifyOtp',
                [
                    'pageTitle' =>
                    'Verify Administrator OTP',

                    'heading' =>
                    'Verify password reset OTP',

                    'description' =>
                    'Enter the four-digit OTP sent to your verified primary mobile number.',

                    'profileReference' =>
                    null,

                    'hidePublicLoginAction' =>
                    true,

                    'expiresAtTimestamp' =>
                    $service->getPendingExpiryTimestamp(
                        $adminUserId
                    ),

                    'verifyAction' =>
                    route_to(
                        'admin.forgot-password.verify.submit'
                    ),

                    'resendAction' =>
                    route_to(
                        'admin.forgot-password.resend'
                    ),

                    'cancelAction' =>
                    route_to(
                        'admin.forgot-password.cancel'
                    ),

                    'cancelLabel' =>
                    'Cancel',

                    'sendLimitMessage' =>
                    'You can request a maximum of five password reset OTPs within 24 hours.',

                    'validationErrors' =>
                    $this->readValidationErrors(),

                    'formAlert' =>
                    $this->readFormAlert(),

                    'pageScripts' => [
                        'assets/js/pages/registration-otp.js',
                        'assets/js/components/submit-loader.js',
                    ],
                ]
            );
        } catch (Throwable $exception) {
            log_message(
                'error',
                'Unable to display administrator password reset OTP page: {message}',
                [
                    'message' =>
                    $exception->getMessage(),
                ]
            );

            $this->clearResetSession();

            return $this->redirectToForgotPassword()
                ->with(
                    'formAlert',
                    [
                        'type' =>
                        'danger',

                        'title' =>
                        'Password reset unavailable',

                        'message' =>
                        'Please start the password reset again.',
                    ]
                );
        }
    }

    /**
     * Verify submitted OTP.
     */
    public function verifyOtp(): RedirectResponse
    {
        $adminUserId =
            $this->pendingAdminUserId();

        if ($adminUserId === null) {
            return $this->redirectToForgotPassword();
        }

        $otp =
            $this->readOtp();

        if (
            !OtpInputNormalizer::isValid(
                $otp
            )
        ) {
            return redirect()
                ->back()
                ->withInput()
                ->with(
                    'validationErrors',
                    [
                        'otp' =>
                        'Please enter the complete four-digit OTP.',
                    ]
                );
        }

        try {
            /** @var AdminPasswordResetService $service */
            $service =
                service(
                    'adminPasswordResetService'
                );

            $result =
                $service->verifyOtp(
                    $adminUserId,
                    $otp
                );

            if (!$result->successful) {
                return redirect()
                    ->back()
                    ->with(
                        'formAlert',
                        [
                            'type' =>
                            'danger',

                            'title' =>
                            'OTP verification failed',

                            'message' =>
                            $result->message,
                        ]
                    );
            }

            session()->regenerate(
                true
            );

            session()->set([
                self::SESSION_OTP_VERIFIED =>
                true,

                self::SESSION_VERIFIED_AT =>
                time(),
            ]);

            return redirect()
                ->to(
                    route_to(
                        'admin.forgot-password.password'
                    )
                )
                ->with(
                    'formAlert',
                    [
                        'type' =>
                        'success',

                        'title' =>
                        'OTP verified',

                        'message' =>
                        'Create your new administrator password.',
                    ]
                );
        } catch (Throwable $exception) {
            log_message(
                'error',
                'Administrator password reset OTP verification failed: {message}',
                [
                    'message' =>
                    $exception->getMessage(),
                ]
            );

            return redirect()
                ->back()
                ->with(
                    'formAlert',
                    [
                        'type' =>
                        'danger',

                        'title' =>
                        'Verification failed',

                        'message' =>
                        'We could not verify the OTP. Please try again.',
                    ]
                );
        }
    }

    /**
     * Resend OTP.
     */
    public function resendOtp(): RedirectResponse
    {
        $adminUserId =
            $this->pendingAdminUserId();

        if ($adminUserId === null) {
            return $this->redirectToForgotPassword();
        }

        if (
            session(
                self::SESSION_OTP_VERIFIED
            ) === true
        ) {
            return redirect()->to(
                route_to(
                    'admin.forgot-password.password'
                )
            );
        }

        try {
            /** @var AdminPasswordResetService $service */
            $service =
                service(
                    'adminPasswordResetService'
                );

            $expiry =
                $service->getPendingExpiryTimestamp(
                    $adminUserId
                );

            if (
                $expiry !== null
                && $expiry > time()
            ) {
                return redirect()
                    ->back()
                    ->with(
                        'formAlert',
                        [
                            'type' =>
                            'warning',

                            'title' =>
                            'Please wait',

                            'message' =>
                            'You can resend the OTP after the timer expires.',
                        ]
                    );
            }

            $result =
                $service->resendOtp(
                    $adminUserId
                );

            if (!$result->successful) {
                return redirect()
                    ->back()
                    ->with(
                        'formAlert',
                        [
                            'type' =>
                            'danger',

                            'title' =>
                            'Unable to resend OTP',

                            'message' =>
                            $result->message,
                        ]
                    );
            }

            return redirect()
                ->to(
                    route_to(
                        'admin.forgot-password.verify'
                    )
                )
                ->with(
                    'formAlert',
                    [
                        'type' =>
                        'success',

                        'title' =>
                        'New OTP sent',

                        'message' =>
                        'A new OTP has been sent to your verified mobile number.',
                    ]
                );
        } catch (Throwable $exception) {
            log_message(
                'error',
                'Administrator password reset OTP resend failed: {message}',
                [
                    'message' =>
                    $exception->getMessage(),
                ]
            );

            return redirect()
                ->back()
                ->with(
                    'formAlert',
                    [
                        'type' =>
                        'danger',

                        'title' =>
                        'Unable to resend OTP',

                        'message' =>
                        'Please try again after a few moments.',
                    ]
                );
        }
    }

    /**
     * Display the new-password page.
     */
    public function passwordPage(): string|RedirectResponse
    {
        $this->preventPageCaching();

        $adminUserId =
            $this->pendingAdminUserId();

        if (
            $adminUserId === null
            || session(
                self::SESSION_OTP_VERIFIED
            ) !== true
        ) {
            return $this->redirectToForgotPassword();
        }

        return view(
            'Admin/Authentication/SetNewPassword',
            [
                'pageTitle' =>
                'Set New Administrator Password',

                'validationErrors' =>
                $this->readValidationErrors(),

                'formAlert' =>
                $this->readFormAlert(),

                'pageScripts' => [
                    'assets/js/components/password-toggle.js',
                    'assets/js/components/submit-loader.js',
                ],
            ]
        );
    }

    /**
     * Validate and replace the password.
     */
    public function updatePassword(): RedirectResponse
    {
        $adminUserId =
            $this->pendingAdminUserId();

        if (
            $adminUserId === null
            || session(
                self::SESSION_OTP_VERIFIED
            ) !== true
        ) {
            return $this->redirectToForgotPassword();
        }

        $validation =
            service(
                'validation'
            );

        $validation->setRules(
            PasswordValidation::passwordRules()
        );

        if (
            !$validation->run(
                $this->request->getPost()
            )
        ) {
            return redirect()
                ->back()
                ->withInput()
                ->with(
                    'validationErrors',
                    $validation->getErrors()
                );
        }

        $verifiedAt =
            (int) session(
                self::SESSION_VERIFIED_AT
            );

        if (
            $verifiedAt <= 0
            || $verifiedAt <
            time()
            - self::VERIFIED_SESSION_LIFETIME_SECONDS
        ) {
            $this->clearResetSession();

            return $this->redirectToForgotPassword()
                ->with(
                    'formAlert',
                    [
                        'type' =>
                        'danger',

                        'title' =>
                        'Password reset expired',

                        'message' =>
                        'Please request a new OTP.',
                    ]
                );
        }

        try {
            /** @var AdminPasswordResetService $service */
            $service =
                service(
                    'adminPasswordResetService'
                );

            $result =
                $service->resetPassword(
                    $adminUserId,
                    (string) $this->request
                        ->getPost(
                            'password'
                        )
                );

            if (!$result->successful) {
                return redirect()
                    ->back()
                    ->withInput()
                    ->with(
                        'formAlert',
                        [
                            'type' =>
                            'danger',

                            'title' =>
                            'Password reset failed',

                            'message' =>
                            $result->message,
                        ]
                    );
            }

            $this->clearResetSession();

            return redirect()
                ->to(
                    route_to(
                        'admin.login'
                    )
                )
                ->with(
                    'formAlert',
                    [
                        'type' =>
                        'success',

                        'title' =>
                        'Password changed',

                        'message' =>
                        'Your administrator password has been changed successfully. Please log in with your new password.',
                    ]
                );
        } catch (Throwable $exception) {
            log_message(
                'error',
                'Administrator password update failed: {message}',
                [
                    'message' =>
                    $exception->getMessage(),
                ]
            );

            return redirect()
                ->back()
                ->with(
                    'formAlert',
                    [
                        'type' =>
                        'danger',

                        'title' =>
                        'Password reset failed',

                        'message' =>
                        'We could not change your password. Please try again.',
                    ]
                );
        }
    }

    /**
     * Cancel the reset process.
     */
    public function cancel(): RedirectResponse
    {
        $this->clearResetSession();

        return redirect()->to(
            route_to(
                'admin.login'
            )
        );
    }

    /**
     * Read and normalize the OTP fields.
     */
    private function readOtp(): string
    {
        return implode(
            '',
            [
                trim(
                    (string) $this->request
                        ->getPost('otp_1')
                ),
                trim(
                    (string) $this->request
                        ->getPost('otp_2')
                ),
                trim(
                    (string) $this->request
                        ->getPost('otp_3')
                ),
                trim(
                    (string) $this->request
                        ->getPost('otp_4')
                ),
            ]
        );
    }

    /**
     * Read the pending Admin ID from the reset session.
     */
    private function pendingAdminUserId(): ?int
    {
        $adminUserId =
            session(
                self::SESSION_ADMIN_ID
            );

        $startedAt =
            session(
                self::SESSION_STARTED_AT
            );

        if (
            !is_numeric($adminUserId)
            || !is_numeric($startedAt)
            || time()
            - (int) $startedAt
            > self::RESET_SESSION_LIFETIME_SECONDS
        ) {
            $this->clearResetSession();

            return null;
        }

        return (int) $adminUserId;
    }

    /**
     * Redirect to the Admin forgot-password page.
     */
    private function redirectToForgotPassword(): RedirectResponse
    {
        return redirect()->to(
            route_to(
                'admin.forgot-password'
            )
        );
    }

    /**
     * Clear all temporary password-reset session state.
     */
    private function clearResetSession(): void
    {
        session()->remove([
            self::SESSION_ADMIN_ID,
            self::SESSION_OTP_VERIFIED,
            self::SESSION_STARTED_AT,
            self::SESSION_VERIFIED_AT,
        ]);
    }
}
