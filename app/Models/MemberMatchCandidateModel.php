<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Database\BaseBuilder;
use App\Models\MemberMembershipModel;
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
        string $sort,
        bool $paginate = true
    ): array {
        if ($viewerUserId <= 0) {
            return [
                'rows' => [],
                'total' => 0,
            ];
        }

        $page = max(
            1,
            $page
        );

        $perPage = max(
            1,
            min(
                50,
                $perPage
            )
        );

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

            $amritdhari =
                $filters['amritdhari']
                ?? '';

            if (
                $amritdhari === '0'
                || $amritdhari === '1'
            ) {
                $builder->where(
                    'bd.is_amritdhari',
                    $amritdhari === '1'
                );
            }

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
        * Match-ranked Search requires the complete database-filtered candidate pool
        * before pagination because Partner Preference filtering and final Match Score
        * ranking occur in the application layer.
        *
        * Do not execute COUNT(*) for this path. The complete candidate collection is
        * already being loaded and MemberSearchService calculates the authoritative
        * total after compulsory preference filtering.
        *
        * Explicit database-sorted Search continues through the normal count and
        * database-pagination path below.
        */
        if (!$paginate) {
            $rows =
                $builder
                ->get()
                ->getResultArray();

            return [
                'rows' =>
                array_values(
                    $rows
                ),

                /*
                * This is the database-filtered pool size only.
                *
                * MemberSearchService recalculates the authoritative Search total after
                * compulsory Partner Preference filtering and Match Score ranking.
                */
                'total' =>
                count(
                    $rows
                ),

                'page' =>
                1,
            ];
        }

        /*
        * --------------------------------------------------------------------------
        * Database-paginated Search
        * --------------------------------------------------------------------------
        *
        * Chronological/activity sorts do not require the complete Match Score pool.
        *
        * For these sorts we still need COUNT(*) so pagination can calculate the
        * correct number of result pages before LIMIT/OFFSET is applied.
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

        $hiddenReportStatusSql =
            $this->db->escape(
                MemberProfileReportModel
                ::STATUS_ACTION_TAKEN
            );

        /*
        * Candidate membership projection.
        *
        * MembershipService defines a paid membership as one which is:
        *
        * - ACTIVE;
        * - already started;
        * - not yet expired.
        *
        * We deliberately repeat those database-level eligibility conditions here
        * rather than calling MembershipService once for every candidate.
        *
        * This keeps member discovery to one candidate query and avoids an N+1
        * membership lookup across Search, Dashboard and Interest collections.
        *
        * CURRENT_TIMESTAMP is evaluated by PostgreSQL for the query and therefore
        * also protects us when the membership-expiry housekeeping job has not yet
        * converted an expired ACTIVE row to EXPIRED.
        */
        $activeMembershipStatusSql =
            $this->db->escape(
                MemberMembershipModel
                ::STATUS_ACTIVE
            );

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
            * Cached authoritative profile-completion Match Score signal.
            *
            * Missing rows safely become zero. Existing members are backfilled by the
            * rebuild script supplied with this phase.
            */
            'COALESCE(
                    scoring_signal.profile_completion,
                    0
                ) AS profile_completion',

            /*
            * Candidate membership projection.
            *
            * NULL means there is no currently usable paid membership and therefore
            * the candidate is a Free member.
            *
            * Do not COALESCE the plan code here. Keeping the raw projection nullable
            * lets the presentation layer explicitly derive the Free state.
            */
            'active_membership.plan_code_snapshot '
                . 'AS membership_plan_code',

            'active_membership.plan_name_snapshot '
                . 'AS membership_plan_name',

            'active_membership.commercial_priority_snapshot '
                . 'AS membership_commercial_priority',

            /*
            * Member verification indicators used by the shared member
            * presentation contract.
            */
            'u.is_aadhaar_verified',

            'primary_mobile.is_verified '
                . 'AS is_mobile_verified',

            'primary_email.is_verified '
                . 'AS is_email_verified',

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

            'country.name AS country_name',
            'city.name AS city_name',

            'ep.highest_education_id',
            'education.name AS education_name',

            'ep.employed_in',

            'ep.occupation_id',
            'occupation.name AS occupation_name',

            'ep.annual_income_id',

            'fd.community_id',

            /*
            * Candidate-level Match Score signal.
            *
            * Only approved photos contribute to ranking.
            *
            * COUNT is performed in one lateral projection rather than loading photos
            * separately for every Search/Dashboard candidate.
            */
            'COALESCE(candidate_photos.approved_photo_count, 0) '
                . 'AS approved_photo_count',
        ]);

        /*
        * Match Score trust signal.
        *
        * This is a PostgreSQL CASE expression rather than a database column.
        *
        * IMPORTANT:
        *
        * Query Builder escaping must be disabled for this expression. If this is
        * placed inside the normal select([...]) array, CodeIgniter attempts to quote
        * the expression as an identifier and generates invalid PostgreSQL SQL.
        *
        * The actual Video Introduction JOIN is defined below using the video_intro
        * alias. Therefore the same approved/active Video Introduction projection is
        * reused for both:
        *
        * - Match Score trust verification;
        * - candidate presentation.
        */
        $builder->select(
            'CASE
                WHEN video_intro.id IS NOT NULL
                THEN TRUE
                ELSE FALSE
            END AS is_video_introduction_verified',
            false
        );

        /*
        * Candidate-intrinsic scoring cache.
        *
        * This is a normal indexed one-to-one LEFT JOIN:
        *
        *     users.id
        *         ->
        *     member_match_scoring_signals.user_id PRIMARY KEY
        *
        * No lateral query or per-candidate lookup is required.
        */
        $builder->join(
            'member_match_scoring_signals scoring_signal',
            'scoring_signal.user_id = u.id',
            'left'
        );

        /*
        * Resolve at most one currently usable paid membership for each candidate.
        *
        * Membership history is intentionally retained, so joining
        * member_memberships directly could duplicate a candidate when historical
        * rows exist.
        *
        * PostgreSQL DISTINCT ON gives us one deterministic current membership per
        * member without requiring a LATERAL JOIN.
        *
        * Membership validity follows the existing MembershipService rules:
        *
        * - status must be ACTIVE;
        * - membership must already have started;
        * - membership must not have expired.
        *
        * CURRENT_TIMESTAMP also protects Search/Dashboard when the expiry
        * housekeeping job has not yet converted an expired ACTIVE row to EXPIRED.
        *
        * Ordering:
        *
        * 1. user_id groups memberships by candidate;
        * 2. latest starts_at wins;
        * 3. highest id provides a deterministic tie-breaker.
        *
        * IMPORTANT:
        *
        * Do not convert this back to:
        *
        *     LEFT JOIN LATERAL (...) USING (TRUE)
        *
        * CodeIgniter Query Builder interprets the literal TRUE join condition as a
        * USING clause and generates invalid PostgreSQL SQL.
        */
        $builder->join(
            '(
                SELECT DISTINCT ON (
                    candidate_membership.user_id
                )
                    candidate_membership.user_id,
                    candidate_membership.id,
                    candidate_membership.plan_code_snapshot,
                    candidate_membership.plan_name_snapshot,
                    candidate_membership.commercial_priority_snapshot
                FROM member_memberships candidate_membership
                WHERE candidate_membership.status = '
                . $activeMembershipStatusSql
                . '
                AND candidate_membership.starts_at <= CURRENT_TIMESTAMP
                AND candidate_membership.expires_at > CURRENT_TIMESTAMP
                ORDER BY
                    candidate_membership.user_id,
                    candidate_membership.starts_at DESC,
                    candidate_membership.id DESC
            ) active_membership',
            'active_membership.user_id = u.id',
            'left',
            false
        );

        /*
        * Candidate approved-photo Match Score signal.
        *
        * Aggregate approved photographs once by member and LEFT JOIN the result.
        *
        * This preserves the N+1 optimization while avoiding a PostgreSQL LATERAL
        * join, which CodeIgniter Query Builder cannot correctly express with the
        * required ON TRUE condition in this implementation.
        *
        * Only approved, non-deleted photographs contribute to Match Score.
        *
        * A candidate having no approved photographs simply has no aggregate row;
        * the SELECT projection above converts that to zero with COALESCE().
        */
        $builder->join(
            '(
                SELECT
                    candidate_photo.member_id,
                    COUNT(*)::INTEGER
                        AS approved_photo_count
                FROM member_photos candidate_photo
                WHERE candidate_photo.status = \'APPROVED\'
                AND candidate_photo.deleted_at IS NULL
                GROUP BY
                    candidate_photo.member_id
            ) candidate_photos',
            'candidate_photos.member_id = u.id',
            'left',
            false
        );

        /*
        * This is a PostgreSQL boolean expression rather than a database
        * column. Escaping must be disabled; otherwise CodeIgniter converts it
        * into an invalid quoted column name:
        *
        *     "video_intro"."id IS NOT NULL"
        */
        $builder->select(
            'video_intro.id IS NOT NULL AS has_video_introduction',
            false
        );

        /*
        * Only an active and approved video introduction should produce the
        * video introduction badge.
        */
        $builder->join(
            'member_video_introductions video_intro',
            "video_intro.member_user_id = u.id
    AND video_intro.is_active = TRUE
    AND video_intro.moderation_status = 'APPROVED'
    AND video_intro.deleted_at IS NULL",
            'left',
            false
        );

        $builder->join(
            'user_contacts primary_mobile',
            "primary_mobile.user_id = u.id
    AND primary_mobile.contact_type = 'MOBILE'
    AND primary_mobile.is_primary = TRUE",
            'left',
            false
        );

        $builder->join(
            'user_contacts primary_email',
            "primary_email.user_id = u.id
    AND primary_email.contact_type = 'EMAIL'
    AND primary_email.is_primary = TRUE",
            'left',
            false
        );

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
            'master_educations education',
            'education.id = '
                . 'ep.highest_education_id',
            'left'
        );

        $builder->join(
            'master_occupations occupation',
            'occupation.id = '
                . 'ep.occupation_id',
            'left'
        );

        $builder->join(
            'member_family_details fd',
            'fd.user_id = u.id',
            'left'
        );

        $builder->join(
            'master_countries country',
            'country.id = bd.country_id',
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
            )
            /*
            * An administrator-confirmed report globally hides the reported
            * profile from every member-facing candidate collection.
            *
            * NOT EXISTS avoids duplicate candidates when multiple reports
            * against the same member reach ACTION_TAKEN.
            */
            ->where(
                'NOT EXISTS ('
                    . 'SELECT 1 '
                    . 'FROM member_profile_reports hidden_report '
                    . 'WHERE hidden_report.reported_user_id = u.id '
                    . 'AND hidden_report.status = '
                    . $hiddenReportStatusSql
                    . ')',
                null,
                false
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
