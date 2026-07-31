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
     * @return array<int, array<string, mixed>>
     */
    public function activeForState(int $stateId): array
    {
        return $this
            ->select('id, name')
            ->where('state_id', $stateId)
            ->where('is_active', true)
            ->orderBy('display_order', 'ASC')
            ->orderBy('name', 'ASC')
            ->findAll();
    }
}
