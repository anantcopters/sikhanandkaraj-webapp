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
     * Search eligible matchmaking candidates.
     *
     * All member eligibility and blocking rules remain centralized through
     * baseCandidateBuilder().
     *
     * @param array<string, mixed> $filters
     *
     * @return array{
     *     rows:list<array<string, mixed>>,
     *     total:int,
     *     page:int
     * }
     */
    public function searchCandidates(
        int $viewerUserId,
        string $viewerGender,
        array $filters,
        int $page,
        int $perPage,
        string $sort
    ): array {
        if ($viewerUserId <= 0) {
            return [
                'rows' => [],
                'total' => 0,
            ];
        }

        $page = max(1, $page);
        $perPage = max(1, min(50, $perPage));

        $builder = $this->baseCandidateBuilder(
            $viewerUserId,
            $viewerGender
        );

        /*
 * --------------------------------------------------------------------------
 * Existing-interaction candidate restriction
 * --------------------------------------------------------------------------
 *
 * Used by Search Quick Links such as shortlist/profile-view activity.
 *
 * Eligibility remains controlled by baseCandidateBuilder(), therefore an
 * inactive, deleted or blocked profile cannot reappear merely because an old
 * interaction record exists.
 */
        if (
            array_key_exists(
                'candidate_ids',
                $filters
            )
        ) {
            $candidateIds =
                is_array(
                    $filters['candidate_ids']
                )
                ? array_values(
                    array_unique(
                        array_filter(
                            array_map(
                                'intval',
                                $filters['candidate_ids']
                            ),
                            static fn(
                                int $memberId
                            ): bool =>
                            $memberId > 0
                        )
                    )
                )
                : [];

            /*
     * A valid activity collection with no members must return zero rows.
     *
     * We cannot simply skip whereIn() because that would accidentally turn an
     * empty activity collection into an unrestricted Search.
     */
            if ($candidateIds === []) {
                return [
                    'rows' =>
                    [],

                    'total' =>
                    0,

                    'page' =>
                    max(
                        1,
                        $page
                    ),
                ];
            }

            $builder->whereIn(
                'u.id',
                $candidateIds
            );
        }

        /*
     * Search-result presentation fields.
     */
        $builder->select([
            'state.name AS state_name',
            'height.height_cm',
            'height.display_name AS height_name',
            'marital.name AS marital_status_name',
        ]);

        $builder->join(
            'master_states state',
            'state.id = bd.state_id',
            'left'
        );

        $builder->join(
            'master_heights height',
            'height.id = bd.height_id',
            'left'
        );

        $builder->join(
            'master_marital_statuses marital',
            'marital.id = bd.marital_status_id',
            'left'
        );

        /*
     * Age.
     *
     * age_min means the candidate must be at least that old.
     */
        $ageMin = $filters['age_min'] ?? null;

        if (is_int($ageMin) && $ageMin >= 18) {
            $builder->where(
                'bd.date_of_birth <=',
                date(
                    'Y-m-d',
                    strtotime(
                        '-' . $ageMin . ' years'
                    )
                )
            );
        }

        /*
     * Maximum age.
     *
     * Example: maximum 30 means DOB must be later than the date representing
     * someone who has already completed 31 years.
     */
        $ageMax = $filters['age_max'] ?? null;

        if (is_int($ageMax) && $ageMax >= 18) {
            $builder->where(
                'bd.date_of_birth >',
                date(
                    'Y-m-d',
                    strtotime(
                        '-'
                            . ($ageMax + 1)
                            . ' years'
                    )
                )
            );
        }

        /*
     * Height range is resolved to centimetres by the service.
     */
        if (
            isset($filters['height_min_cm'])
            && is_int($filters['height_min_cm'])
        ) {
            $builder->where(
                'height.height_cm >=',
                $filters['height_min_cm']
            );
        }

        if (
            isset($filters['height_max_cm'])
            && is_int($filters['height_max_cm'])
        ) {
            $builder->where(
                'height.height_cm <=',
                $filters['height_max_cm']
            );
        }

        $this->applyIntegerArrayFilter(
            $builder,
            'bd.marital_status_id',
            $filters['marital_status_ids'] ?? []
        );

        $this->applyIntegerArrayFilter(
            $builder,
            'bd.country_id',
            $filters['country_ids'] ?? []
        );

        $this->applyIntegerArrayFilter(
            $builder,
            'bd.state_id',
            $filters['state_ids'] ?? []
        );

        /*
     * Advanced-only filters.
     */
        if (
            ($filters['mode'] ?? 'basic')
            === 'advanced'
        ) {
            $this->applyIntegerArrayFilter(
                $builder,
                'bd.city_id',
                $filters['city_ids'] ?? []
            );

            $this->applyIntegerArrayFilter(
                $builder,
                'fd.community_id',
                $filters['community_ids'] ?? []
            );

            $this->applyStringArrayFilter(
                $builder,
                'u.profile_created_for',
                $filters['managed_by'] ?? []
            );

            $this->applyIntegerArrayFilter(
                $builder,
                'ep.highest_education_id',
                $filters['education_ids'] ?? []
            );

            $this->applyIntegerArrayFilter(
                $builder,
                'ep.occupation_id',
                $filters['occupation_ids'] ?? []
            );

            $this->applyStringArrayFilter(
                $builder,
                'ep.employed_in',
                $filters['employed_in'] ?? []
            );

            /*
         * Annual income uses the existing master range IDs.
         *
         * When both endpoints are selected the service expands them to the
         * master IDs that fall inside that range.
         */
            $this->applyIntegerArrayFilter(
                $builder,
                'ep.annual_income_id',
                $filters['annual_income_ids'] ?? []
            );

            $lifestyleIds =
                $filters['lifestyle_option_ids']
                ?? [];

            if (
                is_array($lifestyleIds)
                && $lifestyleIds !== []
            ) {
                /*
             * Match profiles having ALL selected lifestyle options.
             */
                $normalizedLifestyleIds =
                    array_values(
                        array_unique(
                            array_map(
                                'intval',
                                $lifestyleIds
                            )
                        )
                    );

                $builder->where(
                    'u.id IN (
                    SELECT mlo.user_id
                    FROM member_lifestyle_options mlo
                    WHERE mlo.lifestyle_option_id IN ('
                        . implode(
                            ',',
                            $normalizedLifestyleIds
                        )
                        . ')
                    GROUP BY mlo.user_id
                    HAVING COUNT(
                        DISTINCT mlo.lifestyle_option_id
                    ) = '
                        . count(
                            $normalizedLifestyleIds
                        )
                        . '
                )',
                    null,
                    false
                );
            }
        }

        /*
        * Photo settings.
        *
        * Only approved primary photos participate in this filter.
        */
        $photoVisibility =
            $filters['photo_visibility']
            ?? [];

        if (
            is_array($photoVisibility)
            && $photoVisibility !== []
        ) {
            $photoBuilder = $this->db
                ->table('member_photos mp')
                ->select('1')
                ->where(
                    'mp.member_id = u.id',
                    null,
                    false
                )
                ->where(
                    'mp.status',
                    'APPROVED'
                )
                ->where(
                    'mp.is_primary',
                    true
                )
                ->where(
                    'mp.deleted_at',
                    null
                )
                ->whereIn(
                    'mp.visibility',
                    $photoVisibility
                );

            $builder->where(
                'EXISTS ('
                    . $photoBuilder->getCompiledSelect()
                    . ')',
                null,
                false
            );
        }

        /*
        * Count matching profiles before applying pagination.
        */
        $countBuilder =
            clone $builder;

        $total =
            (int)
            $countBuilder
                ->countAllResults();

        /*
        * Clamp manually supplied/outdated page numbers before querying records.
        *
        * This prevents URLs such as ?page=999 from showing an empty page when
        * matching profiles still exist on an earlier valid page.
        */
        $totalPages =
            max(
                1,
                (int) ceil(
                    $total
                        / $perPage
                )
            );

        $page =
            min(
                $page,
                $totalPages
            );

        /*
        * Apply deterministic Search ordering after counting.
        */
        $this->applySearchSorting(
            $builder,
            $sort
        );

        $offset =
            ($page - 1)
            * $perPage;

        $rows =
            $builder
            ->limit(
                $perPage,
                $offset
            )
            ->get()
            ->getResultArray();

        return [
            'rows' =>
            array_values(
                $rows
            ),

            'total' =>
            $total,

            'page' =>
            $page,
        ];
    }

    /**
     * @param list<int> $values
     */
    private function applyIntegerArrayFilter(
        BaseBuilder $builder,
        string $column,
        array $values
    ): void {
        $values = array_values(
            array_unique(
                array_filter(
                    array_map(
                        'intval',
                        $values
                    ),
                    static fn(int $value): bool =>
                    $value > 0
                )
            )
        );

        if ($values === []) {
            return;
        }

        $builder->whereIn(
            $column,
            $values
        );
    }

    /**
     * @param list<string> $values
     */
    private function applyStringArrayFilter(
        BaseBuilder $builder,
        string $column,
        array $values
    ): void {
        $values = array_values(
            array_unique(
                array_filter(
                    array_map(
                        static fn(mixed $value): string =>
                        trim(
                            (string) $value
                        ),
                        $values
                    ),
                    static fn(string $value): bool =>
                    $value !== ''
                )
            )
        );

        if ($values === []) {
            return;
        }

        $builder->whereIn(
            $column,
            $values
        );
    }

    private function applySearchSorting(
        BaseBuilder $builder,
        string $sort
    ): void {
        switch ($sort) {
            case 'oldest':
                $builder
                    ->orderBy(
                        'u.created_at',
                        'ASC'
                    )
                    ->orderBy(
                        'u.id',
                        'ASC'
                    );
                break;

            case 'last_login':
                $builder
                    ->orderBy(
                        'u.last_login_at',
                        'DESC',
                        false
                    )
                    ->orderBy(
                        'u.id',
                        'DESC'
                    );
                break;

            case 'latest':
                $builder
                    ->orderBy(
                        'u.created_at',
                        'DESC'
                    )
                    ->orderBy(
                        'u.id',
                        'DESC'
                    );
                break;

            default:
                /*
             * Default remains deterministic.
             *
             * Later this can become relevance/match scoring.
             */
                $builder
                    ->orderBy(
                        'u.created_at',
                        'DESC'
                    )
                    ->orderBy(
                        'u.id',
                        'DESC'
                    );
                break;
        }
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

            /*
            * Used internally for Last Logged In sorting and converted to a
            * privacy-friendly activity label by MemberSearchService.
            *
            * The raw timestamp must never be rendered to another member.
            */
            'u.last_login_at',

            'bd.date_of_birth',
            'bd.marital_status_id',
            'bd.height_id',
            'bd.mother_tongue_id',
            'bd.drinking_habit_id',
            'bd.eating_habit_id',
            'bd.physical_status_id',
            'bd.number_of_children',
            'bd.country_id',
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

    /**
     * Return the authenticated member's saved location identifiers.
     *
     * Search Quick Links use these values for "same State" and "same City".
     *
     * This method returns identifiers only and does not create another location
     * source outside member_basic_details.
     *
     * @return array{
     *     state_id:int,
     *     city_id:int
     * }
     */
    public function memberLocationForUser(
        int $userId
    ): array {
        if ($userId <= 0) {
            return [
                'state_id' =>
                0,

                'city_id' =>
                0,
            ];
        }

        $row =
            $this->db
            ->table(
                'member_basic_details'
            )
            ->select([
                'state_id',
                'city_id',
            ])
            ->where(
                'user_id',
                $userId
            )
            ->get()
            ->getRowArray();

        if (!is_array($row)) {
            return [
                'state_id' =>
                0,

                'city_id' =>
                0,
            ];
        }

        return [
            'state_id' =>
            max(
                0,
                (int) (
                    $row['state_id']
                    ?? 0
                )
            ),

            'city_id' =>
            max(
                0,
                (int) (
                    $row['city_id']
                    ?? 0
                )
            ),
        ];
    }

    /**
     * Return the authenticated member's active Sikh Community.
     *
     * Search Quick Links use this value for "Same Community".
     *
     * Community remains sourced from member_family_details and the existing
     * master_sikh_communities master table.
     *
     * An inactive/missing master value returns community_id = 0 so the
     * Quick Link becomes unavailable instead of accidentally becoming an
     * unrestricted Search.
     *
     * @return array{
     *     community_id:int,
     *     community_name:string
     * }
     */
    public function memberCommunityForUser(
        int $userId
    ): array {
        if ($userId <= 0) {
            return [
                'community_id' =>
                0,

                'community_name' =>
                '',
            ];
        }

        $row =
            $this->db
            ->table(
                'member_family_details fd'
            )
            ->select([
                'fd.community_id',
                'community.name AS community_name',
            ])
            ->join(
                'master_sikh_communities community',
                'community.id = fd.community_id',
                'inner'
            )
            ->where(
                'fd.user_id',
                $userId
            )
            ->where(
                'community.is_active',
                true
            )
            ->get()
            ->getRowArray();

        if (!is_array($row)) {
            return [
                'community_id' =>
                0,

                'community_name' =>
                '',
            ];
        }

        $communityId =
            max(
                0,
                (int) (
                    $row['community_id']
                    ?? 0
                )
            );

        $communityName =
            trim(
                (string) (
                    $row['community_name']
                    ?? ''
                )
            );

        if (
            $communityId <= 0
            || $communityName === ''
        ) {
            return [
                'community_id' =>
                0,

                'community_name' =>
                '',
            ];
        }

        return [
            'community_id' =>
            $communityId,

            'community_name' =>
            $communityName,
        ];
    }
}
