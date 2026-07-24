<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

final class MasterBirthStarModel extends Model
{
    protected $table = 'master_birth_stars';

    protected $primaryKey = 'id';

    protected $returnType = 'array';

    protected $allowedFields = [
        'code',
        'name',
        'display_order',
        'is_active',
    ];

    protected $useTimestamps = true;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function activeOptions(): array
    {
        return $this
            ->select(['id', 'code', 'name'])
            ->where('is_active', true)
            ->orderBy('display_order', 'ASC')
            ->findAll();
    }
}
