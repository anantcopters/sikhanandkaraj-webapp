<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/**
 * Stores and retrieves Sikh and religious profile details.
 */
final class MemberSikhReligiousDetailModel extends Model
{
    protected $table = 'member_sikh_religious_details';

    protected $primaryKey = 'id';

    protected $returnType = 'array';

    protected $useAutoIncrement = true;

    protected $allowedFields = [
        'user_id',
        'community_id',
        'birth_hour',
        'birth_minute',
        'birth_meridiem',
        'birth_country_id',
        'birth_state_id',
        'birth_city_id',
        'gotra',
        'moon_sign_id',
        'birth_star_id',
        'has_dosh',
    ];

    protected $useTimestamps = true;

    protected $createdField = 'created_at';

    protected $updatedField = 'updated_at';

    protected $skipValidation = true;

    /**
     * @return array<string, mixed>|null
     */
    public function findForUser(int $userId): ?array
    {
        $record = $this
            ->select([
                'member_sikh_religious_details.*',
                'community.name AS community_name',
                'country.name AS birth_country_name',
                'state.name AS birth_state_name',
                'city.name AS birth_city_name',
                'moon_sign.name AS moon_sign_name',
                'moon_sign.english_name '
                    . 'AS moon_sign_english_name',
                'birth_star.name AS birth_star_name',
            ])
            ->join(
                'master_sikh_communities community',
                'community.id = '
                    . 'member_sikh_religious_details.community_id',
                'left'
            )
            ->join(
                'master_countries country',
                'country.id = '
                    . 'member_sikh_religious_details.birth_country_id',
                'left'
            )
            ->join(
                'master_states state',
                'state.id = '
                    . 'member_sikh_religious_details.birth_state_id',
                'left'
            )
            ->join(
                'master_cities city',
                'city.id = '
                    . 'member_sikh_religious_details.birth_city_id',
                'left'
            )
            ->join(
                'master_moon_signs moon_sign',
                'moon_sign.id = '
                    . 'member_sikh_religious_details.moon_sign_id',
                'left'
            )
            ->join(
                'master_birth_stars birth_star',
                'birth_star.id = '
                    . 'member_sikh_religious_details.birth_star_id',
                'left'
            )
            ->where(
                'member_sikh_religious_details.user_id',
                $userId
            )
            ->first();

        return is_array($record)
            ? $record
            : null;
    }
}
