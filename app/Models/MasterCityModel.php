<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

final class MasterCityModel extends Model
{
    protected $table = 'master_cities';

    protected $primaryKey = 'id';

    protected $returnType = 'array';

    protected $useAutoIncrement = true;

    protected $allowedFields = [
        'state_id',
        'name',
        'display_order',
        'is_active',
    ];

    protected $useTimestamps = true;

    protected $skipValidation = true;

    /**
     * Return active cities for one state.
     *
     * @return array<int, array<string, mixed>>
     */
    public function activeForState(
        int $stateId
    ): array {
        if ($stateId <= 0) {
            return [];
        }

        return $this
            ->select(
                'id, state_id, name'
            )
            ->where(
                'state_id',
                $stateId
            )
            ->where(
                'is_active',
                true
            )
            ->orderBy(
                'display_order',
                'ASC'
            )
            ->orderBy(
                'name',
                'ASC'
            )
            ->findAll();
    }

    /**
     * Return active cities belonging to any selected state.
     *
     * @param list<int> $stateIds
     *
     * @return array<int, array<string, mixed>>
     */
    public function activeForStates(
        array $stateIds
    ): array {
        $stateIds = array_values(
            array_unique(
                array_filter(
                    array_map(
                        'intval',
                        $stateIds
                    ),
                    static fn(int $id): bool =>
                    $id > 0
                )
            )
        );

        if ($stateIds === []) {
            return [];
        }

        return $this
            ->select(
                'id, state_id, name'
            )
            ->whereIn(
                'state_id',
                $stateIds
            )
            ->where('name !=', 'Other')
            ->where(
                'is_active',
                true
            )
            ->orderBy(
                'state_id',
                'ASC'
            )
            ->orderBy(
                'display_order',
                'ASC'
            )
            ->orderBy(
                'name',
                'ASC'
            )
            ->findAll();
    }
}
