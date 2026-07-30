<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use App\Services\Authentication\OtpLoginResult;
use App\Services\Authentication\OtpLoginService;
use App\Support\OtpInputNormalizer;
use App\Validation\OtpLoginValidation;
use CodeIgniter\HTTP\RedirectResponse;
use RuntimeException;
use Throwable;

/**
 * Handles passwordless member login through a verified mobile OTP.
 */
final class OtpLoginController extends BaseController
{
    private const SESSION_USER_ID =
    'otp_login_user_id';

    private const SESSION_MOBILE_CONTACT_ID =
    'otp_login_mobile_contact_id';

    private const SESSION_STARTED_AT =
    'otp_login_started_at';

    private const LOGIN_SESSION_LIFETIME_SECONDS =
    1800;

    /**
     * Display the verified-mobile input page.
     */
    public function index(): string|RedirectResponse
    {
        if ($this->isAuthenticated()) {
            return redirect()->to(
                route_to('web.dashboard')
            );
        }

        $this->preventPageCaching();

        return view(
            'Pages/Authentication/LoginOtp',
            [
                'pageTitle' =>
                'Login with OTP',

                'validationErrors' =>
                $this->readValidationErrors(),

                'formAlert' =>
                $this->readFormAlert(),

                'mobileNumber' =>
                $this->readFlashString(
                    'otpLoginMobileNumber'
                ),

                'pageScripts' => [
                    'assets/js/pages/registration-form.js',
                    'assets/js/components/submit-loader.js',
                ],
            ]
        );
    }

    /**
     * Validate the mobile and issue a LOGIN OTP.
     */
    public function sendOtp(): RedirectResponse
    {
        if ($this->isAuthenticated()) {
            return redirect()->to(
                route_to('web.dashboard')
            );
        }

        $mobileNumber = preg_replace(
            '/\D+/',
            '',
            (string) $this->request->getPost(
                'mobile_number'
            )
        ) ?? '';

        $validation = service('validation');

        $validation->setRules(
            OtpLoginValidation::mobileRules()
        );

        if (
            !$validation->run([
                'mobile_number' =>
                $mobileNumber,
            ])
        ) {
            return $this->redirectToOtpLogin(
                mobileNumber: $mobileNumber,
                validationErrors: $validation->getErrors()
            );
        }

        try {
            /** @var OtpLoginService $service */
            $service = service(
                'otpLoginService'
            );

            $result = $service->requestOtp(
                $mobileNumber
            );

            if (!$result->successful) {
                return $this->handleRequestFailure(
                    $result,
                    $mobileNumber
                );
            }

            if (
                $result->userId === null
                || $result->mobileContactId
                === null
            ) {
                throw new RuntimeException(
                    'Successful login OTP request returned no identifiers.'
                );
            }

            /*
             * Start a fresh temporary login state.
             */
            $this->clearOtpLoginSession();

            session()->regenerate(true);

            session()->set([
                self::SESSION_USER_ID =>
                $result->userId,

                self::SESSION_MOBILE_CONTACT_ID =>
                $result->mobileContactId,

                self::SESSION_STARTED_AT =>
                time(),
            ]);

            return redirect()
                ->to(
                    route_to(
                        'web.login.otp.verify'
                    )
                )
                ->with('formAlert', [
                    'type' => 'success',
                    'title' => 'OTP sent',
                    'message' =>
                    $result->message
                        ?? 'An OTP has been sent to your verified mobile number.',
                ]);
        } catch (Throwable $exception) {
            log_message(
                'error',
                'Login OTP request failed: {message}',
                [
                    'message' =>
                    $exception->getMessage(),
                ]
            );

            return $this->redirectToOtpLogin(
                mobileNumber: $mobileNumber,
                formAlert: [
                    'type' => 'danger',
                    'title' =>
                    'Unable to send OTP',
                    'message' =>
                    'We could not send the OTP. '
                        . 'Please try again.',
                ]
            );
        }
    }

