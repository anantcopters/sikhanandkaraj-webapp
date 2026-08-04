<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Database\ConnectionInterface;
use CodeIgniter\Model;
use CodeIgniter\Validation\ValidationInterface;

/**
 * Persists the single Basic Partner Preference row for a member.
 */
final class MemberPartnerBasicPreferenceModel extends Model
{
    protected $table = 'member_partner_basic_preferences';

    protected $primaryKey = 'id';

    protected $returnType = 'array';

    protected $useAutoIncrement = true;

    protected $protectFields = true;

    protected $allowedFields = [
        'user_id',

        'age_from',
        'age_to',
        'is_age_compulsory',

        'height_from_id',
        'height_to_id',
        'is_height_compulsory',

        'marital_status_id',
        'is_marital_status_compulsory',

        'have_children',
        'is_have_children_compulsory',

        'physical_status_id',
        'is_physical_status_compulsory',

        'is_mother_tongue_compulsory',
        'is_eating_habit_compulsory',
        'is_drinking_habit_compulsory',

        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = true;

    protected $dateFormat = 'datetime';

    protected $createdField = 'created_at';

    protected $updatedField = 'updated_at';

    /**
     * @param ConnectionInterface|null $db
     * @param ValidationInterface|null $validation
     */
    public function __construct(
        ?ConnectionInterface $db = null,
        ?ValidationInterface $validation = null
    ) {
        parent::__construct($db, $validation);
    }

    /**
     * Return the preference row for one member.
     *
     * @return array<string, mixed>|null
     */
    public function findForUser(int $userId): ?array
    {
        $row = $this
            ->where('user_id', $userId)
            ->first();

        return is_array($row)
            ? $row
            : null;
    }
}
