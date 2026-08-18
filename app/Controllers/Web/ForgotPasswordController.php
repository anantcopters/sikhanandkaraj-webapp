<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use App\Services\Authentication\PasswordResetService;
use App\Models\ContactVerificationModel;
use App\Validation\LoginValidation;
use App\Validation\PasswordValidation;
use App\Support\OtpInputNormalizer;
use CodeIgniter\HTTP\RedirectResponse;
use Throwable;

/**
 * Handles the forgot-password workflow.
 *
 * Flow:
 * 1. Member submits a registered mobile number or verified email address.
 * 2. OTP is sent only to the member's verified primary mobile number.
 * 3. Member verifies the OTP.
 * 4. Member creates a new password.
 * 5. Member is redirected to the login page.
 *
 * Account status is intentionally not checked in this controller.
 */
final class ForgotPasswordController extends BaseController
{
    private const SESSION_USER_ID =
    'password_reset_user_id';

    private const SESSION_MOBILE_CONTACT_ID =
    'password_reset_mobile_contact_id';

    private const SESSION_OTP_VERIFIED =
    'password_reset_otp_verified';

    private const SESSION_STARTED_AT =
    'password_reset_started_at';

    private const SESSION_VERIFIED_AT =
    'password_reset_verified_at';

    private const SESSION_FLOW =
    'password_reset_flow';

    private const FLOW_PUBLIC_RESET =
    'PUBLIC_FORGOT_PASSWORD';

    private const FLOW_PRELAUNCH_SETUP =
    'AUTHENTICATED_PRELAUNCH_SETUP';

    /**
     * Maximum age of the password-reset session.
     *
     * The OTP itself has its own database-backed expiry. This limit protects
     * the complete browser session from remaining valid indefinitely.
     */
    private const RESET_SESSION_LIFETIME_SECONDS = 1800;

    /**
     * Maximum time allowed to set a password after OTP verification.
     *
     * The service independently verifies the database OTP record. This
     * controller-level limit prevents an old browser session from continuing.
     */
    private const VERIFIED_SESSION_LIFETIME_SECONDS = 900;

    /**
     * Display the forgot-password identifier form.
     */
    public function index(): string
    {
        $this->preventPageCaching();

        return view(
            'Pages/Authentication/ForgotPassword',
            [
                'pageTitle' =>
                'Forgot Password',

                'validationErrors' =>
                $this->readValidationErrors(),

                'formAlert' =>
                $this->readFormAlert(),

                'pageScripts' => [
                    'assets/js/pages/registration-form.js',
                    'assets/js/components/submit-loader.js',
                ],
            ]
        );
    }

    /**
     * Validate the submitted identifier and send an OTP.
     *
     * The identifier can be a registered mobile number or a verified email
     * address. Members who have not added an email must use their mobile number.
     * resolves the member account and sends the OTP only to a verified mobile
     * contact associated with that account.
     */
    public function sendOtp(): RedirectResponse
    {
        /*
        * A new public request replaces any incomplete password workflow in this
        * browser session.
        */
        $this->clearResetSession();

        $loginRules = LoginValidation::rules();

        /**
         * Reuse only the identifier rule from login validation.
         *
         * The forgot-password form does not contain a login password.
         */
        $identifierRules = [
            'identifier' =>
            $loginRules['identifier'],
        ];

        if (! $this->validate($identifierRules)) {
            return redirect()
                ->back()
                ->withInput()
                ->with(
                    'validationErrors',
                    $this->validator->getErrors()
                );
        }

        $identifier = trim(
            (string) $this->request->getPost(
                'identifier'
            )
        );

        try {
            /** @var PasswordResetService $service */
            $service = service(
                'passwordResetService'
            );

            $result = $service->requestOtp(
                $identifier
            );

            if (! $result->successful) {
                return redirect()
                    ->back()
                    ->withInput()
                    ->with('formAlert', [
                        'type' =>
                        'danger',

                        'title' =>
                        'Unable to send OTP',

                        'message' =>
                        $result->message,
                    ]);
            }

            if (
                $result->userId === null
                || $result->mobileContactId === null
            ) {
                log_message(
                    'error',
                    'Password reset OTP result did not contain '
                        . 'required identifiers.'
                );

                return redirect()
                    ->back()
                    ->withInput()
                    ->with('formAlert', [
                        'type' =>
                        'danger',

                        'title' =>
                        'Unable to send OTP',

                        'message' =>
                        'We could not start the password reset. '
                            . 'Please try again.',
                    ]);
            }

            /**
             * Regenerate the session identifier before storing sensitive
             * temporary reset-state information.
             */
            $this->startResetSession(
                $result->userId,
                $result->mobileContactId,
                self::FLOW_PUBLIC_RESET
            );

            return redirect()
                ->to(
                    route_to(
                        'web.forgot-password.verify'
                    )
                )
                ->with('formAlert', [
                    'type' =>
                    'success',

                    'title' =>
                    'OTP sent',

                    'message' =>
                    'An OTP has been sent to your '
                        . 'verified mobile number.',
                ]);
        } catch (Throwable $exception) {
            log_message(
                'error',
                'Password reset OTP request failed: {message}',
                [
                    'message' =>
                    $exception->getMessage(),
                ]
            );

            return redirect()
                ->back()
                ->withInput()
                ->with('formAlert', [
                    'type' =>
                    'danger',

                    'title' =>
                    'Unable to send OTP',

                    'message' =>
                    'We could not send the OTP. '
                        . 'Please try again after a few moments.',
                ]);
        }
    }

