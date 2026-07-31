<?php

declare(strict_types=1);

namespace App\Controllers\Prelaunch;

use App\Controllers\BaseController;
use App\Services\Prelaunch\PrelaunchFieldOfficerService;
use App\Services\Prelaunch\PrelaunchProfileService;
use App\Services\Profile\ProfileMasterDataService;
use App\Validation\Prelaunch\PrelaunchPhotoValidation;
use App\Validation\Prelaunch\PrelaunchProfileValidation;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use Throwable;
use RuntimeException;
use InvalidArgumentException;

/**
 * Standalone pre-launch profile collection controller.
 */
final class PrelaunchProfileController extends BaseController
{
    /**
     * Display the public standalone pre-launch form.
     */
    public function index(): string
    {
        $config = config('Prelaunch');

        if (!$config->profileEntryEnabled) {
            throw PageNotFoundException::forPageNotFound();
        }

        try {
            /** @var ProfileMasterDataService $masterService */
            $masterService = service(
                'profileMasterDataService'
            );

            $selectedStateId = (int) old(
                'state_id',
                0
            );

            $basicDetails =
                $masterService->prelaunchBasicDetailsOptions(
                    $selectedStateId > 0
                        ? $selectedStateId
                        : null
                );

            $educationProfession =
                $masterService
                ->educationProfessionOptions();

            $familyDetails =
                $masterService
                ->prelaunchFamilyDetailsOptions();

            return view(
                'Prelaunch/Profile/Index',
                [
                    'pageTitle' =>
                    'Create Pre-launch Profile',

                    /*
                    * Use the reusable BaseController readers instead of
                    * accessing session flashdata directly.
                    */
                    'validationErrors' =>
                    $this->readValidationErrors(),

                    'formAlert' =>
                    $this->readFormAlert(),

                    'maritalStatuses' =>
                    $basicDetails['maritalStatuses']
                        ?? [],

                    'heights' =>
                    $basicDetails['heights']
                        ?? [],

                    /*
                    * India remains a hidden locked value in the form.
                    */
                    'country' =>
                    $basicDetails['country']
                        ?? null,

                    'states' =>
                    $basicDetails['states']
                        ?? [],

                    'cities' =>
                    $basicDetails['cities']
                        ?? [],

                    'educations' =>
                    $educationProfession['educations']
                        ?? [],

                    'occupations' =>
                    $educationProfession['occupations']
                        ?? [],

                    'employmentTypes' =>
                    $educationProfession['employmentTypes']
                        ?? [],

                    'communities' =>
                    $familyDetails['communities']
                        ?? [],

                    'maximumPhotoSizeKilobytes' =>
                    $config->maximumPhotoSizeKilobytes,

                    'pageScripts' => [
                        'assets/js/pages/prelaunch-profile-form.js',
                        'assets/js/components/submit-loader.js',
                    ],
                ]
            );
        } catch (Throwable $exception) {
            log_message(
                'error',
                'Unable to render standalone pre-launch form. '
                    . 'Exception: {exception}. '
                    . 'Message: {message}. '
                    . 'File: {file}. '
                    . 'Line: {line}.',
                [
                    'exception' =>
                    $exception::class,

                    'message' =>
                    $exception->getMessage(),

                    'file' =>
                    $exception->getFile(),

                    'line' =>
                    $exception->getLine(),
                ]
            );

            if (ENVIRONMENT === 'development') {
                throw $exception;
            }

            throw PageNotFoundException::forPageNotFound();
        }
    }

    /**
     * Return cities for a state selected on the public form.
     */
    public function cities(
        int $stateId
    ): ResponseInterface {
        if ($stateId <= 0) {
            return $this->response
                ->setStatusCode(422)
                ->setJSON([
                    'successful' => false,
                    'message' =>
                    'Please select a valid state.',
                    'items' => [],
                ]);
        }

        try {
            /** @var ProfileMasterDataService $masterService */
            $masterService = service(
                'profileMasterDataService'
            );

            $cities =
                $masterService->citiesForState(
                    $stateId
                );

            return $this->response->setJSON([
                'successful' => true,
                'items' => array_values(
                    array_map(
                        static function (
                            array $city
                        ): array {
                            return [
                                'id' => (int) (
                                    $city['id']
                                    ?? 0
                                ),
                                'name' => (string) (
                                    $city['name']
                                    ?? $city['label']
                                    ?? ''
                                ),
                            ];
                        },
                        $cities
                    )
                ),
            ]);
        } catch (Throwable $exception) {
            log_message(
                'error',
                'Unable to load prelaunch cities. '
                    . 'State ID: {stateId}. '
                    . 'Message: {message}.',
                [
                    'stateId' =>
                    $stateId,

                    'message' =>
                    $exception->getMessage(),
                ]
            );

            return $this->response
                ->setStatusCode(500)
                ->setJSON([
                    'successful' => false,
                    'message' =>
                    'Cities could not be loaded.',
                    'items' => [],
                ]);
        }
    }

