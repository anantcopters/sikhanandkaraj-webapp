<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/**
 * Handles persistence for a member's basic matrimonial details.
 */
final class MemberBasicDetailModel extends Model
{
    protected $table = 'member_basic_details';

    protected $primaryKey = 'id';

    protected $returnType = 'array';

    protected $useAutoIncrement = true;

    protected $allowedFields = [
        'user_id',
        'date_of_birth',
        'marital_status_id',
        'height_id',
        'mother_tongue_id',
        'drinking_habit_id',
        'eating_habit_id',
        'physical_status_id',
        'number_of_children',
        'children_living_together',
        'country_id',
        'state_id',
        'city_id',
        'about_me',
    ];

    protected $useTimestamps = true;

    protected $dateFormat = 'datetime';

    protected $createdField = 'created_at';

    protected $updatedField = 'updated_at';

    protected $skipValidation = true;

    /**
     * Find Basic Details together with readable master values.
     *
     * @return array<string, mixed>|null
     */
    public function findForUser(
        int $userId
    ): ?array {
        $record = $this
            ->select([
                'member_basic_details.*',

                'master_marital_statuses.code '
                    . 'AS marital_status_code',

                'master_marital_statuses.name '
                    . 'AS marital_status_name',

                'master_heights.height_cm',

                'master_heights.display_name '
                    . 'AS height_display_name',

                'master_mother_tongues.name '
                    . 'AS mother_tongue_name',

                'master_drinking_habits.name '
                    . 'AS drinking_habit_name',

                'master_eating_habits.name '
                    . 'AS eating_habit_name',

                'master_physical_statuses.name '
                    . 'AS physical_status_name',

                'master_countries.iso_code '
                    . 'AS country_code',

                'master_countries.name '
                    . 'AS country_name',

                'master_states.name '
                    . 'AS state_name',

                'master_cities.name '
                    . 'AS city_name',
            ])
            ->join(
                'master_marital_statuses',
                'master_marital_statuses.id = '
                    . 'member_basic_details.marital_status_id',
                'left'
            )
            ->join(
                'master_heights',
                'master_heights.id = '
                    . 'member_basic_details.height_id',
                'left'
            )
            ->join(
                'master_mother_tongues',
                'master_mother_tongues.id = '
                    . 'member_basic_details.mother_tongue_id',
                'left'
            )
            ->join(
                'master_drinking_habits',
                'master_drinking_habits.id = '
                    . 'member_basic_details.drinking_habit_id',
                'left'
            )
            ->join(
                'master_eating_habits',
                'master_eating_habits.id = '
                    . 'member_basic_details.eating_habit_id',
                'left'
            )
            ->join(
                'master_physical_statuses',
                'master_physical_statuses.id = '
                    . 'member_basic_details.physical_status_id',
                'left'
            )
            ->join(
                'master_countries',
                'master_countries.id = '
                    . 'member_basic_details.country_id',
                'left'
            )
            ->join(
                'master_states',
                'master_states.id = '
                    . 'member_basic_details.state_id',
                'left'
            )
            ->join(
                'master_cities',
                'master_cities.id = '
                    . 'member_basic_details.city_id',
                'left'
            )
            ->where(
                'member_basic_details.user_id',
                $userId
            )
            ->first();

        return is_array($record)
            ? $record
            : null;
    }
}
