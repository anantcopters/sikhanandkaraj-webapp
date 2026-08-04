<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/**
 * Persists selected mother tongues for Basic Partner Preference.
 */
final class MemberPartnerPreferenceMotherTongueModel extends Model
{
    protected $table =
    'member_partner_preference_mother_tongues';

    protected $primaryKey = 'id';

    protected $returnType = 'array';

    protected $useAutoIncrement = true;

    protected $protectFields = true;

    protected $allowedFields = [
        'partner_basic_preference_id',
        'mother_tongue_id',
        'created_at',
    ];

    protected $useTimestamps = false;

    /**
     * @return list<int>
     */
    public function idsForPreference(int $preferenceId): array
    {
        $rows = $this
            ->select('mother_tongue_id')
            ->where(
                'partner_basic_preference_id',
                $preferenceId
            )
            ->findAll();

        return array_values(
            array_map(
                static fn(array $row): int =>
                (int) $row['mother_tongue_id'],
                $rows
            )
        );
    }
}
