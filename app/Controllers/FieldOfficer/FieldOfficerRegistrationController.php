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
    private const SUCCESS_FLASH_KEY =
    'fieldOfficerRegistrationSuccess';

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

        /*
         * --------------------------------------------------
         * SERVER FIELD VALIDATION
         * --------------------------------------------------
         *
         * Every validation error is returned through
         * validationErrors so the shared form highlights the
         * exact field and displays its message underneath.
         */
        if (
            !$validation->run(
                $validationInput
            )
        ) {
            service(
                'fieldOfficerRegistrationCaptchaService'
            )->clear();

            return $this
                ->registrationValidationRedirect(
                    $input,
                    $validation->getErrors()
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
            return $this
                ->registrationValidationRedirect(
                    $input,
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
         * created_by is never accepted from the browser.
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

            $result =
                $service->register(
                    $input,
                    $createdBy
                );

            $fieldOfficerId = max(
                0,
                (int) (
                    $result['fieldOfficerId']
                    ?? 0
                )
            );

            $officerCode = trim(
                (string) (
                    $result['officerCode']
                    ?? ''
                )
            );

            if (
                $fieldOfficerId <= 0
                || $officerCode === ''
            ) {
                /*
                 * Registration itself has already committed.
                 * Do not tell the user that saving failed.
                 */
                log_message(
                    'critical',
                    'SAK Volunteer registration saved '
                        . 'but success reference was invalid. '
                        . 'Field Officer ID: {id}',
                    [
                        'id' =>
                        $fieldOfficerId,
                    ]
                );

                return redirect()
                    ->to(
                        route_to(
                            'field-officer.register.success'
                        )
                    )
                    ->with(
                        self::SUCCESS_FLASH_KEY,
                        [
                            'officerCode' =>
                            '',
                        ]
                    );
            }

            /*
             * PRG:
             * POST -> redirect -> GET success page.
             *
             * No success banner remains on the form page.
             */
            return redirect()
                ->to(
                    route_to(
                        'field-officer.register.success'
                    )
                )
                ->with(
                    self::SUCCESS_FLASH_KEY,
                    [
                        'officerCode' =>
                        $officerCode,
                    ]
                );
        } catch (RuntimeException $exception) {
            /*
             * Business-level errors such as duplicate mobile,
             * Aadhaar, PAN or UPI belong below their actual
             * field, not in a page-level alert.
             */
            $fieldErrors =
                $this
                ->serviceErrorToFieldErrors(
                    $exception
                        ->getMessage()
                );

            if ($fieldErrors !== []) {
                return $this
                    ->registrationValidationRedirect(
                        $input,
                        $fieldErrors
                    );
            }

            /*
             * Only a genuine non-field business failure uses
             * a page-level error.
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
     * Display registration completion.
     */
    public function success(): string|RedirectResponse
    {
        $this->preventPageCaching();

        $successData =
            session()->getFlashdata(
                self::SUCCESS_FLASH_KEY
            );

        if (!is_array($successData)) {
            return redirect()
                ->to(
                    route_to(
                        'field-officer.register'
                    )
                );
        }

        $officerCode = trim(
            (string) (
                $successData['officerCode']
                ?? ''
            )
        );

        return view(
            'FieldOfficer/Registration/Success',
            [
                'pageTitle' =>
                'Registration Submitted',

                'officerCode' =>
                $officerCode,
            ]
        );
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
     * Redirect to Registration carrying only safe form data
     * and field-specific validation errors.
     *
     * @param array<string, string> $input
     * @param array<string, string> $errors
     */
    private function registrationValidationRedirect(
        array $input,
        array $errors
    ): RedirectResponse {
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
                $errors
            );
    }


    /**
     * Convert known service/business errors into the field
     * validation contract used by every form screen.
     *
     * @return array<string, string>
     */
    private function serviceErrorToFieldErrors(
        string $message
    ): array {
        $message = trim(
            $message
        );

        if ($message === '') {
            return [];
        }

        $normalizedMessage =
            strtolower(
                $message
            );

        if (
            str_contains(
                $normalizedMessage,
                'mobile'
            )
        ) {
            return [
                'mobile_number' =>
                $message,
            ];
        }

        if (
            str_contains(
                $normalizedMessage,
                'aadhaar'
            )
        ) {
            return [
                'aadhaar_number' =>
                $message,
            ];
        }

        if (
            str_contains(
                $normalizedMessage,
                'pan'
            )
        ) {
            return [
                'pan_number' =>
                $message,
            ];
        }

        if (
            str_contains(
                $normalizedMessage,
                'upi'
            )
        ) {
            return [
                'upi_id' =>
                $message,
            ];
        }

        if (
            str_contains(
                $normalizedMessage,
                'country'
            )
        ) {
            return [
                'country_id' =>
                $message,
            ];
        }

        if (
            str_contains(
                $normalizedMessage,
                'state'
            )
        ) {
            return [
                'state_id' =>
                $message,
            ];
        }

        if (
            str_contains(
                $normalizedMessage,
                'city'
            )
        ) {
            return [
                'city_id' =>
                $message,
            ];
        }

        return [];
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
