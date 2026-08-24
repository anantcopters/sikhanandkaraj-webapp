<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

final class MemberPartnerLifestylePreferenceOptionModel extends Model
{
    protected $table =
    'member_partner_lifestyle_preference_options';

    protected $primaryKey = 'id';

    protected $returnType = 'array';

    protected $useAutoIncrement = true;

    protected $protectFields = true;

    protected $allowedFields = [
        'partner_lifestyle_preference_id',
        'lifestyle_option_id',
    ];

    protected $useTimestamps = false;

    protected $skipValidation = true;

    /**
     * @return list<int>
     */
    public function idsForPreference(
        int $preferenceId
    ): array {
        if ($preferenceId <= 0) {
            return [];
        }

        $rows = $this
            ->select(
                'lifestyle_option_id'
            )
            ->where(
                'partner_lifestyle_preference_id',
                $preferenceId
            )
            ->findAll();

        return array_values(
            array_map(
                static fn(array $row): int =>
                (int) $row['lifestyle_option_id'],
                $rows
            )
        );
    }

    /**
     * @param list<int> $optionIds
     */
    public function replaceSelections(
        int $preferenceId,
        array $optionIds
    ): bool {
        if ($preferenceId <= 0) {
            return false;
        }

        $deleted = $this
            ->where(
                'partner_lifestyle_preference_id',
                $preferenceId
            )
            ->delete();

        if ($deleted === false) {
            return false;
        }

        $optionIds = array_values(
            array_unique(
                array_filter(
                    array_map(
                        'intval',
                        $optionIds
                    ),
                    static fn(int $optionId): bool =>
                    $optionId > 0
                )
            )
        );

        if ($optionIds === []) {
            return true;
        }

        $rows = array_map(
            static fn(int $optionId): array => [
                'partner_lifestyle_preference_id' =>
                $preferenceId,

                'lifestyle_option_id' =>
                $optionId,
            ],
            $optionIds
        );

        return $this->insertBatch(
            $rows
        ) !== false;
    }
}
