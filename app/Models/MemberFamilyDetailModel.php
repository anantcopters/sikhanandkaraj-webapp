<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/**
 * Handles the member Family Details profile section.
 */
final class MemberFamilyDetailModel extends Model
{
    protected $table = 'member_family_details';

    protected $primaryKey = 'id';

    protected $returnType = 'array';

    protected $useAutoIncrement = true;

    protected $allowedFields = [
        'user_id',
        'family_value_id',
        'family_type_id',
        'family_status_id',
        'community_id',
        'gotra',
        'father_name',
        'mother_name',
        'parent_contact_number',
        'father_occupation_id',
        'mother_occupation_id',
        'brothers_count',
        'sisters_count',
        'country_id',
        'state_id',
        'city_id',
        'nearest_gurudwara',
        'reference_person_1',
        'reference_person_2',
    ];

    protected $useTimestamps = true;

    protected $dateFormat = 'datetime';

    protected $createdField = 'created_at';

    protected $updatedField = 'updated_at';

    /*
     * Validation is handled by the controller validation class and
     * FamilyDetailsService.
     */
    protected $skipValidation = true;

    /**
     * Find one member's Family Details with readable master values.
     *
     * LEFT JOIN is intentional because family value, family type, family
     * status and occupations are optional and may be NULL.
     *
     * @return array<string, mixed>|null
     */
    public function findForUser(int $userId): ?array
    {
        $record = $this
            ->select([
                'member_family_details.*',

                'family_value.name AS family_value_name',
                'family_type.name AS family_type_name',
                'family_status.name AS family_status_name',

                'community.name AS community_name',

                'father_occupation.name '
                    . 'AS father_occupation_name',

                'mother_occupation.name '
                    . 'AS mother_occupation_name',

                'master_countries.name AS country_name',
                'master_states.name AS state_name',
                'master_cities.name AS city_name',
            ])
            ->join(
                'master_family_values family_value',
                'family_value.id = '
                    . 'member_family_details.family_value_id',
                'left'
            )
            ->join(
                'master_family_types family_type',
                'family_type.id = '
                    . 'member_family_details.family_type_id',
                'left'
            )
            ->join(
                'master_family_statuses family_status',
                'family_status.id = '
                    . 'member_family_details.family_status_id',
                'left'
            )
            ->join(
                'master_sikh_communities community',
                'community.id = '
                    . 'member_family_details.community_id',
                'left'
            )
            ->join(
                'master_family_occupations father_occupation',
                'father_occupation.id = '
                    . 'member_family_details.father_occupation_id',
                'left'
            )
            ->join(
                'master_family_occupations mother_occupation',
                'mother_occupation.id = '
                    . 'member_family_details.mother_occupation_id',
                'left'
            )
            ->join(
                'master_countries',
                'master_countries.id = '
                    . 'member_family_details.country_id',
                'left'
            )
            ->join(
                'master_states',
                'master_states.id = '
                    . 'member_family_details.state_id',
                'left'
            )
            ->join(
                'master_cities',
                'master_cities.id = '
                    . 'member_family_details.city_id',
                'left'
            )
            ->where(
                'member_family_details.user_id',
                $userId
            )
            ->first();

        return is_array($record)
            ? $record
            : null;
    }
}
