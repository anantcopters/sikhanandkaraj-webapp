<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use App\Services\Profile\ProfileMasterDataService;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Supplies authenticated profile master data to dependent fields.
 */
final class ProfileMasterController extends BaseController
{
    /**
     * Return active states for the selected country.
     */
    public function states(int $countryId): ResponseInterface
    {
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
        $service = service('profileMasterDataService');

        return $this->response->setJSON([
            'status' => 'success',
            'data' => array_map(
                static fn(array $state): array => [
                    'value' => (string) $state['id'],
                    'label' => (string) $state['name'],
                ],
                $service->statesForCountry($countryId)
            ),
        ]);
    }

    /**
     * Return active cities for the selected state.
     */
    public function cities(int $stateId): ResponseInterface
    {
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
}