    /**
     * Display the existing reusable OTP screen.
     */
    public function verifyPage(): string|RedirectResponse
    {
        if ($this->isAuthenticated()) {
            return redirect()->to(
                route_to('web.dashboard')
            );
        }

        $this->preventPageCaching();

        $pending =
            $this->getPendingOtpLoginSession();

        if ($pending === null) {
            return $this->redirectToOtpLogin(
                formAlert: [
                    'type' => 'warning',
                    'title' =>
                    'OTP login expired',
                    'message' =>
                    'Please enter your mobile number again.',
                ]
            );
        }

        try {
            /** @var OtpLoginService $service */
            $service = service(
                'otpLoginService'
            );

            $expiresAtTimestamp =
                $service
                ->getPendingExpiryTimestamp(
                    $pending['mobileContactId']
                );

            return view(
                'Pages/Registration/VerifyOtp',
                [
                    'pageTitle' =>
                    'Verify Login OTP',

                    'heading' =>
                    'Verify login OTP',

                    'description' =>
                    'Enter the four-digit OTP sent to your verified mobile number.',

                    'profileReference' =>
                    null,

                    'expiresAtTimestamp' =>
                    $expiresAtTimestamp,

                    'verifyAction' =>
                    route_to(
                        'web.login.otp.verify.submit'
                    ),

                    'resendAction' =>
                    route_to(
                        'web.login.otp.resend'
                    ),

                    'cancelAction' =>
                    route_to(
                        'web.login.otp.cancel'
                    ),

                    'cancelLabel' =>
                    'Cancel login',

                    'sendLimitMessage' =>
                    'You can request a maximum of five login OTPs within 24 hours.',

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
                'Unable to display login OTP page: {message}',
                [
                    'message' =>
                    $exception->getMessage(),
                ]
            );

            $this->clearOtpLoginSession();

            return $this->redirectToOtpLogin(
                formAlert: [
                    'type' => 'danger',
                    'title' =>
                    'OTP login unavailable',
                    'message' =>
                    'Please start OTP login again.',
                ]
            );
        }
    }

    /**
     * Verify OTP and create the authenticated member session.
     */
    public function verifyOtp(): RedirectResponse
    {
        if ($this->isAuthenticated()) {
            return redirect()->to(
                route_to('web.dashboard')
            );
        }

        $pending =
            $this->getPendingOtpLoginSession();

        if ($pending === null) {
            return $this->redirectToOtpLogin();
        }

        $otp = $this->readOtp();

        if (!OtpInputNormalizer::isValid($otp)) {
            return redirect()
                ->back()
                ->withInput()
                ->with('validationErrors', [
                    'otp' =>
                    'Please enter the complete four-digit OTP.',
                ]);
        }

        try {
            /** @var OtpLoginService $service */
            $service = service(
                'otpLoginService'
            );

            $result = $service->verifyOtp(
                $pending['userId'],
                $pending['mobileContactId'],
                $otp
            );

            if (!$result->successful) {
                return redirect()
                    ->back()
                    ->withInput()
                    ->with('formAlert', [
                        'type' => 'danger',
                        'title' =>
                        'OTP verification failed',
                        'message' =>
                        $result->message
                            ?? 'The OTP could not be verified.',
                    ]);
            }

            $user = $result->user;

            if (!is_array($user)) {
                throw new RuntimeException(
                    'Successful OTP login returned no user.'
                );
            }

            $userId = $user['id'] ?? null;

            if (!is_numeric($userId)) {
                throw new RuntimeException(
                    'Successful OTP login returned an invalid user ID.'
                );
            }

            /*
             * Remove temporary OTP state before establishing authenticated
             * session data.
             */
            $this->clearOtpLoginSession();

            /*
             * Regenerate after authentication to prevent session fixation.
             */
            $this->establishMemberSession(
                $user
            );
            return redirect()
                ->to(
                    route_to(
                        'web.dashboard'
                    )
                )
                ->with('formAlert', [
                    'type' => 'success',
                    'title' => 'Welcome back',
                    'message' =>
                    'You have logged in successfully using OTP.',
                ]);
        } catch (Throwable $exception) {
            log_message(
                'error',
                'OTP login verification failed: {message}',
                [
                    'message' =>
                    $exception->getMessage(),
                ]
            );

            return redirect()
                ->back()
                ->with('formAlert', [
                    'type' => 'danger',
                    'title' =>
                    'Verification failed',
                    'message' =>
                    'We could not verify the OTP. '
                        . 'Please try again.',
                ]);
        }
    }

    /**
     * Resend a LOGIN OTP after service-level cooldown validation.
     */
    public function resendOtp(): RedirectResponse
    {
        if ($this->isAuthenticated()) {
            return redirect()->to(
                route_to('web.dashboard')
            );
        }

        $pending =
            $this->getPendingOtpLoginSession();

        if ($pending === null) {
            return $this->redirectToOtpLogin();
        }

        try {
            /** @var OtpLoginService $service */
            $service = service(
                'otpLoginService'
            );

            $result = $service->resendOtp(
                $pending['userId'],
                $pending['mobileContactId']
            );

            return redirect()
                ->back()
                ->with('formAlert', [
                    'type' =>
                    $result->successful
                        ? 'success'
                        : 'danger',

                    'title' =>
                    $result->successful
                        ? 'OTP resent'
                        : 'Unable to resend OTP',

                    'message' =>
                    $result->message
                        ?? 'The OTP could not be resent.',
                ]);
        } catch (Throwable $exception) {
            log_message(
                'error',
                'Login OTP resend failed: {message}',
                [
                    'message' =>
                    $exception->getMessage(),
                ]
            );

            return redirect()
                ->back()
                ->with('formAlert', [
                    'type' => 'danger',
                    'title' =>
                    'Unable to resend OTP',
                    'message' =>
                    'Please try again after a few moments.',
                ]);
        }
    }

    /**
     * Cancel the pending OTP login.
     */
    public function cancel(): RedirectResponse
    {
        $this->clearOtpLoginSession();

        return redirect()->to(
            route_to('web.login')
        );
    }

    /**
     * Return the pending OTP-login session when it remains valid.
     *
     * @return array{
     *     userId: int,
     *     mobileContactId: int
     * }|null
     */
    private function getPendingOtpLoginSession(): ?array
    {
        $userId = session(
            self::SESSION_USER_ID
        );

        $mobileContactId = session(
            self::SESSION_MOBILE_CONTACT_ID
        );

        $startedAt = session(
            self::SESSION_STARTED_AT
        );

        if (
            !is_numeric($userId)
            || !is_numeric($mobileContactId)
            || !is_numeric($startedAt)
        ) {
            $this->clearOtpLoginSession();

            return null;
        }

        if (
            (int) $startedAt
            < time()
            - self::LOGIN_SESSION_LIFETIME_SECONDS
        ) {
            $this->clearOtpLoginSession();

            return null;
        }

        return [
            'userId' =>
            (int) $userId,

            'mobileContactId' =>
            (int) $mobileContactId,
        ];
    }

    /**
     * Read and normalize the four reusable OTP digit fields.
     */
    private function readOtp(): string
    {
        $input = $this->request->getPost();

        return OtpInputNormalizer::fromDigitFields(
            is_array($input)
                ? $input
                : []
        );
    }

    private function handleRequestFailure(
        OtpLoginResult $result,
        string $mobileNumber
    ): RedirectResponse {
        if (
            $result->field !== null
            && $result->message !== null
        ) {
            return $this->redirectToOtpLogin(
                mobileNumber: $mobileNumber,
                validationErrors: [
                    $result->field =>
                    $result->message,
                ]
            );
        }

        return $this->redirectToOtpLogin(
            mobileNumber: $mobileNumber,
            formAlert: [
                'type' => 'danger',
                'title' =>
                'Unable to send OTP',
                'message' =>
                $result->message
                    ?? 'OTP login could not be started.',
            ]
        );
    }

    /**
     * @param array<string, string>      $validationErrors
     * @param array<string, string>|null $formAlert
     */
    private function redirectToOtpLogin(
        string $mobileNumber = '',
        array $validationErrors = [],
        ?array $formAlert = null
    ): RedirectResponse {
        $redirect = redirect()
            ->to(
                route_to(
                    'web.login.otp'
                )
            )
            ->with(
                'otpLoginMobileNumber',
                $mobileNumber
            );

        if ($validationErrors !== []) {
            $redirect->with(
                'validationErrors',
                $validationErrors
            );
        }

        if ($formAlert !== null) {
            $redirect->with(
                'formAlert',
                $formAlert
            );
        }

        return $redirect;
    }

    private function clearOtpLoginSession(): void
    {
        session()->remove([
            self::SESSION_USER_ID,
            self::SESSION_MOBILE_CONTACT_ID,
            self::SESSION_STARTED_AT,
        ]);
    }
}
