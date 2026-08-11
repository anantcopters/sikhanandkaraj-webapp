<?php

declare(strict_types=1);

namespace App\Controllers\FieldOfficer;

use App\Controllers\BaseController;
use App\Services\Admin\FieldOfficerService;
use App\Services\Profile\ProfileMasterDataService;
use App\Validation\FieldOfficerValidation;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use RuntimeException;
use Throwable;

final class FieldOfficerRegistrationController
extends BaseController
{
    /**
     * Display public SAK Volunteer registration.
     */
    public function index(): string|RedirectResponse
    {
        if (
            session(
                'fo_is_authenticated'
            ) === true
        ) {
            return redirect()
                ->to(
                    route_to(
                        'field-officer.dashboard'
                    )
                );
        }

        $this->preventPageCaching();

        $formInput =
            $this->readArrayFlashData(
                'fieldOfficerRegistrationInput'
            );

        $selectedStateId = max(
            0,
            (int) (
                $formInput['state_id']
                ?? 0
            )
        );

        /** @var ProfileMasterDataService $masterService */
        $masterService =
            service(
                'profileMasterDataService'
            );

        $masterData =
            $masterService
            ->basicDetailsOptions(
                $selectedStateId > 0
                    ? $selectedStateId
                    : null
            );

        $captchaService =
            service(
                'fieldOfficerRegistrationCaptchaService'
            );

        $captchaChallenge =
            $captchaService->generate();

        return view(
            'FieldOfficer/Registration/Index',
            [
                'pageTitle' =>
                'Register as SAK Volunteer',

                'formInput' =>
                $formInput,

                'validationErrors' =>
                $this
                    ->readValidationErrors(),

                'formAlert' =>
                $this
                    ->readFormAlert(),

                'countries' =>
                isset(
                    $masterData['country']
                )
                    && is_array(
                        $masterData['country']
                    )
                    ? [
                        $masterData['country'],
                    ]
                    : [],

                'states' =>
                is_array(
                    $masterData['states']
                        ?? null
                )
                    ? $masterData['states']
                    : [],

                'cities' =>
                is_array(
                    $masterData['cities']
                        ?? null
                )
                    ? $masterData['cities']
                    : [],

                'captchaChallenge' =>
                $captchaChallenge,

                'pageScripts' => [
                    'assets/js/pages/admin-field-officer-form.js',
                    'assets/js/components/submit-loader.js',
                ],
            ]
        );
    }


    /**
     * Persist public SAK Volunteer registration.
     */
    public function store(): RedirectResponse
    {
        $input =
            $this->registrationInput();

        $captchaAnswer = trim(
            (string) $this->request
                ->getPost(
                    'captcha_answer'
                )
        );

        $validation =
            service(
                'validation'
            );

        $validation->setRules(
            FieldOfficerValidation
                ::registrationRules()
        );

        $validationInput =
            array_merge(
                $input,
                [
                    'captcha_answer' =>
                    $captchaAnswer,
                ]
            );

        if (
            !$validation->run(
                $validationInput
            )
        ) {
            service(
                'fieldOfficerRegistrationCaptchaService'
            )->clear();

            /*
             * Do not call withInput().
             *
             * Only the explicit allowlisted and normalized
             * registration fields are stored in flash data.
             * CAPTCHA must never be flashed.
             */
            return redirect()
                ->to(
                    route_to(
                        'field-officer.register'
                    )
                )
                ->with(
                    'fieldOfficerRegistrationInput',
                    $input
                )
                ->with(
                    'validationErrors',
                    $validation
                        ->getErrors()
                );
        }

        $captchaService =
            service(
                'fieldOfficerRegistrationCaptchaService'
            );

        if (
            !$captchaService->verify(
                $captchaAnswer
            )
        ) {
            return redirect()
                ->to(
                    route_to(
                        'field-officer.register'
                    )
                )
                ->with(
                    'fieldOfficerRegistrationInput',
                    $input
                )
                ->with(
                    'validationErrors',
                    [
                        'captcha_answer' =>
                        'The security verification '
                            . 'answer is incorrect or '
                            . 'has expired. Please try '
                            . 'the new question.',
                    ]
                );
        }

        /*
         * created_by never comes from the browser.
         *
         * Public registrations are assigned to the explicitly
         * configured Super Admin account.
         */
        $createdBy =
            (int) env(
                'SAK_VOLUNTEER_REGISTRATION_CREATED_BY',
                0
            );

        if ($createdBy <= 0) {
            log_message(
                'critical',
                'SAK Volunteer self-registration '
                    . 'Super Admin owner is not configured.'
            );

            return redirect()
                ->to(
                    route_to(
                        'field-officer.register'
                    )
                )
                ->with(
                    'fieldOfficerRegistrationInput',
                    $input
                )
                ->with(
                    'formAlert',
                    [
                        'type' =>
                        'danger',

                        'title' =>
                        'Registration unavailable',

                        'message' =>
                        'Registration is temporarily '
                            . 'unavailable. Please try again later.',
                    ]
                );
        }

        try {
            /** @var FieldOfficerService $service */
            $service =
                service(
                    'fieldOfficerService'
                );

            $service->register(
                $input,
                $createdBy
            );

            return redirect()
                ->to(
                    route_to(
                        'field-officer.register'
                    )
                )
                ->with(
                    'formAlert',
                    [
                        'type' =>
                        'success',

                        'title' =>
                        'Registration submitted',

                        'message' =>
                        'Your details have been saved '
                            . 'successfully. They will be '
                            . 'checked and approved in due time.',
                    ]
                );
        } catch (RuntimeException $exception) {
            return redirect()
                ->to(
                    route_to(
                        'field-officer.register'
                    )
                )
                ->with(
                    'fieldOfficerRegistrationInput',
                    $input
                )
                ->with(
                    'formAlert',
                    [
                        'type' =>
                        'danger',

                        'title' =>
                        'Registration not saved',

                        'message' =>
                        $exception
                            ->getMessage(),
                    ]
                );
        } catch (Throwable $exception) {
            log_message(
                'error',
                'SAK Volunteer self-registration '
                    . 'failed: {message}',
                [
                    'message' =>
                    $exception
                        ->getMessage(),
                ]
            );

            return redirect()
                ->to(
                    route_to(
                        'field-officer.register'
                    )
                )
                ->with(
                    'fieldOfficerRegistrationInput',
                    $input
                )
                ->with(
                    'formAlert',
                    [
                        'type' =>
                        'danger',

                        'title' =>
                        'Registration not saved',

                        'message' =>
                        'We could not save your '
                            . 'registration. Please try again.',
                    ]
                );
        }
    }


    /**
     * Return cities for the selected State.
     */
    public function cities(
        int $stateId
    ): ResponseInterface {
        if ($stateId <= 0) {
            return $this->response
                ->setStatusCode(422)
                ->setJSON(
                    [
                        'status' =>
                        'error',

                        'message' =>
                        'Invalid state.',

                        'data' =>
                        [],
                    ]
                );
        }

        /** @var ProfileMasterDataService $service */
        $service =
            service(
                'profileMasterDataService'
            );

        $cities =
            array_map(
                static fn(
                    array $city
                ): array => [
                    'value' =>
                    (string) (
                        $city['id']
                        ?? ''
                    ),

                    'label' =>
                    (string) (
                        $city['name']
                        ?? ''
                    ),
                ],
                $service
                    ->citiesForState(
                        $stateId
                    )
            );

        return $this->response
            ->setJSON(
                [
                    'status' =>
                    'success',

                    'data' =>
                    $cities,
                ]
            );
    }


    /**
     * Read only explicitly allowed public fields.
     *
     * @return array<string, string>
     */
    private function registrationInput(): array
    {
        return [
            'full_name' =>
            preg_replace(
                '/\s+/u',
                ' ',
                trim(
                    (string) $this->request
                        ->getPost(
                            'full_name'
                        )
                )
            ) ?? '',

            'mobile_number' =>
            preg_replace(
                '/\D+/',
                '',
                (string) $this->request
                    ->getPost(
                        'mobile_number'
                    )
            ) ?? '',

            'aadhaar_number' =>
            preg_replace(
                '/\D+/',
                '',
                (string) $this->request
                    ->getPost(
                        'aadhaar_number'
                    )
            ) ?? '',

            'pan_number' =>
            strtoupper(
                trim(
                    (string) $this->request
                        ->getPost(
                            'pan_number'
                        )
                )
            ),

            'country_id' =>
            trim(
                (string) $this->request
                    ->getPost(
                        'country_id'
                    )
            ),

            'state_id' =>
            trim(
                (string) $this->request
                    ->getPost(
                        'state_id'
                    )
            ),

            'city_id' =>
            trim(
                (string) $this->request
                    ->getPost(
                        'city_id'
                    )
            ),

            'address' =>
            trim(
                (string) $this->request
                    ->getPost(
                        'address'
                    )
            ),

            'upi_id' =>
            strtolower(
                trim(
                    (string) $this->request
                        ->getPost(
                            'upi_id'
                        )
                )
            ),
        ];
    }
}
