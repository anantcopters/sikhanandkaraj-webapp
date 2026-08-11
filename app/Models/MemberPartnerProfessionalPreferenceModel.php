<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/**
 * Stores the parent Professional Partner Preference row.
 */
final class MemberPartnerProfessionalPreferenceModel extends Model
{
    protected $table =
    'member_partner_professional_preferences';

    protected $primaryKey = 'id';

    protected $returnType = 'array';

    protected $useAutoIncrement = true;

    protected $protectFields = true;

    protected $allowedFields = [
        'user_id',

        'education_match_mode',

        'employed_in_match_mode',

        'occupation_match_mode',

        'annual_income_match_mode',
    ];

    protected $useTimestamps = true;

    protected $dateFormat = 'datetime';

    protected $createdField = 'created_at';

    protected $updatedField = 'updated_at';

    protected $skipValidation = true;

    /**
     * Find the professional preference parent for one user.
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
