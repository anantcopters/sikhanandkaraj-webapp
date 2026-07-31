<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

final class MasterMaritalStatusModel extends Model
{
    protected $table = 'master_marital_statuses';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useAutoIncrement = true;

    protected $allowedFields = [
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
    public function activeOptions(): array
    {
        return $this
            ->select('id, code, name')
            ->where('is_active', true)
            ->orderBy('display_order', 'ASC')
            ->orderBy('name', 'ASC')
            ->findAll();
    }
}
