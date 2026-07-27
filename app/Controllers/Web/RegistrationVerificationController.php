<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use App\Services\Registration\RegistrationOtpService;
use App\Support\OtpInputNormalizer;
use CodeIgniter\HTTP\RedirectResponse;
use Throwable;

/**
 * Handles registration OTP screen, verification, resend and cancellation.
 */
final class RegistrationVerificationController extends BaseController
{
    /**
     * Display the registration OTP screen.
     *
     * A refresh reads expiry from the database. It does not restart
     * the timer and does not generate another OTP.
     *
     * @return string|RedirectResponse
     */
    public function index(): string|RedirectResponse
    {
        /**
         * The OTP screen contains session-specific data, CSRF tokens
         * and a database-backed expiry timestamp. It must never be cached.
         */
        $this->response
            ->setHeader(
                'Cache-Control',
                'no-store, no-cache, must-revalidate, max-age=0'
            )
            ->setHeader('Pragma', 'no-cache')
            ->setHeader('Expires', '0');


        $pending = $this->getPendingSession();

        if ($pending === null) {
            return $this->redirectHomeWithWarning();
        }

        /** @var RegistrationOtpService $service */
        $service = service('registrationOtpService');

        $expiresAtTimestamp =
            $service->getPendingExpiryTimestamp(
                $pending['mobileContactId']
            );

        log_message(
            'debug',
            'OTP timer debug: now={now}, expiry={expiry}, remaining={remaining}',
            [
                'now' => time(),
                'expiry' => $expiresAtTimestamp ?? 0,
                'remaining' => $expiresAtTimestamp !== null
                    ? $expiresAtTimestamp - time()
                    : -1,
            ]
        );

        return view(
            'Pages/Registration/VerifyOtp',
            [
                'pageTitle' =>
                'Verify OTP',

                'heading' =>
                'Verify your mobile',

                'description' =>
                'Enter the four-digit OTP sent to your '
                    . 'registered mobile number.',

                'profileReference' =>
                session('pending_profile_reference'),

                'expiresAtTimestamp' =>
                $expiresAtTimestamp,

                'verifyAction' =>
                route_to(
                    'web.registration.verify.submit'
                ),

                'resendAction' =>
                route_to(
                    'web.registration.otp.resend'
                ),

                'cancelAction' =>
                route_to(
                    'web.registration.cancel'
                ),

                'cancelLabel' =>
                'Cancel',

                'sendLimitMessage' =>
                'You can request a maximum of three '
                    . 'OTPs within 24 hours.',

                'validationErrors' =>
                $this->readValidationErrors(),

                'formAlert' =>
                $this->readFormAlert(),

                'pageScripts' => [
                    'assets/js/pages/registration-otp.js',
                ],
            ]
        );
    }

    /**
     * Verify the submitted four-digit OTP.
     */
    public function verify(): RedirectResponse
    {
        $pending = $this->getPendingSession();

        if ($pending === null) {
            return $this->redirectHomeWithWarning();
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
            /** @var RegistrationOtpService $service */
            $service = service(
                'registrationOtpService'
            );

            $result = $service->verify(
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
                        'title' => 'OTP verification failed',
                        'message' => $result->message,
                    ]);
            }

            /**
             * Prevent session fixation.
             */
            session()->regenerate(true);

            /**
             * Create the authenticated user session.
             *
             * Add any other minimum identifiers needed by the dashboard.
             * Do not store passwords or sensitive contact data.
             */
            $userModel = new \App\Models\UserModel();

            $user = $userModel->find(
                $pending['userId']
            );

            $authenticatedUserName = is_array($user)
                ? trim((string) ($user['full_name'] ?? ''))
                : '';

            if ($authenticatedUserName === '') {
                $authenticatedUserName = 'Member';
            }

            session()->set([
                'auth_user_id' => $pending['userId'],

                'auth_user_name' =>
                $authenticatedUserName,

                'auth_profile_reference' =>
                session('pending_profile_reference'),

                'is_authenticated' => true,

                'authenticated_at' => time(),
            ]);

