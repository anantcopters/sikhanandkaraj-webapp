<?php

declare(strict_types=1);

namespace App\Models\Prelaunch;

use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Model;

/**
 * Persistence model for pre-launch profile records.
 */
final class PrelaunchProfileModel extends Model
{
    public const STATUS_DRAFT = 'DRAFT';
    public const STATUS_APPROVED = 'APPROVED';
    public const STATUS_REJECTED = 'REJECTED';

    public const CREATED_SOURCE_FIELD_OFFICER = 'FIELD_OFFICER';

    protected $table = 'prelaunch_profiles';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useAutoIncrement = true;
    protected $useSoftDeletes = true;
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';
    protected $skipValidation = true;

    protected $allowedFields = [
        'profile_reference',
        'profile_created_for',
        'gender',
        'full_name',
        'date_of_birth',
        'email',
        'country_code',
        'mobile_number',
        'marital_status_id',
        'height_id',
        'country_id',
        'state_id',
        'city_id',
        'highest_education_id',
        'employed_in',
        'occupation_id',
        'father_name',
        'mother_name',
        'parent_contact_number',
        'sikh_community_id',
        'gotra',
        'gotra_maternal',
        'nearest_gurudwara',
        'field_officer_id',
        'created_by',
        'created_source',
        'is_prelaunch_profile',
        'status',
        'reviewed_by',
        'reviewed_at',
        'rejection_reason',
        'migrated_user_id',
        'migrated_at',
        'local_photos_cleanup_after',
        'local_photos_cleaned_at',
        'migration_error',
    ];

    public function __construct(
        ?BaseConnection $database = null
    ) {
        parent::__construct($database);
    }

    /**
     * Check whether a non-empty email is already assigned to another
     * non-deleted prelaunch profile.
     */
    public function emailExists(
        string $email,
        ?int $exceptProfileId = null
    ): bool {
        $normalizedEmail = mb_strtolower(
            trim($email)
        );

        /*
     * Missing email is valid and must never participate in duplicate
     * checking.
     */
        if ($normalizedEmail === '') {
            return false;
        }

        $builder = $this
            ->where(
                'LOWER(email)',
                $normalizedEmail
            )
            ->where('deleted_at', null);

        if ($exceptProfileId !== null) {
            $builder->where(
                'id !=',
                $exceptProfileId
            );
        }

        return $builder->first() !== null;
    }

    public function mobileExists(
        string $countryCode,
        string $mobileNumber,
        ?int $exceptProfileId = null
    ): bool {
        $builder = $this
            ->where('country_code', trim($countryCode))
            ->where('mobile_number', trim($mobileNumber))
            ->where('deleted_at', null);

        if ($exceptProfileId !== null) {
            $builder->where('id !=', $exceptProfileId);
        }

        return $builder->first() !== null;
    }


    /**
     * Prepare the administrator listing query.
     *
     * The model instance is returned so CodeIgniter's native paginate() method
     * can execute the page query and generate matching pager metadata.
     */
    public function adminListQuery(
        ?string $status = null,
        ?string $search = null
    ): self {
        $normalizedStatus = mb_strtoupper(
            trim((string) $status)
        );

        if (
            !in_array(
                $normalizedStatus,
                [
                    self::STATUS_DRAFT,
                    self::STATUS_APPROVED,
                    self::STATUS_REJECTED,
                ],
                true
            )
        ) {
            $normalizedStatus = self::STATUS_DRAFT;
        }

        $normalizedSearch = preg_replace(
            '/\s+/u',
            ' ',
            trim((string) $search)
        ) ?? '';

        $normalizedSearch = mb_substr(
            $normalizedSearch,
            0,
            100
        );

        $this
            ->select([
                'prelaunch_profiles.id',
                'prelaunch_profiles.profile_reference',
                'prelaunch_profiles.profile_created_for',
                'prelaunch_profiles.gender',
                'prelaunch_profiles.full_name',
                'prelaunch_profiles.email',
                'prelaunch_profiles.country_code',
                'prelaunch_profiles.mobile_number',
                'prelaunch_profiles.status',
                'prelaunch_profiles.created_at',
                'prelaunch_profiles.reviewed_at',
                'prelaunch_profiles.migrated_user_id',

                'field_officers.officer_code',
                'field_officers.full_name AS field_officer_name',

                'master_countries.name AS country_name',
                'master_states.name AS state_name',
                'master_cities.name AS city_name',
            ])
            ->join(
                'field_officers',
                'field_officers.id = '
                    . 'prelaunch_profiles.field_officer_id',
                'left'
            )
            ->join(
                'master_countries',
                'master_countries.id = '
                    . 'prelaunch_profiles.country_id',
                'left'
            )
            ->join(
                'master_states',
                'master_states.id = '
                    . 'prelaunch_profiles.state_id',
                'left'
            )
            ->join(
                'master_cities',
                'master_cities.id = '
                    . 'prelaunch_profiles.city_id',
                'left'
            )
            ->where(
                'prelaunch_profiles.status',
                $normalizedStatus
            )
            ->where(
                'prelaunch_profiles.deleted_at',
                null
            );

        if ($normalizedSearch !== '') {
            $this->applyAdminSearch(
                $normalizedSearch
            );
        }

        return $this->orderBy(
            'prelaunch_profiles.created_at',
            'DESC'
        );
    }

