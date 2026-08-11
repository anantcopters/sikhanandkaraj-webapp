<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Services\Admin\FieldOfficerService;
use App\Services\Profile\ProfileMasterDataService;
use App\Validation\FieldOfficerValidation;
use App\Support\AdminErrorContext;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use Throwable;

/**
 * Super Admin SAK Volunteer management controller.
 */
final class FieldOfficerController extends BaseController
{
    /**
     * Display all SAK Volunteers.
     */
    public function index(): string
    {
        /** @var FieldOfficerService $service */
        $service = service(
            'fieldOfficerService'
        );

        return view(
            'Admin/FieldOfficers/Index',
            [
                'pageTitle' =>
                'SAK Volunteers',

                'fieldOfficers' =>
                $service->listFieldOfficers(),

                'formAlert' =>
                $this->readFormAlert(),
            ]
        );
    }

    /**
     * Display the SAK Volunteer creation form.
     */
    public function create(): string
    {
        /** @var ProfileMasterDataService $masterService */
        $masterService = service(
            'profileMasterDataService'
        );

        /*
     * Restore previously submitted values after a validation
     * or business-rule failure.
     */
        $formInput =
            $this->readArrayFlashData(
                'fieldOfficerFormInput'
            );

        /*
     * When the form is being redisplayed after a failed
     * submission, state_id may already be available.
     *
     * Pass that state to the existing master-data service so
     * the corresponding cities are loaded again. Without this,
     * the city select is empty on retry and a corrected form
     * cannot be submitted successfully.
     */
        $selectedStateId = (int) (
            $formInput['state_id']
            ?? 0
        );

        $masterData =
            $masterService
            ->basicDetailsOptions(
                $selectedStateId > 0
                    ? $selectedStateId
                    : null
            );

        return view(
            'Admin/FieldOfficers/Create',
            [
                'pageTitle' =>
                'Add SAK Volunteer',

                'formInput' =>
                $formInput,

                'validationErrors' =>
                $this->readValidationErrors(),

                'formAlert' =>
                $this->readFormAlert(),

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

                /*
             * Critical for retry flow:
             * reload cities for the previously selected state.
             */
                'cities' =>
                is_array(
                    $masterData['cities']
                        ?? null
                )
                    ? $masterData['cities']
                    : [],

                'pageScripts' => [
                    'assets/js/pages/admin-field-officer-form.js',
                    'assets/js/components/submit-loader.js',
                ],
            ]
        );
    }

    /**
     * Persist a new SAK Volunteer.
     */
    public function store(): RedirectResponse
    {
        $input = $this->createInput();

        $validation = service(
            'validation'
        );

        $validation->setRules(
            FieldOfficerValidation::createRules()
        );

        if (!$validation->run($input)) {
            return redirect()
                ->to(
                    route_to(
                        'admin.field-officers.create'
                    )
                )
                ->withInput()
                ->with(
                    'fieldOfficerFormInput',
                    $input
                )
                ->with(
                    'validationErrors',
                    $validation->getErrors()
                );
        }

        try {
            /** @var FieldOfficerService $service */
            $service = service(
                'fieldOfficerService'
            );

            $fieldOfficerId =
                $service->create(
                    $input,
                    (int) session(
                        'admin_user_id'
                    )
                );

            $fieldOfficer =
                $service->findForEdit(
                    $fieldOfficerId
                );

            $isActive =
                (string) (
                    $fieldOfficer['account_status'] ?? ''
                )
                === \App\Models\FieldOfficerModel
                ::STATUS_ACTIVE;

            return redirect()
                ->to(
                    route_to(
                        'admin.field-officers.index'
                    )
                )
                ->with('formAlert', [
                    'type' =>
                    'success',

                    'title' =>
                    'SAK Volunteer added',

                    'message' =>
                    $isActive
                        ? 'The SAK Volunteer was created and activated because a valid UPI ID was provided.'
                        : 'The SAK Volunteer was created in inactive status. Add a valid UPI ID before activating the SAK Volunteer.',
                ]);
        } catch (Throwable $exception) {

            service(
                'applicationErrorLogger'
            )->exception(
                $exception,
                'error',
                AdminErrorContext::forOperation(
                    operation: 'field_officer_create',

                    component: self::class,

                    method: __FUNCTION__,
                )
            );
            return redirect()
                ->to(
                    route_to(
                        'admin.field-officers.create'
                    )
                )
                ->withInput()
                ->with(
                    'fieldOfficerFormInput',
                    $input
                )
                ->with('formAlert', [
                    'type' =>
                    'danger',

                    'title' =>
                    'SAK Volunteer not created',

                    'message' =>
                    $exception->getMessage(),
                ]);
        }
    }

