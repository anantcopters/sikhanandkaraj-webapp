<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Services\Admin\FieldOfficerService;
use App\Services\Profile\ProfileMasterDataService;
use App\Validation\FieldOfficerValidation;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use RuntimeException;
use Throwable;

/**
 * Super Admin Field Officer management controller.
 */
final class FieldOfficerController extends BaseController
{
    public function index(): string
    {
        /** @var FieldOfficerService $service */
        $service = service('fieldOfficerService');

        return view(
            'Admin/FieldOfficers/Index',
            [
                'pageTitle' => 'Field Officers',
                'fieldOfficers' =>
                $service->listFieldOfficers(),
            ]
        );
    }

    public function create(): string
    {
        /** @var ProfileMasterDataService $masterService */
        $masterService = service(
            'profileMasterDataService'
        );

        return view(
            'Admin/FieldOfficers/Create',
            [
                'pageTitle' => 'Add Field Officer',
                'countries' =>
                $masterService->countries(),
                'pageScripts' => [
                    'assets/js/components/submit-loader.js',
                    'assets/js/pages/admin-field-officer-form.js',
                ],
            ]
        );
    }

    public function store(): RedirectResponse
    {
        $input = $this->createInput();

        $validation = service('validation');

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

            $service->create(
                $input,
                (int) session('admin_user_id')
            );

            return redirect()
                ->to(
                    route_to(
                        'admin.field-officers.index'
                    )
                )
                ->with('formAlert', [
                    'type' => 'success',
                    'title' => 'Field Officer added',
                    'message' =>
                    'The Field Officer was created successfully.',
                ]);
        } catch (Throwable $exception) {
            return redirect()
                ->to(
                    route_to(
                        'admin.field-officers.create'
                    )
                )
                ->with(
                    'fieldOfficerFormInput',
                    $input
                )
                ->with('formAlert', [
                    'type' => 'danger',
                    'title' =>
                    'Field Officer not created',
                    'message' =>
                    $exception->getMessage(),
                ]);
        }
    }

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

            $fieldOfficer = $service->findForEdit(
                $fieldOfficerId
            );

            return view(
                'Admin/FieldOfficers/Edit',
                [
                    'pageTitle' =>
                    'Edit Field Officer',
                    'fieldOfficer' =>
                    $fieldOfficer,
                    'countries' =>
                    $masterService->countries(),
                    'states' =>
                    $masterService->statesForCountry(
                        (int) $fieldOfficer['country_id']
                    ),
                    'cities' =>
                    $masterService->citiesForState(
                        (int) $fieldOfficer['state_id']
                    ),
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
                    'type' => 'danger',
                    'title' =>
                    'Field Officer not found',
                    'message' =>
                    $exception->getMessage(),
                ]);
        }
    }

    public function update(
        int $fieldOfficerId
    ): RedirectResponse {
        /*
         * Deliberately read only editable fields.
         * Posted name, code or mobile values are ignored.
         */
        $input = $this->updateInput();

        $validation = service('validation');

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
                (int) session('admin_user_id')
            );

            return redirect()
                ->to(
                    route_to(
                        'admin.field-officers.index'
                    )
                )
                ->with('formAlert', [
                    'type' => 'success',
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
                ->with(
                    'fieldOfficerFormInput',
                    $input
                )
                ->with('formAlert', [
                    'type' => 'danger',
                    'title' =>
                    'Field Officer not updated',
                    'message' =>
                    $exception->getMessage(),
                ]);
        }
    }

    public function states(
        int $countryId
    ): ResponseInterface {
        if ($countryId <= 0) {
            return $this->response
                ->setStatusCode(422)
                ->setJSON([
                    'status' => 'error',
                    'message' => 'Invalid country.',
                    'data' => [],
                ]);
        }

        /** @var ProfileMasterDataService $service */
        $service = service(
            'profileMasterDataService'
        );

        $states = array_map(
            static fn(array $state): array => [
                'value' => (string) $state['id'],
                'label' => (string) $state['name'],
            ],
            $service->statesForCountry($countryId)
        );

        return $this->response->setJSON([
            'status' => 'success',
            'data' => $states,
        ]);
    }

    public function cities(
        int $stateId
    ): ResponseInterface {
        if ($stateId <= 0) {
            return $this->response
                ->setStatusCode(422)
                ->setJSON([
                    'status' => 'error',
                    'message' => 'Invalid state.',
                    'data' => [],
                ]);
        }

        /** @var ProfileMasterDataService $service */
        $service = service(
            'profileMasterDataService'
        );

        $cities = array_map(
            static fn(array $city): array => [
                'value' => (string) $city['id'],
                'label' => (string) $city['name'],
            ],
            $service->citiesForState($stateId)
        );

        return $this->response->setJSON([
            'status' => 'success',
            'data' => $cities,
        ]);
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
            'mobile_number' => preg_replace(
                '/\D+/',
                '',
                (string) $this->request
                    ->getPost('mobile_number')
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
                (int) session('admin_user_id')
            );

            return redirect()
                ->to(
                    route_to(
                        'admin.field-officers.index'
                    )
                )
                ->with('formAlert', [
                    'type' => 'success',
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
                    'type' => 'danger',
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
                (int) session('admin_user_id')
            );

            return redirect()
                ->to(
                    route_to(
                        'admin.field-officers.index'
                    )
                )
                ->with('formAlert', [
                    'type' => 'success',
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
                    'type' => 'danger',
                    'title' =>
                    'Field Officer not deactivated',
                    'message' =>
                    $exception->getMessage(),
                ]);
        }
    }
}
