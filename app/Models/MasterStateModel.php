<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

final class MasterStateModel extends Model
{
    protected $table = 'master_states';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useAutoIncrement = true;

    protected $allowedFields = [
        'country_id',
        'code',
        'name',
        'display_order',
        'is_active',
    ];

    protected $useTimestamps = true;
    protected $skipValidation = true;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function activeForCountry(int $countryId): array
    {
        return $this
            ->select('id, code, name')
            ->where('country_id', $countryId)
            ->where('is_active', true)
            ->orderBy('display_order', 'ASC')
            ->orderBy('name', 'ASC')
            ->findAll();
    }
}