    /**
     * Display the edit form.
     */
    public function edit(
        int $fieldOfficerId
    ): string|RedirectResponse {
        try {
            /** @var FieldOfficerService $service */
            $service = service(
                'fieldOfficerService'
            );

            /** @var ProfileMasterDataService $masterService */
            $masterService = service(
                'profileMasterDataService'
            );

            $fieldOfficer =
                $service->findForEdit(
                    $fieldOfficerId
                );


            $formInput = array_merge(
                $fieldOfficer,
                $this->readArrayFlashData(
                    'fieldOfficerFormInput'
                )
            );

            /*
             * Use the existing master-data bundle and supply the
             * SAK Volunteer's state so its cities are returned.
             */
            $masterData =
                $masterService
                ->basicDetailsOptions(
                    (int) $fieldOfficer['state_id']
                );

            $country = is_array(
                $masterData['country'] ?? null
            )
                ? $masterData['country']
                : [];

            return view(
                'Admin/FieldOfficers/Edit',
                [
                    'pageTitle' => 'Edit SAK Volunteer',

                    'fieldOfficer' =>
                    $fieldOfficer,

                    'formInput' =>
                    $formInput,

                    'validationErrors' =>
                    $this->readValidationErrors(),

                    'formAlert' =>
                    $this->readFormAlert(),

                    'countries' =>
                    isset($masterData['country'])
                        && is_array($masterData['country'])
                        ? [$masterData['country']]
                        : [],

                    'states' =>
                    is_array($masterData['states'] ?? null)
                        ? $masterData['states']
                        : [],

                    'cities' =>
                    is_array($masterData['cities'] ?? null)
                        ? $masterData['cities']
                        : [],

                    'pageScripts' => [
                        'assets/js/components/submit-loader.js',
                        'assets/js/pages/admin-field-officer-form.js',
                    ],
                ]
            );
        } catch (Throwable $exception) {
            return redirect()
                ->to(
                    route_to(
                        'admin.field-officers.index'
                    )
                )
                ->with('formAlert', [
                    'type' =>
                    'danger',

                    'title' =>
                    'SAK Volunteer not found',

                    'message' =>
                    $exception->getMessage(),
                ]);
        }
    }

    /*
    * Name, mobile number and officer code are intentionally
    * excluded because those fields are immutable after creation.
    */
    public function update(
        int $fieldOfficerId
    ): RedirectResponse {
        /*
         * Name, mobile number and officer code are intentionally
         * excluded because those fields are immutable after creation.
         */
        $input = $this->updateInput();

        $validation = service(
            'validation'
        );

        $validation->setRules(
            FieldOfficerValidation::updateRules()
        );

        if (!$validation->run($input)) {
            return redirect()
                ->to(
                    route_to(
                        'admin.field-officers.edit',
                        $fieldOfficerId
                    )
                )
                ->withInput()
                ->with(
                    'fieldOfficerFormInput',
                    $input
                )
                ->with(
                    'validationErrors',
                    $validation->getErrors()
                );
        }

        try {
            /** @var FieldOfficerService $service */
            $service = service(
                'fieldOfficerService'
            );

            $service->update(
                $fieldOfficerId,
                $input,
                (int) session(
                    'admin_user_id'
                )
            );

            return redirect()
                ->to(
                    route_to(
                        'admin.field-officers.index'
                    )
                )
                ->with('formAlert', [
                    'type' =>
                    'success',

                    'title' =>
                    'SAK Volunteer updated',

                    'message' =>
                    'The SAK Volunteer details were updated.',
                ]);
        } catch (Throwable $exception) {
            service(
                'applicationErrorLogger'
            )->exception(
                $exception,
                'error',
                AdminErrorContext::forOperation(
                    operation: 'field_officer_update',

                    component: self::class,

                    method: __FUNCTION__,

                    additionalContext: [
                        'field_officer_id' =>
                        $fieldOfficerId,
                    ]
                )
            );
            return redirect()
                ->to(
                    route_to(
                        'admin.field-officers.edit',
                        $fieldOfficerId
                    )
                )
                ->withInput()
                ->with(
                    'fieldOfficerFormInput',
                    $input
                )
                ->with('formAlert', [
                    'type' =>
                    'danger',

                    'title' =>
                    'SAK Volunteer not updated',

                    'message' =>
                    $exception->getMessage(),
                ]);
        }
    }

