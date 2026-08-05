<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use App\Services\Registration\RegisterFreeResult;
use App\Services\Registration\RegisterFreeService;
use App\Support\AuthenticationErrorContext;
use App\Validation\RegisterFreeValidation;
use CodeIgniter\HTTP\RedirectResponse;
use Throwable;

/**
 * Handles public registration form submissions.
 */
final class RegistrationController extends BaseController
{
    /**
     * Validate and process the Register Free form.
     */
    public function create(): RedirectResponse
    {
        $input = $this->getRegistrationInput();

        $validation = service(
            'validation'
        );

        $validation->setRules(
            RegisterFreeValidation::rulesFor(
                $input
            )
        );

        if (!$validation->run($input)) {
            return redirect()
                ->back()
                ->withInput()
                ->with(
                    'validationErrors',
                    $validation->getErrors()
                );
        }

        /*
         * Only successfully validated fields enter the service. Unexpected
         * submitted fields cannot enter the registration workflow.
         */
        $validatedData = $validation
            ->getValidated();

        try {
            /** @var RegisterFreeService $service */
            $service = service(
                'registerFreeService'
            );

            $result = $service->register(
                $validatedData
            );

            /*
             * Duplicate contact, invalid business state and OTP-limit results
             * are expected outcomes and must not be logged as runtime errors.
             */
            if (!$result->successful) {
                return $this
                    ->handleRegistrationFailure(
                        $result
                    );
            }

            /*
             * Store only identifiers required by the registration OTP flow.
             * Never store the plain OTP in the session.
             */
            session()->set([
                'pending_registration_user_id' =>
                $result->userId,

                'pending_mobile_contact_id' =>
                $result->mobileContactId,

                'pending_profile_reference' =>
                $result->profileReference,
            ]);

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
                        'OTP sent',

                        'message' =>
                        'Please enter the OTP sent to your mobile number.',
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
                    'member_registration',

                    'controller' =>
                    self::class,

                    'method' =>
                    __FUNCTION__,

                    /*
                     * Store only non-sensitive registration classification.
                     */
                    'profile_created_for' =>
                    (string) (
                        $validatedData['profile_created_for']
                        ?? $input['profile_created_for']
                        ?? ''
                    ),

                    'gender' =>
                    (string) (
                        $validatedData['gender']
                        ?? $input['gender']
                        ?? ''
                    ),

                    /*
                     * Limited correlation only; never store the complete
                     * submitted mobile number.
                     */
                    'mobile_suffix' =>
                    AuthenticationErrorContext
                        ::mobileSuffix(
                            (string) (
                                $validatedData['mobile_number']
                                ?? $input['mobile_number']
                                ?? ''
                            )
                        ),
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
                        'Registration failed',

                        'message' =>
                        'We could not complete your registration. '
                            . 'Please try again.',
                    ]
                );
        }
    }

    /**
     * Read and normalize only fields expected from the registration form.
     *
     * Email is intentionally excluded. Even when an outdated or malicious
     * client submits an email field, it cannot enter the validated payload.
     *
     * @return array<string, string>
     */
    private function getRegistrationInput(): array
    {
        return [
            'profile_created_for' =>
            trim(
                (string) $this->request
                    ->getPost(
                        'profile_created_for'
                    )
            ),

            'gender' =>
            trim(
                (string) $this->request
                    ->getPost(
                        'gender'
                    )
            ),

            'full_name' =>
            trim(
                (string) $this->request
                    ->getPost(
                        'full_name'
                    )
            ),

            'country_code' =>
            trim(
                (string) $this->request
                    ->getPost(
                        'country_code'
                    )
            ),

            'mobile_number' =>
            preg_replace(
                '/\D+/',
                '',
                (string) $this->request
                    ->getPost(
                        'mobile_number'
                    )
            ) ?? '',

            /*
             * Passwords must not be trimmed because spaces may legitimately
             * form part of the submitted password.
             */
            'password' =>
            (string) $this->request
                ->getPost(
                    'password'
                ),
        ];
    }

    /**
     * Convert expected service failures into field or form errors.
     */
    private function handleRegistrationFailure(
        RegisterFreeResult $result
    ): RedirectResponse {
        if (
            $result->field !== null
            && $result->message !== null
        ) {
            return redirect()
                ->back()
                ->withInput()
                ->with(
                    'validationErrors',
                    [
                        $result->field =>
                        $result->message,
                    ]
                );
        }

        return redirect()
            ->back()
            ->withInput()
            ->with(
                'formAlert',
                [
                    'type' =>
                    'danger',

                    'title' =>
                    'Registration failed',

                    'message' =>
                    $result->message
                        ?? 'The registration could not be completed.',
                ]
            );
    }
}
