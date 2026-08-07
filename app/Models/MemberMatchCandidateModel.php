<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\Model;

/**
 * Read projection for member discovery.
 *
 * Any member-facing discovery/listing implementation should obtain member
 * records through this model so member-to-member blocking is applied
 * consistently.
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
     * Return all currently eligible matchmaking candidates.
     *
     * @return list<array<string, mixed>>
     */
    public function eligibleCandidates(
        int $viewerUserId,
        string $viewerGender
    ): array {
        if ($viewerUserId <= 0) {
            return [];
        }

        $builder = $this
            ->baseCandidateBuilder(
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
     * Return currently visible profiles for ordered IDs.
     *
     * Used by:
     *
     * - Interested in You;
     * - Interests Sent;
     * - Who Viewed Your Profile;
     * - Profiles You Viewed.
     *
     * The input order is retained after the visibility query.
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
                    static fn(int $memberId): bool =>
                    $memberId > 0
                )
            )
        );

        if (
            $viewerUserId <= 0
            || $memberIds === []
        ) {
            return [];
        }

        $builder = $this
            ->baseCandidateBuilder(
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

        /*
         * Re-index first because WHERE IN does not guarantee the incoming
         * interaction order.
         */
        $byMemberId = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $memberId = max(
                0,
                (int) (
                    $row['id']
                    ?? 0
                )
            );

            if ($memberId <= 0) {
                continue;
            }

            $byMemberId[$memberId] =
                $row;
        }

        $ordered = [];

        foreach ($memberIds as $memberId) {
            if (
                isset(
                    $byMemberId[$memberId]
                )
            ) {
                $ordered[] =
                    $byMemberId[$memberId];
            }
        }

        return $ordered;
    }

    /**
     * Build the common member-discovery query.
     *
     * @return BaseBuilder
     */
    private function baseCandidateBuilder(
        int $viewerUserId,
        string $viewerGender
    ): BaseBuilder {
        /*
         * int type declaration ensures this value cannot contain SQL.
         *
         * It is used only in internally generated JOIN predicates.
         */
        $viewerIdSql =
            (string) $viewerUserId;

        $builder = $this->db
            ->table(
                'users u'
            );

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

        /*
         * Block created by the current viewer:
         *
         * viewer -> candidate
         */
        $builder->join(
            'member_blocks blocked_by_viewer',
            'blocked_by_viewer.blocker_user_id = '
                . $viewerIdSql
                . ' AND '
                . 'blocked_by_viewer.blocked_user_id = u.id',
            'left',
            false
        );

        /*
         * Block created by the candidate:
         *
         * candidate -> viewer
         */
        $builder->join(
            'member_blocks blocking_viewer',
            'blocking_viewer.blocker_user_id = u.id'
                . ' AND '
                . 'blocking_viewer.blocked_user_id = '
                . $viewerIdSql,
            'left',
            false
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
            )
            /*
             * Any relationship block removes the candidate.
             */
            ->where(
                'blocked_by_viewer.id',
                null
            )
            ->where(
                'blocking_viewer.id',
                null
            );

        /*
         * The current application registration model supports M/F.
         *
         * Gender is base eligibility rather than one of the preference
         * percentage criteria.
         */
        $normalizedGender =
            mb_strtoupper(
                trim(
                    $viewerGender
                )
            );

        if (
            in_array(
                $normalizedGender,
                [
                    'M',
                    'F',
                ],
                true
            )
        ) {
            $builder->where(
                'u.gender !=',
                $normalizedGender
            );
        }

        return $builder;
    }
}
