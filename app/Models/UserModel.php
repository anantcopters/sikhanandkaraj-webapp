<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/**
 * Handles user account persistence.
 */
final class UserModel extends Model
{
    /**
     * Account registration is incomplete or mobile verification is pending.
     */
    public const STATUS_PENDING = 'PENDING';

    /**
     * Account has completed mobile verification and may use the application.
     */
    public const STATUS_ACTIVE = 'ACTIVE';

    /**
     * Account has been temporarily restricted.
     */
    public const STATUS_SUSPENDED = 'SUSPENDED';

    /**
     * Account has been logically deleted or disabled.
     */
    public const STATUS_DELETED = 'DELETED';

    protected $table = 'users';

    protected $primaryKey = 'id';

    protected $returnType = 'array';

    protected $useAutoIncrement = true;

    protected $useSoftDeletes = true;

    protected $allowedFields = [
        'prelaunch_profile_id',
        'profile_ref_number',
        'profile_created_for',
        'gender',
        'full_name',
        'password_hash',
        'account_status',
    ];

    protected $useTimestamps = true;

    protected $dateFormat = 'datetime';

    protected $createdField = 'created_at';

    protected $updatedField = 'updated_at';

    protected $deletedField = 'deleted_at';

    protected $skipValidation = true;

    /**
     * Check whether a profile reference already exists.
     */
    public function profileReferenceExists(string $reference): bool
    {
        return $this
            ->where('profile_ref_number', $reference)
            ->countAllResults() > 0;
    }

    /**
     * Find the member created from a prelaunch profile.
     *
     * @return array<string, mixed>|null
     */
    public function findByPrelaunchProfileId(
        int $prelaunchProfileId
    ): ?array {
        if ($prelaunchProfileId <= 0) {
            return null;
        }

        $record = $this
            ->where(
                'prelaunch_profile_id',
                $prelaunchProfileId
            )
            ->first();

        return is_array($record)
            ? $record
            : null;
    }
}
