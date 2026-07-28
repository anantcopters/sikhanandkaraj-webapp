<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use App\Services\Profile\ProfileMasterDataService;
use App\Models\MasterSikhSubcommunityModel;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Supplies authenticated profile master data to dependent fields.
 */
final class ProfileMasterController extends BaseController
{
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

    /**
     * Return active Sikh sub-communities for one community.
     */
    public function subcommunities(
        int $communityId
    ): ResponseInterface {
        if ($communityId <= 0) {
            return $this->response->setJSON([
                'data' => [],
            ]);
        }

        $model = new MasterSikhSubcommunityModel();

        $rows = $model->activeForCommunity($communityId);

        $data = array_map(
            static fn(array $row): array => [
                'value' => (string) $row['id'],
                'label' => (string) $row['name'],
            ],
            $rows
        );

        return $this->response->setJSON([
            'data' => $data,
        ]);
    }
}
