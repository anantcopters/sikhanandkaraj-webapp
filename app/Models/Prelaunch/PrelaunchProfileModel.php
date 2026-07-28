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
        'mother_tongue_id',
        'country_id',
        'state_id',
        'city_id',
        'highest_education_id',
        'employed_in',
        'occupation_id',
        'father_name',
        'mother_name',
        'family_value_id',
        'family_type_id',
        'family_status_id',
        'sikh_community_id',
        'sikh_subcommunity_id',
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
     * Return admin review rows with resolved master names.
     *
     * @return list<array<string, mixed>>
     */
    public function listForAdmin(
        ?string $status = null
    ): array {
        $builder = $this
            ->select([
                'prelaunch_profiles.*',
                'field_officers.officer_code',
                'field_officers.full_name AS field_officer_name',
                'master_states.name AS state_name',
                'master_cities.name AS city_name',
                'master_marital_statuses.name AS marital_status_name',
                'master_heights.label AS height_name',
                'master_mother_tongues.name AS mother_tongue_name',
                'master_educations.name AS education_name',
                'master_occupations.name AS occupation_name',
                'master_family_values.name AS family_value_name',
                'master_family_types.name AS family_type_name',
                'master_family_statuses.name AS family_status_name',
                'master_sikh_communities.name AS community_name',
                'master_sikh_subcommunities.name AS subcommunity_name',
            ])
            ->join(
                'field_officers',
                'field_officers.id = prelaunch_profiles.field_officer_id',
                'inner'
            )
            ->join(
                'master_states',
                'master_states.id = prelaunch_profiles.state_id',
                'inner'
            )
            ->join(
                'master_cities',
                'master_cities.id = prelaunch_profiles.city_id',
                'inner'
            )
            ->join(
                'master_marital_statuses',
                'master_marital_statuses.id = '
                    . 'prelaunch_profiles.marital_status_id',
                'inner'
            )
            ->join(
                'master_heights',
                'master_heights.id = prelaunch_profiles.height_id',
                'inner'
            )
            ->join(
                'master_mother_tongues',
                'master_mother_tongues.id = '
                    . 'prelaunch_profiles.mother_tongue_id',
                'inner'
            )
            ->join(
                'master_educations',
                'master_educations.id = '
                    . 'prelaunch_profiles.highest_education_id',
                'inner'
            )
            ->join(
                'master_occupations',
                'master_occupations.id = '
                    . 'prelaunch_profiles.occupation_id',
                'inner'
            )
            ->join(
                'master_family_values',
                'master_family_values.id = '
                    . 'prelaunch_profiles.family_value_id',
                'inner'
            )
            ->join(
                'master_family_types',
                'master_family_types.id = '
                    . 'prelaunch_profiles.family_type_id',
                'inner'
            )
            ->join(
                'master_family_statuses',
                'master_family_statuses.id = '
                    . 'prelaunch_profiles.family_status_id',
                'inner'
            )
            ->join(
                'master_sikh_communities',
                'master_sikh_communities.id = '
                    . 'prelaunch_profiles.sikh_community_id',
                'inner'
            )
            ->join(
                'master_sikh_subcommunities',
                'master_sikh_subcommunities.id = '
                    . 'prelaunch_profiles.sikh_subcommunity_id',
                'inner'
            );

        if ($status !== null) {
            $builder->where(
                'prelaunch_profiles.status',
                $status
            );
        }

        return $builder
            ->orderBy(
                'prelaunch_profiles.created_at',
                'DESC'
            )
            ->findAll();
    }

    /**
     * Return one complete record for admin review.
     *
     * @return array<string, mixed>|null
     */
    public function findForAdmin(int $profileId): ?array
    {
        $rows = $this
            ->listForAdminById($profileId);

        return $rows[0] ?? null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function listForAdminById(int $profileId): array
    {
        return array_values(array_filter(
            $this->listForAdmin(),
            static fn(array $row): bool =>
            (int) ($row['id'] ?? 0) === $profileId
        ));
    }
}
