<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/**
 * Stores the parent Location Partner Preference row.
 *
 * States and cities are stored in dedicated junction tables.
 */
final class MemberPartnerLocationPreferenceModel extends Model
{
    protected $table =
    'member_partner_location_preferences';

    protected $primaryKey = 'id';

    protected $returnType = 'array';

    protected $useAutoIncrement = true;

    protected $protectFields = true;

    protected $allowedFields = [
        'user_id',
        'location_match_mode',
    ];

    protected $useTimestamps = true;

    protected $dateFormat = 'datetime';

    protected $createdField = 'created_at';

    protected $updatedField = 'updated_at';

    protected $skipValidation = true;

    /**
     * Find the location preference parent for one user.
     *
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
