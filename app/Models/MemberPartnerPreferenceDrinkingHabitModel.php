<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/**
 * Persists selected drinking habits for Basic Partner Preference.
 */
final class MemberPartnerPreferenceDrinkingHabitModel extends Model
{
    protected $table =
    'member_partner_preference_drinking_habits';

    protected $primaryKey = 'id';

    protected $returnType = 'array';

    protected $useAutoIncrement = true;

    protected $protectFields = true;

    protected $allowedFields = [
        'partner_basic_preference_id',
        'drinking_habit_id',
        'created_at',
    ];

    protected $useTimestamps = false;

    /**
     * @return list<int>
     */
    public function idsForPreference(int $preferenceId): array
    {
        $rows = $this
            ->select('drinking_habit_id')
            ->where(
                'partner_basic_preference_id',
                $preferenceId
            )
            ->findAll();

        return array_values(
            array_map(
                static fn(array $row): int =>
                (int) $row['drinking_habit_id'],
                $rows
            )
        );
    }
}
