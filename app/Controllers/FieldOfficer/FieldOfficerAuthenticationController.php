<?php

declare(strict_types=1);

namespace App\Controllers\FieldOfficer;

use App\Controllers\BaseController;
use App\Services\FieldOfficer\FieldOfficerLoginService;
use App\Support\OtpInputNormalizer;
use App\Validation\FieldOfficerValidation;
use CodeIgniter\HTTP\RedirectResponse;
use RuntimeException;
use Throwable;

final class FieldOfficerAuthenticationController
extends BaseController
{
    private const SESSION_PENDING_ID =
    'fo_otp_field_officer_id';

    private const SESSION_STARTED_AT =
    'fo_otp_started_at';

    private const TEMP_LIFETIME_SECONDS =
    1800;

    public function index(): string|RedirectResponse
    {
        if (
            session(
                'fo_is_authenticated'
            ) === true
        ) {
            return redirect()->to(
                route_to(
                    'field-officer.dashboard'
                )
            );
        }

        $this->preventPageCaching();

        return view(
            'FieldOfficer/Authentication/Login',
            [
                'pageTitle' =>
                'Field Officer Login',

                'validationErrors' =>
                $this
                    ->readValidationErrors(),

                'formAlert' =>
                $this->readFormAlert(),

                'mobileNumber' =>
                $this->readFlashString(
                    'foLoginMobile'
                ),

                'pageScripts' => [
                    'assets/js/components/submit-loader.js',
                ],
            ]
        );
    }

    public function sendOtp(): RedirectResponse
    {
        $mobileNumber =
            preg_replace(
                '/\D+/',
                '',
                (string) $this->request
                    ->getPost(
                        'mobile_number'
                    )
            ) ?? '';

        $validation =
            service('validation');

        $validation->setRules(
            FieldOfficerValidation
                ::loginRules()
        );

        if (
            !$validation->run([
                'mobile_number' =>
                $mobileNumber,
            ])
        ) {
            return redirect()
                ->to(
                    route_to(
                        'field-officer.login'
                    )
                )
                ->with(
                    'foLoginMobile',
                    $mobileNumber
                )
                ->with(
                    'validationErrors',
                    $validation
                        ->getErrors()
                );
        }

        try {
            /** @var FieldOfficerLoginService $service */
            $service = service(
                'fieldOfficerLoginService'
            );

            $result =
                $service->requestOtp(
                    $mobileNumber
                );

            if (
                !$result->successful
                || $result
                ->fieldOfficerId === null
            ) {
                return redirect()
                    ->to(
                        route_to(
                            'field-officer.login'
                        )
                    )
                    ->with(
                        'foLoginMobile',
                        $mobileNumber
                    )
                    ->with(
                        'formAlert',
                        [
                            'type' =>
                            'danger',

                            'title' =>
                            'Login unavailable',

                            'message' =>
                            $result->message
                                ?? 'Login could not be started.',
                        ]
                    );
            }

            $this->clearPendingOtp();

            session()->regenerate(
                true
            );

            session()->set([
                self::SESSION_PENDING_ID =>
                $result
                    ->fieldOfficerId,

                self::SESSION_STARTED_AT =>
                time(),
            ]);

            return redirect()
                ->to(
                    route_to(
                        'field-officer.login.verify'
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
                        $result->message,
                    ]
                );
        } catch (Throwable $exception) {
            log_message(
                'error',
                'Field Officer OTP request failed: {message}',
                [
                    'message' =>
                    $exception
                        ->getMessage(),
                ]
            );

            return redirect()
                ->to(
                    route_to(
                        'field-officer.login'
                    )
                )
                ->with(
                    'formAlert',
                    [
                        'type' =>
                        'danger',

                        'title' =>
                        'Login unavailable',

                        'message' =>
                        'The OTP could not be sent. '
                            . 'Please try again.',
                    ]
                );
        }
    }

    public function verifyPage(): string|RedirectResponse
    {
        $fieldOfficerId =
            $this->pendingFieldOfficerId();

        if ($fieldOfficerId === null) {
            return redirect()
                ->to(
                    route_to(
                        'field-officer.login'
                    )
                );
        }

        $this->preventPageCaching();

        /** @var FieldOfficerLoginService $service */
        $service = service(
            'fieldOfficerLoginService'
        );

        return view(
            'Pages/Registration/VerifyOtp',
            [
                'pageTitle' =>
                'Verify Field Officer OTP',

                'heading' =>
                'Verify your mobile',

                'description' =>
                'Enter the four-digit OTP sent '
                    . 'to your registered mobile number.',

                'profileReference' =>
                '',

                'expiresAtTimestamp' =>
                $service
                    ->pendingExpiryTimestamp(
                        $fieldOfficerId
                    ),

                'verifyAction' =>
                route_to(
                    'field-officer.login.verify.submit'
                ),

                'resendAction' =>
                route_to(
                    'field-officer.login.resend'
                ),

                'cancelAction' =>
                route_to(
                    'field-officer.login.cancel'
                ),

                'cancelLabel' =>
                'Cancel',

                'sendLimitMessage' =>
                '',

                'validationErrors' =>
                $this
                    ->readValidationErrors(),

                'formAlert' =>
                $this
                    ->readFormAlert(),

                'pageScripts' => [
                    'assets/js/pages/registration-otp.js',
                    'assets/js/components/submit-loader.js',
                ],
            ]
        );
    }

    public function verifyOtp(): RedirectResponse
    {
        $fieldOfficerId =
            $this->pendingFieldOfficerId();

        if ($fieldOfficerId === null) {
            return redirect()
                ->to(
                    route_to(
                        'field-officer.login'
                    )
                );
        }

        $otp = implode(
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

        if (
            preg_match('/^\d{4}$/', $otp)
            !== 1
        ) {
            return redirect()
                ->back()
                ->withInput()
                ->with(
                    'validationErrors',
                    [
                        'otp' =>
                        'Please enter the complete '
                            . 'four-digit OTP.',
                    ]
                );
        }

        try {
            /** @var FieldOfficerLoginService $service */
            $service = service(
                'fieldOfficerLoginService'
            );

            $result =
                $service->verifyOtp(
                    $fieldOfficerId,
                    $otp
                );

            if (
                !$result->successful
                || !is_array(
                    $result->fieldOfficer
                )
            ) {
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
                            $result->message
                                ?? 'The OTP could not be verified.',
                        ]
                    );
            }

            $fieldOfficer =
                $result
                ->fieldOfficer;

            $this->clearPendingOtp();

            session()->regenerate(
                true
            );

            session()->set([
                'fo_is_authenticated' =>
                true,

                'fo_field_officer_id' =>
                (int) $fieldOfficer['id'],

                'fo_field_officer_name' =>
                (string) $fieldOfficer['full_name'],

                'fo_field_officer_code' =>
                (string) $fieldOfficer['officer_code'],

                'fo_authenticated_at' =>
                time(),
            ]);

            return redirect()
                ->to(
                    route_to(
                        'field-officer.dashboard'
                    )
                )
                ->with(
                    'formAlert',
                    [
                        'type' =>
                        'success',

                        'title' =>
                        'Welcome',

                        'message' =>
                        'Field Officer login successful.',
                    ]
                );
        } catch (Throwable $exception) {
            log_message(
                'error',
                'Field Officer OTP verification '
                    . 'failed: {message}',
                [
                    'message' =>
                    $exception
                        ->getMessage(),
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
                        'The OTP could not be verified.',
                    ]
                );
        }
    }

    public function resendOtp(): RedirectResponse
    {
        $fieldOfficerId =
            $this->pendingFieldOfficerId();

        if ($fieldOfficerId === null) {
            return redirect()
                ->to(
                    route_to(
                        'field-officer.login'
                    )
                );
        }

        /** @var FieldOfficerLoginService $service */
        $service = service(
            'fieldOfficerLoginService'
        );

        $result =
            $service->resendOtp(
                $fieldOfficerId
            );

        return redirect()
            ->back()
            ->with(
                'formAlert',
                [
                    'type' =>
                    $result->successful
                        ? 'success'
                        : 'danger',

                    'title' =>
                    $result->successful
                        ? 'OTP sent'
                        : 'OTP not sent',

                    'message' =>
                    $result->message,
                ]
            );
    }

    public function cancel(): RedirectResponse
    {
        $this->clearPendingOtp();

        return redirect()
            ->to(
                route_to(
                    'field-officer.login'
                )
            );
    }

    public function logout(): RedirectResponse
    {
        session()->remove([
            'fo_is_authenticated',
            'fo_field_officer_id',
            'fo_field_officer_name',
            'fo_field_officer_code',
            'fo_authenticated_at',

            self::SESSION_PENDING_ID,
            self::SESSION_STARTED_AT,
        ]);

        session()->regenerate(
            true
        );

        return redirect()
            ->to(
                route_to(
                    'field-officer.login'
                )
            )
            ->with(
                'formAlert',
                [
                    'type' =>
                    'success',

                    'title' =>
                    'Logged out',

                    'message' =>
                    'You have logged out successfully.',
                ]
            );
    }

    private function pendingFieldOfficerId(): ?int
    {
        $id = session(
            self::SESSION_PENDING_ID
        );

        $startedAt = session(
            self::SESSION_STARTED_AT
        );

        if (
            !is_numeric($id)
            || !is_numeric($startedAt)
            || time() - (int) $startedAt
            > self::TEMP_LIFETIME_SECONDS
        ) {
            $this->clearPendingOtp();

            return null;
        }

        return (int) $id;
    }

    private function clearPendingOtp(): void
    {
        session()->remove([
            self::SESSION_PENDING_ID,
            self::SESSION_STARTED_AT,
        ]);
    }
}
