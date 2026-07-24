<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/**
 * Sikh sub-community options grouped by community.
 */
final class MasterSikhSubcommunityModel extends Model
{
    protected $table = 'master_sikh_subcommunities';

    protected $primaryKey = 'id';

    protected $returnType = 'array';

    protected $allowedFields = [
        'community_id',
        'code',
        'name',
        'display_order',
        'is_active',
    ];

    protected $useTimestamps = true;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function activeForCommunity(
        int $communityId
    ): array {
        if ($communityId <= 0) {
            return [];
        }

        return $this
            ->select(['id', 'code', 'name'])
            ->where('community_id', $communityId)
            ->where('is_active', true)
            ->orderBy('display_order', 'ASC')
            ->orderBy('name', 'ASC')
            ->findAll();
    }
}
