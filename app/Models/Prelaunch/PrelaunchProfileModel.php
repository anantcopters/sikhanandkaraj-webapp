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
        'sikh_community_id',
        'gotra',
        'field_officer_id',
        'created_by',
        'created_source',
        'is_prelaunch_profile',
        'status',
        'reviewed_by',
        'reviewed_at',
        'rejection_reason',
    ];

    public function __construct(
        ?BaseConnection $database = null
    ) {
        parent::__construct($database);
    }

    public function emailExists(
        string $email,
        ?int $exceptProfileId = null
    ): bool {
        $builder = $this
            ->where(
                'LOWER(email)',
                mb_strtolower(trim($email))
            )
            ->where('deleted_at', null);

        if ($exceptProfileId !== null) {
            $builder->where('id !=', $exceptProfileId);
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
     * Return pre-launch profiles for the administrator list.
     *
     * LEFT JOIN is intentional. A missing or inactive master record must
     * not make the complete pre-launch profile disappear from admin review.
     *
     * @return list<array<string, mixed>>
     */
    public function listForAdmin(
        ?string $status = null
    ): array {
        $builder = $this->db
            ->table(
                $this->table
                    . ' AS prelaunch_profiles'
            );

        $this->applyAdminDetailsQuery(
            $builder
        );

        if ($status !== null) {
            $builder->where(
                'prelaunch_profiles.status',
                mb_strtoupper(
                    trim($status)
                )
            );
        }

        $builder
            ->where(
                'prelaunch_profiles.deleted_at',
                null
            )
            ->orderBy(
                'prelaunch_profiles.created_at',
                'DESC'
            );

        return $builder
            ->get()
            ->getResultArray();
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
             * Field Officer information.
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
}