    /**
     * Start password setup for the authenticated migrated member.
     *
     * No mobile number or email address is read from the request. The member
     * identity comes exclusively from the authenticated session.
     */
    public function sendOtpForPasswordSetup(): RedirectResponse
    {
        $userId = $this->authenticatedUserId();

        /*
        * Remove any incomplete public reset state before beginning the
        * authenticated password-setup workflow.
        */
        $this->clearResetSession();

        try {
            /** @var PasswordResetService $service */
            $service = service(
                'passwordResetService'
            );

            $result = $service
                ->requestPasswordSetupOtpForUser(
                    $userId
                );

            if (!$result->successful) {
                return redirect()
                    ->to(
                        route_to(
                            'web.account.settings.section',
                            'password'
                        )
                    )
                    ->with('formAlert', [
                        'type' =>
                        'danger',

                        'title' =>
                        'Unable to send OTP',

                        'message' =>
                        $result->message,
                    ]);
            }

            /*
         * Defensively verify that the service result still belongs to the
         * currently authenticated member.
         */
            if (
                $result->userId !== $userId
                || $result->mobileContactId === null
                || $result->mobileContactId <= 0
            ) {
                log_message(
                    'error',
                    'Authenticated password setup returned invalid '
                        . 'identifiers. Member: {memberId}',
                    [
                        'memberId' =>
                        $userId,
                    ]
                );

                return redirect()
                    ->to(
                        route_to(
                            'web.account.settings.section',
                            'password'
                        )
                    )
                    ->with('formAlert', [
                        'type' =>
                        'danger',

                        'title' =>
                        'Unable to send OTP',

                        'message' =>
                        'We could not start password setup. '
                            . 'Please try again.',
                    ]);
            }

            /*
            * Regenerate before storing password-reset authorization state.
            * The authenticated session values are retained by CodeIgniter.
            */
            $this->startResetSession(
                $userId,
                $result->mobileContactId,
                self::FLOW_PRELAUNCH_SETUP
            );

            return redirect()
                ->to(
                    route_to(
                        'web.forgot-password.verify'
                    )
                )
                ->with('formAlert', [
                    'type' =>
                    'success',

                    'title' =>
                    'OTP sent',

                    'message' =>
                    'An OTP has been sent to your '
                        . 'verified mobile number.',
                ]);
        } catch (Throwable $exception) {
            log_message(
                'error',
                'Authenticated password setup OTP request failed. '
                    . 'Member: {memberId}; reason: {message}',
                [
                    'memberId' =>
                    $userId,

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
                ->with('formAlert', [
                    'type' =>
                    'danger',

                    'title' =>
                    'Unable to send OTP',

                    'message' =>
                    'We could not send the OTP. '
                        . 'Please try again after a few moments.',
                ]);
        }
    }

    /**
     * Display the password-reset OTP verification screen.
     *
     * Refreshing this page reads the existing database expiry and does not
     * create or send another OTP.
     *
     * @return string|RedirectResponse
     */
    public function verifyPage(): string|RedirectResponse
    {
        $this->preventPageCaching();

        $pending = $this->getPendingResetSession();

        if ($pending === null) {
            return $this->redirectToForgotPassword();
        }

        if ($pending['otpVerified']) {
            return redirect()->to(
                route_to(
                    'web.forgot-password.password'
                )
            );
        }

        try {
            /** @var PasswordResetService $service */
            $service = service(
                'passwordResetService'
            );

            $expiresAtTimestamp =
                $service->getPendingExpiryTimestamp(
                    $pending['mobileContactId'],
                    $pending['purpose']
                );

            return view(
                'Pages/Registration/VerifyOtp',
                [
                    'pageTitle' =>
                    'Verify OTP',

                    'heading' =>
                    'Verify password reset OTP',

                    'description' =>
                    'Enter the four-digit OTP sent to your '
                        . 'verified primary mobile number.',

                    /**
                     * Profile reference is intentionally hidden during password reset.
                     */
                    'profileReference' =>
                    null,

                    'expiresAtTimestamp' =>
                    $expiresAtTimestamp,

                    'verifyAction' =>
                    route_to(
                        'web.forgot-password.verify.submit'
                    ),

                    'resendAction' =>
                    route_to(
                        'web.forgot-password.resend'
                    ),

                    'cancelAction' =>
                    route_to(
                        'web.forgot-password.cancel'
                    ),

                    'cancelLabel' =>
                    'Cancel',

                    'sendLimitMessage' =>
                    'You can request a maximum of three '
                        . 'password reset OTPs within 24 hours.',

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
                'Unable to display password reset OTP page: {message}',
                [
                    'message' =>
                    $exception->getMessage(),
                ]
            );

            $this->clearResetSession();

            return redirect()
                ->to(
                    route_to(
                        'web.forgot-password'
                    )
                )
                ->with('formAlert', [
                    'type' =>
                    'danger',

                    'title' =>
                    'Password reset unavailable',

                    'message' =>
                    'We could not load the OTP verification page. '
                        . 'Please start the password reset again.',
                ]);
        }
    }

    /**
     * Verify the submitted OTP.
     */
    public function verifyOtp(): RedirectResponse
    {
        $pending = $this->getPendingResetSession();

        if ($pending === null) {
            return $this->redirectToForgotPassword();
        }

        if ($pending['otpVerified']) {
            return redirect()->to(
                route_to(
                    'web.forgot-password.password'
                )
            );
        }

        $otp = $this->readOtp();

        if (! OtpInputNormalizer::isValid($otp)) {
            return redirect()
                ->back()
                ->withInput()
                ->with('validationErrors', [
                    'otp' =>
                    'Please enter the complete four-digit OTP.',
                ]);
        }

        try {
            /** @var PasswordResetService $service */
            $service = service(
                'passwordResetService'
            );

            $result = $service->verifyOtp(
                $pending['userId'],
                $pending['mobileContactId'],
                $pending['purpose'],
                $otp
            );

            if (! $result->successful) {
                return redirect()
                    ->back()
                    ->withInput()
                    ->with('formAlert', [
                        'type' =>
                        'danger',

                        'title' =>
                        'OTP verification failed',

                        'message' =>
                        $result->message,
                    ]);
            }

            /**
             * Regenerate after successful OTP verification to prevent
             * fixation and separate the verified session from the earlier
             * unverified reset session.
             */
            session()->regenerate(true);

            session()->set([
                self::SESSION_OTP_VERIFIED =>
                true,

                self::SESSION_VERIFIED_AT =>
                time(),
            ]);

            return redirect()
                ->to(
                    route_to(
                        'web.forgot-password.password'
                    )
                )
                ->with('formAlert', [
                    'type' =>
                    'success',

                    'title' =>
                    'OTP verified',

                    'message' =>
                    'Create a new password for your account.',
                ]);
        } catch (Throwable $exception) {
            log_message(
                'error',
                'Password reset OTP verification failed: {message}',
                [
                    'message' =>
                    $exception->getMessage(),
                ]
            );

            return redirect()
                ->back()
                ->with('formAlert', [
                    'type' =>
                    'danger',

                    'title' =>
                    'Verification failed',

                    'message' =>
                    'We could not verify the OTP. '
                        . 'Please try again.',
                ]);
        }
    }

    /**
     * Resend the password-reset OTP after the current OTP expires.
     */
    public function resendOtp(): RedirectResponse
    {
        $pending = $this->getPendingResetSession();

        if ($pending === null) {
            return $this->redirectToForgotPassword();
        }

        if ($pending['otpVerified']) {
            return redirect()->to(
                route_to(
                    'web.forgot-password.password'
                )
            );
        }

        try {
            /** @var PasswordResetService $service */
            $service = service(
                'passwordResetService'
            );

            $currentExpiry =
                $service->getPendingExpiryTimestamp(
                    $pending['mobileContactId'],
                    $pending['purpose']
                );

            /**
             * JavaScript disabling is only a user-interface improvement.
             * The server must independently enforce OTP expiry.
             */
            if (
                $currentExpiry !== null
                && $currentExpiry > time()
            ) {
                return redirect()
                    ->back()
                    ->with('formAlert', [
                        'type' =>
                        'warning',

                        'title' =>
                        'Please wait',

                        'message' =>
                        'You can resend the OTP after '
                            . 'the timer expires.',
                    ]);
            }

            $result = $service->resendOtp(
                $pending['userId'],
                $pending['mobileContactId'],
                $pending['purpose']
            );

            if (! $result->successful) {
                return redirect()
                    ->back()
                    ->with('formAlert', [
                        'type' =>
                        'danger',

                        'title' =>
                        'Unable to resend OTP',

                        'message' =>
                        $result->message,
                    ]);
            }

            return redirect()
                ->to(
                    route_to(
                        'web.forgot-password.verify'
                    )
                )
                ->with('formAlert', [
                    'type' =>
                    'success',

                    'title' =>
                    'New OTP sent',

                    'message' =>
                    'A new OTP has been sent to your '
                        . 'verified mobile number.',
                ]);
        } catch (Throwable $exception) {
            log_message(
                'error',
                'Password reset OTP resend failed: {message}',
                [
                    'message' =>
                    $exception->getMessage(),
                ]
            );

            return redirect()
                ->back()
                ->with('formAlert', [
                    'type' =>
                    'danger',

                    'title' =>
                    'Unable to resend OTP',

                    'message' =>
                    'Please try again after a few moments.',
                ]);
        }
    }

    /**
     * Display the new-password form after successful OTP verification.
     *
     * @return string|RedirectResponse
     */
    public function passwordPage(): string|RedirectResponse
    {
        $this->preventPageCaching();

        $pending = $this->getPendingResetSession();

        if (
            $pending === null
            || ! $pending['otpVerified']
        ) {
            return $this->redirectToForgotPassword();
        }

        return view(
            'Pages/Authentication/SetNewPassword',
            [
                'pageTitle' =>
                'Set New Password',

                'validationErrors' =>
                $this->readValidationErrors(),

                'formAlert' =>
                $this->readFormAlert(),

                'pageScripts' => [
                    'assets/js/pages/registration-form.js',
                ],
            ]
        );
    }

    /**
     * Validate and save the member's new password.
     */
    public function updatePassword(): RedirectResponse
    {
        $pending = $this->getPendingResetSession();

        if (
            $pending === null
            || ! $pending['otpVerified']
        ) {
            return $this->redirectToForgotPassword();
        }

        /**
         * Reuse the existing common password policy.
         *
         * Passing true includes password_confirmation and
         * matches[password].
         */
        if (
            ! $this->validate(
                PasswordValidation::passwordRules(true)
            )
        ) {
            return redirect()
                ->back()
                ->withInput()
                ->with(
                    'validationErrors',
                    $this->validator->getErrors()
                );
        }

        $password = (string) $this->request->getPost(
            'password'
        );

        try {
            /** @var PasswordResetService $service */
            $service = service(
                'passwordResetService'
            );

            $result = $service->resetPassword(
                $pending['userId'],
                $pending['mobileContactId'],
                $pending['purpose'],
                $password
            );

            if (! $result->successful) {
                return redirect()
                    ->back()
                    ->with('formAlert', [
                        'type' =>
                        'danger',

                        'title' =>
                        'Password reset failed',

                        'message' =>
                        $result->message,
                    ]);
            }

            /*
            * Password creation/reset invalidates the current authenticated browser
            * session. The member must authenticate again using the new password.
            */
            session()->destroy();

            return redirect()
                ->to(
                    route_to(
                        'web.login'
                    )
                )
                ->with('formAlert', [
                    'type' =>
                    'success',

                    'title' =>
                    $pending['flow']
                        === self::FLOW_PRELAUNCH_SETUP
                        ? 'Password created successfully'
                        : 'Password reset successful',

                    'message' =>
                    $pending['flow']
                        === self::FLOW_PRELAUNCH_SETUP
                        ? 'Your password has been created. '
                        . 'Please log in using your new password.'
                        : 'Your password has been reset successfully. '
                        . 'Please log in using your new password.',
                ]);
        } catch (Throwable $exception) {
            log_message(
                'error',
                'Password update failed during reset: {message}',
                [
                    'message' =>
                    $exception->getMessage(),
                ]
            );

            return redirect()
                ->back()
                ->with('formAlert', [
                    'type' =>
                    'danger',

                    'title' =>
                    'Password reset failed',

                    'message' =>
                    'We could not update your password. '
                        . 'Please try again.',
                ]);
        }
    }

    /**
     * Cancel the current password authorization workflow.
     */
    public function cancel(): RedirectResponse
    {
        $flow = session(
            self::SESSION_FLOW
        );

        if ($flow === self::FLOW_PRELAUNCH_SETUP) {
            /*
         * The setup flow began from an authenticated account. Cancel it and
         * terminate that authenticated browser session before returning to
         * Login.
         */
            session()->destroy();
        } else {
            $this->clearResetSession();
            session()->regenerate(true);
        }

        return redirect()
            ->to(
                route_to(
                    'web.login'
                )
            )
            ->with('formAlert', [
                'type' =>
                'info',

                'title' =>
                'Password process cancelled',

                'message' =>
                'The password process was cancelled.',
            ]);
    }

    /**
     * Read and normalize the four submitted OTP digit fields.
     */
    private function readOtp(): string
    {
        return OtpInputNormalizer::fromDigitFields(
            [
                'otp_1' =>
                $this->request->getPost('otp_1'),

                'otp_2' =>
                $this->request->getPost('otp_2'),

                'otp_3' =>
                $this->request->getPost('otp_3'),

                'otp_4' =>
                $this->request->getPost('otp_4'),
            ]
        );
    }

    /**
     * Store a newly initiated password authorization workflow.
     */
    private function startResetSession(
        int $userId,
        int $mobileContactId,
        string $flow
    ): void {
        if (
            $userId <= 0
            || $mobileContactId <= 0
            || !in_array(
                $flow,
                [
                    self::FLOW_PUBLIC_RESET,
                    self::FLOW_PRELAUNCH_SETUP,
                ],
                true
            )
        ) {
            throw new \InvalidArgumentException(
                'Invalid password authorization session.'
            );
        }

        session()->regenerate(true);

        session()->set([
            self::SESSION_USER_ID =>
            $userId,

            self::SESSION_MOBILE_CONTACT_ID =>
            $mobileContactId,

            self::SESSION_OTP_VERIFIED =>
            false,

            self::SESSION_STARTED_AT =>
            time(),

            self::SESSION_FLOW =>
            $flow,
        ]);

        session()->remove(
            self::SESSION_VERIFIED_AT
        );
    }

    /**
     * Return validated password authorization session context.
     *
     * For prelaunch setup, the reset user must remain identical to the
     * authenticated member throughout every downstream step.
     *
     * @return array{
     *     userId: int,
     *     mobileContactId: int,
     *     otpVerified: bool,
     *     startedAt: int,
     *     verifiedAt: int|null,
     *     flow: string,
     *     purpose: string
     * }|null
     */
    private function getPendingResetSession(): ?array
    {
        $userId = session(
            self::SESSION_USER_ID
        );

        $mobileContactId = session(
            self::SESSION_MOBILE_CONTACT_ID
        );

        $otpVerified = session(
            self::SESSION_OTP_VERIFIED
        );

        $startedAt = session(
            self::SESSION_STARTED_AT
        );

        $verifiedAt = session(
            self::SESSION_VERIFIED_AT
        );

        $flow = trim(
            (string) session(
                self::SESSION_FLOW
            )
        );

        if (
            !is_numeric($userId)
            || !is_numeric($mobileContactId)
            || !is_numeric($startedAt)
            || !in_array(
                $flow,
                [
                    self::FLOW_PUBLIC_RESET,
                    self::FLOW_PRELAUNCH_SETUP,
                ],
                true
            )
        ) {
            $this->clearResetSession();

            return null;
        }

        $userIdValue = (int) $userId;
        $mobileContactIdValue = (int) $mobileContactId;
        $startedAtTimestamp = (int) $startedAt;
        $isOtpVerified = $otpVerified === true;

        if (
            $userIdValue <= 0
            || $mobileContactIdValue <= 0
            || $startedAtTimestamp <= 0
            || (
                time() - $startedAtTimestamp
            ) > self::RESET_SESSION_LIFETIME_SECONDS
        ) {
            $this->clearResetSession();

            return null;
        }

        /*
     * The Account Settings workflow must remain attached to the member who
     * initiated it. Losing authentication or changing the authenticated user
     * immediately invalidates the setup session.
     */
        if (
            $flow === self::FLOW_PRELAUNCH_SETUP
            && (
                !$this->isAuthenticated()
                || !is_numeric(
                    session(
                        'auth_user_id'
                    )
                )
                || (int) session(
                    'auth_user_id'
                ) !== $userIdValue
            )
        ) {
            $this->clearResetSession();

            return null;
        }

        $verifiedAtTimestamp = null;

        if ($isOtpVerified) {
            if (!is_numeric($verifiedAt)) {
                $this->clearResetSession();

                return null;
            }

            $verifiedAtTimestamp = (int) $verifiedAt;

            if (
                $verifiedAtTimestamp <= 0
                || (
                    time() - $verifiedAtTimestamp
                ) > self::VERIFIED_SESSION_LIFETIME_SECONDS
            ) {
                $this->clearResetSession();

                return null;
            }
        }

        $purpose = $flow
            === self::FLOW_PRELAUNCH_SETUP
            ? ContactVerificationModel::PURPOSE_PASSWORD_SETUP
            : ContactVerificationModel::PURPOSE_PASSWORD_RESET;

        return [
            'userId' =>
            $userIdValue,

            'mobileContactId' =>
            $mobileContactIdValue,

            'otpVerified' =>
            $isOtpVerified,

            'startedAt' =>
            $startedAtTimestamp,

            'verifiedAt' =>
            $verifiedAtTimestamp,

            'flow' =>
            $flow,

            'purpose' =>
            $purpose,
        ];
    }

    /**
     * Remove all temporary password authorization data.
     */
    private function clearResetSession(): void
    {
        session()->remove([
            self::SESSION_USER_ID,
            self::SESSION_MOBILE_CONTACT_ID,
            self::SESSION_OTP_VERIFIED,
            self::SESSION_STARTED_AT,
            self::SESSION_VERIFIED_AT,
            self::SESSION_FLOW,
        ]);
    }

    /**
     * Redirect the member to the beginning of the reset flow.
     */
    private function redirectToForgotPassword(): RedirectResponse
    {
        $this->clearResetSession();

        return redirect()
            ->to(
                route_to(
                    'web.forgot-password'
                )
            )
            ->with('formAlert', [
                'type' =>
                'warning',

                'title' =>
                'Password reset required',

                'message' =>
                'Your password reset session has expired '
                    . 'or is invalid. Please start again.',
            ]);
    }
}
