<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\Model;

/**
 * Read projection for member discovery.
 *
 * Any member-discovery feature should obtain candidate records through
 * this model so member-to-member blocking is consistently enforced.
 */
final class MemberMatchCandidateModel extends Model
{
    protected $table = 'users';

    protected $primaryKey = 'id';

    protected $returnType = 'array';

    protected $useAutoIncrement = true;

    protected $useSoftDeletes = true;

    protected $skipValidation = true;

    /**
     * @return list<array<string, mixed>>
     */
    public function eligibleCandidates(
        int $viewerUserId,
        string $viewerGender
    ): array {
        if ($viewerUserId <= 0) {
            return [];
        }

        $builder = $this->baseCandidateBuilder(
            $viewerUserId,
            $viewerGender
        );

        $builder
            ->orderBy(
                'u.created_at',
                'DESC'
            )
            ->orderBy(
                'u.id',
                'DESC'
            );

        return $builder
            ->get()
            ->getResultArray();
    }

    /**
     * Return currently visible member records for ordered IDs.
     *
     * Used by Interests and Profile Views.
     *
     * @param list<int> $memberIds
     *
     * @return list<array<string, mixed>>
     */
    public function visibleCandidatesByIds(
        int $viewerUserId,
        string $viewerGender,
        array $memberIds
    ): array {
        $memberIds = array_values(
            array_unique(
                array_filter(
                    array_map(
                        'intval',
                        $memberIds
                    ),
                    static fn(int $id): bool =>
                    $id > 0
                )
            )
        );

        if ($memberIds === []) {
            return [];
        }

        $builder = $this->baseCandidateBuilder(
            $viewerUserId,
            $viewerGender
        );

        $rows = $builder
            ->whereIn(
                'u.id',
                $memberIds
            )
            ->get()
            ->getResultArray();

        $byId = [];

        foreach ($rows as $row) {
            $byId[(int) $row['id']] = $row;
        }

        $ordered = [];

        foreach ($memberIds as $memberId) {
            if (isset($byId[$memberId])) {
                $ordered[] = $byId[$memberId];
            }
        }

        return $ordered;
    }

    private function baseCandidateBuilder(
        int $viewerUserId,
        string $viewerGender
    ): BaseBuilder {
        $builder = $this->db
            ->table('users u');

        $builder->select([
            'u.id',
            'u.profile_ref_number',
            'u.full_name',
            'u.gender',
            'u.created_at',

            'bd.date_of_birth',
            'bd.marital_status_id',
            'bd.height_id',
            'bd.mother_tongue_id',
            'bd.drinking_habit_id',
            'bd.eating_habit_id',
            'bd.physical_status_id',
            'bd.number_of_children',
            'bd.state_id',
            'bd.city_id',

            'city.name AS city_name',

            'ep.highest_education_id',
            'ep.employed_in',
            'ep.occupation_id',
            'ep.annual_income_id',

            'fd.community_id',
        ]);

        $builder->join(
            'member_basic_details bd',
            'bd.user_id = u.id',
            'left'
        );

        $builder->join(
            'member_education_profession_details ep',
            'ep.user_id = u.id',
            'left'
        );

        $builder->join(
            'member_family_details fd',
            'fd.user_id = u.id',
            'left'
        );

        $builder->join(
            'master_cities city',
            'city.id = bd.city_id',
            'left'
        );

        $builder
            ->where(
                'u.id !=',
                $viewerUserId
            )
            ->where(
                'u.account_status',
                UserModel::STATUS_ACTIVE
            )
            ->where(
                'u.deleted_at',
                null
            );

        /*
         * Current application data model uses M/F member gender.
         *
         * Gender is basic eligibility rather than a percentage criterion.
         */
        $normalizedGender = strtoupper(
            trim($viewerGender)
        );

        if (
            in_array(
                $normalizedGender,
                ['M', 'F'],
                true
            )
        ) {
            $builder->where(
                'u.gender !=',
                $normalizedGender
            );
        }

        /*
         * A block in either direction makes the pair completely invisible.
         */
        $builder->where(
            <<<'SQL'
NOT EXISTS (
    SELECT 1
    FROM member_blocks mb
    WHERE
        (
            mb.blocker_user_id = u.id
            AND mb.blocked_user_id = :viewer_user_id:
        )
        OR
        (
            mb.blocker_user_id = :viewer_user_id:
            AND mb.blocked_user_id = u.id
        )
)
SQL,
            [
                'viewer_user_id' =>
                $viewerUserId,
            ],
            false
        );

        return $builder;
    }
}
