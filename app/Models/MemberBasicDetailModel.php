<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/**
 * Handles persistence for a member's basic matrimonial details.
 */
final class MemberBasicDetailModel extends Model
{
    protected $table = 'member_basic_details';

    protected $primaryKey = 'id';

    protected $returnType = 'array';

    protected $useAutoIncrement = true;

    protected $allowedFields = [
        'user_id',
        'date_of_birth',
        'marital_status',
        'height_cm',
        'mother_tongue',
        'current_city',
        'current_state',
        'country_code',
    ];

    protected $useTimestamps = true;

    protected $dateFormat = 'datetime';

    protected $createdField = 'created_at';

    protected $updatedField = 'updated_at';

    protected $skipValidation = true;

    /**
     * Find the basic-details record belonging to a user.
     *
     * @return array<string, mixed>|null
     */
    public function findForUser(int $userId): ?array
    {
        $record = $this
            ->where('user_id', $userId)
            ->first();

        return is_array($record) ? $record : null;
    }
}
