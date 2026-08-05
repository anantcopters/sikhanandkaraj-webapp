<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use App\Models\UserModel;
use App\Services\Registration\RegistrationOtpService;
use App\Support\OtpInputNormalizer;
use CodeIgniter\HTTP\RedirectResponse;
use RuntimeException;
use Throwable;

/**
 * Handles registration OTP display, verification, resend and cancellation.
 */
final class RegistrationVerificationController extends BaseController
{
    /**
     * Display the registration OTP screen.
     *
     * Refreshing the page reads the existing database expiry and does not
     * restart the timer or generate another OTP.
     */
    public function index(): string|RedirectResponse
    {
        $this->preventPageCaching();

        $pending = $this->getPendingSession();

        if ($pending === null) {
            return $this->redirectHomeWithWarning();
        }

        try {
            /** @var RegistrationOtpService $service */
            $service = service(
                'registrationOtpService'
            );

            $expiresAtTimestamp = $service
                ->getPendingExpiryTimestamp(
                    $pending['mobileContactId']
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
                    session(
                        'pending_profile_reference'
                    ),

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
                        'assets/js/components/submit-loader.js',
                    ],
                ]
            );
        } catch (Throwable $exception) {
            service(
                'applicationErrorLogger'
            )->exception(
                $exception,
                'error',
                [
                    'operation' =>
                    'registration_otp_page',

                    'controller' =>
                    self::class,

                    'method' =>
                    __FUNCTION__,

                    'member_user_id' =>
                    $pending['userId'],

                    'mobile_contact_id' =>
                    $pending['mobileContactId'],
                ]
            );

            return redirect()
                ->to(
                    route_to(
                        'web.home'
                    )
                )
                ->with(
                    'formAlert',
                    [
                        'type' =>
                        'danger',

                        'title' =>
                        'Verification unavailable',

                        'message' =>
                        'We could not load the OTP verification page. '
                            . 'Please try again.',
                    ]
                );
        }
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

        if (!OtpInputNormalizer::isValid($otp)) {
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
            /** @var RegistrationOtpService $service */
            $service = service(
                'registrationOtpService'
            );

            $result = $service->verify(
                $pending['userId'],
                $pending['mobileContactId'],
                $otp
            );

            /*
             * Invalid, expired or exhausted OTPs are expected verification
             * outcomes and must not be logged as application errors.
             */
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
                            'OTP verification failed',

                            'message' =>
                            $result->message,
                        ]
                    );
            }

            $userModel = new UserModel();

            $user = $userModel->find(
                $pending['userId']
            );

            if (!is_array($user)) {
                throw new RuntimeException(
                    'Verified registration user could not be loaded.'
                );
            }

            $authenticatedUserName = trim(
                (string) (
                    $user['full_name']
                    ?? ''
                )
            );

            if ($authenticatedUserName === '') {
                $authenticatedUserName =
                    'Member';
            }

            /*
             * Regenerate after successful authentication to prevent session
             * fixation.
             */
            session()->regenerate(true);

            session()->set([
                'auth_user_id' =>
                $pending['userId'],

                'auth_user_name' =>
                $authenticatedUserName,

                'auth_profile_reference' =>
                session(
                    'pending_profile_reference'
                ),

                'is_authenticated' =>
                true,

                'authenticated_at' =>
                time(),
            ]);

            /*
             * Remove temporary registration state only after the authenticated
             * session has been established successfully.
             */
            $this->clearPendingSession();

            return redirect()
                ->to(
                    route_to(
                        'web.dashboard'
                    )
                )
                ->with(
                    'formAlert',
                    [
                        'type' =>
                        'success',

                        'title' =>
                        'Registration completed',

                        'message' =>
                        'Your mobile number has been verified successfully.',
                    ]
                );
        } catch (Throwable $exception) {
            /*
             * Never include the submitted OTP in the diagnostic context.
             */
            service(
                'applicationErrorLogger'
            )->exception(
                $exception,
                'error',
                [
                    'operation' =>
                    'registration_otp_verification',

                    'controller' =>
                    self::class,

                    'method' =>
                    __FUNCTION__,

                    'member_user_id' =>
                    $pending['userId'],

                    'mobile_contact_id' =>
                    $pending['mobileContactId'],
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
                        'We could not verify the OTP. '
                            . 'Please try again.',
                    ]
                );
        }
    }

    /**
     * Resend the registration OTP after the current OTP expires.
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

            $currentExpiry = $service
                ->getPendingExpiryTimestamp(
                    $pending['mobileContactId']
                );

            /*
             * JavaScript disabling is only a user-interface improvement.
             * The server independently enforces OTP expiry.
             */
            if (
                $currentExpiry !== null
                && $currentExpiry > time()
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
                            'You can resend the OTP after '
                                . 'the timer expires.',
                        ]
                    );
            }

            $result = $service->issue(
                $pending['mobileContactId']
            );

            /*
             * OTP send limits and cooldown failures are expected outcomes.
             */
            if (!$result->successful) {
                return redirect()
                    ->back()
                    ->with(
                        'formAlert',
                        [
                            'type' =>
                            'danger',

                            'title' =>
                            'OTP limit reached',

                            'message' =>
                            $result->message,
                        ]
                    );
            }

            return redirect()
                ->to(
                    route_to(
                        'web.registration.verify'
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
                        'A new OTP has been sent to your mobile number.',
                    ]
                );
        } catch (Throwable $exception) {
            service(
                'applicationErrorLogger'
            )->exception(
                $exception,
                'error',
                [
                    'operation' =>
                    'registration_otp_resend',

                    'controller' =>
                    self::class,

                    'method' =>
                    __FUNCTION__,

                    'member_user_id' =>
                    $pending['userId'],

                    'mobile_contact_id' =>
                    $pending['mobileContactId'],
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
     * Cancel the current registration verification session.
     *
     * The incomplete database registration remains so contact limits and
     * incomplete-registration history cannot be bypassed.
     */
    public function cancel(): RedirectResponse
    {
        $this->clearPendingSession();

        return redirect()
            ->to(
                route_to(
                    'web.home'
                )
            )
            ->with(
                'formAlert',
                [
                    'type' =>
                    'info',

                    'title' =>
                    'Verification cancelled',

                    'message' =>
                    'OTP verification was cancelled.',
                ]
            );
    }

    /**
     * Read and normalize the four submitted OTP digit fields.
     */
    private function readOtp(): string
    {
        return OtpInputNormalizer::fromDigitFields(
            [
                'otp_1' =>
                $this->request
                    ->getPost(
                        'otp_1'
                    ),

                'otp_2' =>
                $this->request
                    ->getPost(
                        'otp_2'
                    ),

                'otp_3' =>
                $this->request
                    ->getPost(
                        'otp_3'
                    ),

                'otp_4' =>
                $this->request
                    ->getPost(
                        'otp_4'
                    ),
            ]
        );
    }

    /**
     * Return validated pending-registration identifiers.
     *
     * @return array{
     *     userId:int,
     *     mobileContactId:int
     * }|null
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

        $resolvedUserId =
            (int) $userId;

        $resolvedMobileContactId =
            (int) $mobileContactId;

        if (
            $resolvedUserId <= 0
            || $resolvedMobileContactId <= 0
        ) {
            return null;
        }

        return [
            'userId' =>
            $resolvedUserId,

            'mobileContactId' =>
            $resolvedMobileContactId,
        ];
    }

    /**
     * Remove temporary registration verification state.
     */
    private function clearPendingSession(): void
    {
        session()->remove([
            'pending_registration_user_id',
            'pending_mobile_contact_id',
            'pending_profile_reference',
        ]);
    }

    /**
     * Redirect when no valid pending registration exists.
     */
    private function redirectHomeWithWarning(): RedirectResponse
    {
        return redirect()
            ->to(
                route_to(
                    'web.home'
                )
            )
            ->with(
                'formAlert',
                [
                    'type' =>
                    'warning',

                    'title' =>
                    'Registration required',

                    'message' =>
                    'Please complete the registration form first.',
                ]
            );
    }
}
