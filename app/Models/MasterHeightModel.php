<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

final class MasterHeightModel extends Model
{
    protected $table = 'master_heights';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useAutoIncrement = true;

    protected $allowedFields = [
        'height_cm',
        'display_name',
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
            ->select('id, height_cm, display_name')
            ->where('is_active', true)
            ->orderBy('display_order', 'ASC')
            ->findAll();
    }
}
