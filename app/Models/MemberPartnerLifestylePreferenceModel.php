<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

final class MemberPartnerLifestylePreferenceModel extends Model
{
    protected $table =
    'member_partner_lifestyle_preferences';

    protected $primaryKey = 'id';

    protected $returnType = 'array';

    protected $useAutoIncrement = true;

    protected $protectFields = true;

    protected $allowedFields = [
        'user_id',
        'lifestyle_category_id',
        'is_compulsory',
    ];

    protected $useTimestamps = true;

    protected $dateFormat = 'datetime';

    protected $createdField = 'created_at';

    protected $updatedField = 'updated_at';

    protected $skipValidation = true;

    /**
     * @return array<string,mixed>|null
     */
    public function findForUserAndCategory(
        int $userId,
        int $categoryId
    ): ?array {
        if (
            $userId <= 0
            || $categoryId <= 0
        ) {
            return null;
        }

        $row = $this
            ->where(
                'user_id',
                $userId
            )
            ->where(
                'lifestyle_category_id',
                $categoryId
            )
            ->first();

        return is_array($row)
            ? $row
            : null;
    }

    /**
     * @return list<array<string,mixed>>
     */
    public function findForUser(
        int $userId
    ): array {
        if ($userId <= 0) {
            return [];
        }

        return array_values(
            $this
                ->where(
                    'user_id',
                    $userId
                )
                ->findAll()
        );
    }
}
