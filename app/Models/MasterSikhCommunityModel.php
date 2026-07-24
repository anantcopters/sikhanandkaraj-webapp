<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/**
 * Active Sikh community options used by member profiles.
 */
final class MasterSikhCommunityModel extends Model
{
    protected $table = 'master_sikh_communities';

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
            ->orderBy('name', 'ASC')
            ->findAll();
    }
}
