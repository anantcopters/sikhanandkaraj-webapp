<?php

declare(strict_types=1);

namespace App\Controllers\Prelaunch;

use App\Controllers\BaseController;
use App\Services\Prelaunch\PrelaunchFieldOfficerService;
use App\Services\Prelaunch\PrelaunchProfileService;
use App\Services\Profile\ProfileMasterDataService;
use App\Validation\Prelaunch\PrelaunchPhotoValidation;
use App\Validation\Prelaunch\PrelaunchProfileValidation;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use Throwable;

/**
 * Standalone pre-launch profile collection controller.
 */
final class PrelaunchProfileController extends BaseController
{
    public function index(): string
    {
        /** @var ProfileMasterDataService $masterService */
        $masterService = service(
            'profileMasterDataService'
        );

        $selectedStateId = (int) old(
            'state_id',
            0
        );

        $masterData =
            $masterService->basicDetailsOptions(
                $selectedStateId > 0
                    ? $selectedStateId
                    : null
            );

        /*
         * Reuse existing profile master-data methods.
         * Add a prelaunchOptions() aggregator to the current service if
         * no existing method already returns all these datasets.
         */
        return view(
            'Prelaunch/Profile/Index',
            [
                'pageTitle' =>
                'Create Pre-launch Profile',

                'validationErrors' =>
                session('validationErrors') ?? [],

                'formAlert' =>
                session('formAlert'),

                'maritalStatuses' =>
                $masterData['maritalStatuses']
                    ?? $masterData['marital_statuses']
                    ?? [],

                'heights' =>
                $masterData['heights']
                    ?? [],

                'motherTongues' =>
                $masterData['motherTongues']
                    ?? $masterData['mother_tongues']
                    ?? [],

                'countries' =>
                isset($masterData['country'])
                    && is_array($masterData['country'])
                    ? [$masterData['country']]
                    : ($masterData['countries'] ?? []),

                'states' =>
                $masterData['states']
                    ?? [],

                'cities' =>
                $masterData['cities']
                    ?? [],

                'educations' =>
                $masterData['educations']
                    ?? [],

                'occupations' =>
                $masterData['occupations']
                    ?? [],

                'familyValues' =>
                $masterData['familyValues']
                    ?? [],

                'familyTypes' =>
                $masterData['familyTypes']
                    ?? [],

                'familyStatuses' =>
                $masterData['familyStatuses']
                    ?? [],

                'communities' =>
                $masterData['communities']
                    ?? [],

                'subcommunities' =>
                $masterData['subcommunities']
                    ?? [],

                'pageScripts' => [
                    'assets/js/pages/prelaunch-profile-form.js',
                    'assets/js/components/submit-loader.js',
                ],
            ]
        );
    }

    /**
     * Verify an active Field Officer for the public pre-launch form.
     */
    public function verifyFieldOfficer(): ResponseInterface
    {
        $config = config('Prelaunch');

        if (!$config->profileEntryEnabled) {
            throw \CodeIgniter\Exceptions\PageNotFoundException
                ::forPageNotFound();
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
                'errors' => [
                    'required' =>
                    'Please enter a Field Officer code.',

                    'min_length' =>
                    'Please enter a valid Field Officer code.',

                    'max_length' =>
                    'The Field Officer code cannot exceed 20 characters.',

                    'regex_match' =>
                    'The Field Officer code may contain only letters, numbers and hyphens.',
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

            $fieldOfficer = $service->verifyCode(
                $officerCode
            );

            return $this->response->setJSON([
                'successful' =>
                true,

                'message' =>
                'Field Officer verified successfully.',

                'fieldOfficer' => [
                    'id' =>
                    $fieldOfficer['id'],

                    'officerCode' =>
                    $fieldOfficer['officer_code'],

                    'fullName' =>
                    $fieldOfficer['full_name'],

                    'countryName' =>
                    $fieldOfficer['country_name'],

                    'stateName' =>
                    $fieldOfficer['state_name'],

                    'cityName' =>
                    $fieldOfficer['city_name'],

                    'location' =>
                    $fieldOfficer['location'],
                ],

                'csrfName' =>
                csrf_token(),

                'csrfHash' =>
                csrf_hash(),
            ]);
        } catch (Throwable $exception) {
            log_message(
                'notice',
                'Pre-launch Field Officer verification failed: {message}',
                [
                    'message' =>
                    $exception->getMessage(),
                ]
            );

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
        }
    }

    public function store(): RedirectResponse
    {

        $config = config('Prelaunch');

        if (!$config->profileEntryEnabled) {
            throw \CodeIgniter\Exceptions\PageNotFoundException
                ::forPageNotFound();
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

            $profileId = $service->createDraft(
                $validation->getValidated(),
                [
                    $this->request->getFile('photo_1'),
                    $this->request->getFile('photo_2'),
                    $this->request->getFile('photo_3'),
                ]
            );

            return redirect()
                ->to(
                    route_to(
                        'prelaunch.profile.success',
                        $profileId
                    )
                );
        } catch (Throwable $exception) {
            log_message(
                'error',
                'Prelaunch profile creation failed: {message}',
                [
                    'message' =>
                    $exception->getMessage(),
                ]
            );

            return redirect()
                ->back()
                ->withInput()
                ->with('formAlert', [
                    'type' => 'danger',
                    'title' => 'Profile not saved',
                    'message' => $exception->getMessage(),
                ]);
        }
    }

    public function success(
        int $profileId
    ): string {
        return view(
            'Prelaunch/Profile/Success',
            [
                'pageTitle' => 'Profile saved',
                'profileId' => $profileId,
            ]
        );
    }

    /**
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

            'email' =>
            mb_strtolower(trim(
                (string) $this->request->getPost(
                    'email'
                )
            )),

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

            'mother_tongue_id' =>
            trim((string) $this->request->getPost(
                'mother_tongue_id'
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

            'family_value_id' =>
            trim((string) $this->request->getPost(
                'family_value_id'
            )),

            'family_type_id' =>
            trim((string) $this->request->getPost(
                'family_type_id'
            )),

            'family_status_id' =>
            trim((string) $this->request->getPost(
                'family_status_id'
            )),

            'sikh_community_id' =>
            trim((string) $this->request->getPost(
                'sikh_community_id'
            )),

            'sikh_subcommunity_id' =>
            trim((string) $this->request->getPost(
                'sikh_subcommunity_id'
            )),

            'field_officer_code' =>
            mb_strtoupper(trim(
                (string) $this->request->getPost(
                    'field_officer_code'
                )
            )),

            'verified_field_officer_id' =>
            trim((string) $this->request->getPost(
                'verified_field_officer_id'
            )),

            'consent' =>
            trim((string) $this->request->getPost(
                'consent'
            )),

            /*
             * File fields are included so CI4 upload rules can validate
             * them in the same validation run.
             */
            'photo_1' =>
            $this->request->getFile('photo_1'),

            'photo_2' =>
            $this->request->getFile('photo_2'),

            'photo_3' =>
            $this->request->getFile('photo_3'),
        ];
    }
}
