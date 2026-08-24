<?php

declare(strict_types=1);

namespace App\Controllers\Prelaunch;

use App\Controllers\BaseController;
use App\Services\Prelaunch\PrelaunchFieldOfficerService;
use App\Services\Prelaunch\PrelaunchProfileService;
use App\Services\Profile\ProfileMasterDataService;
use App\Validation\Prelaunch\PrelaunchPhotoValidation;
use App\Validation\Prelaunch\PrelaunchProfileValidation;
use App\Support\PrelaunchErrorContext;
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
        $this->preventPageCaching();

        $config = config('Prelaunch');

        if (!$config->profileEntryEnabled) {
            throw PageNotFoundException::forPageNotFound();
        }

        /*
        * Initialize before the try block because the catch block
        * includes this value in operational error context.
        */
        $selectedStateId = 0;
        $selectedCountryId = 0;

        try {
            /** @var ProfileMasterDataService $masterService */
            $masterService = service(
                'profileMasterDataService'
            );

            $selectedStateId = (int) old(
                'state_id',
                0
            );

            $selectedCountryId = (int) old(
                'country_id',
                0
            );

            $basicDetails =
                $masterService->prelaunchBasicDetailsOptions(
                    $selectedStateId > 0
                        ? $selectedStateId
                        : null,
                    $selectedCountryId > 0
                        ? $selectedCountryId
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

                    'countries' =>
                    $basicDetails['countries']
                        ?? [],

                    'states' =>
                    $basicDetails['states']
                        ?? [],

                    'cities' =>
                    $basicDetails['cities']
                        ?? [],

                    /*
                    * Retain flat education data for backward compatibility
                    * with any existing included components.
                    */
                    'educations' =>
                    $educationProfession['educations']
                        ?? [],

                    /*
                    * The prelaunch Education & Profession partial renders
                    * education categories through native optgroups.
                    */
                    'educationGroups' =>
                    $educationProfession['educationGroups']
                        ?? [],

                    /*
                    * Retain the flat collection for backward compatibility
                    * with any included prelaunch components.
                    */
                    'occupations' =>
                    $educationProfession['occupations']
                        ?? [],

                    /*
                    * The Education & Profession prelaunch partial renders
                    * occupations using accessible HTML optgroups.
                    */
                    'occupationGroups' =>
                    $educationProfession['occupationGroups']
                        ?? [],

                    'employmentTypes' =>
                    $educationProfession['employmentTypes']
                        ?? [],

                    'communities' =>
                    $familyDetails['communities']
                        ?? [],

                    'maximumPhotoSizeKilobytes' =>
                    $config->maximumPhotoSizeKilobytes,

                    'maximumPhotos' =>
                    $config->maximumPhotos,

                    'requiresFieldOfficerVerification' =>
                    $config->requiresFieldOfficerVerification,

                    'minimumPhotoWidth' =>
                    $config->minimumPhotoWidthPixels,

                    'minimumPhotoHeight' =>
                    $config->minimumPhotoHeightPixels,

                    'recommendedPhotoWidth' =>
                    $config->recommendedPhotoWidthPixels,

                    'recommendedPhotoHeight' =>
                    $config->recommendedPhotoHeightPixels,

                    'pageScripts' => [
                        'assets/js/pages/prelaunch-profile-form.js',
                    ],
                ]
            );
        } catch (Throwable $exception) {
            service(
                'applicationErrorLogger'
            )->exception(
                $exception,
                'error',
                PrelaunchErrorContext::forOperation(
                    operation: 'prelaunch_profile_form_render',

                    component: self::class,

                    method: __FUNCTION__,

                    additionalContext: [
                        'selected_state_id' =>
                        $selectedStateId > 0
                            ? $selectedStateId
                            : null,

                        'profile_entry_enabled' =>
                        (bool) $config
                            ->profileEntryEnabled,
                    ]
                )
            );

            /*
            * Development retains the normal detailed exception page.
            * Production continues to hide implementation details.
            */
            if (ENVIRONMENT === 'development') {
                throw $exception;
            }

            throw PageNotFoundException
                ::forPageNotFound();
        }
    }

    /**
     * Return states for a country selected on the public form.
     */
    public function states(int $countryId): ResponseInterface
    {
        if ($countryId <= 0) {
            return $this->response
                ->setStatusCode(422)
                ->setJSON([
                    'successful' => false,
                    'message' => 'Please select a valid country.',
                    'items' => [],
                ]);
        }

        try {
            /** @var ProfileMasterDataService $masterService */
            $masterService = service('profileMasterDataService');

            return $this->response->setJSON([
                'successful' => true,
                'items' => array_values(array_map(
                    static fn(array $state): array => [
                        'id' => (int) ($state['id'] ?? 0),
                        'name' => (string) ($state['name'] ?? ''),
                    ],
                    $masterService->statesForCountry($countryId)
                )),
            ]);
        } catch (Throwable $exception) {
            service('applicationErrorLogger')->exception(
                $exception,
                'error',
                PrelaunchErrorContext::forOperation(
                    operation: 'prelaunch_state_master_load',
                    component: self::class,
                    method: __FUNCTION__,
                    additionalContext: ['country_id' => $countryId]
                )
            );

            return $this->response
                ->setStatusCode(500)
                ->setJSON([
                    'successful' => false,
                    'message' => 'States could not be loaded.',
                    'items' => [],
                ]);
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
            service(
                'applicationErrorLogger'
            )->exception(
                $exception,
                'error',
                PrelaunchErrorContext::forOperation(
                    operation: 'prelaunch_city_master_load',

                    component: self::class,

                    method: __FUNCTION__,

                    additionalContext: [
                        'state_id' =>
                        $stateId,
                    ]
                )
            );

            return $this->response
                ->setStatusCode(500)
                ->setJSON([
                    'successful' =>
                    false,

                    'message' =>
                    'Cities could not be loaded.',

                    'items' =>
                    [],
                ]);
        }
    }

    /**
     * Verify an active SAK Volunteer.
     *
     * This endpoint belongs only to the actual production
     * prelaunch workflow.
     */
    public function verifyFieldOfficer(): ResponseInterface
    {
        $config = config('Prelaunch');

        if (
            !$config->profileEntryEnabled
            || !$config->requiresFieldOfficerVerification
        ) {
            throw PageNotFoundException::forPageNotFound();
        }

        if (!$this->request->isAJAX()) {
            return $this->response
                ->setStatusCode(400)
                ->setJSON([
                    'successful' =>
                    false,

                    'message' =>
                    'Invalid SAK Volunteer verification request.',

                    'csrfName' =>
                    csrf_token(),

                    'csrfHash' =>
                    csrf_hash(),
                ]);
        }

        $officerCode = mb_strtoupper(
            trim(
                (string) $this->request
                    ->getPost(
                        'field_officer_code'
                    )
            )
        );

        $validation =
            service('validation');

        $validation->setRules([
            'field_officer_code' => [
                'label' =>
                'SAK Volunteer code',

                'rules' => [
                    'required',
                    'exact_length[11]',
                    'regex_match[/^FOSAK[0-9]{6}$/]',
                ],

                'errors' => [
                    'required' =>
                    'Please enter the SAK Volunteer code.',

                    'exact_length' =>
                    'Please enter a valid SAK Volunteer code.',

                    'regex_match' =>
                    'Please enter a valid SAK Volunteer code.',
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
                    'successful' =>
                    false,

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

            return $this->response
                ->setJSON([
                    'successful' =>
                    true,

                    'message' =>
                    'SAK Volunteer verified successfully.',

                    'fieldOfficer' => [
                        /*
                     * The ID is only a client-side verification marker.
                     *
                     * It is revalidated against the officer code
                     * again during profile save.
                     */
                        'id' =>
                        (int) $fieldOfficer['id'],

                        'officerCode' =>
                        (string) $fieldOfficer['officer_code'],

                        'fullName' =>
                        (string) $fieldOfficer['full_name'],

                        'stateName' =>
                        (string) (
                            $fieldOfficer['state_name'] ?? ''
                        ),

                        'cityName' =>
                        (string) (
                            $fieldOfficer['city_name'] ?? ''
                        ),

                        'location' =>
                        (string) (
                            $fieldOfficer['location'] ?? ''
                        ),
                    ],

                    /*
                 * CSRF token regenerates on POST.
                 * Return the new token so Save Profile can
                 * use the current token afterwards.
                 */
                    'csrfName' =>
                    csrf_token(),

                    'csrfHash' =>
                    csrf_hash(),
                ]);
        } catch (RuntimeException $exception) {
            return $this->response
                ->setStatusCode(422)
                ->setJSON([
                    'successful' =>
                    false,

                    'message' =>
                    $exception->getMessage(),

                    'csrfName' =>
                    csrf_token(),

                    'csrfHash' =>
                    csrf_hash(),
                ]);
        } catch (Throwable $exception) {
            service(
                'applicationErrorLogger'
            )->exception(
                $exception,
                'error',
                PrelaunchErrorContext::forOperation(
                    operation: 'prelaunch_field_officer_verify',

                    component: self::class,

                    method: __FUNCTION__
                )
            );

            return $this->response
                ->setStatusCode(500)
                ->setJSON([
                    'successful' =>
                    false,

                    'message' =>
                    'The SAK Volunteer could not be '
                        . 'verified. Please try again.',

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

        /*
 * QA/development continue to use the configured SAK Volunteer.
 *
 * Production gets the officer from explicit verified user input,
 * so profileFieldOfficerId is not required there.
 */
        if (
            !$config->requiresFieldOfficerVerification
            && $config->profileFieldOfficerId <= 0
        ) {
            service(
                'applicationErrorLogger'
            )->error(
                'Prelaunch profile entry is unavailable because '
                    . 'profileFieldOfficerId is not configured.',

                PrelaunchErrorContext::forOperation(
                    operation: 'prelaunch_profile_configuration',

                    component: self::class,

                    method: __FUNCTION__,

                    additionalContext: [
                        'profile_field_officer_id' =>
                        $config->profileFieldOfficerId,
                    ]
                ),

                'critical'
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
                        'Profile entry unavailable',

                        'message' =>
                        'The profile cannot be saved at the moment. '
                            . 'Please contact the administrator.',
                    ]
                );
        }

        $input = $this->input();

        $validation = service('validation');

        /*
        * Validate both profile data and uploaded photographs.
        *
        * PrelaunchProfileValidation applies the gender-specific
        * minimum DOB rule:
        *
        * Male   => 21 years
        * Female => 18 years
        *
        * Keep PrelaunchPhotoValidation merged with the profile rules.
        * Photo validation existed before the gender-specific DOB
        * change and must remain authoritative server-side.
        */
        $validation->setRules(
            array_merge(
                PrelaunchProfileValidation::createRules(
                    $config
                        ->requiresFieldOfficerVerification,
                    (string) (
                        $input['gender']
                        ?? ''
                    )
                ),
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

            $photos = [];

            for (
                $sequence = 1;
                $sequence <= $config->maximumPhotos;
                $sequence++
            ) {
                $photo = $this->request->getFile(
                    'photo_' . $sequence
                );

                if ($photo !== null) {
                    $photos[] = $photo;
                }
            }

            $result = $service->createDraft(
                $validation->getValidated(),
                $photos
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
            $uploadedPhotoCount = isset($photos)
                && is_array($photos)
                ? count($photos)
                : 0;

            service(
                'applicationErrorLogger'
            )->exception(
                $exception,
                'error',
                PrelaunchErrorContext::forOperation(
                    operation: 'prelaunch_profile_create',

                    component: self::class,

                    method: __FUNCTION__,

                    additionalContext: [
                        /*
                 * Only operational metadata is retained.
                 * Submitted profile/contact values are intentionally excluded.
                 */
                        'uploaded_photo_count' =>
                        $uploadedPhotoCount,

                        'maximum_photos' =>
                        $config->maximumPhotos,

                        'configured_field_officer_id' =>
                        $config->profileFieldOfficerId,
                    ]
                )
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
                        'Profile not saved',

                        'message' =>
                        'The profile could not be saved. '
                            . 'Please try again.',
                    ]
                );
        }
    }

    public function success(
        int $profileId
    ): string {
        $this->preventPageCaching();
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

            'parent_contact_number' =>
            preg_replace(
                '/\D+/',
                '',
                (string) $this->request
                    ->getPost(
                        'parent_contact_number'
                    )
            ) ?? '',

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
