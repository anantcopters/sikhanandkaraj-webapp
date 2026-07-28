<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Services\Admin\FieldOfficerService;
use App\Services\Profile\ProfileMasterDataService;
use App\Validation\FieldOfficerValidation;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use Throwable;

/**
 * Super Admin Field Officer management controller.
 */
final class FieldOfficerController extends BaseController
{
    /**
     * Display all Field Officers.
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
                'Field Officers',

                'fieldOfficers' =>
                $service->listFieldOfficers(),

                'formAlert' =>
                $this->readFormAlert(),
            ]
        );
    }

    /**
     * Display the Field Officer creation form.
     */
    public function create(): string
    {
        /** @var ProfileMasterDataService $masterService */
        $masterService = service(
            'profileMasterDataService'
        );

        $masterData = $masterService->basicDetailsOptions();

        $formInput = $this->readArrayFlashData(
            'fieldOfficerFormInput'
        );

        return view(
            'Admin/FieldOfficers/Create',
            [
                'pageTitle' => 'Add Field Officer',

                'formInput' => $formInput,

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

                'cities' => [],

                'pageScripts' => [
                    'assets/js/components/submit-loader.js',
                    'assets/js/pages/admin-field-officer-form.js',
                ],
            ]
        );
    }

    /**
     * Persist a new Field Officer.
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
                    'Field Officer added',

                    'message' =>
                    $isActive
                        ? 'The Field Officer was created and activated because a valid UPI ID was provided.'
                        : 'The Field Officer was created in inactive status. Add a valid UPI ID before activating the Field Officer.',
                ]);
        } catch (Throwable $exception) {
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
                    'Field Officer not created',

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
             * Field Officer's state so its cities are returned.
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
                    'pageTitle' => 'Edit Field Officer',

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
                    'Field Officer not found',

                    'message' =>
                    $exception->getMessage(),
                ]);
        }
    }

    /**
     * Update editable Field Officer details.
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
                    'Field Officer updated',

                    'message' =>
                    'The Field Officer details were updated.',
                ]);
        } catch (Throwable $exception) {
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
                    'Field Officer not updated',

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
     * Activate an inactive Field Officer.
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
                    'Field Officer activated',

                    'message' =>
                    'The Field Officer is now active.',
                ]);
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
                    'Field Officer not activated',

                    'message' =>
                    $exception->getMessage(),
                ]);
        }
    }

    /**
     * Deactivate an active Field Officer.
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
                    'Field Officer deactivated',

                    'message' =>
                    'The Field Officer is now inactive.',
                ]);
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
                    'Field Officer not deactivated',

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
            'full_name' => trim(
                (string) $this->request
                    ->getPost('full_name')
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

            'country_id' => trim(
                (string) $this->request
                    ->getPost('country_id')
            ),

            'state_id' => trim(
                (string) $this->request
                    ->getPost('state_id')
            ),

            'city_id' => trim(
                (string) $this->request
                    ->getPost('city_id')
            ),

            'address' => trim(
                (string) $this->request
                    ->getPost('address')
            ),

            'upi_id' => strtolower(
                trim(
                    (string) $this->request
                        ->getPost('upi_id')
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
            'country_id' => trim(
                (string) $this->request
                    ->getPost('country_id')
            ),

            'state_id' => trim(
                (string) $this->request
                    ->getPost('state_id')
            ),

            'city_id' => trim(
                (string) $this->request
                    ->getPost('city_id')
            ),

            'address' => trim(
                (string) $this->request
                    ->getPost('address')
            ),

            'upi_id' => strtolower(
                trim(
                    (string) $this->request
                        ->getPost('upi_id')
                )
            ),
        ];
    }
}