    /**
     * Return cities for the selected state.
     *
     * This endpoint is required because the existing profile endpoint
     * is protected by member authentication, while this controller is
     * protected by administrator authentication.
     */
    public function cities(
        int $stateId
    ): ResponseInterface {
        if ($stateId <= 0) {
            return $this->response
                ->setStatusCode(422)
                ->setJSON([
                    'status' =>
                    'error',

                    'message' =>
                    'Invalid state.',

                    'data' => [],
                ]);
        }

        /** @var ProfileMasterDataService $service */
        $service = service(
            'profileMasterDataService'
        );

        $cities = array_map(
            static fn(array $city): array => [
                'value' =>
                (string) $city['id'],

                'label' =>
                (string) $city['name'],
            ],
            $service->citiesForState(
                $stateId
            )
        );

        return $this->response->setJSON([
            'status' =>
            'success',

            'data' =>
            $cities,
        ]);
    }

    /**
     * Activate an inactive SAK Volunteer.
     */
    public function activate(
        int $fieldOfficerId
    ): RedirectResponse {
        try {
            /** @var FieldOfficerService $service */
            $service = service(
                'fieldOfficerService'
            );

            $service->activate(
                $fieldOfficerId,
                (int) session(
                    'admin_user_id'
                )
            );

            return redirect()
                ->to(
                    route_to(
                        'admin.field-officers.index'
                    )
                )
                ->with('formAlert', [
                    'type' =>
                    'success',

                    'title' =>
                    'SAK Volunteer activated',

                    'message' =>
                    'The SAK Volunteer is now active.',
                ]);
        } catch (Throwable $exception) {
            service(
                'applicationErrorLogger'
            )->exception(
                $exception,
                'error',
                AdminErrorContext::forOperation(
                    operation: 'field_officer_activate',

                    component: self::class,

                    method: __FUNCTION__,

                    additionalContext: [
                        'field_officer_id' =>
                        $fieldOfficerId,
                    ]
                )
            );
            return redirect()
                ->to(
                    route_to(
                        'admin.field-officers.index'
                    )
                )
                ->with('formAlert', [
                    'type' =>
                    'danger',

                    'title' =>
                    'SAK Volunteer not activated',

                    'message' =>
                    $exception->getMessage(),
                ]);
        }
    }

    /**
     * Deactivate an active SAK Volunteer.
     */
    public function deactivate(
        int $fieldOfficerId
    ): RedirectResponse {
        try {
            /** @var FieldOfficerService $service */
            $service = service(
                'fieldOfficerService'
            );

            $service->deactivate(
                $fieldOfficerId,
                (int) session(
                    'admin_user_id'
                )
            );

            return redirect()
                ->to(
                    route_to(
                        'admin.field-officers.index'
                    )
                )
                ->with('formAlert', [
                    'type' =>
                    'success',

                    'title' =>
                    'SAK Volunteer deactivated',

                    'message' =>
                    'The SAK Volunteer is now inactive.',
                ]);
        } catch (Throwable $exception) {
            service(
                'applicationErrorLogger'
            )->exception(
                $exception,
                'error',
                AdminErrorContext::forOperation(
                    operation: 'field_officer_deactivate',

                    component: self::class,

                    method: __FUNCTION__,

                    additionalContext: [
                        'field_officer_id' =>
                        $fieldOfficerId,
                    ]
                )
            );
            return redirect()
                ->to(
                    route_to(
                        'admin.field-officers.index'
                    )
                )
                ->with('formAlert', [
                    'type' =>
                    'danger',

                    'title' =>
                    'SAK Volunteer not deactivated',

                    'message' =>
                    $exception->getMessage(),
                ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function createInput(): array
    {
        return [
            'full_name' =>
            trim(
                (string) $this->request
                    ->getPost(
                        'full_name'
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

    /**
     * @return array<string, mixed>
     */
    private function updateInput(): array
    {
        return [
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
