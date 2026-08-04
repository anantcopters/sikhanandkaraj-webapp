<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/**
 * Stores the member's preferred state and city.
 */
final class MemberPartnerLocationPreferenceModel extends Model
{
    protected $table =
    'member_partner_location_preferences';

    protected $primaryKey = 'id';

    protected $returnType = 'array';

    protected $useAutoIncrement = true;

    protected $protectFields = true;

    protected $allowedFields = [
        'user_id',
        'state_id',
        'city_id',
        'location_match_mode',
    ];

    protected $useTimestamps = true;

    protected $dateFormat = 'datetime';

    protected $createdField = 'created_at';

    protected $updatedField = 'updated_at';

    protected $skipValidation = true;

    /**
     * @return array<string, mixed>|null
     */
    public function findForUser(int $userId): ?array
    {
        $row = $this
            ->select([
                'member_partner_location_preferences.*',
                'master_states.name AS state_name',
                'master_cities.name AS city_name',
            ])
            ->join(
                'master_states',
                'master_states.id = '
                    . 'member_partner_location_preferences.state_id',
                'left'
            )
            ->join(
                'master_cities',
                'master_cities.id = '
                    . 'member_partner_location_preferences.city_id',
                'left'
            )
            ->where(
                'member_partner_location_preferences.user_id',
                $userId
            )
            ->first();

        return is_array($row)
            ? $row
            : null;
    }
}