    /**
     * Apply PostgreSQL-safe, case-insensitive administrator search.
     */
    private function applyAdminSearch(
        string $search
    ): void {
        /*
     * Escape LIKE wildcard characters supplied by the administrator.
     */
        $escapedSearch = $this->db
            ->escapeLikeString(
                trim($search)
            );

        /*
     * Quote the full pattern before embedding it in the expression.
     *
     * PostgreSQL receives:
     *
     * ILIKE '%toor%'
     *
     * instead of:
     *
     * ILIKE %toor%
     */
        $quotedPattern = $this->db->escape(
            '%' . $escapedSearch . '%'
        );

        $this
            ->groupStart()
            ->where(
                'prelaunch_profiles.profile_reference '
                    . 'ILIKE ' . $quotedPattern,
                null,
                false
            )
            ->orWhere(
                'prelaunch_profiles.full_name '
                    . 'ILIKE ' . $quotedPattern,
                null,
                false
            )
            ->orWhere(
                'COALESCE(prelaunch_profiles.email, \'\') '
                    . 'ILIKE ' . $quotedPattern,
                null,
                false
            )
            ->orWhere(
                'COALESCE(prelaunch_profiles.mobile_number, \'\') '
                    . 'ILIKE ' . $quotedPattern,
                null,
                false
            )
            ->orWhere(
                'CONCAT('
                    . 'COALESCE(prelaunch_profiles.country_code, \'\'), '
                    . 'COALESCE(prelaunch_profiles.mobile_number, \'\')'
                    . ') ILIKE ' . $quotedPattern,
                null,
                false
            )
            ->orWhere(
                'COALESCE(field_officers.full_name, \'\') '
                    . 'ILIKE ' . $quotedPattern,
                null,
                false
            )
            ->orWhere(
                'COALESCE(field_officers.officer_code, \'\') '
                    . 'ILIKE ' . $quotedPattern,
                null,
                false
            )
            ->orWhere(
                'COALESCE(master_countries.name, \'\') '
                    . 'ILIKE ' . $quotedPattern,
                null,
                false
            )
            ->orWhere(
                'COALESCE(master_states.name, \'\') '
                    . 'ILIKE ' . $quotedPattern,
                null,
                false
            )
            ->orWhere(
                'COALESCE(master_cities.name, \'\') '
                    . 'ILIKE ' . $quotedPattern,
                null,
                false
            )
            ->groupEnd();
    }
    
    /**
     * Return one complete profile for administrator review.
     *
     * @return array<string, mixed>|null
     */
    public function findForAdmin(
        int $profileId
    ): ?array {
        if ($profileId <= 0) {
            return null;
        }

        $builder = $this->db
            ->table(
                $this->table
                    . ' AS prelaunch_profiles'
            );

        $this->applyAdminDetailsQuery(
            $builder
        );

        $record = $builder
            ->where(
                'prelaunch_profiles.id',
                $profileId
            )
            ->where(
                'prelaunch_profiles.deleted_at',
                null
            )
            ->limit(1)
            ->get()
            ->getRowArray();

        return is_array($record)
            ? $record
            : null;
    }

    /**
     * Apply common selection and joins used by administrator screens.
     *
     * This method modifies the supplied query builder and does not execute
     * the query.
     */
    private function applyAdminDetailsQuery(
        \CodeIgniter\Database\BaseBuilder $builder
    ): void {
        $builder
            ->select([
                'prelaunch_profiles.*',

                /*
             * SAK Volunteer information.
             */
                'field_officers.officer_code',
                'field_officers.full_name AS field_officer_name',
                'field_officers.account_status AS field_officer_status',

                /*
             * Member location.
             */
                'master_countries.name AS country_name',
                'master_states.name AS state_name',
                'master_cities.name AS city_name',

                /*
             * Basic details.
             */
                'master_marital_statuses.name AS marital_status_name',
                'master_heights.display_name AS height_name',

                /*
             * Education and profession.
             */
                'master_educations.name AS education_name',
                'master_occupations.name AS occupation_name',

                /*
             * Family details.
             */
                'master_sikh_communities.name AS community_name',
            ])
            ->join(
                'field_officers',
                'field_officers.id = '
                    . 'prelaunch_profiles.field_officer_id',
                'left'
            )
            ->join(
                'master_countries',
                'master_countries.id = '
                    . 'prelaunch_profiles.country_id',
                'left'
            )
            ->join(
                'master_states',
                'master_states.id = '
                    . 'prelaunch_profiles.state_id',
                'left'
            )
            ->join(
                'master_cities',
                'master_cities.id = '
                    . 'prelaunch_profiles.city_id',
                'left'
            )
            ->join(
                'master_marital_statuses',
                'master_marital_statuses.id = '
                    . 'prelaunch_profiles.marital_status_id',
                'left'
            )
            ->join(
                'master_heights',
                'master_heights.id = '
                    . 'prelaunch_profiles.height_id',
                'left'
            )
            ->join(
                'master_educations',
                'master_educations.id = '
                    . 'prelaunch_profiles.highest_education_id',
                'left'
            )
            ->join(
                'master_occupations',
                'master_occupations.id = '
                    . 'prelaunch_profiles.occupation_id',
                'left'
            )
            ->join(
                'master_sikh_communities',
                'master_sikh_communities.id = '
                    . 'prelaunch_profiles.sikh_community_id',
                'left'
            );
    }

    /**
     * Return migrated profiles whose locally staged photos may be removed.
     *
     * @return list<array<string, mixed>>
     */
    public function findDueForPhotoCleanup(
        int $limit = 100
    ): array {
        $safeLimit = max(
            1,
            min($limit, 500)
        );

        return $this
            ->where(
                'migrated_user_id IS NOT NULL',
                null,
                false
            )
            ->where(
                'local_photos_cleanup_after <=',
                date('Y-m-d H:i:s')
            )
            ->where(
                'local_photos_cleaned_at',
                null
            )
            ->where(
                'deleted_at',
                null
            )
            ->orderBy(
                'local_photos_cleanup_after',
                'ASC'
            )
            ->findAll($safeLimit);
    }
}
