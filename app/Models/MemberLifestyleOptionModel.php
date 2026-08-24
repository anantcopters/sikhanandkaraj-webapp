<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

final class MemberLifestyleOptionModel extends Model
{
    protected $table = 'member_lifestyle_options';

    protected $primaryKey = 'id';

    protected $returnType = 'array';

    protected $allowedFields = [
        'user_id',
        'lifestyle_option_id',
    ];

    protected $useTimestamps = false;

    /**
     * @return list<int>
     */
    public function selectedIdsForUser(int $userId): array
    {
        $rows = $this
            ->select('lifestyle_option_id')
            ->where('user_id', $userId)
            ->findAll();

        return array_map(
            static fn(array $row): int =>
            (int) $row['lifestyle_option_id'],
            $rows
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function selectedDetailsForUser(int $userId): array
    {
        return $this
            ->select(
                'master_lifestyle_options.id, '
                    . 'master_lifestyle_options.name, '
                    . 'master_lifestyle_options.code, '
                    . 'master_lifestyle_categories.id AS category_id, '
                    . 'master_lifestyle_categories.code AS category_code, '
                    . 'master_lifestyle_categories.name AS category_name, '
                    . 'master_lifestyle_categories.icon_class'
            )
            ->join(
                'master_lifestyle_options',
                'master_lifestyle_options.id = '
                    . 'member_lifestyle_options.lifestyle_option_id'
            )
            ->join(
                'master_lifestyle_categories',
                'master_lifestyle_categories.id = '
                    . 'master_lifestyle_options.lifestyle_category_id'
            )
            ->where('member_lifestyle_options.user_id', $userId)
            ->where('master_lifestyle_options.is_active', true)
            ->where('master_lifestyle_categories.is_active', true)
            ->orderBy(
                'master_lifestyle_categories.display_order',
                'ASC'
            )
            ->orderBy(
                'master_lifestyle_options.display_order',
                'ASC'
            )
            ->findAll();
    }

    /**
     * Return Lifestyle selections for multiple members without N+1 queries.
     *
     * @param list<int> $userIds
     *
     * @return array<int,list<int>>
     */
    public function selectedIdsForUsers(
        array $userIds
    ): array {
        $userIds = array_values(
            array_unique(
                array_filter(
                    array_map(
                        'intval',
                        $userIds
                    ),
                    static fn(int $userId): bool =>
                    $userId > 0
                )
            )
        );

        if ($userIds === []) {
            return [];
        }

        $rows = $this
            ->select([
                'user_id',
                'lifestyle_option_id',
            ])
            ->whereIn(
                'user_id',
                $userIds
            )
            ->findAll();

        $result = [];

        foreach ($rows as $row) {
            $userId = (int) (
                $row['user_id']
                ?? 0
            );

            $optionId = (int) (
                $row['lifestyle_option_id']
                ?? 0
            );

            if (
                $userId <= 0
                || $optionId <= 0
            ) {
                continue;
            }

            $result[$userId][] =
                $optionId;
        }

        foreach ($result as &$optionIds) {
            $optionIds = array_values(
                array_unique(
                    $optionIds
                )
            );
        }

        unset($optionIds);

        return $result;
    }
}
