<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/**
 * Stores the parent Religious Partner Preference row.
 */
final class MemberPartnerReligiousPreferenceModel extends Model
{
    protected $table =
    'member_partner_religious_preferences';

    protected $primaryKey = 'id';

    protected $returnType = 'array';

    protected $useAutoIncrement = true;

    protected $protectFields = true;

    protected $allowedFields = [
        'user_id',
        'community_match_mode',
    ];

    protected $useTimestamps = true;

    protected $dateFormat = 'datetime';

    protected $createdField = 'created_at';

    protected $updatedField = 'updated_at';

    protected $skipValidation = true;

    /**
     * @return array<string, mixed>|null
     */
    public function findForUser(int $userId): ?array
    {
        $row = $this
            ->where('user_id', $userId)
            ->first();

        return is_array($row)
            ? $row
            : null;
    }
}
