<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/**
 * Handles a member's education and professional details.
 */
final class MemberEducationProfessionDetailModel extends Model
{
    protected $table = 'member_education_profession_details';

    protected $primaryKey = 'id';

    protected $returnType = 'array';

    protected $useAutoIncrement = true;

    protected $allowedFields = [
        'user_id',
        'highest_education_id',
        'education_detail',
        'college_institution',
        'employed_in',
        'occupation_id',
        'occupation_detail',
        'organization',
        'annual_income_id',
    ];

    protected $useTimestamps = true;

    protected $dateFormat = 'datetime';

    protected $createdField = 'created_at';

    protected $updatedField = 'updated_at';

    /**
     * Validation is handled by the dedicated validation class and service.
     */
    protected $skipValidation = true;

    /**
     * Find details with readable master values.
     *
     * @return array<string, mixed>|null
     */
    public function findForUser(int $userId): ?array
    {
        $record = $this
            ->select([
                'member_education_profession_details.*',

                'master_educations.name AS highest_education_name',

                'master_occupations.name AS occupation_name',

                'master_annual_incomes.display_name '
                    . 'AS annual_income_display_name',
                'master_annual_incomes.min_amount '
                    . 'AS annual_income_min_amount',
                'master_annual_incomes.max_amount '
                    . 'AS annual_income_max_amount',
            ])
            ->join(
                'master_educations',
                'master_educations.id = '
                    . 'member_education_profession_details.'
                    . 'highest_education_id',
                'left'
            )
            ->join(
                'master_occupations',
                'master_occupations.id = '
                    . 'member_education_profession_details.'
                    . 'occupation_id',
                'left'
            )
            ->join(
                'master_annual_incomes',
                'master_annual_incomes.id = '
                    . 'member_education_profession_details.'
                    . 'annual_income_id',
                'left'
            )
            ->where(
                'member_education_profession_details.user_id',
                $userId
            )
            ->first();

        return is_array($record)
            ? $record
            : null;
    }
}