    /**
     * Verify an active Field Officer.
     */
    public function verifyFieldOfficer(): ResponseInterface
    {
        $config = config('Prelaunch');

        if (!$config->profileEntryEnabled) {
            throw PageNotFoundException::forPageNotFound();
        }

        if (!$this->request->isAJAX()) {
            return $this->response
                ->setStatusCode(400)
                ->setJSON([
                    'successful' => false,
                    'message' =>
                    'Invalid Field Officer verification request.',
                    'csrfName' =>
                    csrf_token(),
                    'csrfHash' =>
                    csrf_hash(),
                ]);
        }

        $officerCode = mb_strtoupper(
            trim(
                (string) $this->request->getPost(
                    'field_officer_code'
                )
            )
        );

        $validation = service('validation');

        $validation->setRules([
            'field_officer_code' => [
                'label' => 'Field Officer code',
                'rules' => [
                    'required',
                    'min_length[4]',
                    'max_length[20]',
                    'regex_match[/^[A-Z0-9-]+$/]',
                ],
            ],
        ]);

        if (
            !$validation->run([
                'field_officer_code' =>
                $officerCode,
            ])
        ) {
            return $this->response
                ->setStatusCode(422)
                ->setJSON([
                    'successful' => false,
                    'message' =>
                    $validation->getError(
                        'field_officer_code'
                    ),

                    'csrfName' =>
                    csrf_token(),

                    'csrfHash' =>
                    csrf_hash(),
                ]);
        }

        try {
            /** @var PrelaunchFieldOfficerService $service */
            $service = service(
                'prelaunchFieldOfficerService'
            );

            $fieldOfficer =
                $service->verifyCode(
                    $officerCode
                );

            return $this->response->setJSON([
                'successful' => true,

                'message' =>
                'Field Officer verified successfully.',

                'fieldOfficer' => [
                    'id' =>
                    (int) $fieldOfficer['id'],

                    'officerCode' =>
                    (string) $fieldOfficer['officer_code'],

                    'fullName' =>
                    (string) $fieldOfficer['full_name'],

                    'countryName' =>
                    (string) (
                        $fieldOfficer['country_name']
                        ?? ''
                    ),

                    'stateName' =>
                    (string) (
                        $fieldOfficer['state_name']
                        ?? ''
                    ),

                    'cityName' =>
                    (string) (
                        $fieldOfficer['city_name']
                        ?? ''
                    ),

                    'location' =>
                    (string) (
                        $fieldOfficer['location']
                        ?? ''
                    ),
                ],

                'csrfName' =>
                csrf_token(),

                'csrfHash' =>
                csrf_hash(),
            ]);
        } catch (Throwable $exception) {
            log_message(
                'notice',
                'Prelaunch Field Officer verification failed: '
                    . '{message}',
                [
                    'message' =>
                    $exception->getMessage(),
                ]
            );

            return $this->response
                ->setStatusCode(422)
                ->setJSON([
                    'successful' => false,

                    'message' =>
                    'Prelaunch Field Officer verification failed',

                    'csrfName' =>
                    csrf_token(),

                    'csrfHash' =>
                    csrf_hash(),
                ]);
        }
    }

    /**
     * Save a prelaunch draft profile.
     */
    public function store(): RedirectResponse
    {
        $config = config('Prelaunch');

        if (!$config->profileEntryEnabled) {
            throw PageNotFoundException::forPageNotFound();
        }

        $input = $this->input();

        $validation = service('validation');

        $validation->setRules(
            array_merge(
                PrelaunchProfileValidation::createRules(),
                PrelaunchPhotoValidation::rules()
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

        try {
            /** @var PrelaunchProfileService $service */
            $service = service(
                'prelaunchProfileService'
            );

            $result = $service->createDraft(
                $validation->getValidated(),
                [
                    $this->request->getFile(
                        'photo_1'
                    ),
                    $this->request->getFile(
                        'photo_2'
                    ),
                    $this->request->getFile(
                        'photo_3'
                    ),
                ]
            );

            /*
            * Field-specific business failures returned through the service
            * result continue to use the existing validation error structure.
            */
            if (!$result->successful) {
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
                            'type' => 'danger',
                            'title' => 'Profile not saved',
                            'message' =>
                            $result->message
                                ?? 'The profile could not be saved.',
                        ]
                    );
            }

            /*
            * A successful service result must contain both identifiers.
            * Missing identifiers indicate an internal contract failure.
            */
            if (
                $result->profileId === null
                || $result->profileReference === null
            ) {
                throw new RuntimeException(
                    'The successful prelaunch profile result is incomplete.'
                );
            }

            return redirect()
                ->to(
                    route_to(
                        'prelaunch.profile.success',
                        $result->profileId
                    )
                )
                ->with(
                    'profileReference',
                    $result->profileReference
                );
        } catch (InvalidArgumentException $exception) {
            /*
            * InvalidArgumentException is used only for safe,
            * user-correctable input and photo upload errors.
            *
            * Its message can therefore be displayed using the project's
            * existing formAlert flashdata structure.
            */
            return redirect()
                ->back()
                ->withInput()
                ->with(
                    'formAlert',
                    [
                        'type' => 'danger',
                        'title' =>
                        'Please check the submitted details',

                        'message' =>
                        $exception->getMessage(),
                    ]
                );
        } catch (Throwable $exception) {
            /*
            * Database, filesystem, image-processing and programming errors
            * must not expose their internal messages in the browser.
            */
            log_message(
                'error',
                'Prelaunch profile creation failed. '
                    . 'Exception: {exception}. '
                    . 'Message: {message}. '
                    . 'File: {file}. '
                    . 'Line: {line}.',
                [
                    'exception' =>
                    $exception::class,

                    'message' =>
                    $exception->getMessage(),

                    'file' =>
                    $exception->getFile(),

                    'line' =>
                    $exception->getLine(),
                ]
            );

            return redirect()
                ->back()
                ->withInput()
                ->with(
                    'formAlert',
                    [
                        'type' => 'danger',
                        'title' => 'Profile not saved',
                        'message' =>
                        'The profile could not be saved. Please try again.',
                    ]
                );
        }
    }