            /**
             * Remove temporary registration state after authentication.
             */
            session()->remove([
                'pending_registration_user_id',
                'pending_mobile_contact_id',
                'pending_profile_reference',
            ]);

            return redirect()
                ->to(route_to('web.dashboard'))
                ->with('formAlert', [
                    'type' => 'success',
                    'title' => 'Registration completed',
                    'message' =>
                    'Your mobile number has been verified successfully.',
                ]);
        } catch (Throwable $exception) {
            log_message(
                'error',
                'Registration OTP verification failed: {message}',
                [
                    'message' => $exception->getMessage(),
                ]
            );

            return redirect()
                ->back()
                ->with('formAlert', [
                    'type' => 'danger',
                    'title' => 'Verification failed',
                    'message' =>
                    'We could not verify the OTP. Please try again.',
                ]);
        }
    }

    /**
     * Resend OTP after the existing OTP has expired.
     */
    public function resend(): RedirectResponse
    {
        $pending = $this->getPendingSession();

        if ($pending === null) {
            return $this->redirectHomeWithWarning();
        }

        try {
            /** @var RegistrationOtpService $service */
            $service = service(
                'registrationOtpService'
            );

            $currentExpiry =
                $service->getPendingExpiryTimestamp(
                    $pending['mobileContactId']
                );

            /**
             * Server-side enforcement is mandatory.
             *
             * The disabled link in JavaScript is only for user experience.
             */
            if (
                $currentExpiry !== null
                && $currentExpiry > time()
            ) {
                return redirect()
                    ->back()
                    ->with('formAlert', [
                        'type' => 'warning',
                        'title' => 'Please wait',
                        'message' =>
                        'You can resend the OTP after the timer expires.',
                    ]);
            }

            $result = $service->issue(
                $pending['mobileContactId']
            );

            if (!$result->successful) {
                return redirect()
                    ->back()
                    ->with('formAlert', [
                        'type' => 'danger',
                        'title' => 'OTP limit reached',
                        'message' => $result->message,
                    ]);
            }

            return redirect()
                ->to(route_to('web.registration.verify'))
                ->with('formAlert', [
                    'type' => 'success',
                    'title' => 'New OTP sent',
                    'message' =>
                    'A new OTP has been sent to your mobile number.',
                ]);
        } catch (Throwable $exception) {
            log_message(
                'error',
                'Registration OTP resend failed: {message}',
                [
                    'message' => $exception->getMessage(),
                ]
            );

            return redirect()
                ->back()
                ->with('formAlert', [
                    'type' => 'danger',
                    'title' => 'Unable to resend OTP',
                    'message' =>
                    'Please try again after a few moments.',
                ]);
        }
    }

    /**
     * Cancel the current registration verification session.
     *
     * The pending database registration is retained so the mobile-number
     * rate limit and incomplete registration history cannot be bypassed.
     */
    public function cancel(): RedirectResponse
    {
        session()->remove([
            'pending_registration_user_id',
            'pending_mobile_contact_id',
            'pending_profile_reference',
        ]);

        return redirect()
            ->to(route_to('web.home'))
            ->with('formAlert', [
                'type' => 'info',
                'title' => 'Verification cancelled',
                'message' =>
                'OTP verification was cancelled.',
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
     * Return validated pending-registration session identifiers.
     *
     * @return array{userId: int, mobileContactId: int}|null
     */
    private function getPendingSession(): ?array
    {
        $userId = session(
            'pending_registration_user_id'
        );

        $mobileContactId = session(
            'pending_mobile_contact_id'
        );

        if (
            !is_numeric($userId)
            || !is_numeric($mobileContactId)
        ) {
            return null;
        }

        return [
            'userId' => (int) $userId,
            'mobileContactId' =>
            (int) $mobileContactId,
        ];
    }

    private function redirectHomeWithWarning(): RedirectResponse
    {
        return redirect()
            ->to(route_to('web.home'))
            ->with('formAlert', [
                'type' => 'warning',
                'title' => 'Registration required',
                'message' =>
                'Please complete the registration form first.',
            ]);
    }
}