    public function success(
        int $profileId
    ): string {
        return view(
            'Prelaunch/Profile/Success',
            [
                'pageTitle' =>
                'Profile saved',

                'profileId' =>
                $profileId,

                'profileReference' =>
                (string) (
                    session(
                        'profileReference'
                    )
                    ?? ''
                ),
            ]
        );
    }

    /**
     * Normalize submitted form data.
     *
     * Only fields belonging to the current prelaunch form are accepted.
     * Removed UI fields are intentionally not read from the request.
     *
     * @return array<string, mixed>
     */
    private function input(): array
    {
        return [
            'profile_created_for' =>
            trim((string) $this->request->getPost(
                'profile_created_for'
            )),

            'gender' =>
            trim((string) $this->request->getPost(
                'gender'
            )),

            'full_name' =>
            trim((string) $this->request->getPost(
                'full_name'
            )),

            'date_of_birth' =>
            trim((string) $this->request->getPost(
                'date_of_birth'
            )),

            'email' => $this->normalizeOptionalEmail(
                $this->request->getPost('email')
            ),

            'country_code' =>
            trim((string) $this->request->getPost(
                'country_code'
            )),

            'mobile_number' =>
            preg_replace(
                '/\D+/',
                '',
                (string) $this->request->getPost(
                    'mobile_number'
                )
            ) ?? '',

            'marital_status_id' =>
            trim((string) $this->request->getPost(
                'marital_status_id'
            )),

            'height_id' =>
            trim((string) $this->request->getPost(
                'height_id'
            )),

            'country_id' =>
            trim((string) $this->request->getPost(
                'country_id'
            )),

            'state_id' =>
            trim((string) $this->request->getPost(
                'state_id'
            )),

            'city_id' =>
            trim((string) $this->request->getPost(
                'city_id'
            )),

            'highest_education_id' =>
            trim((string) $this->request->getPost(
                'highest_education_id'
            )),

            'employed_in' =>
            trim((string) $this->request->getPost(
                'employed_in'
            )),

            'occupation_id' =>
            trim((string) $this->request->getPost(
                'occupation_id'
            )),

            'father_name' =>
            trim((string) $this->request->getPost(
                'father_name'
            )),

            'mother_name' =>
            trim((string) $this->request->getPost(
                'mother_name'
            )),

            'gotra' =>
            trim((string) $this->request->getPost(
                'gotra'
            )),

            'nearest_gurudwara' =>
            $this->normalizeOptionalText(
                $this->request->getPost(
                    'nearest_gurudwara'
                )
            ),

            'sikh_community_id' =>
            trim((string) $this->request->getPost(
                'sikh_community_id'
            )),

            'field_officer_code' =>
            mb_strtoupper(
                trim((string) $this->request->getPost(
                    'field_officer_code'
                ))
            ),

            'verified_field_officer_id' =>
            trim((string) $this->request->getPost(
                'verified_field_officer_id'
            )),

            'consent' =>
            trim((string) $this->request->getPost(
                'consent'
            )),
        ];
    }

    /**
     * Normalize an optional email submitted by the public form.
     */
    private function normalizeOptionalEmail(
        mixed $email
    ): string {
        return mb_strtolower(
            trim((string) $email)
        );
    }

    /**
     * Normalize an optional submitted text value.
     *
     * Empty values are returned as an empty string for validation. The service
     * converts the value to NULL before persistence.
     */
    private function normalizeOptionalText(
        mixed $value
    ): string {
        return trim(
            preg_replace(
                '/\s+/u',
                ' ',
                (string) $value
            ) ?? ''
        );
    }
}
